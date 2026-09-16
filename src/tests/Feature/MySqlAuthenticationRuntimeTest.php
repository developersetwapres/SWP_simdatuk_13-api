<?php

use App\Mail\ForgotPassword;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserDeviceSession;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/**
 * @return list<string>
 */
function phaseSixBAuthTables(): array
{
    return [
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'personal_access_tokens',
        'user_device_sessions',
        'otps',
        'password_reset_tokens',
    ];
}

function phaseSixBCloneConnection(): Connection
{
    return DB::connection('mysql');
}

function phaseSixBExistingRoleId(): int
{
    return (int) phaseSixBCloneConnection()->table('roles')->orderBy('id')->value('id');
}

function phaseSixBPrefix(): string
{
    return 'codex_phase6b_'.bin2hex(random_bytes(6));
}

function phaseSixBCreateUser(?string $prefix = null): User
{
    $prefix ??= phaseSixBPrefix();
    $connection = phaseSixBCloneConnection();
    $userId = $connection->table('users')->insertGetId([
        'role_id' => phaseSixBExistingRoleId(),
        'email' => $prefix.'@example.invalid',
        'username' => $prefix,
        'password' => Hash::make('Password@123'),
        'name' => 'Phase 6B Runtime User',
        'status' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return User::query()->findOrFail($userId);
}

beforeEach(function (): void {
    if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
        $this->markTestSkipped('MySQL clone verification is opt-in.');
    }

    $cloneDatabase = (string) env('SIMDATUK_CLONE_DATABASE');

    if ($cloneDatabase !== 'SWP_simdatuk_test13') {
        throw new RuntimeException('Refusing MySQL writes: expected the authorized SWP_simdatuk_test13 clone.');
    }

    config()->set('database.default', 'mysql');
    config()->set('database.connections.mysql.database', $cloneDatabase);
    DB::purge('mysql');

    $connection = phaseSixBCloneConnection();

    if ($connection->getDriverName() !== 'mysql' || $connection->getDatabaseName() !== $cloneDatabase) {
        throw new RuntimeException('Refusing MySQL writes: clone connection guard failed.');
    }

    $engines = $connection->table('information_schema.tables')
        ->where('table_schema', $cloneDatabase)
        ->whereIn('table_name', phaseSixBAuthTables())
        ->pluck('ENGINE')
        ->map(fn (?string $engine): string => strtoupper((string) $engine));

    if ($engines->count() !== count(phaseSixBAuthTables()) || $engines->contains(fn (string $engine): bool => $engine !== 'INNODB')) {
        throw new RuntimeException('Refusing MySQL writes: every auth table must exist and use InnoDB.');
    }

    $connection->beginTransaction();
});

afterEach(function (): void {
    if (env('RUN_SIMDATUK_MYSQL_CLONE_TESTS') !== '1') {
        return;
    }

    $connection = phaseSixBCloneConnection();

    while ($connection->transactionLevel() > 0) {
        $connection->rollBack();
    }

    DB::disconnect('mysql');
});

it('matches the authoritative auth schema and loads real model relations', function () {
    $connection = phaseSixBCloneConnection();
    $columnTypes = $connection->table('information_schema.columns')
        ->where('table_schema', 'SWP_simdatuk_test13')
        ->whereIn('table_name', phaseSixBAuthTables())
        ->whereIn('column_name', [
            'role_id',
            'password',
            'permitted_actions',
            'sanctum_token_id',
            'expire_at',
            'token',
        ])
        ->get()
        ->mapWithKeys(fn (object $column): array => [
            strtolower($column->TABLE_NAME.'.'.$column->COLUMN_NAME) => strtolower($column->COLUMN_TYPE),
        ]);

    expect($columnTypes['users.role_id'])->toBe('bigint unsigned')
        ->and($columnTypes['users.password'])->toBe('varchar(255)')
        ->and($columnTypes['permissions.permitted_actions'])->toBe('varchar(10)')
        ->and($columnTypes['user_device_sessions.sanctum_token_id'])->toBe('varchar(255)')
        ->and($columnTypes['otps.expire_at'])->toBe('datetime')
        ->and($columnTypes['password_reset_tokens.expire_at'])->toBe('datetime')
        ->and($columnTypes['personal_access_tokens.token'])->toBe('varchar(64)');

    $existingHash = (string) $connection->table('users')->whereNotNull('password')->value('password');
    $existingUser = User::query()->whereNotNull('role_id')->firstOrFail();
    $role = Role::query()->with('permissions')->findOrFail($existingUser->role_id);
    $permission = Permission::query()->with('roles')->orderBy('id')->firstOrFail();

    expect(password_get_info($existingHash)['algoName'])->toBe('bcrypt')
        ->and(strlen($existingHash))->toBe(60)
        ->and($existingUser->role?->is($role))->toBeTrue()
        ->and($role->permissions)->toHaveCount(28)
        ->and($permission->roles)->not->toBeEmpty()
        ->and($existingUser->deviceSessions)->toBeIterable();
});

it('runs login bearer authentication session listing and both logout paths on MySQL', function () {
    $prefix = phaseSixBPrefix();
    $user = phaseSixBCreateUser($prefix);
    $oldToken = $user->createToken('old');
    UserDeviceSession::query()->create([
        'user_id' => $user->id,
        'device_identifier' => 'old-device',
        'device_name' => 'Old Device',
        'ip_address' => '198.51.100.10',
        'user_agent' => 'Old Client',
        'sanctum_token_id' => $oldToken->accessToken->id,
        'last_activity_at' => now()->subDay(),
    ]);

    $loginResponse = $this->withHeaders([
        'User-Agent' => 'SIMDATUK Mobile Client',
        'CF-Connecting-IP' => '203.0.113.60',
    ])->postJson('/api/login', [
        'username' => $prefix,
        'password' => 'Password@123',
    ]);

    $loginResponse->assertOk()
        ->assertJsonPath('code', 200)
        ->assertJsonPath('message', 'Pengguna berhasil login.')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('device_info.previous_sessions_revoked', true);

    $plainTextToken = (string) $loginResponse->json('token');
    $newToken = $user->tokens()->sole();
    $newSession = $user->deviceSessions()->sole();

    expect($newToken->name)->toBe('web')
        ->and($newSession->sanctum_token_id)->toBe((string) $newToken->id)
        ->and($newSession->ip_address)->toBe('203.0.113.60');

    $this->withToken($plainTextToken)
        ->getJson('/api/active-sessions')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->withToken($plainTextToken)
        ->deleteJson('/api/logout')
        ->assertExactJson([
            'code' => 200,
            'message' => 'Pengguna berhasil logout.',
            'data' => null,
        ]);

    expect($user->tokens()->count())->toBe(0)
        ->and($user->deviceSessions()->count())->toBe(0);

    $firstToken = $user->createToken('first');
    $secondToken = $user->createToken('second');

    foreach ([$firstToken, $secondToken] as $index => $token) {
        UserDeviceSession::query()->create([
            'user_id' => $user->id,
            'device_identifier' => 'logout-all-'.$index,
            'device_name' => 'Runtime Device',
            'ip_address' => '203.0.113.61',
            'user_agent' => 'SIMDATUK Runtime Client',
            'sanctum_token_id' => $token->accessToken->id,
            'last_activity_at' => now(),
        ]);
    }

    $this->withToken($firstToken->plainTextToken)
        ->deleteJson('/api/logout-all-devices')
        ->assertExactJson([
            'code' => 200,
            'message' => 'Berhasil logout dari semua perangkat.',
            'data' => null,
        ]);

    expect($user->tokens()->count())->toBe(0)
        ->and($user->deviceSessions()->count())->toBe(0);
});

it('runs forgot password OTP verification and password reset on MySQL with mail faked', function () {
    Mail::fake();
    $prefix = phaseSixBPrefix();
    $user = phaseSixBCreateUser($prefix);

    $this->postJson('/api/forgot-password', [
        'email' => $user->email,
    ])->assertExactJson([
        'code' => 200,
        'message' => 'Email sudah dikirim.',
        'data' => null,
    ]);

    $otp = phaseSixBCloneConnection()->table('otps')->where('email', $user->email)->sole();

    Mail::assertSent(ForgotPassword::class, fn (ForgotPassword $mail): bool => $mail->hasTo($user->email));

    $verifyResponse = $this->postJson('/api/verify-otp', [
        'email' => $user->email,
        'otp' => $otp->code,
    ]);

    $verifyResponse->assertOk()
        ->assertJsonPath('code', 200)
        ->assertJsonPath('message', 'Kode OTP berhasil diverifikasi.');

    $resetToken = (string) $verifyResponse->json('reset_token');

    expect($resetToken)->toHaveLength(40)
        ->and(phaseSixBCloneConnection()->table('otps')->where('email', $user->email)->count())->toBe(0)
        ->and(phaseSixBCloneConnection()->table('password_reset_tokens')->where('email', $user->email)->count())->toBe(1);

    $this->postJson('/api/reset-password', [
        'reset_token' => $resetToken,
        'password' => 'NewPassword@123',
        'password_confirmation' => 'NewPassword@123',
    ])->assertExactJson([
        'code' => 200,
        'message' => 'Reset password berhasil disimpan.',
        'data' => null,
    ]);

    expect(Hash::check(
        'NewPassword@123',
        phaseSixBCloneConnection()->table('users')->where('id', $user->id)->value('password'),
    ))->toBeTrue()
        ->and(phaseSixBCloneConnection()->table('password_reset_tokens')->where('email', $user->email)->count())->toBe(0);
});

it('executes real permission queries timestamp macros and cleanup logic inside rollback', function () {
    $connection = phaseSixBCloneConnection();
    $user = phaseSixBCreateUser();

    $permission = $connection->table('permissions as p')
        ->join('role_permissions as rp', 'p.id', '=', 'rp.permission_id')
        ->where('rp.role_id', $user->role_id)
        ->where('p.name', 'Master Data - Data Pengguna')
        ->select('rp.read', 'rp.delete')
        ->first();

    expect((int) $permission->read)->toBe(1)
        ->and((int) $permission->delete)->toBe(0);

    Route::get('/api/users', fn () => response()->json(['allowed' => true]))
        ->middleware(['auth:sanctum', 'role.access']);
    Route::delete('/api/users', fn () => response()->json(['allowed' => true]))
        ->middleware(['auth:sanctum', 'role.access']);
    Sanctum::actingAs($user);

    $this->getJson('/api/users')->assertOk()->assertExactJson(['allowed' => true]);
    $this->deleteJson('/api/users')->assertForbidden()->assertExactJson([
        'code' => 403,
        'message' => "Access denied. You don't have permission to delete Master Data - Data Pengguna.",
        'data' => null,
    ]);

    $macroOtpCode = (string) random_int(100000, 999999);
    $inserted = $connection->table('otps')->insertTs([
        'email' => phaseSixBPrefix().'@example.invalid',
        'code' => $macroOtpCode,
        'expire_at' => now()->addMinutes(5),
    ]);
    $sessionId = $connection->table('user_device_sessions')->insertGetIdTs([
        'user_id' => $user->id,
        'device_identifier' => phaseSixBPrefix(),
        'last_activity_at' => now(),
    ]);
    $updated = $connection->table('user_device_sessions')
        ->where('id', $sessionId)
        ->updateTs(['device_name' => 'Macro Verified']);

    expect($inserted)->toBeTrue()
        ->and($sessionId)->toBeInt()
        ->and($updated)->toBe(1)
        ->and($connection->table('otps')->where('code', $macroOtpCode)->whereNotNull('created_at')->exists())->toBeTrue()
        ->and($connection->table('user_device_sessions')->where('id', $sessionId)->value('device_name'))->toBe('Macro Verified');

    $oldToken = $user->createToken('cleanup-runtime');
    $oldToken->accessToken->forceFill(['created_at' => now()->subDays(31)])->save();
    UserDeviceSession::query()->create([
        'user_id' => $user->id,
        'device_identifier' => 'expired-runtime-session',
        'sanctum_token_id' => $oldToken->accessToken->id,
        'last_activity_at' => now()->subDays(31),
    ]);

    expect(Artisan::call('sessions:clean', ['--days' => 30]))->toBe(0)
        ->and($user->tokens()->whereKey($oldToken->accessToken->id)->exists())->toBeFalse()
        ->and($user->deviceSessions()->where('device_identifier', 'expired-runtime-session')->exists())->toBeFalse();

    $softDeleteTables = $connection->table('information_schema.columns')
        ->where('table_schema', 'SWP_simdatuk_test13')
        ->where('column_name', 'deleted_at')
        ->count();

    expect($softDeleteTables)->toBe(0)
        ->and(Builder::hasMacro('deleteTs'))->toBeTrue();
});
