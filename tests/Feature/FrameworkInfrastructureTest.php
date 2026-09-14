<?php

use App\Http\Middleware\ApiRateLimit;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\RoleBasedAccess;
use App\Http\Middleware\ValidateDeviceSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

it('registers the SIMDATUK middleware aliases and API middleware order', function () {
    $router = app(Router::class);
    $aliases = $router->getMiddleware();
    $apiGroup = $router->getMiddlewareGroups()['api'];

    expect($aliases)
        ->toMatchArray([
            'api.rate.limit' => ApiRateLimit::class,
            'permission' => CheckPermission::class,
            'role.access' => RoleBasedAccess::class,
            'device.session' => ValidateDeviceSession::class,
        ])
        ->and($apiGroup)->toBe([
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            SubstituteBindings::class,
        ]);

    $userRoute = collect($router->getRoutes()->getRoutes())
        ->first(fn ($route) => $route->uri() === 'api/user');

    expect($router->gatherRouteMiddleware($userRoute))
        ->not->toContain(ValidateDeviceSession::class)
        ->not->toContain(CheckPermission::class)
        ->not->toContain(RoleBasedAccess::class);
});

it('registers the named API limiter and query macros', function () {
    $request = Request::create('/api/probe', 'GET', server: [
        'REMOTE_ADDR' => '203.0.113.10',
    ]);

    $limit = RateLimiter::limiter('api')($request);

    expect($limit)
        ->toBeInstanceOf(Limit::class)
        ->and($limit->maxAttempts)->toBe(60)
        ->and($limit->decaySeconds)->toBe(60)
        ->and($limit->key)->toBe('203.0.113.10')
        ->and(Builder::hasMacro('insertTs'))->toBeTrue()
        ->and(Builder::hasMacro('insertGetIdTs'))->toBeTrue()
        ->and(Builder::hasMacro('updateTs'))->toBeTrue()
        ->and(Builder::hasMacro('deleteTs'))->toBeTrue();
});

it('preserves the custom limiter headers and 429 envelope', function () {
    $middleware = app(ApiRateLimit::class);
    $request = Request::create('/api/login', 'POST', server: [
        'REMOTE_ADDR' => '198.51.100.20',
        'SERVER_NAME' => 'simdatuk.test',
    ]);

    $firstResponse = $middleware->handle(
        $request,
        fn () => response()->json(['accepted' => true]),
        1,
        1,
    );

    expect($firstResponse->getStatusCode())->toBe(200)
        ->and($firstResponse->headers->get('X-RateLimit-Limit'))->toBe('1')
        ->and($firstResponse->headers->get('X-RateLimit-Remaining'))->toBe('0');

    $limitedResponse = $middleware->handle(
        $request,
        fn () => response()->json(['accepted' => true]),
        1,
        1,
    );

    expect($limitedResponse->getStatusCode())->toBe(429)
        ->and($limitedResponse->headers->get('X-RateLimit-Limit'))->toBe('1')
        ->and($limitedResponse->headers->get('X-RateLimit-Remaining'))->toBe('0')
        ->and($limitedResponse->getData(true))->toMatchArray([
            'code' => 429,
            'data' => null,
        ]);
});

it('applies the legacy security headers globally', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-XSS-Protection', '1; mode=block')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
        ->assertHeader('Content-Security-Policy');
});

it('preserves trusted proxy and trim string configuration', function () {
    Route::match(['GET', 'POST'], '/_phase4/request-configuration', function (Request $request) {
        return response()->json([
            'ip' => $request->ip(),
            'secure' => $request->isSecure(),
            'name' => $request->input('name'),
            'password' => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
        ]);
    });

    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.10'])
        ->withHeaders([
            'X-Forwarded-For' => '198.51.100.10',
            'X-Forwarded-Proto' => 'https',
        ])
        ->postJson('/_phase4/request-configuration', [
            'name' => '  SIMDATUK  ',
            'password' => '  secret  ',
            'password_confirmation' => '  secret  ',
        ])
        ->assertOk()
        ->assertJson([
            'ip' => '198.51.100.10',
            'secure' => true,
            'name' => 'SIMDATUK',
            'password' => '  secret  ',
            'password_confirmation' => '  secret  ',
        ])
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
});

it('locks the current role access mapping quirks without querying the database', function () {
    $middleware = new RoleBasedAccess;
    $requiredAction = new ReflectionMethod($middleware, 'getRequiredAction');
    $permissionFromRoute = new ReflectionMethod($middleware, 'getPermissionFromRoute');

    expect($requiredAction->invoke($middleware, Request::create('/api/institutions/1', 'POST')))
        ->toBe('create')
        ->and($requiredAction->invoke($middleware, Request::create('/api/institutions/1', 'PUT')))
        ->toBe('update')
        ->and($permissionFromRoute->invoke(
            $middleware,
            Request::create('/api/employees/1?type=2', 'GET'),
        ))->toBe('Data Pegawai - Non ASN')
        ->and($permissionFromRoute->invoke(
            $middleware,
            Request::create('/api/employees/1?type=unexpected', 'GET'),
        ))->toBe('Data Pegawai - ASN')
        ->and($permissionFromRoute->invoke(
            $middleware,
            Request::create('/api/export/employees/1', 'POST'),
        ))->toBeNull()
        ->and($permissionFromRoute->invoke(
            $middleware,
            Request::create('/api/recapitulations-asn/category', 'GET'),
        ))->toBe('Rekapitulasi - Komposisi Pegawai');
});

it('preserves the SIMDATUK production JSON exception envelopes', function () {
    config()->set('app.debug', false);

    Route::get('/_phase4/authorization-error', function () {
        throw new AuthorizationException;
    });

    Route::get('/_phase4/validation-error', function (Request $request) {
        $request->validate(['required_value' => ['required']]);
    });

    Route::get('/_phase4/internal-error', function () {
        throw new RuntimeException('sensitive detail');
    });

    $this->getJson('/_phase4/authorization-error')->assertExactJson([
        'code' => 401,
        'message' => 'Anda tidak memiliki hak akses!',
        'data' => null,
    ]);

    $this->getJson('/_phase4/validation-error')
        ->assertUnprocessable()
        ->assertJsonPath('code', 422)
        ->assertJsonPath('message', 'The required value field is required.')
        ->assertJsonPath('data.required_value.0', 'The required value field is required.');

    $this->getJson('/_phase4/internal-error')->assertExactJson([
        'code' => 400,
        'message' => 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!',
        'data' => null,
    ]);

    $this->withHeader('Accept', '*/*')
        ->get('/_phase4/internal-error')
        ->assertStatus(500)
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

    $this->getJson('/_phase4/not-found')->assertExactJson([
        'code' => 404,
        'message' => 'Endpoint tidak ditemukan!',
        'data' => null,
    ]);

    $this->getJson('/api/user')->assertExactJson([
        'code' => 401,
        'message' => 'Anda harus login terlebih dahulu!',
        'data' => null,
    ]);

    $this->postJson('/api/user')->assertExactJson([
        'code' => 405,
        'message' => 'Method yang digunakan salah!',
        'data' => null,
    ]);
});
