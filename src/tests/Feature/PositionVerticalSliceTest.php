<?php

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseNineTables(): array
{
    return [
        'positions',
        'position_echelons',
        'echelons',
        'employment_types',
        'users',
        'roles',
        'permissions',
        'role_permissions',
    ];
}

function phaseNineConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseNinePrefix(): string
{
    return 'codex_phase9_'.bin2hex(random_bytes(6));
}

function phaseNineActingAsPositionManager(): User
{
    $userId = phaseNineConnection()->table('users as u')
        ->join('role_permissions as rp', 'rp.role_id', '=', 'u.role_id')
        ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
        ->where('u.status', true)
        ->where('p.name', 'Master Data - Data Jabatan')
        ->where('rp.create', true)
        ->where('rp.read', true)
        ->where('rp.delete', true)
        ->orderBy('u.id')
        ->value('u.id');

    $user = User::query()->findOrFail($userId);
    Sanctum::actingAs($user);

    return $user;
}

/** @param array<string, mixed> $overrides */
function phaseNineInsertPosition(array $overrides = []): int
{
    return phaseNineConnection()->table('positions')->insertGetIdTs(array_merge([
        'parent_id' => null,
        'name' => phaseNinePrefix().' Position',
        'available' => 2,
        'type' => 2,
        'entity' => 1,
        'vertical_order' => 20,
        'horizontal_order' => 1,
        'status' => true,
    ], $overrides));
}

/** @param array<string, mixed> $overrides */
function phaseNineInsertUser(array $overrides = []): int
{
    $prefix = phaseNinePrefix();
    $roleId = phaseNineConnection()->table('roles')->orderBy('id')->value('id');

    return phaseNineConnection()->table('users')->insertGetId(array_merge([
        'role_id' => $roleId,
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 9 Runtime User',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

it('registers Position routes in Laravel 10 order with available-order before the dynamic route', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/positions'))
        ->map(fn ($route): array => [
            'method' => $route->methods()[0],
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
            'middleware' => $route->middleware(),
        ])
        ->values()
        ->all();

    expect($routes)->toBe([
        [
            'method' => 'GET',
            'uri' => 'api/positions',
            'action' => 'App\Http\Controllers\PositionController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'POST',
            'uri' => 'api/positions',
            'action' => 'App\Http\Controllers\PositionController@create',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/positions/available-order',
            'action' => 'App\Http\Controllers\PositionController@availableOrder',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/positions/{id}',
            'action' => 'App\Http\Controllers\PositionController@show',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'POST',
            'uri' => 'api/positions/{id}',
            'action' => 'App\Http\Controllers\PositionController@update',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'DELETE',
            'uri' => 'api/positions/{id}',
            'action' => 'App\Http\Controllers\PositionController@delete',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
    ]);
});

describe('Position MySQL clone runtime', function () {
    beforeEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            $this->markTestSkipped('MySQL clone verification is opt-in.');
        }

        $cloneDatabase = (string) env('SIMDATUK_CLONE_DATABASE');

        if ($cloneDatabase !== 'SWP_simdatuk_test13') {
            throw new RuntimeException('Refusing MySQL writes: expected the authorized SWP_simdatuk_test13 clone.');
        }

        config()->set('app.debug', false);
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', $cloneDatabase);
        DB::purge('mysql');

        $connection = phaseNineConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL writes: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', phaseNineTables())
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== count(phaseNineTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL writes: every Position table must exist and use InnoDB.');
        }

        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }

        $connection = phaseNineConnection();

        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        DB::disconnect('mysql');
    });

    it('reads searches paginates and renders the last three parents', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $position = $connection->table('positions')
            ->whereNotNull('parent_id')
            ->orderBy('id')
            ->first();
        $expectedTotal = $connection->table('positions')
            ->where('name', 'LIKE', '%'.$position->name.'%')
            ->count();

        $response = $this->getJson('/api/positions?search='.rawurlencode($position->name).'&limit=2&page=1');

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('pagination.total', $expectedTotal)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.current_page', 1);

        $responsePosition = collect($response->json('data'))->firstWhere('id', $position->id);
        expect($responsePosition)->not->toBeNull()
            ->and($responsePosition['type'])->toHaveKeys(['id', 'name'])
            ->and($responsePosition)->not->toHaveKey('parent_id');

        $parentNames = [];
        $parentId = $position->parent_id;

        while ($parentId !== null && count($parentNames) < 3) {
            $parent = $connection->table('positions')->where('id', $parentId)->first();

            if (! $parent) {
                break;
            }

            $parentNames[] = $parent->name;
            $parentId = $parent->parent_id;
        }

        expect($responsePosition['hierarchies'])->toBe(
            $parentNames === [] ? '-' : implode(' > ', array_reverse($parentNames)),
        );
    });

    it('filters by type and parent while preserving manual pagination', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $parentId = $connection->table('positions')
            ->whereNotNull('parent_id')
            ->select('parent_id')
            ->groupBy('parent_id')
            ->orderByRaw('COUNT(*) DESC')
            ->value('parent_id');
        $type = (int) $connection->table('positions')
            ->where('parent_id', $parentId)
            ->value('type');
        $expectedTotal = $connection->table('positions')
            ->where('parent_id', $parentId)
            ->where('type', $type)
            ->count();

        $response = $this->getJson("/api/positions?filter_parent=true&parent_id={$parentId}&type={$type}&limit=3&page=1");

        $response->assertOk()
            ->assertJsonPath('pagination.total', $expectedTotal)
            ->assertJsonPath('pagination.per_page', 3);
        expect(collect($response->json('data'))->pluck('type.id')->unique()->all())->toBe([$type]);
    });

    it('returns detail hierarchy echelon and occupancy data from the clone', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $position = $connection->table('positions as p')
            ->join('position_echelons as pe', 'pe.position_id', '=', 'p.id')
            ->whereNotNull('p.parent_id')
            ->select('p.*')
            ->orderBy('p.id')
            ->first();
        $expectedFilled = $connection->table('users')
            ->where('position_id', $position->id)
            ->whereIn('employment_status', [1, 6, 10])
            ->whereNot('employment_type_id', 16)
            ->count();
        $expectedHierarchy = [];
        $parentId = $position->parent_id;

        while ($parentId !== null) {
            $parent = $connection->table('positions')
                ->select('id', 'name', 'parent_id')
                ->where('id', $parentId)
                ->first();

            if (! $parent) {
                break;
            }

            $expectedHierarchy[] = (array) $parent;
            $parentId = $parent->parent_id;
        }

        $response = $this->getJson('/api/positions/'.$position->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $position->id)
            ->assertJsonPath('data.filled', $expectedFilled)
            ->assertJsonPath('data.order', $position->horizontal_order)
            ->assertJsonPath('data.hierarchies', array_reverse($expectedHierarchy))
            ->assertJsonStructure(['data' => ['type' => ['id', 'name'], 'entity' => ['id', 'name'], 'echelons']]);
    });

    it('returns available horizontal orders using the existing 1 through 20 rule', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $parentId = $connection->table('positions')
            ->whereNotNull('parent_id')
            ->value('parent_id');
        $existingHorizontal = $connection->table('positions')
            ->where('type', '!=', 3)
            ->where('parent_id', $parentId)
            ->pluck('horizontal_order')
            ->all();
        $expectedHorizontal = array_values(array_filter(
            range(1, 20),
            fn (int $order): bool => ! in_array($order, $existingHorizontal),
        ));
        $this->getJson('/api/positions/available-order?id='.$parentId)
            ->assertExactJson(['code' => 200, 'message' => 'success', 'data' => $expectedHorizontal]);
    });

    it('returns available root orders when no parent ID is supplied', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $existingVertical = $connection->table('positions')
            ->where('type', '!=', 3)
            ->whereNull('parent_id')
            ->pluck('vertical_order')
            ->all();
        $expectedVertical = array_values(array_filter(
            range(1, 20),
            fn (int $order): bool => ! in_array($order, $existingVertical),
        ));

        $this->getJson('/api/positions/available-order')
            ->assertExactJson(['code' => 200, 'message' => 'success', 'data' => $expectedVertical]);
    });

    it('preserves Position validation response messages', function () {
        phaseNineActingAsPositionManager();

        $this->getJson('/api/positions?type=1,bad')
            ->assertUnprocessable()
            ->assertExactJson([
                'code' => 422,
                'message' => 'Format type tidak sesuai.',
                'data' => ['type' => ['Format type tidak sesuai.']],
            ]);

        $response = $this->postJson('/api/positions', [
            'name' => '',
            'type' => 4,
            'entity' => 3,
            'position_echelons' => [['echelon_id' => 'bad', 'available' => 'bad']],
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Nama Jabatan tidak boleh kosong.')
            ->assertJsonPath('data.name.0', 'Nama Jabatan tidak boleh kosong.')
            ->assertJsonPath('data.type.0', 'Type harus diantara 1, 2, atau 3.')
            ->assertJsonPath('data.entity.0', 'Entity harus diantara 1 atau 2.')
            ->assertJsonPath('data.order.0', 'Order tidak boleh kosong.');
        expect($response->json('data')['position_echelons.0.echelon_id'][0])->toBe('Echelon ID harus berupa angka.');
    });

    it('runs Position create detail update and delete with pivot cascade', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $parentId = $connection->table('positions')->orderBy('id')->value('id');
        $echelonIds = $connection->table('echelons')->orderBy('id')->limit(3)->pluck('id')->all();
        $name = phaseNinePrefix().' CRUD Position';
        $updatedName = $name.' Updated';

        $this->postJson('/api/positions', [
            'name' => $name,
            'parent_id' => $parentId,
            'available' => 9,
            'type' => 2,
            'entity' => 1,
            'order' => 7,
            'status' => true,
            'position_echelons' => [
                ['echelon_id' => $echelonIds[0], 'available' => 2],
                ['echelon_id' => $echelonIds[1], 'available' => 3],
            ],
        ])->assertExactJson([
            'code' => 200,
            'message' => 'Jabatan berhasil ditambah.',
            'data' => null,
        ]);

        expect($connection->transactionLevel())->toBe(1);
        $position = $connection->table('positions')->where('name', $name)->sole();
        $pivots = $connection->table('position_echelons')->where('position_id', $position->id)->orderBy('id')->get();

        expect((int) $position->available)->toBe(0)
            ->and((int) $position->vertical_order)->toBe(1)
            ->and((int) $position->horizontal_order)->toBe(7)
            ->and($pivots)->toHaveCount(2)
            ->and($pivots->pluck('horizontal_order')->map(fn ($value): int => (int) $value)->all())->toBe([1, 2]);

        $this->getJson('/api/positions/'.$position->id)
            ->assertOk()
            ->assertJsonPath('data.name', $name)
            ->assertJsonPath('data.order', 7)
            ->assertJsonCount(2, 'data.echelons');

        $this->postJson('/api/positions/'.$position->id, [
            'name' => $updatedName,
            'parent_id' => $parentId,
            'available' => 12,
            'type' => 2,
            'entity' => 2,
            'order' => 8,
            'status' => false,
            'deleted_echelon_id' => [$pivots[1]->id],
            'position_echelons' => [
                ['id' => $pivots[0]->id, 'echelon_id' => $echelonIds[0], 'available' => 4],
                ['echelon_id' => $echelonIds[2], 'available' => 5],
            ],
        ])->assertExactJson([
            'code' => 200,
            'message' => 'Jabatan berhasil diubah.',
            'data' => null,
        ]);

        expect($connection->transactionLevel())->toBe(1);
        $updated = $connection->table('positions')->where('id', $position->id)->sole();
        $updatedPivots = $connection->table('position_echelons')->where('position_id', $position->id)->orderBy('horizontal_order')->get();

        expect($updated->name)->toBe($updatedName)
            ->and((int) $updated->available)->toBe(0)
            ->and((int) $updated->horizontal_order)->toBe(8)
            ->and((int) $updated->entity)->toBe(2)
            ->and((int) $updated->status)->toBe(0)
            ->and($updatedPivots)->toHaveCount(2)
            ->and($updatedPivots->pluck('echelon_id')->map(fn ($value): int => (int) $value)->all())->toBe([$echelonIds[0], $echelonIds[2]])
            ->and($updatedPivots->pluck('available')->map(fn ($value): int => (int) $value)->all())->toBe([4, 5]);

        $this->deleteJson('/api/positions/'.$position->id)
            ->assertExactJson([
                'code' => 200,
                'message' => 'Jabatan berhasil dihapus.',
                'data' => null,
            ]);

        expect($connection->table('positions')->where('id', $position->id)->exists())->toBeFalse()
            ->and($connection->table('position_echelons')->where('position_id', $position->id)->exists())->toBeFalse();

        $this->getJson('/api/positions/'.$position->id)
            ->assertNotFound()
            ->assertExactJson(['code' => 404, 'message' => 'Jabatan tidak ditemukan.', 'data' => null]);
    });

    it('rejects a missing echelon through the real foreign key and rolls back the Position insert', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $name = phaseNinePrefix().' Invalid FK Position';
        $missingEchelonId = ((int) $connection->table('echelons')->max('id')) + 100000;

        $this->postJson('/api/positions', [
            'name' => $name,
            'parent_id' => null,
            'available' => 1,
            'type' => 1,
            'entity' => 1,
            'order' => 20,
            'status' => true,
            'position_echelons' => [['echelon_id' => $missingEchelonId, 'available' => 1]],
        ])->assertExactJson([
            'code' => 400,
            'message' => 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!',
            'data' => null,
        ]);

        expect($connection->transactionLevel())->toBe(1)
            ->and($connection->table('positions')->where('name', $name)->exists())->toBeFalse();
    });

    it('enforces echelon occupancy capacity and preserves the early-return transaction quirk', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $echelon = $connection->table('echelons')->orderBy('id')->first();
        $employmentTypeId = $connection->table('employment_types')->where('id', '!=', 16)->orderBy('id')->value('id');
        $positionId = phaseNineInsertPosition();
        $pivotId = $connection->table('position_echelons')->insertGetIdTs([
            'position_id' => $positionId,
            'echelon_id' => $echelon->id,
            'available' => 1,
            'horizontal_order' => 1,
        ]);
        phaseNineInsertUser([
            'position_id' => $positionId,
            'echelon_id' => $echelon->id,
            'employment_status' => 1,
            'employment_type_id' => $employmentTypeId,
        ]);

        $this->postJson('/api/positions/'.$positionId, [
            'name' => 'Capacity Protected Position',
            'parent_id' => null,
            'available' => 0,
            'type' => 2,
            'entity' => 1,
            'order' => 20,
            'status' => true,
            'position_echelons' => [
                ['id' => $pivotId, 'echelon_id' => $echelon->id, 'available' => 0],
            ],
        ])->assertNotFound()
            ->assertExactJson([
                'code' => 404,
                'message' => 'Eselon '.$echelon->name.' sudah terisi 1 orang.',
                'data' => null,
            ]);

        expect($connection->transactionLevel())->toBe(2)
            ->and($connection->table('positions')->where('id', $positionId)->value('name'))->not->toBe('Capacity Protected Position');
    });

    it('blocks deletion for every user reference regardless of active occupancy filters', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $positionId = phaseNineInsertPosition();
        phaseNineInsertUser([
            'position_id' => $positionId,
            'employment_status' => 99,
            'employment_type_id' => 16,
        ]);

        $this->deleteJson('/api/positions/'.$positionId)
            ->assertNotFound()
            ->assertExactJson([
                'code' => 404,
                'message' => 'Jabatan ini masih digunakan oleh beberapa pegawai.',
                'data' => null,
            ]);

        expect($connection->table('positions')->where('id', $positionId)->exists())->toBeTrue();
    });

    it('enforces mapped permissions and keeps POST update mapped to create', function () {
        $connection = phaseNineConnection();
        $prefix = phaseNinePrefix();
        $roleId = $connection->table('roles')->insertGetId([
            'name' => $prefix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = $connection->table('permissions')
            ->where('name', 'Master Data - Data Jabatan')
            ->value('id');
        $connection->table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'create' => false,
            'read' => true,
            'update' => true,
            'delete' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $userId = phaseNineInsertUser(['role_id' => $roleId]);
        Sanctum::actingAs(User::query()->findOrFail($userId));
        $positionId = $connection->table('positions')->orderBy('id')->value('id');

        $this->getJson('/api/positions/available-order')->assertOk();
        $deniedCreate = "Access denied. You don't have permission to create Master Data - Data Jabatan.";

        $this->postJson('/api/positions', [])->assertForbidden()->assertJsonPath('message', $deniedCreate);
        $this->postJson('/api/positions/'.$positionId, [])->assertForbidden()->assertJsonPath('message', $deniedCreate);
        $this->deleteJson('/api/positions/'.$positionId)
            ->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to delete Master Data - Data Jabatan.");
    });

    it('proves the outer transaction removes isolated API writes', function () {
        $connection = phaseNineConnection();
        phaseNineActingAsPositionManager();
        $name = phaseNinePrefix().' Rollback Position';

        $this->postJson('/api/positions', [
            'name' => $name,
            'parent_id' => null,
            'available' => 1,
            'type' => 2,
            'entity' => 1,
            'order' => 20,
            'status' => true,
            'position_echelons' => [],
        ])->assertOk();

        expect($connection->transactionLevel())->toBe(1)
            ->and($connection->table('positions')->where('name', $name)->exists())->toBeTrue();

        $connection->rollBack();

        expect($connection->table('positions')->where('name', $name)->exists())->toBeFalse();

        $connection->beginTransaction();
    });
});
