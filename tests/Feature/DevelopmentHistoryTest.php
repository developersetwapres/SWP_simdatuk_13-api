<?php

use App\Models\User;
use App\Repositories\RecognitionRepository;
use App\Repositories\TrainingRepository;
use Illuminate\Database\Connection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseFifteenTables(): array
{
    return [
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'training_histories',
        'training_history_users',
        'training_levels',
        'groups',
        'recognition_histories',
        'recognition_history_users',
        'recognitions',
        'decrees',
    ];
}

function phaseFifteenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseFifteenPrefix(): string
{
    return 'codex_phase15_'.bin2hex(random_bytes(6));
}

/**
 * @param  array<string, array{create?: bool, read?: bool, update?: bool, delete?: bool}>  $permissionFlags
 */
function phaseFifteenActingAs(array $permissionFlags): User
{
    $connection = phaseFifteenConnection();
    $prefix = phaseFifteenPrefix();
    $roleId = $connection->table('roles')->insertGetIdTs(['name' => $prefix.' Role']);
    $permissions = $connection->table('permissions')
        ->whereIn('name', array_keys($permissionFlags))
        ->get(['id', 'name']);

    expect($permissions)->toHaveCount(count($permissionFlags));

    $connection->table('role_permissions')->insertTs($permissions->map(function (object $permission) use ($roleId, $permissionFlags): array {
        $flags = $permissionFlags[$permission->name];

        return [
            'role_id' => $roleId,
            'permission_id' => $permission->id,
            'create' => $flags['create'] ?? false,
            'read' => $flags['read'] ?? false,
            'update' => $flags['update'] ?? false,
            'delete' => $flags['delete'] ?? false,
        ];
    })->all());
    $userId = $connection->table('users')->insertGetIdTs([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 15 Authorization User',
        'status' => true,
    ]);
    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

/** @return array<string, array{create: bool, read: bool, update: bool, delete: bool}> */
function phaseFifteenAllPermissions(): array
{
    $all = ['create' => true, 'read' => true, 'update' => true, 'delete' => true];

    return [
        'Data Riwayat - Pelatihan Struktural' => $all,
        'Data Riwayat - Pelatihan Fungsional' => $all,
        'Data Riwayat - Pelatihan Teknis' => $all,
        'Data Riwayat - Penghargaan' => $all,
    ];
}

/** @return array<string, mixed> */
function phaseFifteenTrainingPayload(int $type, int $userId, string $name): array
{
    $connection = phaseFifteenConnection();

    return [
        'period_month' => 9,
        'period_year' => '2026',
        'name' => $name,
        'reference_number' => 'PHASE15-'.$type,
        'level' => $type === 1 || $type === 2
            ? $connection->table('training_levels')->where('level_type', $type)->value('id')
            : null,
        'group_id' => $type === 3 ? $connection->table('groups')->where('type', 2)->value('id') : null,
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-02',
        'duration' => 2,
        'organizer' => 'Codex Phase 15',
        'link' => 'https://example.invalid/training',
        'type' => $type,
        'description' => 'Transactional clone fixture',
        'users' => [['user_id' => $userId]],
    ];
}

it('registers Training and Recognition history routes in Laravel 10 declaration order and keeps Employee detail disabled', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/training-histories') || str_starts_with($route->uri(), 'api/recognition-histories'))
        ->map(fn ($route): array => [
            'method' => $route->methods()[0],
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
            'middleware' => $route->middleware(),
        ])
        ->values()
        ->all();

    expect($routes)->toBe([
        ['method' => 'GET', 'uri' => 'api/training-histories', 'action' => 'App\\Http\\Controllers\\TrainingHistoryController@index', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/training-histories/groups', 'action' => 'App\\Http\\Controllers\\TrainingHistoryController@technicalGroups', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/training-histories', 'action' => 'App\\Http\\Controllers\\TrainingHistoryController@create', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/training-histories/{id}', 'action' => 'App\\Http\\Controllers\\TrainingHistoryController@show', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/training-histories/{id}', 'action' => 'App\\Http\\Controllers\\TrainingHistoryController@update', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'DELETE', 'uri' => 'api/training-histories/{id}', 'action' => 'App\\Http\\Controllers\\TrainingHistoryController@delete', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/training-histories/levels/structural', 'action' => 'App\\Http\\Controllers\\TrainingHistoryController@structuralLevels', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/training-histories/levels/functional', 'action' => 'App\\Http\\Controllers\\TrainingHistoryController@functionalLevels', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/recognition-histories', 'action' => 'App\\Http\\Controllers\\RecognitionHistoryController@index', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/recognition-histories', 'action' => 'App\\Http\\Controllers\\RecognitionHistoryController@create', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/recognition-histories/{id}', 'action' => 'App\\Http\\Controllers\\RecognitionHistoryController@show', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/recognition-histories/{id}', 'action' => 'App\\Http\\Controllers\\RecognitionHistoryController@update', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'DELETE', 'uri' => 'api/recognition-histories/{id}', 'action' => 'App\\Http\\Controllers\\RecognitionHistoryController@delete', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
    ]);

    expect(collect(Route::getRoutes()->getRoutes())->contains(fn ($route): bool => $route->uri() === 'api/employees/{id}'))->toBeFalse();
});

describe('Training and Recognition histories on the guarded MySQL clone', function () {
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
        $connection = phaseFifteenConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL access: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', phaseFifteenTables())
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== count(phaseFifteenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL access: every Phase 15 table must exist and use InnoDB.');
        }

        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }

        $connection = phaseFifteenConnection();

        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        DB::disconnect('mysql');
    });

    it('confirms real schema population, all three Training modes, FK integrity, and nullable document data', function () {
        $connection = phaseFifteenConnection();
        $typeCounts = $connection->table('training_histories')
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        expect($typeCounts->keys()->map(fn ($type): int => (int) $type)->sort()->values()->all())->toBe([1, 2, 3])
            ->and($typeCounts->every(fn ($count): bool => (int) $count > 0))->toBeTrue()
            ->and($connection->table('training_history_users as thu')
                ->leftJoin('training_histories as th', 'th.id', '=', 'thu.training_history_id')
                ->leftJoin('users as u', 'u.id', '=', 'thu.user_id')
                ->where(fn ($query) => $query->whereNull('th.id')->orWhereNull('u.id'))->count())->toBe(0)
            ->and($connection->table('recognition_history_users as rhu')
                ->leftJoin('recognition_histories as rh', 'rh.id', '=', 'rhu.recognition_history_id')
                ->leftJoin('users as u', 'u.id', '=', 'rhu.user_id')
                ->where(fn ($query) => $query->whereNull('rh.id')->orWhereNull('u.id'))->count())->toBe(0)
            ->and($connection->table('training_history_users')->whereNotNull('certificate')->count())->toBe(0);
    });

    it('lists, searches, and paginates each Training mode in source ordering', function (int $type) {
        $connection = phaseFifteenConnection();
        phaseFifteenActingAs(phaseFifteenAllPermissions());
        $candidate = $connection->table('training_histories')->where('type', $type)->whereNotNull('name')->orderByDesc('updated_at')->firstOrFail(['id', 'name']);

        $response = $this->getJson('/api/training-histories?type='.$type.'&search='.rawurlencode($candidate->name).'&limit=5');
        $response->assertOk()
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonStructure(['data' => [['id', 'created_at', 'name', 'period_month', 'period_year', 'start_date', 'end_date', 'total']], 'pagination']);

        $returned = collect($response->json('data'))->firstWhere('id', $candidate->id);
        expect($returned)->not->toBeNull()
            ->and((int) $returned['total'])->toBe($connection->table('training_history_users')->where('training_history_id', $candidate->id)->count());
    })->with([1, 2, 3]);

    it('uses Structural authorization by default before rejecting a missing Training type', function () {
        phaseFifteenActingAs([
            'Data Riwayat - Pelatihan Struktural' => ['read' => true],
            'Data Riwayat - Pelatihan Fungsional' => ['read' => false],
            'Data Riwayat - Pelatihan Teknis' => ['read' => true],
        ]);

        $this->getJson('/api/training-histories')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Tipe tidak boleh kosong.');
    });

    it('selects the permission named by each valid Training type', function (int $type, bool $allowed, string $permission) {
        phaseFifteenActingAs([
            'Data Riwayat - Pelatihan Struktural' => ['read' => $type === 1 ? $allowed : true],
            'Data Riwayat - Pelatihan Fungsional' => ['read' => $type === 2 ? $allowed : true],
            'Data Riwayat - Pelatihan Teknis' => ['read' => $type === 3 ? $allowed : true],
        ]);

        $response = $this->getJson('/api/training-histories?type='.$type);

        if ($allowed) {
            $response->assertOk();
        } else {
            $response->assertForbidden()
                ->assertJsonPath('message', "Access denied. You don't have permission to read {$permission}.");
        }
    })->with([
        'Structural allowed' => [1, true, 'Data Riwayat - Pelatihan Struktural'],
        'Functional denied' => [2, false, 'Data Riwayat - Pelatihan Fungsional'],
        'Technical allowed' => [3, true, 'Data Riwayat - Pelatihan Teknis'],
    ]);

    it('uses Structural authorization before rejecting an invalid Training type', function () {
        phaseFifteenActingAs([
            'Data Riwayat - Pelatihan Struktural' => ['read' => true],
            'Data Riwayat - Pelatihan Fungsional' => ['read' => false],
            'Data Riwayat - Pelatihan Teknis' => ['read' => false],
        ]);

        $this->getJson('/api/training-histories?type=99')
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Tipe harus diantara 1, 2 atau 3.');
    });

    it('keeps Training master endpoints on the legacy default Structural permission', function () {
        phaseFifteenActingAs([
            'Data Riwayat - Pelatihan Struktural' => ['read' => true],
            'Data Riwayat - Pelatihan Fungsional' => ['read' => false],
            'Data Riwayat - Pelatihan Teknis' => ['read' => false],
        ]);

        $this->getJson('/api/training-histories/groups')->assertOk()->assertJsonPath('message', 'success');
        $this->getJson('/api/training-histories/levels/structural')->assertOk()->assertJsonPath('message', 'success');
        $this->getJson('/api/training-histories/levels/functional')->assertOk()->assertJsonPath('message', 'success');
        $this->getJson('/api/training-histories/groups?type=3')
            ->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to read Data Riwayat - Pelatihan Teknis.");
    });

    it('preserves Training show projection for Structural, Functional, and Technical modes', function (int $type, string $label) {
        $connection = phaseFifteenConnection();
        $actingUser = phaseFifteenActingAs(phaseFifteenAllPermissions());
        $query = $connection->table('training_histories')->where('type', $type);

        if ($type === 3) {
            $query->whereNotNull('group_id');
        } else {
            $query->whereNotNull('level');
        }

        $history = $query->first(['id']);

        if (! $history && $type === 3) {
            $historyId = $connection->table('training_histories')->insertGetIdTs([
                'name' => phaseFifteenPrefix(),
                'type' => 3,
                'group_id' => $connection->table('groups')->where('type', 2)->value('id'),
            ]);
            $connection->table('training_history_users')->insertTs([
                'training_history_id' => $historyId,
                'user_id' => $actingUser->id,
                'certificate' => null,
            ]);
            $history = (object) ['id' => $historyId];
        }

        expect($history)->not->toBeNull();
        $response = $this->getJson("/api/training-histories/{$history->id}?type={$type}")
            ->assertOk()
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('data.type', $label)
            ->assertJsonStructure(['data' => [
                'id', 'period_month', 'period_year', 'name', 'reference_number', 'type', 'start_date', 'end_date',
                'duration', 'organizer', 'link', 'description', 'level', 'group_id', 'level_name', 'group_name',
                'users',
            ]]);

        if ($type === 1) {
            expect($response->json('data.level_name'))->not->toBeNull();
        } elseif ($type === 2) {
            expect($response->json('data.level_name'))->not->toBeNull();
        } else {
            expect($response->json('data.group_name'))->not->toBeNull();
        }

        expect(collect($response->json('data.users'))->every(fn (array $user): bool => str_starts_with($user['certificate'], config('app.url'))))->toBeTrue();
    })->with([
        'Structural' => [1, 'Pelatihan Struktural'],
        'Functional' => [2, 'Pelatihan Fungsional'],
        'Technical' => [3, 'Pelatihan Teknis'],
    ]);

    it('returns Employee-detail-compatible Training repository projections for every mode', function (int $type) {
        $connection = phaseFifteenConnection();
        $userId = $connection->table('training_history_users as thu')
            ->join('training_histories as th', 'th.id', '=', 'thu.training_history_id')
            ->where('th.type', $type)
            ->select('thu.user_id')
            ->groupBy('thu.user_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('thu.user_id');
        $trainings = app(TrainingRepository::class)->getDetail($userId, $type);
        $expectedCount = $connection->table('training_history_users as thu')
            ->join('training_histories as th', 'th.id', '=', 'thu.training_history_id')
            ->where('thu.user_id', $userId)
            ->where('th.type', $type)
            ->count();

        expect($trainings)->toHaveCount($expectedCount)
            ->and(array_keys((array) $trainings->first()))->toBe([
                'id', 'period_month', 'period_year', 'name', 'level', 'start_date', 'end_date', 'duration',
                'organizer', 'reference_number', 'link', 'certificate', 'type', 'description',
            ])
            ->and($trainings->every(fn (object $training): bool => (int) $training->type === $type && str_starts_with($training->certificate, config('app.url'))))->toBeTrue();

        $dates = $trainings->pluck('start_date')->filter()->map(fn (string $date): int => Carbon::createFromFormat('d-m-Y', $date)->getTimestamp())->all();
        $sorted = $dates;
        rsort($sorted);
        expect(array_values($dates))->toBe(array_values($sorted));
    })->with([1, 2, 3]);

    it('lists and shows Recognition history with the source master and Employee projections', function () {
        $connection = phaseFifteenConnection();
        phaseFifteenActingAs(phaseFifteenAllPermissions());
        $candidate = $connection->table('recognition_histories as rh')
            ->join('recognitions as r', 'r.id', '=', 'rh.recognition_id')
            ->whereNotNull('r.name')
            ->orderByDesc('rh.updated_at')
            ->firstOrFail(['rh.id', 'r.name']);

        $this->getJson('/api/recognition-histories?search='.rawurlencode($candidate->name).'&limit=5')
            ->assertOk()
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('pagination.per_page', 5)
            ->assertJsonStructure(['data' => [['id', 'created_at', 'name', 'period_month', 'period_year', 'awarding_institution', 'total']], 'pagination']);

        $expectedChildren = $connection->table('recognition_history_users')->where('recognition_history_id', $candidate->id)->count();
        $this->getJson("/api/recognition-histories/{$candidate->id}")
            ->assertOk()
            ->assertJsonPath('message', 'success')
            ->assertJsonCount($expectedChildren, 'data.users')
            ->assertJsonStructure(['data' => [
                'id', 'period_month', 'period_year', 'recognition_id', 'recognition_name', 'description',
                'type_of_decree', 'decree_date', 'decree_number', 'decree_year', 'awarding_institution', 'created_at',
                'users' => [['id', 'user_id', 'name', 'employee_id_number', 'created_at']],
            ]]);
    });

    it('returns Employee-detail-compatible Recognition repository projection and ordering', function () {
        $connection = phaseFifteenConnection();
        $userId = $connection->table('recognition_history_users')
            ->select('user_id')->groupBy('user_id')->orderByRaw('COUNT(*) DESC')->value('user_id');
        $recognitions = app(RecognitionRepository::class)->getDetail($userId);
        $expectedCount = $connection->table('recognition_history_users')->where('user_id', $userId)->count();

        expect($recognitions)->toHaveCount($expectedCount)
            ->and(array_keys((array) $recognitions->first()))->toBe([
                'id', 'period_month', 'period_year', 'recognition_id', 'recognition_name', 'description',
                'type_of_decree', 'type_of_decree_name', 'decree_date', 'decree_number', 'decree_year', 'awarding_institution',
            ]);

        $dates = $recognitions->pluck('decree_date')->filter()->map(fn (string $date): int => Carbon::createFromFormat('d-m-Y', $date)->getTimestamp())->all();
        $sorted = $dates;
        rsort($sorted);
        expect(array_values($dates))->toBe(array_values($sorted));
    });

    it('returns empty Employee-detail repository collections for users without histories', function () {
        $connection = phaseFifteenConnection();
        $withoutTraining = $connection->table('users as u')
            ->leftJoin('training_history_users as thu', 'u.id', '=', 'thu.user_id')
            ->whereNull('thu.id')->value('u.id');
        $withoutRecognition = $connection->table('users as u')
            ->leftJoin('recognition_history_users as rhu', 'u.id', '=', 'rhu.user_id')
            ->whereNull('rhu.id')->value('u.id');

        expect(app(TrainingRepository::class)->getDetail($withoutTraining, 1))->toBeEmpty()
            ->and(app(TrainingRepository::class)->getDetail($withoutTraining, 2))->toBeEmpty()
            ->and(app(TrainingRepository::class)->getDetail($withoutTraining, 3))->toBeEmpty()
            ->and(app(RecognitionRepository::class)->getDetail($withoutRecognition))->toBeEmpty();
    });

    it('creates, updates, and deletes an isolated Training history for every mode', function (int $type) {
        $connection = phaseFifteenConnection();
        phaseFifteenActingAs(phaseFifteenAllPermissions());
        $prefix = phaseFifteenPrefix();
        $userId = $connection->table('users')->whereNull('role_id')->value('id');
        $payload = phaseFifteenTrainingPayload($type, $userId, $prefix);

        $this->postJson('/api/training-histories', $payload)
            ->assertOk()->assertJsonPath('message', 'Pelatihan berhasil ditambah.');
        $historyId = $connection->table('training_histories')->where('name', $prefix)->value('id');
        $child = $connection->table('training_history_users')->where('training_history_id', $historyId)->first();
        expect($child->certificate)->toBeNull();

        $payload['name'] = $prefix.' updated';
        $payload['users'] = [[
            'id' => $child->id,
            'user_id' => $userId,
            'delete_certificate' => false,
        ]];
        $this->postJson("/api/training-histories/{$historyId}", $payload)
            ->assertOk()->assertJsonPath('message', 'Pelatihan berhasil diupdate.');
        expect($connection->table('training_histories')->where('id', $historyId)->value('name'))->toBe($prefix.' updated');

        $this->deleteJson("/api/training-histories/{$historyId}?type={$type}")
            ->assertOk()->assertJsonPath('message', 'Riwayat pelatihan berhasil dihapus.');
        expect($connection->table('training_histories')->where('id', $historyId)->exists())->toBeFalse()
            ->and($connection->table('training_history_users')->where('id', $child->id)->exists())->toBeFalse();
    })->with([1, 2, 3]);

    it('executes the Training certificate branch only against a fake S3 disk', function () {
        $connection = phaseFifteenConnection();
        phaseFifteenActingAs(phaseFifteenAllPermissions());
        Storage::fake('s3');
        $prefix = phaseFifteenPrefix();
        $userId = $connection->table('users')->whereNull('role_id')->value('id');
        $payload = phaseFifteenTrainingPayload(1, $userId, $prefix);
        $payload['users'][0]['certificate'] = UploadedFile::fake()->create('certificate.pdf', 10, 'application/pdf');

        $this->post('/api/training-histories', $payload, ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('message', 'Pelatihan berhasil ditambah.');
        $historyId = $connection->table('training_histories')->where('name', $prefix)->value('id');
        $certificate = $connection->table('training_history_users')->where('training_history_id', $historyId)->value('certificate');

        expect($certificate)->toStartWith('/certificate/')->toEndWith('.pdf');
        Storage::disk('s3')->assertExists(ltrim($certificate, '/'));
    });

    it('creates, updates, and deletes an isolated Recognition history with child cascade', function () {
        $connection = phaseFifteenConnection();
        phaseFifteenActingAs(phaseFifteenAllPermissions());
        $prefix = phaseFifteenPrefix();
        $userId = $connection->table('users')->whereNull('role_id')->value('id');
        $recognitionId = $connection->table('recognitions')->value('id');
        $decreeId = $connection->table('decrees')->value('id');
        $payload = [
            'period_month' => 9,
            'period_year' => '2026',
            'recognition_id' => $recognitionId,
            'description' => $prefix,
            'type_of_decree' => $decreeId,
            'decree_date' => '2026-09-01',
            'decree_number' => 'PHASE15-RECOGNITION',
            'decree_year' => '2026',
            'awarding_institution' => 'Codex Phase 15',
            'users' => [['user_id' => $userId]],
        ];

        $this->postJson('/api/recognition-histories', $payload)
            ->assertOk()->assertJsonPath('message', 'Penghargaan berhasil ditambah.');
        $historyId = $connection->table('recognition_histories')->where('description', $prefix)->value('id');
        $child = $connection->table('recognition_history_users')->where('recognition_history_id', $historyId)->first();

        $payload['description'] = $prefix.' updated';
        $payload['users'] = [['id' => $child->id, 'user_id' => $userId]];
        $this->postJson("/api/recognition-histories/{$historyId}", $payload)
            ->assertOk()->assertJsonPath('message', 'Penghargaan berhasil diupdate.');
        expect($connection->table('recognition_histories')->where('id', $historyId)->value('description'))->toBe($prefix.' updated');

        $this->deleteJson("/api/recognition-histories/{$historyId}")
            ->assertOk()->assertJsonPath('message', 'Riwayat penghargaan berhasil dihapus.');
        expect($connection->table('recognition_histories')->where('id', $historyId)->exists())->toBeFalse()
            ->and($connection->table('recognition_history_users')->where('id', $child->id)->exists())->toBeFalse();
    });

    it('preserves exact nested validation messages and Recognition validation gaps', function () {
        phaseFifteenActingAs(phaseFifteenAllPermissions());

        $training = $this->postJson('/api/training-histories', [
            'period_month' => 1234567890123,
            'type' => 9,
            'users' => [['certificate' => 'not-a-file']],
        ])->assertUnprocessable();
        expect($training->json('data'))->toMatchArray([
            'period_month' => ['Bulan periode riwayat harus diantara 1 hingga 12.'],
            'name' => ['Nama diklat tidak boleh kosong.'],
            'reference_number' => ['No surat perintah tidak boleh kosong.'],
            'type' => ['Tipe pelatihan harus diantara 1, 2 atau 3.'],
            'users.0.user_id' => ['User ID tidak boleh kosong.'],
            'users.0.certificate' => [
                'Sertifikat harus berupa file.',
                'Sertifikat harus berupa jpg, jpeg atau png.',
            ],
        ]);

        $recognition = $this->postJson('/api/recognition-histories', [
            'period_month' => 1234567890123,
            'recognition_id' => 'x',
            'users' => [['user_id' => 'not-validated-by-create-request']],
        ])->assertUnprocessable();
        expect($recognition->json('data'))->toMatchArray([
            'period_month' => ['Bulan periode riwayat harus diantara 1 hingga 12.'],
            'recognition_id' => ['Penghargaan harus berupa angka.'],
            'type_of_decree' => ['Jenis SK tidak boleh kosong.'],
            'decree_date' => ['Tanggal SK tidak boleh kosong.'],
            'decree_number' => ['No SK Penghargaan tidak boleh kosong.'],
        ])->not->toHaveKey('users.0.user_id');
    });

    it('preserves missing-history messages', function () {
        $connection = phaseFifteenConnection();
        phaseFifteenActingAs(phaseFifteenAllPermissions());
        $missingTraining = ((int) $connection->table('training_histories')->max('id')) + 1_000_000;
        $missingRecognition = ((int) $connection->table('recognition_histories')->max('id')) + 1_000_000;

        $this->getJson("/api/training-histories/{$missingTraining}?type=1")
            ->assertNotFound()->assertJsonPath('message', 'Pelatihan tidak ditemukan.');
        $this->deleteJson("/api/training-histories/{$missingTraining}?type=1")
            ->assertNotFound()->assertJsonPath('message', 'Riwayat pelatihan tidak ditemukan.');
        $this->getJson("/api/recognition-histories/{$missingRecognition}")
            ->assertNotFound()->assertJsonPath('message', 'Penghargaan tidak ditemukan.');
        $this->deleteJson("/api/recognition-histories/{$missingRecognition}")
            ->assertNotFound()->assertJsonPath('message', 'Riwayat penghargaan tidak ditemukan.');
    });

    it('rolls back parent creation when real foreign keys reject otherwise valid payloads', function () {
        $connection = phaseFifteenConnection();
        phaseFifteenActingAs(phaseFifteenAllPermissions());
        $userId = $connection->table('users')->whereNull('role_id')->value('id');
        $trainingPrefix = phaseFifteenPrefix();
        $training = phaseFifteenTrainingPayload(1, $userId, $trainingPrefix);
        $training['level'] = 999999999;

        $this->postJson('/api/training-histories', $training)
            ->assertStatus(400)
            ->assertJsonPath('message', 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        expect($connection->table('training_histories')->where('name', $trainingPrefix)->exists())->toBeFalse();

        $recognitionPrefix = phaseFifteenPrefix();
        $this->postJson('/api/recognition-histories', [
            'period_month' => 9,
            'period_year' => '2026',
            'recognition_id' => 999999999,
            'description' => $recognitionPrefix,
            'type_of_decree' => $connection->table('decrees')->value('id'),
            'decree_date' => '2026-09-01',
            'decree_number' => 'PHASE15-FK',
        ])->assertStatus(400)
            ->assertJsonPath('message', 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        expect($connection->table('recognition_histories')->where('description', $recognitionPrefix)->exists())->toBeFalse();
    });

    it('preserves Recognition permission mapping and POST update uses create permission', function () {
        $connection = phaseFifteenConnection();
        $missing = ((int) $connection->table('recognition_histories')->max('id')) + 1_000_000;
        phaseFifteenActingAs([
            'Data Riwayat - Penghargaan' => ['read' => true, 'create' => false, 'update' => true, 'delete' => false],
        ]);

        $this->getJson('/api/recognition-histories')->assertOk();
        $this->postJson("/api/recognition-histories/{$missing}", [])
            ->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to create Data Riwayat - Penghargaan.");
        $this->deleteJson("/api/recognition-histories/{$missing}")
            ->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to delete Data Riwayat - Penghargaan.");
    });

    it('rolls back every isolated Phase 15 fixture without residue', function () {
        $connection = phaseFifteenConnection();
        $baseline = [
            'training' => $connection->table('training_histories')->count(),
            'training_users' => $connection->table('training_history_users')->count(),
            'recognition' => $connection->table('recognition_histories')->count(),
            'recognition_users' => $connection->table('recognition_history_users')->count(),
        ];
        $prefix = phaseFifteenPrefix();
        $connection->table('training_histories')->insertGetIdTs(['name' => $prefix, 'type' => 1]);
        $connection->table('recognition_histories')->insertGetIdTs(['description' => $prefix]);
        $connection->rollBack();

        expect($connection->table('training_histories')->count())->toBe($baseline['training'])
            ->and($connection->table('training_history_users')->count())->toBe($baseline['training_users'])
            ->and($connection->table('recognition_histories')->count())->toBe($baseline['recognition'])
            ->and($connection->table('recognition_history_users')->count())->toBe($baseline['recognition_users'])
            ->and($connection->table('training_histories')->where('name', $prefix)->exists())->toBeFalse()
            ->and($connection->table('recognition_histories')->where('description', $prefix)->exists())->toBeFalse();

        $connection->beginTransaction();
    });
});
