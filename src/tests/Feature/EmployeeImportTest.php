<?php

use App\Models\User;
use App\Support\ImportTemplate;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

function phaseTwentyThreeConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseTwentyThreePrefix(): string
{
    return 'codex_phase23_'.bin2hex(random_bytes(6));
}

/** @return list<string> */
function phaseTwentyThreeTables(): array
{
    return [
        'users', 'roles', 'permissions', 'role_permissions', 'activity_logs',
        'employment_types', 'positions', 'grades', 'echelons', 'institutions', 'residences',
        'groups', 'decrees', 'recognitions', 'disciplinaries', 'training_levels',
        'user_educations', 'user_families', 'position_histories', 'position_history_users',
        'grade_histories', 'grade_history_users', 'training_histories', 'training_history_users',
        'recognition_histories', 'recognition_history_users', 'target_histories', 'target_history_users',
        'performance_histories', 'performance_history_users', 'disciplinary_histories',
        'disciplinary_history_users', 'user_leaves', 'user_notes', 'user_assessments',
        'user_competencies', 'user_talents',
    ];
}

function phaseTwentyThreeActingAs(bool $allowed, int $type = 1): User
{
    $db = phaseTwentyThreeConnection();
    $prefix = phaseTwentyThreePrefix();
    $roleId = $db->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $permission = match ($type) {
        2 => 'Data Pegawai - Non ASN',
        3 => 'Data Pegawai - Outsourcing',
        default => 'Data Pegawai - ASN',
    };
    $permissionId = $db->table('permissions')->where('name', $permission)->value('id');
    $db->table('role_permissions')->insertTs([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
        'create' => $allowed,
        'read' => $allowed,
        'update' => false,
        'delete' => false,
    ]);
    $id = $db->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 23 Import Operator',
        'employee_id_number' => 'p23'.substr(hash('sha256', $prefix), 0, 12),
        'description' => $prefix,
        'type' => 1,
        'status' => true,
        'employment_status' => 1,
        'date_of_birth' => '1990-01-01',
    ]);
    $user = User::query()->findOrFail($id);
    Sanctum::actingAs($user);

    return $user;
}

function phaseTwentyThreeWorkbook(int $type, string $marker, bool $duplicate = false): string
{
    $name = match ($type) {
        1 => 'DATA PEGAWAI ASN',
        2 => 'DATA PEGAWAI NON ASN',
        3 => 'DATA PEGAWAI OUTSOURCE',
    };
    $book = IOFactory::load(ImportTemplate::path($name));
    $sheet = $book->getSheet(0);
    $nip = $duplicate
        ? (string) phaseTwentyThreeConnection()->table('users')->whereNotNull('employee_id_number')->value('employee_id_number')
        : '923'.substr(hash('sha256', $marker), 0, 12);
    $nik = '823'.substr(hash('sha256', 'nik'.$marker), 0, 12);
    $common = [
        1 => 'Phase 23 '.$marker,
        4 => $nip,
        6 => 'Jakarta',
        7 => '01/01/1990',
        8 => 'Islam',
        9 => 'Laki-laki',
    ];
    foreach ($common as $column => $value) {
        $sheet->setCellValue([$column, 2], $value);
    }

    if ($type === 3) {
        for ($column = 1; $column <= 41; $column++) {
            $sheet->setCellValue([$column, 2], null);
        }
        foreach ([1 => 'Phase 23 '.$marker, 2 => $nip, 3 => 'Jakarta', 4 => '01/01/1990', 5 => 'Islam', 6 => 'Laki-laki'] as $column => $value) {
            $sheet->setCellValue([$column, 2], $value);
        }
    }

    if ($type === 1) {
        $values = [13 => 'TNI/POLRI', 14 => '01/01/2020', 16 => 'Plt. Sekretaris Wakil Presiden',
            17 => '01/01/2020', 18 => 'Pembina Utama', 19 => '01/01/2020', 23 => 'SD/Sederajat',
            33 => 'Aktif', 35 => $nik, 44 => $marker.'@example.invalid', 45 => 'Kontak 08123456789'];
    } elseif ($type === 2) {
        $values = [11 => 'TNI/POLRI', 13 => 'Perbantuan Non ASN', 14 => '01/01/2020',
            20 => 'SD/Sederajat', 24 => 'Aktif', 26 => $nik, 35 => 'Kontak 08123456789'];
    } else {
        $values = [8 => 'TNI/POLRI', 9 => '01/01/2020', 10 => 'Pengemudi', 11 => '01/01/2020',
            12 => 'SD/Sederajat', 16 => 'Aktif', 18 => $nik, 27 => 'Kontak 08123456789'];
    }
    foreach ($values as $column => $value) {
        $sheet->setCellValue([$column, 2], $value);
    }

    $path = tempnam(sys_get_temp_dir(), 'simdatuk-p23-').'.xlsx';
    IOFactory::createWriter($book, 'Xlsx')->save($path);

    return $path;
}

function phaseTwentyThreeUpload(string $path): UploadedFile
{
    return new UploadedFile($path, basename($path), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

it('registers all four import routes in source order and middleware', function () {
    $uris = [
        'api/employees/import', 'api/employees/import/download-template/{type}',
        'api/employees/import/histories', 'api/employees/import/download-failed-import/{id}',
    ];
    $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => in_array($route->uri(), $uris, true))
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware()])
        ->values()->all();

    expect($routes)->toBe([
        ['POST', 'api/employees/import', 'App\\Http\\Controllers\\ImportEmployeeController@import', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/employees/import/download-template/{type}', 'App\\Http\\Controllers\\ImportEmployeeController@downloadTemplate', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/employees/import/histories', 'App\\Http\\Controllers\\ImportEmployeeController@getRiwayatImport', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/employees/import/download-failed-import/{id}', 'App\\Http\\Controllers\\ImportEmployeeController@downloadImportErrorLog', ['api', 'auth:sanctum', 'role.access']],
    ]);
});

describe('Phase 23 import on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }
        $database = (string) env('SIMDATUK_CLONE_DATABASE');
        if ($database !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing Phase 23 clone access: exact database name mismatch.');
        }
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
        $connection = phaseTwentyThreeConnection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $database) {
            throw new RuntimeException('Refusing Phase 23 clone access: MySQL/database guard failed.');
        }
        $engines = $connection->table('information_schema.tables')->where('table_schema', $database)
            ->whereIn('table_name', phaseTwentyThreeTables())->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));
        if ($engines->count() !== count(phaseTwentyThreeTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing Phase 23 clone access: guarded tables must exist and use InnoDB.');
        }
        $connection->beginTransaction();
        $this->phaseTwentyThreeFiles = [];
    });

    afterEach(function (): void {
        foreach ($this->phaseTwentyThreeFiles ?? [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        ImportTemplate::cleanup();
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') === '1') {
            $connection = phaseTwentyThreeConnection();
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            DB::disconnect('mysql');
        }
    });

    it('downloads byte-identical source templates and preserves workbook contracts', function (int $type, string $name, array $sheets, string $firstHeading) {
        // Route parameters are ignored by RoleBasedAccess; without a query/body type it falls back to ASN.
        phaseTwentyThreeActingAs(true, 1);
        $response = $this->get("/api/employees/import/download-template/{$type}")->assertOk();
        expect($response->baseResponse)->toBeInstanceOf(BinaryFileResponse::class);
        $path = $response->baseResponse->getFile()->getPathname();
        $book = IOFactory::load($path);
        $encoded = file_get_contents(resource_path("import-templates/{$name}.xlsx.base64"));
        expect($book->getSheetNames())->toBe($sheets)
            ->and($book->getSheet(0)->getCell('A1')->getValue())->toBe($firstHeading)
            ->and(hash_file('sha256', $path))->toBe(hash('sha256', base64_decode(trim($encoded), true)))
            ->and($response->headers->get('content-disposition'))->toContain($name.'.xlsx');
    })->with([
        'ASN' => [1, 'DATA PEGAWAI ASN', ['Data Pegawai', 'Riwayat Pendidikan', 'Riwayat Jabatan', 'Riwayat Golongan', 'Riwayat Pelatihan Struktural', 'Riwayat Pelatihan Fungsional', 'Riwayat Pelatihan Teknis', 'Riwayat Penghargaan', 'Riwayat SKP', 'Penilaian Prestasi Kerja', 'Riwayat Hukuman Disiplin', 'Riwayat Keluarga', 'Riwayat Cuti', 'Riwayat Catatan', 'Hasil Assessment', 'Hasil Uji Kompetensi', 'Hasil Talent Pool'], 'Nama *'],
        'Non ASN' => [2, 'DATA PEGAWAI NON ASN', ['Data Pegawai', 'Riwayat Jabatan'], 'Nama *'],
        'Outsource' => [3, 'DATA PEGAWAI OUTSOURCE', ['Data Pegawai', 'Riwayat Pendidikan', 'Riwayat Jabatan', 'Riwayat Pelatihan Teknis', 'Riwayat Keluarga', 'Riwayat Catatan'], 'Nama *'],
    ]);

    it('imports a minimal employee for every supported type and records success', function (int $type) {
        $operator = phaseTwentyThreeActingAs(true, $type);
        $marker = phaseTwentyThreePrefix();
        $path = phaseTwentyThreeWorkbook($type, $marker);
        $this->phaseTwentyThreeFiles[] = $path;
        $importResponse = $this->post('/api/employees/import', ['type' => (string) $type, 'file' => phaseTwentyThreeUpload($path)]);
        $importResponse->assertOk()->assertJsonPath('code', 200)->assertJsonPath('message', 'Import pegawai berhasil');
        expect(phaseTwentyThreeConnection()->table('users')->where('name', 'Phase 23 '.$marker)->where('type', $type)->exists())->toBeTrue()
            ->and(phaseTwentyThreeConnection()->table('activity_logs')->where('user_id', $operator->id)
                ->where('type', match ($type) { 1 => 'add-bulk-asn', 2 => 'add-bulk-non-asn', 3 => 'add-bulk-outsource' })
                ->where('status', 'success')->exists())->toBeTrue();
    })->with(['ASN' => [1], 'Non ASN' => [2], 'Outsource' => [3]]);

    it('rejects duplicate and invalid rows as an all-row failure with a downloadable PDF log', function () {
        phaseTwentyThreeActingAs(true, 1);
        $path = phaseTwentyThreeWorkbook(1, phaseTwentyThreePrefix(), true);
        $this->phaseTwentyThreeFiles[] = $path;
        $response = $this->post('/api/employees/import', ['type' => '1', 'file' => phaseTwentyThreeUpload($path)])
            ->assertStatus(400)->assertJsonPath('code', 400)->assertJsonPath('message', 'Import pegawai gagal');
        $logId = $response->json('data.log_id');
        expect(phaseTwentyThreeConnection()->table('activity_logs')->where('id', $logId)->where('status', 'failed')->exists())->toBeTrue();
        $pdf = $this->get("/api/employees/import/download-failed-import/{$logId}")->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        expect(substr($pdf->getContent(), 0, 4))->toBe('%PDF')
            ->and($pdf->headers->get('content-disposition'))->toContain('Hasil error import excel');
    });

    it('preserves all-row rejection when a workbook mixes a valid and invalid row', function () {
        phaseTwentyThreeActingAs(true, 1);
        $marker = phaseTwentyThreePrefix();
        $path = phaseTwentyThreeWorkbook(1, $marker);
        $book = IOFactory::load($path);
        $sheet = $book->getSheet(0);
        for ($column = 1; $column <= 45; $column++) {
            $sheet->setCellValue([$column, 3], $sheet->getCell([$column, 2])->getValue());
        }
        $sheet->setCellValue('A3', null);
        $sheet->setCellValue('D3', '723'.substr(hash('sha256', $marker), 0, 12));
        $sheet->setCellValue('AI3', '623'.substr(hash('sha256', $marker), 0, 12));
        IOFactory::createWriter($book, 'Xlsx')->save($path);
        $this->phaseTwentyThreeFiles[] = $path;

        $this->post('/api/employees/import', ['type' => '1', 'file' => phaseTwentyThreeUpload($path)])
            ->assertStatus(400)->assertJsonPath('message', 'Import pegawai gagal');
        expect(phaseTwentyThreeConnection()->table('users')->where('name', 'Phase 23 '.$marker)->exists())->toBeFalse();
    });

    it('preserves validation, authorization, malformed workbook, history, and missing artifact contracts', function () {
        phaseTwentyThreeActingAs(false, 1);
        $this->postJson('/api/employees/import', ['type' => 1])->assertForbidden();

        phaseTwentyThreeActingAs(true, 1);
        $validation = $this->postJson('/api/employees/import', [])->assertStatus(422);
        expect($validation->json())->toHaveKeys(['message', 'errors'])
            ->and($validation->json('errors.file.0'))->toBe('The file field is required.');
        $this->getJson('/api/employees/import/histories')->assertStatus(422)->assertJsonPath('errors.type.0', 'Type harus diisi.');
        $this->getJson('/api/employees/import/download-template/99?type=1')->assertStatus(404)->assertJsonPath('message', 'File not found.');
        $this->getJson('/api/employees/import/download-failed-import/not-numeric?type=1')->assertStatus(400)
            ->assertJsonPath('message', 'Hasil error import tidak ditemukan');

        $marker = phaseTwentyThreePrefix();
        phaseTwentyThreeConnection()->table('activity_logs')->insertTs([
            'user_id' => auth()->id(), 'type' => 'add-bulk-asn', 'description' => $marker,
        ]);
        $this->getJson('/api/employees/import/histories?type=1&limit=1')->assertOk()
            ->assertJsonPath('code', 200)->assertJsonStructure(['data', 'pagination']);

        $malformed = tempnam(sys_get_temp_dir(), 'simdatuk-p23-').'.xlsx';
        file_put_contents($malformed, 'not-an-xlsx');
        $this->phaseTwentyThreeFiles[] = $malformed;
        $this->postJson('/api/employees/import', ['type' => '1', 'file' => phaseTwentyThreeUpload($malformed)])
            ->assertStatus(422)->assertJsonPath('errors.file.0', 'The file field must be a file of type: xlsx, csv.');
    });
});
