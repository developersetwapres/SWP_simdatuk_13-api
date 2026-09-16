<?php

use App\Mail\RegisterVerification;
use App\Models\User;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/** @return list<string> */
function phaseTenTables(): array
{
    return [
        'roles',
        'permissions',
        'role_permissions',
        'users',
        'personal_access_tokens',
    ];
}

function phaseTenConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseTenPrefix(): string
{
    return 'codex_phase10_'.bin2hex(random_bytes(6));
}

function phaseTenActingAsAclManager(): User
{
    $connection = phaseTenConnection();
    $candidateRoleIds = $connection->table('users')
        ->whereNotNull('role_id')
        ->where('status', true)
        ->distinct()
        ->pluck('role_id');

    foreach ($candidateRoleIds as $roleId) {
        $rolePermission = $connection->table('role_permissions as rp')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->where('p.name', 'Master Data - Data Role Pengguna')
            ->where('rp.create', true)
            ->where('rp.read', true)
            ->where('rp.delete', true)
            ->exists();
        $userPermission = $connection->table('role_permissions as rp')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('rp.role_id', $roleId)
            ->where('p.name', 'Master Data - Data Pengguna')
            ->where('rp.create', true)
            ->where('rp.read', true)
            ->where('rp.update', true)
            ->exists();

        if ($rolePermission && $userPermission) {
            $user = User::query()
                ->where('role_id', $roleId)
                ->where('status', true)
                ->orderBy('id')
                ->firstOrFail();
            Sanctum::actingAs($user);

            return $user;
        }
    }

    throw new RuntimeException('The clone has no active ACL manager with the required Role/User flags.');
}

function phaseTenExistingRoleId(): int
{
    return (int) phaseTenConnection()->table('roles')->orderBy('id')->value('id');
}

function phaseTenInsertRole(?string $name = null): int
{
    return phaseTenConnection()->table('roles')->insertGetIdTs([
        'name' => $name ?? phaseTenPrefix().' Role',
    ]);
}

/** @param array<string, mixed> $overrides */
function phaseTenInsertUser(array $overrides = []): int
{
    return phaseTenConnection()->table('users')->insertGetIdTs(array_merge([
        'name' => 'Phase 10 Isolated Employee',
        'status' => false,
    ], $overrides));
}

it('registers Role and User routes in the Laravel 10 declaration order', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/roles') || str_starts_with($route->uri(), 'api/users'))
        ->map(fn ($route): array => [
            'method' => $route->methods()[0],
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
            'middleware' => $route->middleware(),
        ])
        ->values()
        ->all();

    expect($routes)->toBe([
        ['method' => 'GET', 'uri' => 'api/roles', 'action' => 'App\Http\Controllers\RoleController@index', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/roles', 'action' => 'App\Http\Controllers\RoleController@create', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/roles/{id}', 'action' => 'App\Http\Controllers\RoleController@show', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/roles/{id}', 'action' => 'App\Http\Controllers\RoleController@update', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'DELETE', 'uri' => 'api/roles/{id}', 'action' => 'App\Http\Controllers\RoleController@delete', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/users', 'action' => 'App\Http\Controllers\UserController@index', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/users', 'action' => 'App\Http\Controllers\UserController@create', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'GET', 'uri' => 'api/users/{id}', 'action' => 'App\Http\Controllers\UserController@show', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'POST', 'uri' => 'api/users/{id}', 'action' => 'App\Http\Controllers\UserController@update', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
        ['method' => 'PUT', 'uri' => 'api/users/status', 'action' => 'App\Http\Controllers\UserController@status', 'middleware' => ['api', 'auth:sanctum', 'role.access']],
    ]);
});

describe('Role and User administration on the MySQL clone', function () {
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

        $connection = phaseTenConnection();

        if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
            throw new RuntimeException('Refusing MySQL writes: clone connection guard failed.');
        }

        $engines = $connection->table('information_schema.tables')
            ->where('table_schema', $cloneDatabase)
            ->whereIn('table_name', phaseTenTables())
            ->pluck('ENGINE')
            ->map(fn (?string $engine): string => strtoupper((string) $engine));

        if ($engines->count() !== count(phaseTenTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
            throw new RuntimeException('Refusing MySQL writes: every Phase 10 table must exist and use InnoDB.');
        }

        $connection->beginTransaction();
    });

    afterEach(function (): void {
        if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
            return;
        }

        $connection = phaseTenConnection();

        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        DB::disconnect('mysql');
    });

    it('lists searches and paginates Roles in descending creation order', function () {
        $connection = phaseTenConnection();
        phaseTenActingAsAclManager();
        $prefix = phaseTenPrefix();
        $olderId = $connection->table('roles')->insertGetId([
            'name' => $prefix.' Older',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        $newerId = $connection->table('roles')->insertGetId([
            'name' => $prefix.' Newer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/roles?search='.$prefix.'&limit=1&page=1');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('data.0.id', $newerId);
        expect($response->json('data.0.id'))->not->toBe($olderId);
    });

    it('runs complete Role CRUD and persists permission flags and user reassignment', function () {
        $connection = phaseTenConnection();
        $manager = phaseTenActingAsAclManager();
        $permissions = $connection->table('permissions')->orderBy('id')->limit(3)->get();
        $name = phaseTenPrefix().' Managed Role';
        $updatedName = $name.' Updated';

        $this->postJson('/api/roles', [
            'name' => $name,
            'permissions' => [
                ['id' => $permissions[0]->id, 'permitted_actions' => 'cr'],
                ['id' => $permissions[1]->id, 'permitted_actions' => 'ud'],
            ],
        ])->assertExactJson(['code' => 200, 'message' => 'Role berhasil ditambah.', 'data' => null]);

        expect($connection->transactionLevel())->toBe(1);
        $role = $connection->table('roles')->where('name', $name)->sole();
        $firstPivot = $connection->table('role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', $permissions[0]->id)
            ->sole();
        $secondPivot = $connection->table('role_permissions')
            ->where('role_id', $role->id)
            ->where('permission_id', $permissions[1]->id)
            ->sole();

        expect([(int) $firstPivot->create, (int) $firstPivot->read, (int) $firstPivot->update, (int) $firstPivot->delete])->toBe([1, 1, 0, 0])
            ->and([(int) $secondPivot->create, (int) $secondPivot->read, (int) $secondPivot->update, (int) $secondPivot->delete])->toBe([0, 0, 1, 1]);

        $this->getJson('/api/roles/'.$role->id)
            ->assertOk()
            ->assertJsonPath('data.name', $name)
            ->assertJsonCount(2, 'data.permissions')
            ->assertJsonPath('data.permissions.0.permitted_actions', $permissions[0]->permitted_actions);

        $this->postJson('/api/roles/'.$role->id, [
            'name' => $updatedName,
            'permissions' => [
                ['id' => $permissions[2]->id, 'permitted_actions' => 'crud'],
            ],
        ])->assertExactJson(['code' => 200, 'message' => 'Role telah berhasil diupdate.', 'data' => null]);

        expect($connection->table('roles')->where('id', $role->id)->value('name'))->toBe($updatedName)
            ->and($connection->table('role_permissions')->where('role_id', $role->id)->count())->toBe(1)
            ->and($connection->table('role_permissions')->where('role_id', $role->id)->value('permission_id'))->toBe($permissions[2]->id);

        $assignedUserId = phaseTenInsertUser([
            'role_id' => $role->id,
            'username' => phaseTenPrefix(),
            'email' => phaseTenPrefix().'@example.invalid',
            'password' => Hash::make('Password@123'),
            'status' => true,
        ]);

        $this->deleteJson('/api/roles/'.$role->id, ['role_id' => $manager->role_id])
            ->assertExactJson(['code' => 200, 'message' => 'Role berhasil dihapus.', 'data' => null]);

        expect($connection->table('roles')->where('id', $role->id)->exists())->toBeFalse()
            ->and($connection->table('role_permissions')->where('role_id', $role->id)->exists())->toBeFalse()
            ->and($connection->table('users')->where('id', $assignedUserId)->value('role_id'))->toBe($manager->role_id);
    });

    it('returns Role validation messages and preserves duplicate-name handling', function () {
        $connection = phaseTenConnection();
        phaseTenActingAsAclManager();
        $name = phaseTenPrefix().' Duplicate Role';
        phaseTenInsertRole($name);
        $permissionId = $connection->table('permissions')->orderBy('id')->value('id');

        $this->postJson('/api/roles', [
            'name' => $name,
            'permissions' => [['id' => $permissionId, 'permitted_actions' => 'r']],
        ])->assertUnprocessable()
            ->assertExactJson([
                'code' => 422,
                'message' => 'Nama sudah digunakan.',
                'data' => ['name' => ['Nama sudah digunakan.']],
            ]);
    });

    it('rolls back a Role when a numeric but nonexistent permission reaches the real foreign key', function () {
        $connection = phaseTenConnection();
        phaseTenActingAsAclManager();
        $name = phaseTenPrefix().' Invalid Permission Role';
        $missingPermissionId = ((int) $connection->table('permissions')->max('id')) + 100000;

        $this->postJson('/api/roles', [
            'name' => $name,
            'permissions' => [['id' => $missingPermissionId, 'permitted_actions' => 'crud']],
        ])->assertExactJson([
            'code' => 400,
            'message' => 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!',
            'data' => null,
        ]);

        expect($connection->transactionLevel())->toBe(1)
            ->and($connection->table('roles')->where('name', $name)->exists())->toBeFalse();
    });

    it('preserves the open transaction on a missing Role delete', function () {
        $connection = phaseTenConnection();
        phaseTenActingAsAclManager();
        $missingRoleId = ((int) $connection->table('roles')->max('id')) + 100000;

        $this->deleteJson('/api/roles/'.$missingRoleId, ['role_id' => phaseTenExistingRoleId()])
            ->assertNotFound()
            ->assertExactJson(['code' => 404, 'message' => 'Role tidak ditemukan.', 'data' => null]);

        expect($connection->transactionLevel())->toBe(2);
    });

    it('enforces Role permissions and keeps POST update mapped to create', function () {
        $connection = phaseTenConnection();
        $prefix = phaseTenPrefix();
        $roleId = phaseTenInsertRole($prefix.' Restricted Role');
        $permissionId = $connection->table('permissions')->where('name', 'Master Data - Data Role Pengguna')->value('id');
        $connection->table('role_permissions')->insertTs([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'create' => false,
            'read' => true,
            'update' => true,
            'delete' => false,
        ]);
        $userId = phaseTenInsertUser([
            'role_id' => $roleId,
            'username' => $prefix,
            'email' => $prefix.'@example.invalid',
            'password' => Hash::make('Password@123'),
            'status' => true,
        ]);
        Sanctum::actingAs(User::query()->findOrFail($userId));
        $deniedCreate = "Access denied. You don't have permission to create Master Data - Data Role Pengguna.";

        $this->getJson('/api/roles')->assertOk();
        $this->postJson('/api/roles', [])->assertForbidden()->assertJsonPath('message', $deniedCreate);
        $this->postJson('/api/roles/'.$roleId, [])->assertForbidden()->assertJsonPath('message', $deniedCreate);
        $this->deleteJson('/api/roles/'.$roleId, ['role_id' => phaseTenExistingRoleId()])
            ->assertForbidden()
            ->assertJsonPath('message', "Access denied. You don't have permission to delete Master Data - Data Role Pengguna.");
    });

    it('lists and shows only role-assigned Users with the legacy response fields', function () {
        phaseTenActingAsAclManager();
        $roleId = phaseTenExistingRoleId();
        $prefix = phaseTenPrefix();
        $userIdSuffix = random_int(100000, 999999);
        $userId = phaseTenInsertUser([
            'role_id' => $roleId,
            'username' => $prefix,
            'email' => $prefix.'@example.invalid',
            'password' => Hash::make('Password@123'),
            'employee_id_number' => 'P10'.$userIdSuffix,
            'employee_registration_number' => 'R10'.$userIdSuffix,
            'status' => true,
        ]);
        $roleName = phaseTenConnection()->table('roles')->where('id', $roleId)->value('name');

        $this->getJson('/api/users?search='.$prefix.'&limit=1&page=1')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.id', $userId)
            ->assertJsonPath('data.0.role_name', $roleName)
            ->assertJsonMissingPath('data.0.email');

        $this->getJson('/api/users/'.$userId)
            ->assertExactJson([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'id' => $userId,
                    'username' => $prefix,
                    'email' => $prefix.'@example.invalid',
                    'name' => 'Phase 10 Isolated Employee',
                    'employee_id_number' => 'P10'.$userIdSuffix,
                    'status' => 1,
                    'role' => ['id' => $roleId, 'name' => $roleName],
                ],
            ]);
    });

    it('activates an existing employee row and emails the generated password', function () {
        Mail::fake();
        $connection = phaseTenConnection();
        phaseTenActingAsAclManager();
        $roleId = phaseTenExistingRoleId();
        $candidateId = phaseTenInsertUser();
        $username = phaseTenPrefix();
        $email = $username.'@example.invalid';

        $this->postJson('/api/users', [
            'user_id' => $candidateId,
            'username' => $username,
            'email' => $email,
            'role_id' => $roleId,
        ])->assertExactJson(['code' => 200, 'message' => 'Pengguna berhasil ditambah.', 'data' => null]);

        $stored = $connection->table('users')->where('id', $candidateId)->sole();
        $plainPassword = null;
        Mail::assertSent(RegisterVerification::class, function (RegisterVerification $mail) use ($email, &$plainPassword): bool {
            $plainPassword = (string) $mail->request->password;

            return $mail->hasTo($email);
        });

        expect($stored->username)->toBe($username)
            ->and($stored->email)->toBe($email)
            ->and($stored->role_id)->toBe($roleId)
            ->and((int) $stored->status)->toBe(1)
            ->and($plainPassword)->toHaveLength(8)
            ->and(Hash::check($plainPassword, $stored->password))->toBeTrue();

        $mailable = new RegisterVerification(request()->duplicate(null, [
            'name' => $stored->name,
            'username' => $username,
            'password' => $plainPassword,
        ]));
        expect($mailable->envelope()->subject)->toBe('Verifikasi Email')
            ->and($mailable->render())->toContain('Verifikasi Email', $username, e($plainPassword));
    });

    it('keeps password status and mail unchanged when User email is unchanged', function () {
        Mail::fake();
        phaseTenActingAsAclManager();
        $roleId = phaseTenExistingRoleId();
        $prefix = phaseTenPrefix();
        $username = 'p10_'.bin2hex(random_bytes(6));
        $passwordHash = Hash::make('ExistingPassword@123');
        $userId = phaseTenInsertUser([
            'role_id' => $roleId,
            'username' => $username,
            'email' => $prefix.'@example.invalid',
            'password' => $passwordHash,
            'status' => false,
        ]);

        $this->postJson('/api/users/'.$userId, [
            'username' => $username.'_updated',
            'email' => $prefix.'@example.invalid',
            'role_id' => $roleId,
        ])->assertExactJson(['code' => 200, 'message' => 'Pengguna berhasil diupdate.', 'data' => null]);

        $stored = phaseTenConnection()->table('users')->where('id', $userId)->sole();
        expect($stored->username)->toBe($username.'_updated')
            ->and($stored->password)->toBe($passwordHash)
            ->and((int) $stored->status)->toBe(0);
        Mail::assertNothingSent();
    });

    it('regenerates password activates and emails User after an email change without revoking tokens', function () {
        Mail::fake();
        phaseTenActingAsAclManager();
        $roleId = phaseTenExistingRoleId();
        $prefix = phaseTenPrefix();
        $username = 'p10_'.bin2hex(random_bytes(6));
        $oldHash = Hash::make('ExistingPassword@123');
        $userId = phaseTenInsertUser([
            'role_id' => $roleId,
            'username' => $username,
            'email' => $prefix.'@example.invalid',
            'password' => $oldHash,
            'status' => false,
        ]);
        $user = User::query()->findOrFail($userId);
        $user->createToken('phase10-existing');
        $newEmail = $prefix.'_new@example.invalid';

        $this->postJson('/api/users/'.$userId, [
            'username' => $username.'_updated',
            'email' => $newEmail,
            'role_id' => $roleId,
        ])->assertExactJson(['code' => 200, 'message' => 'Pengguna berhasil diupdate.', 'data' => null]);

        $stored = phaseTenConnection()->table('users')->where('id', $userId)->sole();
        $plainPassword = null;
        Mail::assertSent(RegisterVerification::class, function (RegisterVerification $mail) use ($newEmail, &$plainPassword): bool {
            $plainPassword = (string) $mail->request->password;

            return $mail->hasTo($newEmail);
        });

        expect($stored->email)->toBe($newEmail)
            ->and((int) $stored->status)->toBe(1)
            ->and($stored->password)->not->toBe($oldHash)
            ->and(Hash::check($plainPassword, $stored->password))->toBeTrue()
            ->and($user->tokens()->count())->toBe(1);
    });

    it('returns mail failure after committed User activation and preserves that DB side effect', function () {
        $connection = phaseTenConnection();
        phaseTenActingAsAclManager();
        $roleId = phaseTenExistingRoleId();
        $candidateId = phaseTenInsertUser();
        $username = phaseTenPrefix();
        $email = $username.'@example.invalid';
        Mail::shouldReceive('to')->once()->with($email)->andThrow(new RuntimeException('Simulated mail failure'));

        $this->postJson('/api/users', [
            'user_id' => $candidateId,
            'username' => $username,
            'email' => $email,
            'role_id' => $roleId,
        ])->assertNotFound()
            ->assertExactJson([
                'code' => 404,
                'message' => 'Gagal mengirimkan email, silakan hubungi admin.',
                'data' => null,
            ]);

        expect($connection->transactionLevel())->toBe(1)
            ->and($connection->table('users')->where('id', $candidateId)->value('username'))->toBe($username)
            ->and($connection->table('users')->where('id', $candidateId)->value('role_id'))->toBe($roleId);
    });

    it('updates User status with the legacy message and does not revoke existing tokens', function () {
        phaseTenActingAsAclManager();
        $roleId = phaseTenExistingRoleId();
        $prefix = phaseTenPrefix();
        $userId = phaseTenInsertUser([
            'role_id' => $roleId,
            'username' => $prefix,
            'email' => $prefix.'@example.invalid',
            'password' => Hash::make('Password@123'),
            'status' => true,
        ]);
        $user = User::query()->findOrFail($userId);
        $user->createToken('phase10-status');

        $this->putJson('/api/users/status', ['id' => $userId, 'status' => false])
            ->assertExactJson(['code' => 200, 'message' => 'Pengguna berhasil dinonaktifkan.', 'data' => null]);

        expect((int) phaseTenConnection()->table('users')->where('id', $userId)->value('status'))->toBe(0)
            ->and($user->tokens()->count())->toBe(1);
    });

    it('preserves User duplicate and status validation messages', function () {
        phaseTenActingAsAclManager();
        $roleId = phaseTenExistingRoleId();
        $prefix = phaseTenPrefix();
        phaseTenInsertUser([
            'role_id' => $roleId,
            'username' => $prefix,
            'email' => $prefix.'@example.invalid',
            'password' => Hash::make('Password@123'),
            'status' => true,
        ]);
        $candidateId = phaseTenInsertUser();

        $this->postJson('/api/users', [
            'user_id' => $candidateId,
            'username' => $prefix,
            'email' => $prefix.'@example.invalid',
            'role_id' => $roleId,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Username sudah digunakan.')
            ->assertJsonPath('data.username.0', 'Username sudah digunakan.')
            ->assertJsonPath('data.email.0', 'Email sudah digunakan.');
    });

    it('preserves the typo in invalid User status validation', function () {
        phaseTenActingAsAclManager();

        $this->putJson('/api/users/status', ['id' => 1, 'status' => 'invalid'])
            ->assertUnprocessable()
            ->assertExactJson([
                'code' => 422,
                'message' => 'ID harus berupa boolean.',
                'data' => ['status' => ['ID harus berupa boolean.']],
            ]);
    });

    it('returns source-compatible User and Role lookup failures', function () {
        phaseTenActingAsAclManager();
        $missingUserId = ((int) phaseTenConnection()->table('users')->max('id')) + 100000;

        $this->postJson('/api/users', [
            'user_id' => $missingUserId,
            'username' => phaseTenPrefix(),
            'email' => phaseTenPrefix().'@example.invalid',
            'role_id' => phaseTenExistingRoleId(),
        ])->assertUnprocessable()
            ->assertExactJson(['code' => 422, 'message' => 'Pengguna tidak ditemukan.', 'data' => null]);
    });

    it('rejects a missing Role before activating an existing employee row', function () {
        Mail::fake();
        $connection = phaseTenConnection();
        phaseTenActingAsAclManager();
        $candidateId = phaseTenInsertUser();
        $missingRoleId = ((int) $connection->table('roles')->max('id')) + 100000;

        $this->postJson('/api/users', [
            'user_id' => $candidateId,
            'username' => phaseTenPrefix(),
            'email' => phaseTenPrefix().'@example.invalid',
            'role_id' => $missingRoleId,
        ])->assertUnprocessable()
            ->assertExactJson(['code' => 422, 'message' => 'Role tidak ditemukan.', 'data' => null]);

        $candidate = $connection->table('users')->where('id', $candidateId)->sole();
        expect($candidate->role_id)->toBeNull()
            ->and($candidate->username)->toBeNull()
            ->and($candidate->password)->toBeNull();
        Mail::assertNothingSent();
    });

    it('enforces User permissions while PUT status uses update and POST update uses create', function () {
        $connection = phaseTenConnection();
        $prefix = phaseTenPrefix();
        $roleId = phaseTenInsertRole($prefix.' Restricted User Role');
        $permissionId = $connection->table('permissions')->where('name', 'Master Data - Data Pengguna')->value('id');
        $connection->table('role_permissions')->insertTs([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'create' => false,
            'read' => true,
            'update' => true,
            'delete' => false,
        ]);
        $userId = phaseTenInsertUser([
            'role_id' => $roleId,
            'username' => $prefix,
            'email' => $prefix.'@example.invalid',
            'password' => Hash::make('Password@123'),
            'status' => true,
        ]);
        Sanctum::actingAs(User::query()->findOrFail($userId));
        $deniedCreate = "Access denied. You don't have permission to create Master Data - Data Pengguna.";

        $this->getJson('/api/users?search='.$prefix)->assertOk();
        $this->postJson('/api/users', [])->assertForbidden()->assertJsonPath('message', $deniedCreate);
        $this->postJson('/api/users/'.$userId, [])->assertForbidden()->assertJsonPath('message', $deniedCreate);
        $this->putJson('/api/users/status', ['id' => $userId, 'status' => false])
            ->assertOk()
            ->assertJsonPath('message', 'Pengguna berhasil dinonaktifkan.');
    });
});
