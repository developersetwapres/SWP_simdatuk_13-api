<?php

use App\Models\User;
use App\Repositories\GradeRepository;
use App\Repositories\PositionRepository;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseThirteenTables(): array
{
    return [
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'position_histories',
        'position_history_users',
        'position_history_echelons',
        'grade_histories',
        'grade_history_users',
        'positions',
        'grades',
        'groups',
        'decrees',
    ];
}

function phaseThirteenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseThirteenPrefix(): string
{
    return 'codex_phase13_'.bin2hex(random_bytes(6));
}

/**
 * @param  array<string, array{create?: bool, read?: bool, update?: bool, delete?: bool}>  $permissionFlags
 */
function phaseThirteenActingAs(array $permissionFlags): User
{
    $connection = phaseThirteenConnection();
    $prefix = phaseThirteenPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $permissions = $connection->table('permissions')
        ->whereIn('name', ['Data Riwayat - Jabatan', 'Data Riwayat - Golongan'])
        ->get(['id', 'name']);

    expect($permissions)->toHaveCount(2);

    $pivots = $permissions->map(function (object $permission) use ($roleId, $permissionFlags): array {
        $flags = $permissionFlags[$permission->name] ?? [];

        return [
            'role_id' => $roleId,
            'permission_id' => $permission->id,
            'create' => $flags['create'] ?? false,
            'read' => $flags['read'] ?? false,
            'update' => $flags['update'] ?? false,
            'delete' => $flags['delete'] ?? false,
        ];
    })->all();
    $connection->table('role_permissions')->insertTs($pivots);
    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 13 Authorization User',
        'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

/** @return array<string, array{create: bool, read: bool, update: bool, delete: bool}> */
function phaseThirteenAllHistoryPermissions(): array
{
    $all = ['create' => true, 'read' => true, 'update' => true, 'delete' => true];

    return [
        'Data Riwayat - Jabatan' => $all,
        'Data Riwayat - Golongan' => $all,
    ];
}

it('registers the two history route groups in source order without Employee detail', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/position-histories') || str_starts_with($route->uri(), 'api/grade-histories'))
        ->map(fn ($route): array => [
            'method' => $route->methods()[0],
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
            'middleware' => $route->middleware(),
        ])
        ->values()
        ->all();

    expect($routes)->toBe([
        ['method' => 'GET', 'uri' => 'api/position-histories', 'action' => 'App\\Http\\Controllers\\PositionHistoryController@index', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/position-histories', 'action' => 'App\\Http\\Controllers\\PositionHistoryController@create', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/position-histories/{id}', 'action' => 'App\\Http\\Controllers\\PositionHistoryController@show', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/position-histories/{id}', 'action' => 'App\\Http\\Controllers\\PositionHistoryController@update', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'DELETE', 'uri' => 'api/position-histories/{id}', 'action' => 'App\\Http\\Controllers\\PositionHistoryController@delete', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/grade-histories', 'action' => 'App\\Http\\Controllers\\GradeHistoryController@index', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/grade-histories', 'action' => 'App\\Http\\Controllers\\GradeHistoryController@create', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/grade-histories/{id}', 'action' => 'App\\Http\\Controllers\\GradeHistoryController@show', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/grade-histories/{id}', 'action' => 'App\\Http\\Controllers\\GradeHistoryController@update', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'DELETE', 'uri' => 'api/grade-histories/{id}', 'action' => 'App\\Http\\Controllers\\GradeHistoryController@delete', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
    ]);

    expect(collect(Route::getRoutes()->getRoutes())->contains(fn ($route): bool => $route->uri() === 'api/employees/{id}'))->toBeFalse();
});

describe('Position and Grade history on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }

        $cloneDatabase = (string) env('SIMDATUK_CLONE_DATABASE');

        if ($cloneDatabase !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing MySQL access: expected the authorized SWP_simdatuk_test13 clone.');
        }

        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $cloneDatabase);
        DB::purge('mysql');

        $connection = phaseThirteenConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL access: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', phaseThirteenTables())
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== count(phaseThirteenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL access: every Phase 13 table must exist and use InnoDB.');
        }

        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }

        $connection = phaseThirteenConnection();

        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        DB::disconnect('mysql');
    });

    it('lists searches and paginates each history domain in source ordering', function (string $path, string $parentTable, string $childTable, string $foreignKey) {
        $connection = phaseThirteenConnection();
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());
        $candidate = $connection->table($parentTable)->whereNotNull('name')->orderByDesc('updated_at')->firstOrFail(['id', 'name']);
        $response = $this->getJson("/api/{$path}?search=".rawurlencode($candidate->name).'&limit=5');

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'period_month', 'period_year', 'created_at', 'total']],
                'pagination',
            ]);

        $returned = collect($response->json('data'))->firstWhere('id', $candidate->id);
        expect($returned)->not->toBeNull()
            ->and((int) $returned['total'])->toBe($connection->table($childTable)->where($foreignKey, $candidate->id)->count())
            ->and($returned['created_at'])->toMatch('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}$/');
    })->with([
        'Position history' => ['position-histories', 'position_histories', 'position_history_users', 'position_history_id'],
        'Grade history' => ['grade-histories', 'grade_histories', 'grade_history_users', 'grade_history_id'],
    ]);

    it('returns the source empty pagination envelope', function (string $path) {
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());

        $this->getJson("/api/{$path}?search=codex_phase13_no_match")
            ->assertOk()
            ->assertJsonPath('message', 'Mohon maaf, data tidak ditemukan.')
            ->assertJsonPath('pagination.total', 0);
    })->with(['position-histories', 'grade-histories']);

    it('returns the source index validation messages', function (string $path) {
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());

        $this->getJson("/api/{$path}?page=zero&limit=0")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Page harus berupa angka.')
            ->assertJsonPath('data.page.0', 'Page harus berupa angka.')
            ->assertJsonPath('data.limit.0', 'Limit minimal harus 1 atau lebih.');
    })->with(['position-histories', 'grade-histories']);

    it('shows Position history with exact joined child projection and legacy document URLs', function () {
        $connection = phaseThirteenConnection();
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());
        $history = $connection->table('position_histories as ph')
            ->join('position_history_users as phu', 'ph.id', '=', 'phu.position_history_id')
            ->select('ph.id')
            ->groupBy('ph.id')
            ->orderByRaw('COUNT(phu.id) DESC')
            ->firstOrFail();
        $expectedCount = $connection->table('position_history_users')->where('position_history_id', $history->id)->count();

        $response = $this->getJson("/api/position-histories/{$history->id}");

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonCount($expectedCount, 'data.users')
            ->assertJsonStructure(['data' => [
                'id', 'period_month', 'period_year', 'name', 'created_at',
                'users' => [[
                    'id', 'user_id', 'name', 'employee_id_number', 'position', 'group_id', 'group_name',
                    'echelon', 'position_status', 'effective_date', 'decree', 'decree_document', 'decree_number',
                    'type_decree_id', 'type_decree_name', 'type_termination_decree_id', 'type_termination_decree_name',
                    'decree_date', 'termination_date', 'termination_decree', 'termination_decree_number',
                    'termination_decree_date', 'status',
                ]],
            ]]);

        expect(collect($response->json('data.users'))->every(fn (array $user): bool => str_starts_with($user['decree_document'], config('app.url'))))->toBeTrue();
    });

    it('shows Grade history with exact Grade and Employee projection', function () {
        $connection = phaseThirteenConnection();
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());
        $history = $connection->table('grade_histories as gh')
            ->join('grade_history_users as ghu', 'gh.id', '=', 'ghu.grade_history_id')
            ->select('gh.id')
            ->groupBy('gh.id')
            ->orderByRaw('COUNT(ghu.id) DESC')
            ->firstOrFail();
        $expectedCount = $connection->table('grade_history_users as ghu')
            ->join('users as u', 'u.id', '=', 'ghu.user_id')
            ->join('grades as g', 'g.id', '=', 'ghu.grade_id')
            ->where('ghu.grade_history_id', $history->id)
            ->count();

        $this->getJson("/api/grade-histories/{$history->id}")
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonCount($expectedCount, 'data.users')
            ->assertJsonStructure(['data' => [
                'id', 'period_month', 'period_year', 'name', 'created_at',
                'users' => [['id', 'user_id', 'name', 'employee_id_number', 'grade_id', 'grade_name', 'grade_code', 'effective_date', 'decree_number', 'status', 'created_at']],
            ]]);
    });

    it('preserves missing-history messages including the Position show typo', function () {
        $connection = phaseThirteenConnection();
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());
        $missingPosition = ((int) $connection->table('position_histories')->max('id')) + 1_000_000;
        $missingGrade = ((int) $connection->table('grade_histories')->max('id')) + 1_000_000;

        $this->getJson("/api/position-histories/{$missingPosition}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Riwayat golongan tidak ditemukan.');
        $this->getJson("/api/grade-histories/{$missingGrade}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Riwayat golongan tidak ditemukan.');
    });

    it('returns Employee-detail-compatible Position repository history in descending effective date order', function () {
        $connection = phaseThirteenConnection();
        $userId = $connection->table('position_history_users')
            ->select('user_id')
            ->groupBy('user_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('user_id');

        $positions = app(PositionRepository::class)->getDetail($userId);
        $expectedCount = $connection->table('position_history_users')->where('user_id', $userId)->count();

        expect($positions)->toHaveCount($expectedCount)
            ->and(array_keys((array) $positions->first()))->toBe([
                'id', 'period_month', 'period_year', 'position', 'group_id', 'group_name', 'echelon',
                'echelon_name', 'position_status', 'effective_date', 'decree', 'decree_document', 'decree_number',
                'type_decree_id', 'type_decree_name', 'type_termination_decree_id', 'type_termination_decree_name',
                'decree_date', 'termination_date', 'termination_decree', 'termination_decree_number',
                'termination_decree_date', 'status',
            ])
            ->and($positions->every(fn (object $position): bool => str_starts_with($position->decree_document, config('app.url'))))->toBeTrue();

        $timestamps = $positions->pluck('effective_date')
            ->filter()
            ->map(fn (string $date): int => Carbon::createFromFormat('d-m-Y', $date)->getTimestamp())
            ->values()
            ->all();
        $sortedTimestamps = $timestamps;
        rsort($sortedTimestamps);
        expect($timestamps)->toBe($sortedTimestamps);
    });

    it('returns Employee-detail-compatible Grade repository history and master projection', function () {
        $connection = phaseThirteenConnection();
        $userId = $connection->table('grade_history_users')
            ->select('user_id')
            ->groupBy('user_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('user_id');

        $grades = app(GradeRepository::class)->getDetail($userId);
        $expectedCount = $connection->table('grade_history_users')->where('user_id', $userId)->count();

        expect($grades)->toHaveCount($expectedCount)
            ->and(array_keys((array) $grades->first()))->toBe([
                'id', 'period_month', 'period_year', 'grade_id', 'grade_name', 'grade_code', 'effective_date',
                'decree_name', 'decree_document', 'type_of_decree', 'type_of_decree_name', 'decree_number',
                'decree_date', 'description', 'status',
            ])
            ->and($grades->every(fn (object $grade): bool => str_starts_with($grade->decree_document, config('app.url'))))->toBeTrue();

        $timestamps = $grades->pluck('effective_date')
            ->filter()
            ->map(fn (string $date): int => Carbon::createFromFormat('d-m-Y', $date)->getTimestamp())
            ->values()
            ->all();
        $sortedTimestamps = $timestamps;
        rsort($sortedTimestamps);
        expect($timestamps)->toBe($sortedTimestamps);
    });

    it('returns empty repository collections for an Employee without each history', function () {
        $connection = phaseThirteenConnection();
        $withoutPosition = $connection->table('users as u')
            ->leftJoin('position_history_users as phu', 'u.id', '=', 'phu.user_id')
            ->whereNull('phu.id')
            ->value('u.id');
        $withoutGrade = $connection->table('users as u')
            ->leftJoin('grade_history_users as ghu', 'u.id', '=', 'ghu.user_id')
            ->whereNull('ghu.id')
            ->value('u.id');

        expect(app(PositionRepository::class)->getDetail($withoutPosition))->toBeEmpty()
            ->and(app(GradeRepository::class)->getDetail($withoutGrade))->toBeEmpty();
    });

    it('creates updates and deletes isolated Position history with child cascade', function () {
        $connection = phaseThirteenConnection();
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());
        $prefix = phaseThirteenPrefix();
        $userId = $connection->table('users')->whereNull('role_id')->value('id');
        $groupId = $connection->table('groups')->value('id');
        $echelonId = $connection->table('position_history_echelons')->value('id');

        $this->postJson('/api/position-histories', [
            'name' => $prefix,
            'period_month' => 9,
            'period_year' => '2026',
            'users' => [[
                'user_id' => $userId,
                'position' => 'Phase 13 Position',
                'group_id' => $groupId,
                'echelon' => $echelonId,
                'position_status' => 1,
                'effective_date' => '2026-09-01',
                'status' => true,
            ]],
        ])->assertOk()->assertJsonPath('message', 'Riwayat jabatan berhasil ditambah.');

        $historyId = $connection->table('position_histories')->where('name', $prefix)->value('id');
        $child = $connection->table('position_history_users')->where('position_history_id', $historyId)->first();
        expect($child->decree_document)->toBeNull();

        $this->postJson("/api/position-histories/{$historyId}", [
            'name' => $prefix.' updated',
            'users' => [[
                'id' => $child->id,
                'user_id' => $userId,
                'position' => 'Phase 13 Updated Position',
                'group_id' => $groupId,
                'echelon' => $echelonId,
                'position_status' => 2,
                'effective_date' => '2026-09-02',
                'status' => false,
            ]],
        ])->assertOk()->assertJsonPath('message', 'Riwayat jabatan berhasil diupdate.');

        expect($connection->table('position_history_users')->where('id', $child->id)->value('position'))->toBe('Phase 13 Updated Position');

        $this->deleteJson("/api/position-histories/{$historyId}")
            ->assertOk()
            ->assertJsonPath('message', 'Riwayat jabatan berhasil dihapus.');
        expect($connection->table('position_histories')->where('id', $historyId)->exists())->toBeFalse()
            ->and($connection->table('position_history_users')->where('id', $child->id)->exists())->toBeFalse();
    });

    it('creates updates and deletes Grade history while synchronizing the Employee current Grade', function () {
        $connection = phaseThirteenConnection();
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());
        $prefix = phaseThirteenPrefix();
        $employee = $connection->table('users')->whereNull('role_id')->first(['id', 'grade_id', 'grade_effective_date']);
        $gradeIds = $connection->table('grades')->orderBy('id')->limit(2)->pluck('id');

        $this->postJson('/api/grade-histories', [
            'name' => $prefix,
            'period_month' => 9,
            'period_year' => '2026',
            'users' => [[
                'user_id' => $employee->id,
                'grade_id' => $gradeIds[0],
                'effective_date' => '2026-09-01',
                'decree_number' => 'PHASE13-CREATE',
                'status' => false,
            ]],
        ])->assertOk()->assertJsonPath('message', 'Riwayat golongan berhasil ditambah.');

        $historyId = $connection->table('grade_histories')->where('name', $prefix)->value('id');
        $child = $connection->table('grade_history_users')->where('grade_history_id', $historyId)->first();
        $currentEmployee = $connection->table('users')->where('id', $employee->id)->first(['grade_id', 'grade_effective_date']);
        expect((int) $child->status)->toBe(1)
            ->and((int) $currentEmployee->grade_id)->toBe((int) $gradeIds[0])
            ->and($currentEmployee->grade_effective_date)->toBe('2026-09-01');

        $this->postJson("/api/grade-histories/{$historyId}", [
            'name' => $prefix.' updated',
            'users' => [[
                'id' => $child->id,
                'user_id' => $employee->id,
                'grade_id' => $gradeIds[1],
                'effective_date' => '2026-09-02',
                'decree_number' => 'PHASE13-UPDATE',
                'status' => false,
            ]],
        ])->assertOk()->assertJsonPath('message', 'Riwayat golongan berhasil diupdate.');

        $updatedEmployee = $connection->table('users')->where('id', $employee->id)->first(['grade_id', 'grade_effective_date']);
        expect((int) $updatedEmployee->grade_id)->toBe((int) $gradeIds[1])
            ->and($updatedEmployee->grade_effective_date)->toBe('2026-09-02');

        $this->deleteJson("/api/grade-histories/{$historyId}")
            ->assertOk()
            ->assertJsonPath('message', 'Riwayat golongan berhasil dihapus.');
        expect($connection->table('grade_histories')->where('id', $historyId)->exists())->toBeFalse()
            ->and($connection->table('grade_history_users')->where('id', $child->id)->exists())->toBeFalse()
            ->and((int) $connection->table('users')->where('id', $employee->id)->value('grade_id'))->toBe((int) $gradeIds[1]);
    });

    it('preserves nested validation messages and source typos', function () {
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());

        $positionResponse = $this->postJson('/api/position-histories', [
            'period_month' => 1234567890123,
            'users' => [['position_status' => 9, 'echelon' => 999999999]],
        ])->assertUnprocessable();

        expect($positionResponse->json('data'))->toMatchArray([
            'period_month' => ['Bulan periode riwayat harus diantara 1 hingga 12.'],
            'users.0.user_id' => ['User ID tidak boleh kosong.'],
            'users.0.echelon' => ['Eselon tidak ditemukan.'],
            'users.0.position_status' => ['Keterangan Jabatan harus diantara 1, 2, 3 atau 4.'],
        ]);

        $gradeResponse = $this->postJson('/api/grade-histories/999999999', [
            'name' => str_repeat('x', 161),
            'users' => [['id' => 'not-a-number', 'decree_number' => str_repeat('x', 161)]],
        ])->assertUnprocessable();

        expect($gradeResponse->json('data'))->toMatchArray([
            'name' => ['Nama riwayat penghargaan tidak boleh lebih dari 160 karakter.'],
            'users.0.id' => ['ID harus berupa angka.'],
            'users.0.decree_number' => ['Nomor SK golongan tidak beloh lebih dari 160 karakter.'],
        ]);
    });

    it('rolls back parent creation when real child foreign keys reject the payload', function (string $path, string $parentTable, array $payload) {
        $connection = phaseThirteenConnection();
        phaseThirteenActingAs(phaseThirteenAllHistoryPermissions());
        $prefix = phaseThirteenPrefix();
        $before = $connection->table($parentTable)->count();
        $payload['name'] = $prefix;

        $this->postJson("/api/{$path}", $payload)
            ->assertStatus(400)
            ->assertJsonPath('message', 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');

        expect($connection->table($parentTable)->count())->toBe($before)
            ->and($connection->table($parentTable)->where('name', $prefix)->exists())->toBeFalse();
    })->with([
        'Position user FK' => ['position-histories', 'position_histories', ['users' => [['user_id' => 999999999, 'status' => true]]]],
        'Grade master FK' => ['grade-histories', 'grade_histories', ['users' => [['user_id' => 1, 'grade_id' => 999999999, 'effective_date' => '2026-09-01']]]],
    ]);

    it('enforces the two route permission mappings and POST update uses create', function () {
        $connection = phaseThirteenConnection();
        $missingPosition = ((int) $connection->table('position_histories')->max('id')) + 1_000_000;
        $missingGrade = ((int) $connection->table('grade_histories')->max('id')) + 1_000_000;
        phaseThirteenActingAs([
            'Data Riwayat - Jabatan' => ['read' => true, 'create' => true, 'update' => false],
            'Data Riwayat - Golongan' => ['read' => false, 'create' => false, 'update' => true],
        ]);

        $this->getJson('/api/position-histories')->assertOk();
        $this->getJson('/api/grade-histories')
            ->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to read Data Riwayat - Golongan.");
        $this->postJson("/api/position-histories/{$missingPosition}", [])
            ->assertNotFound()
            ->assertJsonPath('message', 'Riwayat jabatan tidak ditemukan.');
        $this->postJson("/api/grade-histories/{$missingGrade}", [])
            ->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to create Data Riwayat - Golongan.");
    });

    it('rolls back every isolated Phase 13 fixture without residue', function () {
        $connection = phaseThirteenConnection();
        $baseline = [
            'positions' => $connection->table('position_histories')->count(),
            'position_users' => $connection->table('position_history_users')->count(),
            'grades' => $connection->table('grade_histories')->count(),
            'grade_users' => $connection->table('grade_history_users')->count(),
        ];
        $prefix = phaseThirteenPrefix();
        $connection->table('position_histories')->insertGetIdTs(['name' => $prefix]);
        $connection->table('grade_histories')->insertGetIdTs(['name' => $prefix]);
        $connection->rollBack();

        expect($connection->table('position_histories')->count())->toBe($baseline['positions'])
            ->and($connection->table('position_history_users')->count())->toBe($baseline['position_users'])
            ->and($connection->table('grade_histories')->count())->toBe($baseline['grades'])
            ->and($connection->table('grade_history_users')->count())->toBe($baseline['grade_users'])
            ->and($connection->table('position_histories')->where('name', $prefix)->exists())->toBeFalse()
            ->and($connection->table('grade_histories')->where('name', $prefix)->exists())->toBeFalse();

        $connection->beginTransaction();
    });
});
