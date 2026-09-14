<?php

use App\Http\Middleware\ApiRateLimit;
use App\Http\Middleware\RoleBasedAccess;
use App\Http\Middleware\ValidateDeviceSession;
use App\Mail\ForgotPassword;
use App\Models\User;
use App\Models\UserDeviceSession;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;

function createPhaseSixSchema(): void
{
    if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
        throw new RuntimeException('Phase 6 tests may only write to disposable SQLite memory.');
    }

    Schema::dropAllTables();

    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->unique();
        $table->string('username')->unique();
        $table->string('password');
        $table->unsignedBigInteger('role_id')->nullable();
        $table->boolean('status')->default(true);
        $table->string('photo_profile')->nullable();
        $table->string('employee_id_number')->nullable();
        $table->string('employee_registration_number')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('permissions', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('permitted_actions')->nullable();
        $table->timestamps();
    });

    Schema::create('role_permissions', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('role_id');
        $table->unsignedBigInteger('permission_id');
        $table->boolean('create')->default(false);
        $table->boolean('read')->default(false);
        $table->boolean('update')->default(false);
        $table->boolean('delete')->default(false);
        $table->timestamps();
    });

    Schema::create('personal_access_tokens', function (Blueprint $table): void {
        $table->id();
        $table->string('tokenable_type');
        $table->unsignedBigInteger('tokenable_id');
        $table->string('name');
        $table->string('token', 64)->unique();
        $table->text('abilities')->nullable();
        $table->timestamp('last_used_at')->nullable();
        $table->timestamp('expires_at')->nullable();
        $table->timestamps();
    });

    Schema::create('user_device_sessions', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('device_identifier');
        $table->string('device_name')->nullable();
        $table->string('ip_address')->nullable();
        $table->text('user_agent')->nullable();
        $table->string('sanctum_token_id')->nullable();
        $table->timestamp('last_activity_at');
        $table->timestamps();
    });

    Schema::create('otps', function (Blueprint $table): void {
        $table->string('email');
        $table->string('code')->unique();
        $table->dateTime('expire_at');
        $table->timestamp('created_at');
    });

    Schema::create('password_reset_tokens', function (Blueprint $table): void {
        $table->string('email');
        $table->string('verification_code')->unique();
        $table->dateTime('expire_at');
        $table->timestamp('created_at')->nullable();
    });
}

function createPhaseSixRole(string $name = 'administrator'): int
{
    return DB::table('roles')->insertGetId([
        'name' => $name,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createPhaseSixUser(array $attributes = []): User
{
    $defaults = [
        'name' => 'SIMDATUK User',
        'email' => fake()->unique()->safeEmail(),
        'username' => fake()->unique()->userName(),
        'password' => Hash::make('Password@123'),
        'role_id' => createPhaseSixRole(),
        'status' => true,
        'photo_profile' => null,
        'employee_id_number' => '198001010001',
        'employee_registration_number' => 'REG-001',
    ];

    return User::query()->forceCreate([...$defaults, ...$attributes]);
}

beforeEach(function (): void {
    createPhaseSixSchema();
    config()->set('app.debug', false);
    config()->set('app.key', 'base64:phase-six-test-key');
    config()->set('app.url', 'http://localhost');
});

it('registers only the Phase 6 auth routes with legacy middleware composition', function () {
    $router = app(Router::class);
    $routes = collect($router->getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/'))
        ->keyBy(fn ($route): string => $route->uri());

    expect($routes->keys()->sort()->values()->all())->toBe([
        'api/active-sessions',
        'api/forgot-password',
        'api/login',
        'api/logout',
        'api/logout-all-devices',
        'api/reset-password',
        'api/verify-otp',
    ]);

    $loginMiddleware = $router->gatherRouteMiddleware($routes['api/login']);
    $logoutMiddleware = $router->gatherRouteMiddleware($routes['api/logout']);

    expect($loginMiddleware)->toContain(ApiRateLimit::class.':5,1')
        ->and($loginMiddleware)->not->toContain(RoleBasedAccess::class)
        ->and($logoutMiddleware)->toContain(RoleBasedAccess::class)
        ->and($logoutMiddleware)->not->toContain(ValidateDeviceSession::class);
});

it('returns the established unauthenticated contract on protected auth routes', function () {
    $this->deleteJson('/api/logout')->assertExactJson([
        'code' => 401,
        'message' => 'Anda harus login terlebih dahulu!',
        'data' => null,
    ]);
});

it('preserves login failure contracts', function () {
    $validUser = createPhaseSixUser(['username' => 'valid-user']);

    $this->postJson('/api/login', [
        'username' => $validUser->username,
        'password' => 'wrong-password',
    ])->assertExactJson([
        'code' => 401,
        'message' => 'Username atau password yang anda masukkan salah.',
        'data' => null,
    ]);

    $this->postJson('/api/login', [
        'username' => 'missing-user',
        'password' => 'Password@123',
    ])->assertUnprocessable()
        ->assertJsonPath('code', 422)
        ->assertJsonPath('message', 'Terjadi kesalahan, silakan coba lagi.');
});

it('rejects users without a role using the established contract', function () {
    createPhaseSixUser(['username' => 'no-role', 'role_id' => null]);

    $this->postJson('/api/login', [
        'username' => 'no-role',
        'password' => 'Password@123',
    ])->assertExactJson([
        'code' => 401,
        'message' => 'Akun anda tidak memiliki peran yang valid, silakan hubungi admin.',
        'data' => null,
    ]);
});

it('rejects inactive users using the established contract', function () {
    createPhaseSixUser(['username' => 'inactive-user', 'status' => false]);

    $this->postJson('/api/login', [
        'username' => 'inactive-user',
        'password' => 'Password@123',
    ])->assertExactJson([
        'code' => 401,
        'message' => 'Akun anda tidak aktif, silakan hubungi admin.',
        'data' => null,
    ]);
});

it('issues a web token and revokes previous token and device sessions on login', function () {
    $roleId = createPhaseSixRole();
    $user = createPhaseSixUser([
        'role_id' => $roleId,
        'username' => 'login-user',
        'email' => 'login@simdatuk.test',
    ]);
    $permissionId = DB::table('permissions')->insertGetId([
        'name' => 'Master Data - Data Pengguna',
        'permitted_actions' => 'crud',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('role_permissions')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
        'create' => true,
        'read' => true,
        'update' => false,
        'delete' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $oldToken = $user->createToken('old');
    UserDeviceSession::query()->create([
        'user_id' => $user->id,
        'device_identifier' => 'old-device',
        'device_name' => 'Old Device',
        'ip_address' => '198.51.100.1',
        'user_agent' => 'Old Client',
        'sanctum_token_id' => $oldToken->accessToken->id,
        'last_activity_at' => now()->subDay(),
    ]);

    $response = $this->withHeaders([
        'User-Agent' => 'SIMDATUK Mobile Client',
        'CF-Connecting-IP' => '203.0.113.20',
    ])->postJson('/api/login', [
        'username' => 'login-user',
        'password' => 'Password@123',
    ]);

    $response->assertOk()
        ->assertJsonPath('code', 200)
        ->assertJsonPath('message', 'Pengguna berhasil login.')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.photo_profile', 'http://localhost:8000/img/profile.jpg')
        ->assertJsonPath('user.role.id', $roleId)
        ->assertJsonPath('user.permissions.0.name', 'Master Data - Data Pengguna')
        ->assertJsonPath('device_info.device_type', 'Mobile Device')
        ->assertJsonPath('device_info.previous_sessions_revoked', true);

    expect($response->json('token'))->toMatch('/^\d+\|.+$/')
        ->and(DB::table('personal_access_tokens')->pluck('name')->all())->toBe(['web'])
        ->and(DB::table('user_device_sessions')->count())->toBe(1)
        ->and(DB::table('user_device_sessions')->value('ip_address'))->toBe('203.0.113.20');
});

it('uses the production recaptcha HTTP contract before issuing a token', function () {
    config()->set('app.env', 'production');
    createPhaseSixUser(['username' => 'captcha-user']);
    Http::fake([
        'www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
    ]);

    $this->postJson('/api/login', [
        'username' => 'captcha-user',
        'password' => 'Password@123',
        'recaptcha_token' => 'rejected-token',
    ])->assertExactJson([
        'code' => 404,
        'message' => 'reCAPTCHA verification failed.',
        'data' => null,
    ]);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
        && $request['response'] === 'rejected-token');
    expect(DB::table('personal_access_tokens')->count())->toBe(0);
});

it('revokes only the current bearer token and matching session on logout', function () {
    $user = createPhaseSixUser();
    $currentToken = $user->createToken('web');
    $otherToken = $user->createToken('other');

    foreach ([$currentToken, $otherToken] as $index => $token) {
        UserDeviceSession::query()->create([
            'user_id' => $user->id,
            'device_identifier' => 'device-'.$index,
            'device_name' => 'Device '.$index,
            'ip_address' => '203.0.113.'.($index + 10),
            'user_agent' => 'SIMDATUK Client',
            'sanctum_token_id' => $token->accessToken->id,
            'last_activity_at' => now(),
        ]);
    }

    $this->withToken($currentToken->plainTextToken)
        ->deleteJson('/api/logout')
        ->assertExactJson([
            'code' => 200,
            'message' => 'Pengguna berhasil logout.',
            'data' => null,
        ]);

    expect(DB::table('personal_access_tokens')->pluck('id')->all())->toBe([$otherToken->accessToken->id])
        ->and(DB::table('user_device_sessions')->pluck('sanctum_token_id')->all())
        ->toBe([(string) $otherToken->accessToken->id]);
});

it('lists active sessions and revokes all devices', function () {
    $user = createPhaseSixUser();
    $firstToken = $user->createToken('first');
    $secondToken = $user->createToken('second');

    foreach ([$firstToken, $secondToken] as $index => $token) {
        UserDeviceSession::query()->create([
            'user_id' => $user->id,
            'device_identifier' => 'device-'.$index,
            'device_name' => 'Device '.$index,
            'ip_address' => '203.0.113.'.($index + 20),
            'user_agent' => 'SIMDATUK Client',
            'sanctum_token_id' => $token->accessToken->id,
            'last_activity_at' => now()->subMinutes($index),
        ]);
    }

    $this->withToken($firstToken->plainTextToken)
        ->getJson('/api/active-sessions')
        ->assertOk()
        ->assertJsonPath('code', 200)
        ->assertJsonPath('message', 'Berhasil mengambil data sesi aktif.')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.device_name', 'Device 0');

    $this->withToken($firstToken->plainTextToken)
        ->deleteJson('/api/logout-all-devices')
        ->assertExactJson([
            'code' => 200,
            'message' => 'Berhasil logout dari semua perangkat.',
            'data' => null,
        ]);

    expect(DB::table('personal_access_tokens')->count())->toBe(0)
        ->and(DB::table('user_device_sessions')->count())->toBe(0);
});

it('creates a five-minute OTP and sends the legacy mail synchronously', function () {
    Mail::fake();
    createPhaseSixUser(['email' => 'reset@simdatuk.test']);
    $beforeRequest = time();

    $this->postJson('/api/forgot-password', [
        'email' => 'reset@simdatuk.test',
    ])->assertExactJson([
        'code' => 200,
        'message' => 'Email sudah dikirim.',
        'data' => null,
    ]);

    $otp = DB::table('otps')->first();
    $afterRequest = time();
    $expiresAt = strtotime($otp->expire_at);

    expect($otp->code)->toMatch('/^\d{6}$/')
        ->and($expiresAt)->toBeGreaterThanOrEqual($beforeRequest + 299)
        ->and($expiresAt)->toBeLessThanOrEqual($afterRequest + 301);

    Mail::assertSent(ForgotPassword::class, function (ForgotPassword $mail) use ($otp): bool {
        return $mail->hasTo('reset@simdatuk.test')
            && str_contains($mail->render(), $otp->code);
    });
});

it('verifies OTP and resets the password with the legacy five-minute token', function () {
    $user = createPhaseSixUser(['email' => 'otp@simdatuk.test']);
    DB::table('otps')->insert([
        'email' => $user->email,
        'code' => '123456',
        'expire_at' => now()->addMinutes(5),
        'created_at' => now(),
    ]);

    $verifyResponse = $this->postJson('/api/verify-otp', [
        'email' => $user->email,
        'otp' => '123456',
    ]);

    $verifyResponse->assertOk()
        ->assertJsonPath('code', 200)
        ->assertJsonPath('message', 'Kode OTP berhasil diverifikasi.');

    $resetToken = $verifyResponse->json('reset_token');
    $resetExpiresAt = strtotime(DB::table('password_reset_tokens')->value('expire_at'));

    expect($resetToken)->toHaveLength(40)
        ->and(DB::table('otps')->count())->toBe(0)
        ->and($resetExpiresAt)->toBeGreaterThanOrEqual(time() + 299)
        ->and($resetExpiresAt)->toBeLessThanOrEqual(time() + 301);

    $this->postJson('/api/reset-password', [
        'reset_token' => $resetToken,
        'password' => 'NewPassword@123',
        'password_confirmation' => 'NewPassword@123',
    ])->assertExactJson([
        'code' => 200,
        'message' => 'Reset password berhasil disimpan.',
        'data' => null,
    ]);

    expect(Hash::check('NewPassword@123', DB::table('users')->where('id', $user->id)->value('password')))->toBeTrue()
        ->and(DB::table('password_reset_tokens')->count())->toBe(0);
});

it('preserves expired and missing OTP and reset-token responses', function () {
    $user = createPhaseSixUser(['email' => 'expired@simdatuk.test']);
    DB::table('otps')->insert([
        'email' => $user->email,
        'code' => '654321',
        'expire_at' => date('Y-m-d H:i:s', strtotime('-1 second')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-6 minutes')),
    ]);
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'verification_code' => 'expired-reset-token',
        'expire_at' => date('Y-m-d H:i:s', strtotime('-1 second')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-6 minutes')),
    ]);

    $this->postJson('/api/verify-otp', [
        'email' => $user->email,
        'otp' => '654321',
    ])->assertExactJson([
        'code' => 404,
        'message' => 'Kode OTP sudah kadaluarsa.',
        'data' => null,
    ]);

    $this->postJson('/api/reset-password', [
        'reset_token' => 'expired-reset-token',
        'password' => 'NewPassword@123',
        'password_confirmation' => 'NewPassword@123',
    ])->assertExactJson([
        'code' => 404,
        'message' => 'Reset token sudah kadaluarsa.',
        'data' => null,
    ]);

    $this->postJson('/api/verify-otp', [
        'email' => $user->email,
        'otp' => '000000',
    ])->assertExactJson([
        'code' => 404,
        'message' => 'Kode OTP tidak ditemukan.',
        'data' => null,
    ]);
});

it('enforces mapped role access while retaining its allow and deny envelopes', function () {
    $user = createPhaseSixUser();
    $permissionId = DB::table('permissions')->insertGetId([
        'name' => 'Master Data - Data Role Pengguna',
        'permitted_actions' => 'read',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('role_permissions')->insert([
        'role_id' => $user->role_id,
        'permission_id' => $permissionId,
        'create' => false,
        'read' => false,
        'update' => false,
        'delete' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Route::get('/api/roles', fn () => response()->json(['allowed' => true]))
        ->middleware(['auth:sanctum', 'role.access']);
    Sanctum::actingAs($user);

    $this->getJson('/api/roles')->assertExactJson([
        'code' => 403,
        'message' => "Access denied. You don't have permission to read Master Data - Data Role Pengguna.",
        'data' => null,
    ]);

    DB::table('role_permissions')->where('role_id', $user->role_id)->update(['read' => true]);

    $this->getJson('/api/roles')->assertOk()->assertExactJson(['allowed' => true]);
});
