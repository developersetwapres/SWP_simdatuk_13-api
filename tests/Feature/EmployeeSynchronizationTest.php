<?php

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseTwentyTables(): array
{
    return [
        'users', 'roles', 'permissions', 'role_permissions', 'employment_types', 'grades',
        'position_histories', 'position_history_users', 'grade_histories', 'grade_history_users',
        'user_educations', 'user_families', 'training_histories', 'training_history_users',
    ];
}

function phaseTwentyConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseTwentyPrefix(): string
{
    return 'codex_phase20_'.bin2hex(random_bytes(6));
}

function phaseTwentyActingAs(bool $read, string $permissionName = 'Data Pegawai - ASN'): User
{
    $connection = phaseTwentyConnection();
    $prefix = phaseTwentyPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $permissionId = $connection->table('permissions')->where('name', $permissionName)->value('id');
    $connection->table('role_permissions')->insertTs([
        'role_id' => $roleId, 'permission_id' => $permissionId,
        'create' => false, 'read' => $read, 'update' => false, 'delete' => false,
    ]);
    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId, 'email' => $prefix.'@example.invalid', 'username' => $prefix,
        'password' => Hash::make('Password@123'), 'name' => 'Phase 20 Authorization User', 'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

/** @return array<string, mixed> */
function phaseTwentyEmployeeItem(string $nip): array
{
    return [
        'gelardepan' => 'Dr', 'nmpeg' => 'Phase 20 Synchronized Employee', 'gelarbelakang' => 'M.Si',
        'nipbaru' => $nip, 'niplama' => substr($nip, 0, 10), 'tempatlahir' => 'Jakarta',
        'tgllahir' => '1990-01-02', 'agama' => 'Islam', 'jeniskelamin' => 'Perempuan',
        'statkwn' => 'Kawin', 'jenispeg' => 'Sipil', 'pend_terakhir' => 'S2',
        'karpeg' => substr($nip, 0, 12), 'npwp' => '12.345.678.9-012.345',
        'statuspeg' => 'Aktif', 'nik' => '7'.str_pad((string) random_int(0, 999999999999999), 15, '0', STR_PAD_LEFT),
        'alamat' => 'Phase 20 Address', 'nohp' => '081234567890',
        'mkseluruhtahun' => 10, 'mkseluruhbulan' => 2, 'mkgoltahun' => 4, 'mkgolbulan' => 6,
        'email' => strtolower($nip).'@office.example.invalid',
    ];
}

/** @return array<string, array{status: int, data: array<string, mixed>}> */
function phaseTwentySuccessfulResponses(string $base, string $nip): array
{
    return [
        $base.'/token' => ['status' => 200, 'data' => ['access_token' => 'phase20-token']],
        $base.'/pegawai/v1/simdatuk/pegawaiAll' => ['status' => 200, 'data' => ['data' => [phaseTwentyEmployeeItem($nip)]]],
        $base.'/pegawai/v1/simdatuk/riwayatJabatan' => ['status' => 200, 'data' => ['data' => [[
            'nipbaru' => $nip, 'nama_jabatan' => 'Phase 20 Position', 'tmt' => '2026-09-01', 'nosk' => 'PH20-POS',
        ]]]],
        $base.'/pegawai/v1/simdatuk/riwayatGolongan' => ['status' => 200, 'data' => ['data' => [[
            'nipbaru' => $nip, 'tmt' => '2026-09-01', 'golongan' => 'IV/e',
        ]]]],
        $base.'/pegawai/v1/simdatuk/riwayatPendidikan' => ['status' => 200, 'data' => ['data' => [[
            'nipbaru' => $nip, 'pendidikan_formal' => 'S2', 'thn_lulus' => '2020',
            'nama_alamat_lembaga' => 'Phase 20 University', 'bidang_studi' => 'Technology',
        ]]]],
        $base.'/pegawai/v1/simdatuk/riwayatIstriSuami' => ['status' => 200, 'data' => ['data' => [[
            'nipbaru' => $nip, 'jenis_kelamin' => 'Laki-laki', 'status_perkawinan' => 'Menikah',
            'tanggal_lahir' => '1991-02-03', 'nama_istri_suami' => 'Phase 20 Spouse',
            'tempat_lahir' => 'Bandung', 'pekerjaan' => 'Professional',
        ]]]],
        $base.'/pegawai/v1/simdatuk/riwayatAnak' => ['status' => 200, 'data' => ['data' => [[
            'nipbaru' => $nip, 'jenis_kelamin' => 'Perempuan', 'status_perkawinan' => 'Belum Kawin',
            'tanggal_lahir' => '2015-04-05', 'nama_anak' => 'Phase 20 Child',
            'tempat_lahir' => 'Jakarta', 'pekerjaan' => null,
        ]]]],
        $base.'/pegawai/v1/simdatuk/riwayatDiklatTeknis' => ['status' => 200, 'data' => ['data' => [[
            'nipbaru' => $nip, 'tgl_mulai' => '2026-09-10', 'tgl_selesai' => '2026-09-12',
            'nama_diklat' => 'Phase 20 Technical Training', 'no_sertifikat' => 'PH20-CERT',
        ]]]],
    ];
}

function phaseTwentyFakeSuccessfulSync(string $base, string $nip): void
{
    $responses = phaseTwentySuccessfulResponses($base, $nip);
    Http::fake(function (ClientRequest $request) use ($responses) {
        $response = $responses[$request->url()] ?? null;
        if (! $response) {
            throw new RuntimeException('Unexpected Phase 20 HTTP request: '.$request->url());
        }

        return Http::response($response['data'], $response['status']);
    });
}

it('registers synchronization before the Employee wildcard and preserves the complete active Employee order', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/employees'))
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware()])
        ->values()->all();

    expect($routes)->toBe([
        ['GET', 'api/employees', 'App\\Http\\Controllers\\EmployeeController@index', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/employees', 'App\\Http\\Controllers\\EmployeeController@create', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/employees/synchronization', 'App\\Http\\Controllers\\SynchronizationController@index', ['api', 'auth:sanctum', 'role.access']],
        ['GET', 'api/employees/{id}', 'App\\Http\\Controllers\\EmployeeController@show', ['api', 'auth:sanctum', 'role.access']],
        ['POST', 'api/employees/{id}', 'App\\Http\\Controllers\\EmployeeController@update', ['api', 'auth:sanctum', 'role.access']],
        ['DELETE', 'api/employees/{id}', 'App\\Http\\Controllers\\EmployeeController@delete', ['api', 'auth:sanctum', 'role.access']],
        ['PUT', 'api/employees/status', 'App\\Http\\Controllers\\EmployeeController@status', ['api', 'auth:sanctum', 'role.access']],
    ]);
});

it('keeps the seven-call synchronization operation order and legacy mapping constants', function () {
    $controller = file_get_contents(app_path('Http/Controllers/SynchronizationController.php'));
    $calls = ['$this->getAccessToken();', '$this->getPegawai();', '$this->getPosition();', '$this->getGrade();', '$this->getEducation();', '$this->getFamily();', '$this->getTraining();'];
    $offsets = array_map(fn (string $call): int|false => strpos($controller, $call), $calls);

    expect($offsets)->not->toContain(false)
        ->and($offsets)->toBe(collect($offsets)->sort()->values()->all())
        ->and($controller)->toContain("'IV/e' => 1")
        ->toContain("'Sipil' => 2")
        ->toContain('$duration = $endDate->diff($endDate);');
});

describe('Employee synchronization on the guarded MySQL clone with HTTP fakes', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }
        $database = (string) env('SIMDATUK_CLONE_DATABASE');
        if ($database !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing MySQL writes: expected the authorized SWP_simdatuk_test13 clone.');
        }
        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
        $connection = phaseTwentyConnection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $database) {
            throw new RuntimeException('Refusing MySQL writes: clone connection guard failed.');
        }
        $engines = $connection->table('information_schema.tables')->where('table_schema', $database)
            ->whereIn('table_name', phaseTwentyTables())->pluck('ENGINE')->map(fn (?string $engine): string => strtoupper((string) $engine));
        if ($engines->count() !== count(phaseTwentyTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL writes: every Phase 20 table must exist and use InnoDB.');
        }
        putenv('SIMSDM_URL=https://simsdm.phase20.test');
        putenv('SIMSDM_CLIENT_ID=phase20-client');
        putenv('SIMSDM_CLIENT_SECRET=phase20-secret');
        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }
        $connection = phaseTwentyConnection();
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        putenv('SIMSDM_URL');
        putenv('SIMSDM_CLIENT_ID');
        putenv('SIMSDM_CLIENT_SECRET');
        DB::disconnect('mysql');
    });

    it('maps all eight fake upstream responses into clone rows and remains idempotent', function () {
        $base = 'https://simsdm.phase20.test';
        $nip = 'P20'.substr(hash('sha256', phaseTwentyPrefix()), 0, 15);
        phaseTwentyActingAs(true);
        phaseTwentyFakeSuccessfulSync($base, $nip);

        $this->getJson('/api/employees/synchronization')->assertOk()->assertExactJson([
            'code' => 200, 'message' => 'Pegawai berhasil disinkronisasi.', 'data' => null,
        ]);
        $user = phaseTwentyConnection()->table('users')->where('employee_id_number', $nip)->firstOrFail();
        expect($user->type)->toBeNull()
            ->and((int) $user->employment_type_id)->toBe(2)
            ->and((int) $user->education_level)->toBe(7)
            ->and((int) $user->religion)->toBe(1)
            ->and((int) $user->gender)->toBe(0);

        $expectedCounts = [
            'position_history_users' => 1, 'grade_history_users' => 1, 'user_educations' => 1,
            'user_families' => 2, 'training_history_users' => 1,
        ];
        foreach ($expectedCounts as $table => $count) {
            expect(phaseTwentyConnection()->table($table)->where('user_id', $user->id)->count(), $table)->toBe($count);
        }
        $trainingId = phaseTwentyConnection()->table('training_history_users')->where('user_id', $user->id)->value('training_history_id');
        expect((int) phaseTwentyConnection()->table('training_histories')->where('id', $trainingId)->value('duration'))->toBe(0);

        $this->getJson('/api/employees/synchronization')->assertOk();
        expect(phaseTwentyConnection()->table('users')->where('employee_id_number', $nip)->count())->toBe(1);
        foreach ($expectedCounts as $table => $count) {
            expect(phaseTwentyConnection()->table($table)->where('user_id', $user->id)->count(), $table)->toBe($count);
        }

        Http::assertSentCount(16);
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === $base.'/token'
            && $request['grant_type'] === 'client_credentials'
            && $request['client_id'] === 'phase20-client'
            && $request['client_secret'] === 'phase20-secret');
        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), '/pegawai/v1/simdatuk/')
            && $request->hasHeader('Authorization', 'Bearer phase20-token'));
    });

    it('returns success after ordinary upstream HTTP failures and performs no domain writes', function () {
        phaseTwentyActingAs(true);
        $before = collect(['users', 'position_history_users', 'grade_history_users', 'user_educations', 'user_families', 'training_history_users'])
            ->mapWithKeys(fn (string $table): array => [$table => phaseTwentyConnection()->table($table)->count()]);
        Http::fake(fn () => Http::response(['error' => 'fake upstream failure'], 500));

        $this->getJson('/api/employees/synchronization')->assertOk()->assertExactJson([
            'code' => 200, 'message' => 'Pegawai berhasil disinkronisasi.', 'data' => null,
        ]);

        Http::assertSentCount(8);
        foreach ($before as $table => $count) {
            expect(phaseTwentyConnection()->table($table)->count(), $table)->toBe($count);
        }
    });

    it('preserves partial DB writes when a later HTTP call throws because synchronization has no transaction', function () {
        $base = 'https://simsdm.phase20.test';
        $nip = 'P20'.substr(hash('sha256', phaseTwentyPrefix()), 0, 15);
        $visited = [];
        phaseTwentyActingAs(true);
        $responses = phaseTwentySuccessfulResponses($base, $nip);
        Http::fake(function (ClientRequest $request) use ($responses, $base, &$visited) {
            $visited[] = $request->url();
            if ($request->url() === $base.'/pegawai/v1/simdatuk/riwayatJabatan') {
                throw new ConnectionException('Fake Phase 20 connection failure');
            }
            $response = $responses[$request->url()];

            return Http::response($response['data'], $response['status']);
        });

        $this->getJson('/api/employees/synchronization')->assertStatus(400)->assertExactJson([
            'code' => 400, 'message' => 'Gagal melakukan sinkronisasi.', 'data' => null,
        ]);

        expect(phaseTwentyConnection()->table('users')->where('employee_id_number', $nip)->exists())->toBeTrue()
            ->and(phaseTwentyConnection()->transactionLevel())->toBe(1)
            ->and($visited)->toBe([
                $base.'/token',
                $base.'/pegawai/v1/simdatuk/pegawaiAll',
                $base.'/pegawai/v1/simdatuk/riwayatJabatan',
            ]);
        // Laravel records only the two fake requests that returned a response;
        // the third request is observed above but throws before being recorded.
        Http::assertSentCount(2);
    });

    it('uses default ASN read authorization and never contacts upstream when denied', function () {
        phaseTwentyActingAs(false);
        Http::fake();

        $this->getJson('/api/employees/synchronization')->assertStatus(403)->assertExactJson([
            'code' => 403,
            'message' => "Access denied. You don't have permission to read Data Pegawai - ASN.",
            'data' => null,
        ]);
        Http::assertNothingSent();
    });

    it('selects Non ASN permission when synchronization is requested with type 2', function () {
        phaseTwentyActingAs(true, 'Data Pegawai - Non ASN');
        Http::fake(fn () => Http::response(['error' => 'fake'], 500));

        $this->getJson('/api/employees/synchronization?type=2')->assertOk();
        Http::assertSentCount(8);
    });
});
