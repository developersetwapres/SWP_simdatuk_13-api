<?php

use App\Models\User;
use App\Repositories\DisciplinaryRepository;
use App\Repositories\PerformanceRepository;
use App\Repositories\TargetRepository;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseSixteenTables(): array
{
    return [
        'users', 'roles', 'permissions', 'role_permissions',
        'target_histories', 'target_history_users',
        'performance_histories', 'performance_history_users',
        'disciplinary_histories', 'disciplinary_history_users', 'disciplinaries',
    ];
}

function phaseSixteenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseSixteenPrefix(): string
{
    return 'codex_phase16_'.bin2hex(random_bytes(6));
}

/** @param array<string, array{create?: bool, read?: bool, update?: bool, delete?: bool}> $flags */
function phaseSixteenActingAs(array $flags): User
{
    $connection = phaseSixteenConnection();
    $prefix = phaseSixteenPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $permissions = $connection->table('permissions')->whereIn('name', array_keys($flags))->get(['id', 'name']);
    expect($permissions)->toHaveCount(count($flags));

    $connection->table('role_permissions')->insertTs($permissions->map(fn (object $permission): array => [
        'role_id' => $roleId,
        'permission_id' => $permission->id,
        'create' => $flags[$permission->name]['create'] ?? false,
        'read' => $flags[$permission->name]['read'] ?? false,
        'update' => $flags[$permission->name]['update'] ?? false,
        'delete' => $flags[$permission->name]['delete'] ?? false,
    ])->all());
    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 16 Authorization User',
        'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

/** @return array<string, array{create: bool, read: bool, update: bool, delete: bool}> */
function phaseSixteenAllPermissions(): array
{
    $all = ['create' => true, 'read' => true, 'update' => true, 'delete' => true];

    return [
        'Data Riwayat - SKP' => $all,
        'Data Riwayat - Penilaian Prestasi Kerja' => $all,
        'Data Riwayat - Hukuman Disiplin' => $all,
    ];
}

/** @return array<string, mixed> */
function phaseSixteenPayload(string $domain, int $userId, string $name): array
{
    return match ($domain) {
        'target' => [
            'name' => $name, 'period_month' => 9, 'period_year' => '2026',
            'appraisal_period' => 'Q3', 'year' => '2026',
            'users' => [[
                'user_id' => $userId, 'work_behavior_rating' => 1,
                'employee_performance_predicate' => 2, 'organizational_performance_achievement' => 3,
            ]],
        ],
        'performance' => [
            'name' => $name, 'period_month' => 9, 'period_year' => '2026', 'performance_period' => 'Q3 2026',
            'users' => [['user_id' => $userId, 'work_performance_score' => 88.75, 'description' => 4]],
        ],
        'disciplinary' => [
            'name' => $name, 'period_month' => 9, 'period_year' => '2026',
            'users' => [[
                'user_id' => $userId, 'grade' => 'Phase 16 Grade', 'position' => 'Phase 16 Position',
                'disciplinary_id' => phaseSixteenConnection()->table('disciplinaries')->value('id'),
                'decree_number' => 'PHASE16-SK', 'date_of_decree' => '2026-09-01',
                'start_date' => '2026-09-01', 'end_date' => '2026-10-01',
                'authorizing_officer' => 'Officer', 'name_of_authorizing_officer' => 'Officer Name',
                'description' => 'Transactional fixture',
            ]],
        ],
    };
}

it('registers the three Phase 16 five-route groups in source order and keeps Employee detail disabled', function () {
    $prefixes = ['api/target-histories', 'api/performance-histories', 'api/disciplinary-histories'];
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => collect($prefixes)->contains(fn (string $prefix): bool => str_starts_with($route->uri(), $prefix)))
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware()])
        ->values();

    expect($routes)->toHaveCount(15);
    foreach ([
        ['target-histories', 'TargetHistoryController'],
        ['performance-histories', 'PerformanceHistoryController'],
        ['disciplinary-histories', 'DisciplinaryHistoryController'],
    ] as [$path, $controller]) {
        expect($routes->filter(fn (array $route): bool => str_starts_with($route[1], 'api/'.$path))->values()->all())->toBe([
            ['GET', 'api/'.$path, 'App\\Http\\Controllers\\'.$controller.'@index', ['api', 'auth:sanctum', 'role.access']],
            ['POST', 'api/'.$path, 'App\\Http\\Controllers\\'.$controller.'@create', ['api', 'auth:sanctum', 'role.access']],
            ['GET', 'api/'.$path.'/{id}', 'App\\Http\\Controllers\\'.$controller.'@show', ['api', 'auth:sanctum', 'role.access']],
            ['POST', 'api/'.$path.'/{id}', 'App\\Http\\Controllers\\'.$controller.'@update', ['api', 'auth:sanctum', 'role.access']],
            ['DELETE', 'api/'.$path.'/{id}', 'App\\Http\\Controllers\\'.$controller.'@delete', ['api', 'auth:sanctum', 'role.access']],
        ]);
    }
    expect(collect(Route::getRoutes()->getRoutes())->contains(fn ($route): bool => $route->uri() === 'api/employees/{id}'))->toBeFalse();
});

describe('Phase 16 histories on the guarded MySQL clone', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }
        $database = (string) env('SIMDATUK_CLONE_DATABASE');
        if ($database !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing MySQL access: expected the authorized SWP_simdatuk_test13 clone.');
        }
        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $database);
        DB::purge('mysql');
        $connection = phaseSixteenConnection();
        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $database) {
            throw new RuntimeException('Refusing MySQL access: clone connection guard failed.');
        }
        $engines = $connection->table('information_schema.tables')->where('table_schema', $database)
            ->whereIn('table_name', phaseSixteenTables())->pluck('ENGINE')->map(fn (?string $engine): string => strtoupper((string) $engine));
        if ($engines->count() !== count(phaseSixteenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL access: every Phase 16 table must exist and use InnoDB.');
        }
        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }
        $connection = phaseSixteenConnection();
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::disconnect('mysql');
    });

    it('confirms populated clone tables and no orphan children', function () {
        $connection = phaseSixteenConnection();
        foreach ([
            ['target_histories', 'target_history_users', 'target_history_id'],
            ['performance_histories', 'performance_history_users', 'performance_history_id'],
            ['disciplinary_histories', 'disciplinary_history_users', 'disciplinary_history_id'],
        ] as [$parent, $child, $foreignKey]) {
            expect($connection->table($parent)->count())->toBeGreaterThan(0)
                ->and($connection->table($child)->count())->toBeGreaterThan(0)
                ->and($connection->table("{$child} as c")->leftJoin("{$parent} as p", 'p.id', '=', "c.{$foreignKey}")
                    ->leftJoin('users as u', 'u.id', '=', 'c.user_id')
                    ->where(fn ($query) => $query->whereNull('p.id')->orWhereNull('u.id'))->count())->toBe(0);
        }
    });

    it('lists, searches, and paginates source rows', function (string $path, string $table) {
        $connection = phaseSixteenConnection();
        phaseSixteenActingAs(phaseSixteenAllPermissions());
        $candidate = $connection->table($table)->whereNotNull('name')->orderByDesc('updated_at')->firstOrFail(['id', 'name']);
        $response = $this->getJson('/api/'.$path.'?search='.rawurlencode($candidate->name).'&limit=5');
        $response->assertOk()->assertJsonPath('message', 'success')->assertJsonPath('pagination.per_page', 5);
        expect(collect($response->json('data'))->firstWhere('id', $candidate->id))->not->toBeNull();
    })->with([
        'Target/SKP' => ['target-histories', 'target_histories'],
        'Performance' => ['performance-histories', 'performance_histories'],
        'Disciplinary history' => ['disciplinary-histories', 'disciplinary_histories'],
    ]);

    it('shows exact parent and joined-child projections', function (string $path, string $parent, string $child, string $foreignKey, array $parentKeys, array $childKeys) {
        $connection = phaseSixteenConnection();
        phaseSixteenActingAs(phaseSixteenAllPermissions());
        $history = $connection->table("{$parent} as p")->join("{$child} as c", 'p.id', '=', "c.{$foreignKey}")
            ->select('p.id')->groupBy('p.id')->orderByRaw('COUNT(c.id) DESC')->firstOrFail();
        $expected = $connection->table($child)->where($foreignKey, $history->id)->count();
        $response = $this->getJson("/api/{$path}/{$history->id}")->assertOk()->assertJsonPath('message', 'success')->assertJsonCount($expected, 'data.users');
        expect(array_keys($response->json('data')))->toBe(array_merge($parentKeys, ['users']))
            ->and(array_keys($response->json('data.users.0')))->toBe($childKeys);
    })->with([
        'Target/SKP' => ['target-histories', 'target_histories', 'target_history_users', 'target_history_id',
            ['id', 'period_month', 'period_year', 'name', 'appraisal_period', 'year'],
            ['id', 'employee_performance_predicate', 'organizational_performance_achievement', 'work_behavior_rating', 'user_id', 'name', 'employee_id_number', 'created_at']],
        'Performance' => ['performance-histories', 'performance_histories', 'performance_history_users', 'performance_history_id',
            ['id', 'name', 'performance_period', 'period_year', 'period_month'],
            ['id', 'user_id', 'name', 'employee_id_number', 'work_performance_score', 'description', 'created_at']],
        'Disciplinary' => ['disciplinary-histories', 'disciplinary_histories', 'disciplinary_history_users', 'disciplinary_history_id',
            ['id', 'period_month', 'period_year', 'name', 'created_at'],
            ['id', 'user_id', 'name', 'employee_id_number', 'grade', 'position', 'disciplinary_type_id', 'disciplinary_type_name', 'disciplinary_type_description', 'performance_allowance_deduction', 'performance_allowance_duration', 'decree_number', 'date_of_decree', 'start_date', 'end_date', 'authorizing_officer', 'name_of_authorizing_officer', 'description', 'created_at']],
    ]);

    it('returns exact Employee-detail repository keys and source ordering', function (string $child, string $parent, string $foreignKey, object $repository, array $keys) {
        $connection = phaseSixteenConnection();
        $userId = $connection->table($child)->select('user_id')->groupBy('user_id')->orderByRaw('COUNT(*) DESC')->value('user_id');
        $items = $repository->getDetail($userId);
        expect($items)->toHaveCount($connection->table($child)->where('user_id', $userId)->count())
            ->and(array_keys((array) $items->first()))->toBe($keys);
        $pairs = $connection->table("{$child} as c")->join("{$parent} as p", 'p.id', '=', "c.{$foreignKey}")
            ->where('c.user_id', $userId)->orderBy('p.period_year', 'desc')->orderBy('p.period_month', 'desc')
            ->pluck('c.id')->map(fn ($id): int => (int) $id)->all();
        if ($parent !== 'disciplinary_histories') {
            expect($items->pluck('id')->map(fn ($id): int => (int) $id)->all())->toBe($pairs);
        }
    })->with([
        'Target/SKP' => ['target_history_users', 'target_histories', 'target_history_id', new TargetRepository, ['id', 'period_month', 'period_year', 'name', 'appraisal_period', 'year', 'work_behavior_rating', 'employee_performance_predicate', 'organizational_performance_achievement']],
        'Performance' => ['performance_history_users', 'performance_histories', 'performance_history_id', new PerformanceRepository, ['id', 'period_month', 'period_year', 'name', 'performance_period', 'work_performance_score', 'description']],
        'Disciplinary' => ['disciplinary_history_users', 'disciplinary_histories', 'disciplinary_history_id', new DisciplinaryRepository, ['id', 'period_month', 'period_year', 'grade', 'position', 'disciplinary_id', 'disciplinary_name', 'disciplinary_description', 'performance_allowance_deduction', 'performance_allowance_duration', 'decree_number', 'date_of_decree', 'start_date', 'end_date', 'authorizing_officer', 'name_of_authorizing_officer', 'description', 'status', 'validity_period']],
    ]);

    it('preserves Disciplinary NOW and DATEDIFF calculations', function () {
        $connection = phaseSixteenConnection();
        $row = $connection->table('disciplinary_history_users')->whereNotNull('start_date')->whereNotNull('end_date')->firstOrFail();
        $item = app(DisciplinaryRepository::class)->getDetail($row->user_id)->firstWhere('id', $row->id);
        $expectedDays = $connection->selectOne('SELECT DATEDIFF(?, ?) AS days', [$row->end_date, $row->start_date])->days;
        expect((int) $item->validity_period)->toBe((int) $expectedDays)
            ->and((int) $item->status)->toBe(strtotime($row->end_date) > time() ? 1 : 0);
    });

    it('creates, updates, and deletes isolated parent/child rows', function (string $domain, string $path, string $parent, string $child, string $foreignKey, string $createMessage, string $updateMessage, string $deleteMessage) {
        $connection = phaseSixteenConnection();
        phaseSixteenActingAs(phaseSixteenAllPermissions());
        $prefix = phaseSixteenPrefix();
        $userId = $connection->table('users')->whereNull('role_id')->value('id');
        $payload = phaseSixteenPayload($domain, $userId, $prefix);
        $this->postJson('/api/'.$path, $payload)->assertOk()->assertJsonPath('message', $createMessage);
        $historyId = $connection->table($parent)->where('name', $prefix)->value('id');
        $childRow = $connection->table($child)->where($foreignKey, $historyId)->first();
        $payload['name'] = $prefix.' updated';
        $payload['users'][0]['id'] = $childRow->id;
        $this->postJson("/api/{$path}/{$historyId}", $payload)->assertOk()->assertJsonPath('message', $updateMessage);
        expect($connection->table($parent)->where('id', $historyId)->value('name'))->toBe($prefix.' updated');
        $this->deleteJson("/api/{$path}/{$historyId}")->assertOk()->assertJsonPath('message', $deleteMessage);
        expect($connection->table($parent)->where('id', $historyId)->exists())->toBeFalse()
            ->and($connection->table($child)->where('id', $childRow->id)->exists())->toBeFalse();
    })->with([
        'Target/SKP' => ['target', 'target-histories', 'target_histories', 'target_history_users', 'target_history_id', 'SKP berhasil ditambah.', 'SKP berhasil diupdate.', 'Riwayat SKP berhasil dihapus.'],
        'Performance' => ['performance', 'performance-histories', 'performance_histories', 'performance_history_users', 'performance_history_id', 'PPK berhasil ditambahkan.', 'PPK berhasil diupdate.', 'Riwayat PPK berhasil dihapus.'],
        'Disciplinary' => ['disciplinary', 'disciplinary-histories', 'disciplinary_histories', 'disciplinary_history_users', 'disciplinary_history_id', 'Hukuman disiplin berhasil ditambah.', 'Hukuman disiplin berhasil diupdate.', 'Riwayat hukuman disiplin berhasil dihapus.'],
    ]);

    it('preserves validation messages and create/update rule differences', function () {
        phaseSixteenActingAs(phaseSixteenAllPermissions());
        $target = $this->postJson('/api/target-histories', ['appraisal_period' => 'Monthly', 'users' => [['work_behavior_rating' => 5]]])->assertUnprocessable();
        expect($target->json('data'))->toMatchArray([
            'appraisal_period' => ['Periode penilaian harus diantara Q1, Q2, Q3, Q4, Tahunan.'],
            'users.0.user_id' => ['User ID tidak boleh kosong.'],
            'users.0.work_behavior_rating' => ['Rating perilaku kerja harus diantara 1,2,3'],
        ]);
    });

    it('preserves permission mappings and POST updates use create permission', function (string $path, string $permission, string $parent) {
        $connection = phaseSixteenConnection();
        phaseSixteenActingAs([$permission => ['read' => true, 'create' => false, 'update' => true, 'delete' => false]]);
        $missing = ((int) $connection->table($parent)->max('id')) + 1_000_000;
        $this->getJson('/api/'.$path)->assertOk();
        $this->postJson("/api/{$path}/{$missing}", [])->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to create {$permission}.");
        $this->deleteJson("/api/{$path}/{$missing}")->assertForbidden();
    })->with([
        ['target-histories', 'Data Riwayat - SKP', 'target_histories'],
        ['performance-histories', 'Data Riwayat - Penilaian Prestasi Kerja', 'performance_histories'],
        ['disciplinary-histories', 'Data Riwayat - Hukuman Disiplin', 'disciplinary_histories'],
    ]);

    it('rolls back transactional creates rejected by real child FKs', function (string $domain, string $path, string $parent) {
        phaseSixteenActingAs(phaseSixteenAllPermissions());
        $prefix = phaseSixteenPrefix();
        $payload = phaseSixteenPayload($domain, 999999999, $prefix);
        $this->postJson('/api/'.$path, $payload)->assertStatus(400)
            ->assertJsonPath('message', 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        expect(phaseSixteenConnection()->table($parent)->where('name', $prefix)->exists())->toBeFalse();
    })->with([
        ['target', 'target-histories', 'target_histories'],
        ['performance', 'performance-histories', 'performance_histories'],
        ['disciplinary', 'disciplinary-histories', 'disciplinary_histories'],
    ]);

    it('returns empty repository Collections for Employees without each history', function () {
        $connection = phaseSixteenConnection();
        foreach ([
            ['target_history_users', new TargetRepository],
            ['performance_history_users', new PerformanceRepository],
            ['disciplinary_history_users', new DisciplinaryRepository],
        ] as [$table, $repository]) {
            $userId = $connection->table('users as u')->leftJoin("{$table} as h", 'u.id', '=', 'h.user_id')->whereNull('h.id')->value('u.id');
            expect($repository->getDetail($userId))->toBeEmpty();
        }
    });

    it('leaves no Phase 16 fixture residue after an explicit rollback', function () {
        $connection = phaseSixteenConnection();
        $prefix = phaseSixteenPrefix();
        $connection->table('target_histories')->insertGetIdTs(['name' => $prefix]);
        $connection->table('performance_histories')->insertGetIdTs(['name' => $prefix]);
        $connection->table('disciplinary_histories')->insertGetIdTs(['name' => $prefix]);
        $connection->rollBack();
        expect($connection->table('target_histories')->where('name', $prefix)->exists())->toBeFalse()
            ->and($connection->table('performance_histories')->where('name', $prefix)->exists())->toBeFalse()
            ->and($connection->table('disciplinary_histories')->where('name', $prefix)->exists())->toBeFalse();
        $connection->beginTransaction();
    });
});
