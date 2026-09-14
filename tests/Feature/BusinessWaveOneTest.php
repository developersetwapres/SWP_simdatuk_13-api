<?php

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseSevenTables(): array
{
    return [
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'grades',
        'institutions',
        'decrees',
        'groups',
    ];
}

function phaseSevenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseSevenPrefix(): string
{
    return 'codex_phase7_'.bin2hex(random_bytes(6));
}

function phaseSevenActingAsExistingUser(): User
{
    $user = User::query()
        ->whereNotNull('role_id')
        ->where('status', true)
        ->firstOrFail();

    Sanctum::actingAs($user);

    return $user;
}

it('registers the Wave 1 route snapshot in Laravel 10 order', function () {
    $waveUris = [
        'api/grades',
        'api/institutions',
        'api/institutions/{id}',
        'api/decrees',
        'api/groups',
        'api/permissions',
    ];

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => in_array($route->uri(), $waveUris, true))
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
            'uri' => 'api/grades',
            'action' => 'App\Http\Controllers\GradeController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/institutions',
            'action' => 'App\Http\Controllers\InstitutionController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'POST',
            'uri' => 'api/institutions',
            'action' => 'App\Http\Controllers\InstitutionController@create',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/institutions/{id}',
            'action' => 'App\Http\Controllers\InstitutionController@show',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'POST',
            'uri' => 'api/institutions/{id}',
            'action' => 'App\Http\Controllers\InstitutionController@update',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'DELETE',
            'uri' => 'api/institutions/{id}',
            'action' => 'App\Http\Controllers\InstitutionController@delete',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/decrees',
            'action' => 'App\Http\Controllers\DecreeController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/groups',
            'action' => 'App\Http\Controllers\GroupController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/permissions',
            'action' => 'App\Http\Controllers\PermissionController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
    ]);
});

describe('Wave 1 MySQL clone runtime', function () {
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

        $connection = phaseSevenConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL writes: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', phaseSevenTables())
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== count(phaseSevenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL writes: every Wave 1 table must exist and use InnoDB.');
        }

        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }

        $connection = phaseSevenConnection();

        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        DB::disconnect('mysql');
    });

    it('reads searches paginates and transforms every lookup module', function () {
        $connection = phaseSevenConnection();
        phaseSevenActingAsExistingUser();

        $grade = $connection->table('grades')->orderBy('id')->first();
        $gradeType = ((int) $grade->type === 1) ? 'PNS' : 'PPPK';

        $this->getJson('/api/grades?search='.rawurlencode($grade->code))
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonFragment([
                'id' => $grade->id,
                'name' => $grade->name,
                'code' => $grade->code,
                'type' => $gradeType,
            ]);

        $this->getJson('/api/decrees?search='.phaseSevenPrefix())
            ->assertExactJson([
                'code' => 200,
                'message' => 'Mohon maaf, data tidak ditemukan.',
                'data' => [],
            ]);

        $groupCount = $connection->table('groups')->where('type', 1)->count();
        $groupResponse = $this->getJson('/api/groups');
        $groupResponse->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonCount($groupCount, 'data');

        expect(collect($groupResponse->json('data'))->pluck('type')->unique()->all())
            ->toBe(['Rumpun Riwayat Pegawai']);

        $permission = $connection->table('permissions')->orderBy('id')->first();
        $this->getJson('/api/permissions')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonCount($connection->table('permissions')->count(), 'data')
            ->assertJsonFragment([
                'id' => $permission->id,
                'name' => $permission->name,
                'permitted_actions' => $permission->permitted_actions,
            ]);
    });

    it('preserves Grade pagination metadata against MySQL', function () {
        $connection = phaseSevenConnection();
        phaseSevenActingAsExistingUser();

        $this->getJson('/api/grades?limit=2&page=2')
            ->assertOk()
            ->assertJsonPath('pagination.total', $connection->table('grades')->count())
            ->assertJsonPath('pagination.count', 4)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonCount(2, 'data');
    });

    it('preserves validation response messages on list and mutation endpoints', function () {
        phaseSevenActingAsExistingUser();
        $existingName = (string) phaseSevenConnection()->table('institutions')->value('name');

        $this->getJson('/api/grades?page=0')
            ->assertUnprocessable()
            ->assertExactJson([
                'code' => 422,
                'message' => 'Page minimal harus 1 atau lebih.',
                'data' => [
                    'page' => ['Page minimal harus 1 atau lebih.'],
                ],
            ]);

        $this->postJson('/api/institutions', [])
            ->assertUnprocessable()
            ->assertExactJson([
                'code' => 422,
                'message' => 'Nama tidak boleh kosong.',
                'data' => [
                    'name' => ['Nama tidak boleh kosong.'],
                ],
            ]);

        $this->postJson('/api/institutions', ['name' => $existingName])
            ->assertUnprocessable()
            ->assertExactJson([
                'code' => 422,
                'message' => 'Nama sudah digunakan.',
                'data' => [
                    'name' => ['Nama sudah digunakan.'],
                ],
            ]);
    });

    it('runs Institution CRUD and not-found behavior inside a rollback', function () {
        $connection = phaseSevenConnection();
        phaseSevenActingAsExistingUser();
        $name = phaseSevenPrefix().' Institution';
        $updatedName = $name.' Updated';

        $this->postJson('/api/institutions', ['name' => $name])
            ->assertExactJson([
                'code' => 200,
                'message' => 'Institusi berhasil ditambah.',
                'data' => null,
            ]);

        $institution = $connection->table('institutions')->where('name', $name)->sole();

        expect($institution->created_at)->not->toBeNull();

        $this->getJson('/api/institutions/'.$institution->id)
            ->assertExactJson([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'id' => $institution->id,
                    'name' => $name,
                ],
            ]);

        $this->getJson('/api/institutions?search='.rawurlencode($name).'&limit=1')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('pagination.count', 1)
            ->assertJsonPath('data.0.id', $institution->id);

        $this->postJson('/api/institutions/'.$institution->id, ['name' => $updatedName])
            ->assertExactJson([
                'code' => 200,
                'message' => 'Institusi berhasil diupdate.',
                'data' => null,
            ]);

        expect($connection->table('institutions')->where('id', $institution->id)->value('name'))->toBe($updatedName)
            ->and($connection->table('institutions')->where('id', $institution->id)->value('updated_at'))->not->toBeNull();

        $this->deleteJson('/api/institutions/'.$institution->id)
            ->assertExactJson([
                'code' => 200,
                'message' => 'Institusi berhasil dihapus.',
                'data' => null,
            ]);

        $this->getJson('/api/institutions/'.$institution->id)
            ->assertNotFound()
            ->assertExactJson([
                'code' => 404,
                'message' => 'Institusi tidak ditemukan.',
                'data' => null,
            ]);
    });

    it('uses actual permission rows for mapped denials and preserves unmapped fall-through', function () {
        $connection = phaseSevenConnection();
        $prefix = phaseSevenPrefix();
        $roleId = $connection->table('roles')->insertGetId([
            'name' => $prefix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $institutionPermissionId = $connection->table('permissions')
            ->where('name', 'Master Data - Data Instansi')
            ->value('id');

        $connection->table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $institutionPermissionId,
            'create' => false,
            'read' => true,
            'update' => true,
            'delete' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = $connection->table('users')->insertGetId([
            'role_id' => $roleId,
            'email' => $prefix.'@example.invalid',
            'username' => $prefix,
            'password' => Hash::make('Password@123'),
            'name' => 'Phase 7 Authorization User',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = User::query()->findOrFail($userId);
        Sanctum::actingAs($user);

        $this->getJson('/api/institutions')->assertOk();

        $deniedMessage = "Access denied. You don't have permission to create Master Data - Data Instansi.";

        $this->postJson('/api/institutions', ['name' => $prefix.' denied'])
            ->assertForbidden()
            ->assertExactJson([
                'code' => 403,
                'message' => $deniedMessage,
                'data' => null,
            ]);

        $this->postJson('/api/institutions/1', ['name' => $prefix.' update denied'])
            ->assertForbidden()
            ->assertExactJson([
                'code' => 403,
                'message' => $deniedMessage,
                'data' => null,
            ]);

        $this->getJson('/api/grades')
            ->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to read Master Data - Data Golongan.");

        $this->getJson('/api/decrees')->assertOk();
    });
});
