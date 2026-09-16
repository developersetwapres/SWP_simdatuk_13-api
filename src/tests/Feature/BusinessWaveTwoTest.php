<?php

use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseEightTables(): array
{
    return [
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'echelons',
        'disciplinaries',
        'residences',
        'recognitions',
        'employment_types',
    ];
}

function phaseEightConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseEightPrefix(): string
{
    return 'codex_phase8_'.bin2hex(random_bytes(6));
}

function phaseEightActingAsExistingUser(): User
{
    $user = User::query()
        ->whereNotNull('role_id')
        ->where('status', true)
        ->firstOrFail();

    Sanctum::actingAs($user);

    return $user;
}

it('registers the Wave 2 route snapshot in Laravel 10 order', function () {
    $waveUris = [
        'api/employment-types',
        'api/employment-types/{id}',
        'api/echelons',
        'api/disciplinaries',
        'api/residences',
        'api/recognitions',
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
            'uri' => 'api/employment-types',
            'action' => 'App\Http\Controllers\EmploymentTypeController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'POST',
            'uri' => 'api/employment-types',
            'action' => 'App\Http\Controllers\EmploymentTypeController@create',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/employment-types/{id}',
            'action' => 'App\Http\Controllers\EmploymentTypeController@show',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'POST',
            'uri' => 'api/employment-types/{id}',
            'action' => 'App\Http\Controllers\EmploymentTypeController@update',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'DELETE',
            'uri' => 'api/employment-types/{id}',
            'action' => 'App\Http\Controllers\EmploymentTypeController@delete',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/echelons',
            'action' => 'App\Http\Controllers\EchelonController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/disciplinaries',
            'action' => 'App\Http\Controllers\DisciplinaryController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/residences',
            'action' => 'App\Http\Controllers\ResidenceController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
        [
            'method' => 'GET',
            'uri' => 'api/recognitions',
            'action' => 'App\Http\Controllers\RecognitionController@index',
            'middleware' => ['api', 'auth:sanctum', 'role.access'],
        ],
    ]);
});

describe('Wave 2 MySQL clone runtime', function () {
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

        $connection = phaseEightConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL writes: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', phaseEightTables())
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== count(phaseEightTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL writes: every Wave 2 table must exist and use InnoDB.');
        }

        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }

        $connection = phaseEightConnection();

        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        DB::disconnect('mysql');
    });

    it('reads searches paginates and preserves empty behavior for lookup modules', function () {
        $connection = phaseEightConnection();
        phaseEightActingAsExistingUser();

        $echelon = $connection->table('echelons')->orderBy('id')->first();
        $this->getJson('/api/echelons?search='.rawurlencode($echelon->name))
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonFragment([
                'id' => $echelon->id,
                'name' => $echelon->name,
            ]);

        $this->getJson('/api/disciplinaries?limit=2&page=2')
            ->assertOk()
            ->assertJsonPath('pagination.total', $connection->table('disciplinaries')->count())
            ->assertJsonPath('pagination.count', 4)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'description',
                    'performance_allowance_deduction',
                    'performance_allowance_duration',
                ]],
            ]);

        $this->getJson('/api/residences?search='.phaseEightPrefix())
            ->assertExactJson([
                'code' => 200,
                'message' => 'Mohon maaf, data tidak ditemukan.',
                'data' => [],
            ]);

        $recognition = $connection->table('recognitions')->orderBy('id')->first();
        $this->getJson('/api/recognitions')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('message', 'success')
            ->assertJsonCount($connection->table('recognitions')->count(), 'data')
            ->assertJsonFragment([
                'id' => $recognition->id,
                'name' => $recognition->name,
                'description' => $recognition->description,
                'created_at' => $recognition->created_at,
            ]);
    });

    it('filters and paginates EmploymentType by type', function () {
        $connection = phaseEightConnection();
        phaseEightActingAsExistingUser();
        $type = (int) $connection->table('employment_types')
            ->select('type')
            ->groupBy('type')
            ->orderByRaw('COUNT(*) DESC')
            ->value('type');
        $expectedCount = $connection->table('employment_types')->where('type', $type)->count();

        $response = $this->getJson("/api/employment-types?type={$type}&limit=2&page=1");
        $response->assertOk()
            ->assertJsonPath('pagination.total', $expectedCount)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.current_page', 1);

        expect(collect($response->json('data'))->pluck('type')->unique()->all())->toBe([$type]);
    });

    it('preserves the false status filter fall-through quirk', function () {
        $connection = phaseEightConnection();
        phaseEightActingAsExistingUser();
        $inactiveName = phaseEightPrefix().' Inactive Employment Type';
        $connection->table('employment_types')->insertTs([
            'name' => $inactiveName,
            'status' => false,
            'type' => 1,
        ]);

        $response = $this->getJson('/api/employment-types?status=0');
        $response->assertOk()
            ->assertJsonCount($connection->table('employment_types')->count(), 'data')
            ->assertJsonFragment([
                'name' => $inactiveName,
                'status' => 0,
            ]);

        expect(collect($response->json('data'))->pluck('status')->unique()->sort()->values()->all())
            ->toContain(0, 1);
    });

    it('preserves list and EmploymentType validation messages', function () {
        phaseEightActingAsExistingUser();

        $this->getJson('/api/echelons?limit=0')
            ->assertUnprocessable()
            ->assertExactJson([
                'code' => 422,
                'message' => 'Limit minimal harus 1 atau lebih.',
                'data' => [
                    'limit' => ['Limit minimal harus 1 atau lebih.'],
                ],
            ]);

        $this->postJson('/api/employment-types', [
            'name' => '',
            'status' => 'invalid',
            'type' => 4,
        ])->assertUnprocessable()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', 'Nama tidak boleh kosong.')
            ->assertJsonPath('data.name.0', 'Nama tidak boleh kosong.')
            ->assertJsonPath('data.status.0', 'Status harus berupa boolean.')
            ->assertJsonPath('data.type.0', 'Tipe harus diantara 1, 2 atau 3.');
    });

    it('runs EmploymentType CRUD without touching referenced existing rows', function () {
        $connection = phaseEightConnection();
        phaseEightActingAsExistingUser();
        $name = phaseEightPrefix().' Employment Type';
        $updatedName = $name.' Updated';

        $this->postJson('/api/employment-types', [
            'name' => $name,
            'status' => true,
            'type' => 3,
        ])->assertExactJson([
            'code' => 200,
            'message' => 'Jenis pegawai berhasil ditambah.',
            'data' => null,
        ]);

        $employmentType = $connection->table('employment_types')->where('name', $name)->sole();

        expect($employmentType->created_at)->not->toBeNull()
            ->and($connection->table('users')->where('employment_type_id', $employmentType->id)->count())->toBe(0);

        $this->getJson('/api/employment-types/'.$employmentType->id)
            ->assertExactJson([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'id' => $employmentType->id,
                    'name' => $name,
                    'status' => 1,
                    'type' => 3,
                ],
            ]);

        $this->getJson('/api/employment-types?search='.rawurlencode($name).'&limit=1')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $employmentType->id);

        $this->postJson('/api/employment-types/'.$employmentType->id, [
            'name' => $updatedName,
            'status' => false,
            'type' => 2,
        ])->assertExactJson([
            'code' => 200,
            'message' => 'Jenis pegawai berhasil diupdate.',
            'data' => null,
        ]);

        $updated = $connection->table('employment_types')->where('id', $employmentType->id)->sole();
        expect($updated->name)->toBe($updatedName)
            ->and((int) $updated->status)->toBe(0)
            ->and((int) $updated->type)->toBe(2)
            ->and($updated->updated_at)->not->toBeNull();

        $this->deleteJson('/api/employment-types/'.$employmentType->id)
            ->assertExactJson([
                'code' => 200,
                'message' => 'Jenis pegawai berhasil dihapus.',
                'data' => null,
            ]);

        $this->getJson('/api/employment-types/'.$employmentType->id)
            ->assertNotFound()
            ->assertExactJson([
                'code' => 404,
                'message' => 'Jenis pegawai tidak ditemukan.',
                'data' => null,
            ]);
    });

    it('enforces mapped EmploymentType permissions and preserves lookup fall-through', function () {
        $connection = phaseEightConnection();
        $prefix = phaseEightPrefix();
        $roleId = $connection->table('roles')->insertGetId([
            'name' => $prefix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $permissionId = $connection->table('permissions')
            ->where('name', 'Master Data - Jenis Pegawai')
            ->value('id');

        $connection->table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
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
            'name' => 'Phase 8 Authorization User',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Sanctum::actingAs(User::query()->findOrFail($userId));

        $this->getJson('/api/employment-types')->assertOk();

        $deniedMessage = "Access denied. You don't have permission to create Master Data - Jenis Pegawai.";

        $this->postJson('/api/employment-types', [
            'name' => $prefix.' denied',
            'status' => true,
            'type' => 1,
        ])->assertForbidden()
            ->assertExactJson([
                'code' => 403,
                'message' => $deniedMessage,
                'data' => null,
            ]);

        $this->postJson('/api/employment-types/1', [
            'name' => $prefix.' update denied',
            'status' => true,
            'type' => 1,
        ])->assertForbidden()
            ->assertJsonPath('message', $deniedMessage);

        $this->getJson('/api/echelons')->assertOk();
    });
});
