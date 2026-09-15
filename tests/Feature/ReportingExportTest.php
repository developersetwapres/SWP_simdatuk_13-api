<?php

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** @return list<string> */
function phaseTwentyTwoTables(): array
{
    return [
        'users', 'roles', 'permissions', 'role_permissions', 'positions', 'position_echelons',
        'echelons', 'grades', 'residences', 'institutions', 'employment_types',
        'user_educations', 'user_families', 'position_histories', 'position_history_users',
        'position_history_echelons', 'groups', 'grade_histories', 'grade_history_users', 'decrees',
        'training_histories', 'training_history_users', 'training_levels',
        'recognition_histories', 'recognition_history_users', 'recognitions',
        'target_histories', 'target_history_users', 'performance_histories', 'performance_history_users',
        'disciplinary_histories', 'disciplinary_history_users', 'disciplinaries',
        'user_leaves', 'user_notes', 'user_credits', 'user_assessments', 'user_competencies', 'user_talents',
    ];
}

function phaseTwentyTwoConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseTwentyTwoPrefix(): string
{
    return 'codex_phase22_'.bin2hex(random_bytes(6));
}

/** @param list<string> $readPermissions */
function phaseTwentyTwoActingAs(array $readPermissions = []): User
{
    $connection = phaseTwentyTwoConnection();
    $prefix = phaseTwentyTwoPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    foreach ($readPermissions as $permissionName) {
        $permissionId = $connection->table('permissions')->where('name', $permissionName)->value('id');
        $connection->table('role_permissions')->insertTs([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'create' => false,
            'read' => true,
            'update' => false,
            'delete' => false,
        ]);
    }
    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 22 Export Employee',
        'employee_id_number' => 'p22'.substr(hash('sha256', $prefix), 0, 12),
        'description' => $prefix,
        'type' => 1,
        'status' => true,
        'employment_status' => 1,
        'date_of_birth' => '1990-01-01',
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

function phaseTwentyTwoAttachUniqueStudy(User $user): string
{
    $major = phaseTwentyTwoPrefix();
    phaseTwentyTwoConnection()->table('user_educations')->insertTs([
        'user_id' => $user->id,
        'level' => 8,
        'name' => 'Phase 22 University',
        'major' => $major,
        'year_of_graduation' => 2026,
    ]);

    return $major;
}

function phaseTwentyTwoBinaryPath($response): string
{
    $base = $response->baseResponse;
    expect($base)->toBeInstanceOf(BinaryFileResponse::class);

    return $base->getFile()->getPathname();
}

function phaseTwentyTwoDeleteBinary($response): void
{
    $path = phaseTwentyTwoBinaryPath($response);
    if (is_file($path)) {
        unlink($path);
    }
}

it('registers the nine Phase 22 routes in Laravel 10 order', function () {
    $uris = [
        'api/diagrams/export', 'api/export/study-programs', 'api/export/recapitulations/{type}',
        'api/export/comparisons', 'api/export/comparison-promotions', 'api/export/employees/{type}',
        'api/export/employees-drh/{id}', 'api/export/employees-drh', 'api/export/preview',
    ];
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => in_array($route->uri(), $uris, true))
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware()])
        ->values()->all();

    expect($routes)->toBe([
        ['GET', 'api/diagrams/export', 'App\\Http\\Controllers\\DiagramController@export', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/export/study-programs', 'App\\Http\\Controllers\\ExportController@studyPrograms', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/export/recapitulations/{type}', 'App\\Http\\Controllers\\ExportRecapitulationController@recapitulation', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/export/comparisons', 'App\\Http\\Controllers\\ExportComparisonController@comparison', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/export/comparison-promotions', 'App\\Http\\Controllers\\ExportComparisonController@comparisonPromotion', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/export/employees/{type}', 'App\\Http\\Controllers\\ExportController@employees', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/export/employees-drh/{id}', 'App\\Http\\Controllers\\ExportController@detailEmployee', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/export/employees-drh', 'App\\Http\\Controllers\\ExportController@zipDetailEmployee', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/export/preview', 'App\\Http\\Controllers\\ExportController@exportExcelsPreview', ['api', 'auth:sanctum', 'role.access']],
    ]);
});

describe('Phase 22 exports on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }
        $database = (string) env('SIMDATUK_CLONE_DATABASE');
        if ($database !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing Phase 22 clone access: exact database name mismatch.');
        }
        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
        $connection = phaseTwentyTwoConnection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $database) {
            throw new RuntimeException('Refusing Phase 22 clone access: MySQL/database guard failed.');
        }
        $engines = $connection->table('information_schema.tables')->where('table_schema', $database)
            ->whereIn('table_name', phaseTwentyTwoTables())->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));
        if ($engines->count() !== count(phaseTwentyTwoTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing Phase 22 clone access: every guarded table must exist and use InnoDB.');
        }
        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }
        $connection = phaseTwentyTwoConnection();
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::disconnect('mysql');
    });

    it('preserves export authorization fall-through and validation', function () {
        phaseTwentyTwoActingAs();
        $this->getJson('/api/export/study-programs')->assertOk()->assertJsonPath('code', 200);
        $this->postJson('/api/export/comparisons', [])->assertStatus(422)
            ->assertJsonPath('data.user_id.0', 'User ID tidak boleh kosong.')
            ->assertJsonPath('data.output.0', 'Output tidak boleh kosong.');
        $this->getJson('/api/export/recapitulations/99')->assertStatus(404)
            ->assertJsonPath('message', 'Tipe tidak ditemukan. harap gunakan 1, 2, 3 atau 4.');
    });

    it('returns distinct latest study programs from the clone', function () {
        $user = phaseTwentyTwoActingAs();
        $major = phaseTwentyTwoAttachUniqueStudy($user);
        $response = $this->getJson('/api/export/study-programs')->assertOk()
            ->assertJsonPath('code', 200)->assertJsonPath('message', 'success');
        expect($response->json('data'))->toContain($major);
    });

    it('generates the diagram PDF contract', function () {
        phaseTwentyTwoActingAs(['Rekapitulasi - Peta Jabatan']);
        $response = $this->get('/api/diagrams/export')->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        expect($response->headers->get('content-disposition'))->toContain('peta-jabatan.pdf')
            ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
    });

    it('generates each recapitulation PDF contract', function (int $type, string $title) {
        phaseTwentyTwoActingAs();
        $response = $this->get("/api/export/recapitulations/{$type}")->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        expect($response->headers->get('content-disposition'))->toContain($title)
            ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
    })->with([
        'all' => [1, 'Rekapitulasi Pegawai'],
        'ASN' => [2, 'Rekapitulasi Pegawai ASN'],
        'Non ASN' => [3, 'Rekapitulasi Pegawai Non ASN'],
        'Outsource' => [4, 'Rekapitulasi Pegawai Outsourcing'],
    ]);

    it('generates comparison output in every supported format', function (string $output, string $mime) {
        phaseTwentyTwoActingAs();
        $ids = phaseTwentyTwoConnection()->table('users')->where('type', 1)
            ->whereIn('employment_status', [1, 6, 10])->orderBy('id')->limit(2)->pluck('id')->all();
        expect($ids)->toHaveCount(2);
        $response = $this->post('/api/export/comparisons', ['user_id' => $ids, 'output' => $output])
            ->assertOk()->assertHeader('content-type', $mime);

        if ($output === '.pdf') {
            expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
        } else {
            $path = phaseTwentyTwoBinaryPath($response);
            expect(is_file($path))->toBeTrue()->and(filesize($path))->toBeGreaterThan(100);
            if ($output === '.xlsx') {
                $sheet = IOFactory::load($path)->getActiveSheet();
                expect($sheet->getCell('A6')->getValue())->toBe('Nama')
                    ->and($sheet->getCell('A7')->getValue())->toBe('Jabatan')
                    ->and($sheet->getDrawingCollection())->toHaveCount(1);
            } else {
                expect(file_get_contents($path))->toContain('Nama')->toContain('Jabatan');
            }
            phaseTwentyTwoDeleteBinary($response);
        }
    })->with([
        'PDF' => ['.pdf', 'application/pdf'],
        'XLSX' => ['.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'CSV' => ['.csv', 'text/csv; charset=UTF-8'],
    ]);

    it('generates comparison-promotion PDF', function () {
        phaseTwentyTwoActingAs();
        $ids = phaseTwentyTwoConnection()->table('users')->where('type', 1)
            ->whereIn('employment_status', [1, 6, 10])->orderBy('id')->limit(2)->pluck('id')->all();
        $response = $this->post('/api/export/comparison-promotions', ['user_id' => $ids])
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        expect($response->headers->get('content-disposition'))->toContain('promotion-user.pdf')
            ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
    });

    it('generates Employee tabular output in every supported format', function (string $type, string $mime) {
        $user = phaseTwentyTwoActingAs();
        $major = phaseTwentyTwoAttachUniqueStudy($user);
        $response = $this->post("/api/export/employees/{$type}", [
            'study_programs' => [$major],
            'isName' => 1,
            'isNip' => 1,
        ])->assertOk()->assertHeader('content-type', $mime);

        if ($type === 'pdf') {
            expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
        } else {
            $path = phaseTwentyTwoBinaryPath($response);
            expect(is_file($path))->toBeTrue()->and(filesize($path))->toBeGreaterThan(50);
            if ($type === 'xlsx') {
                $sheet = IOFactory::load($path)->getActiveSheet();
                expect($sheet->getCell('A2')->getValue())->toBe('No')
                    ->and($sheet->getCell('B2')->getValue())->toBe('Nama')
                    ->and($sheet->getCell('B3')->getValue())->toBe($user->name)
                    ->and($sheet->getDrawingCollection())->toHaveCount(1);
            } else {
                expect(file_get_contents($path))->toContain('Nama')->toContain($user->name);
            }
            phaseTwentyTwoDeleteBinary($response);
        }
    })->with([
        'PDF' => ['pdf', 'application/pdf'],
        'XLSX' => ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'CSV' => ['csv', 'text/csv; charset=UTF-8'],
    ]);

    it('generates one Employee DRH PDF and preserves missing Employee response', function () {
        $user = phaseTwentyTwoActingAs();
        $response = $this->post("/api/export/employees-drh/{$user->id}")->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        expect($response->headers->get('content-disposition'))->toContain($user->name)
            ->and(substr($response->getContent(), 0, 4))->toBe('%PDF');
    });

    it('preserves missing Employee DRH response', function () {
        phaseTwentyTwoActingAs();
        $missingId = ((int) phaseTwentyTwoConnection()->table('users')->max('id')) + 100000;
        $this->postJson("/api/export/employees-drh/{$missingId}")->assertStatus(404)
            ->assertExactJson(['code' => 404, 'message' => 'Pegawai tidak ditemukan.', 'data' => null]);
    });

    it('preserves Employee export validation messages', function () {
        phaseTwentyTwoActingAs();
        $this->postJson('/api/export/employees/xlsx', ['employee_type' => 'ASN'])
            ->assertStatus(422)->assertJsonPath('data.employee_type.0', 'Employee Type harus berupa array');
    });

    it('generates native ZipArchive DRH with the source filename layout and no PDF residue', function () {
        $user = phaseTwentyTwoActingAs();
        $response = $this->post('/api/export/employees-drh', [
            'job_description' => [$user->description],
        ])->assertOk()->assertHeader('content-type', 'application/zip');
        $path = phaseTwentyTwoBinaryPath($response);
        $zip = new ZipArchive;
        expect($zip->open($path))->toBeTrue()->and($zip->numFiles)->toBe(1)
            ->and($zip->getNameIndex(0))->toBe($user->name.'_'.$user->employee_id_number.'.pdf');
        $pdfName = $zip->getNameIndex(0);
        $pdfBytes = $zip->getFromName($pdfName);
        $zip->close();
        expect(substr($pdfBytes, 0, 4))->toBe('%PDF')
            ->and(is_file(storage_path('app/public/document/'.$pdfName)))->toBeFalse();
        phaseTwentyTwoDeleteBinary($response);
        expect(is_file($path))->toBeFalse();
    });

    it('returns an Employee export preview with source pagination envelope', function () {
        $user = phaseTwentyTwoActingAs();
        $major = phaseTwentyTwoAttachUniqueStudy($user);
        $this->postJson('/api/export/preview', [
            'study_programs' => [$major],
            'isName' => 1,
            'isNip' => 1,
            'limit' => 10,
        ])->assertOk()->assertJsonPath('code', 200)->assertJsonPath('message', 'success')
            ->assertJsonPath('data.0.name', $user->name)
            ->assertJsonStructure(['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages', 'links']]);
    });
});
