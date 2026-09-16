<?php

use App\Mail\ForgotPassword;
use App\Mail\RegisterVerification;
use App\Http\Controllers\ImportEmployeeController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('keeps Composer lifecycle scripts free of database schema commands', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $scripts = json_encode($composer['scripts'], JSON_THROW_ON_ERROR);

    expect($scripts)
        ->not->toContain('artisan migrate')
        ->not->toContain('migrate:fresh')
        ->not->toContain('db:wipe')
        ->not->toContain('db:seed')
        ->not->toContain('migrate --graceful');

    expect(file_get_contents(base_path('README.md')))
        ->toContain('DO NOT RUN `php artisan migrate`')
        ->toContain('No database migration or seeding command belongs in this sequence.');
});

it('documents a production-safe neutral environment contract', function () {
    $example = file_get_contents(base_path('.env.example'));

    expect($example)
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('TRUSTED_PROXIES=CHANGE_ME_PROXY_IP_OR_CIDR')
        ->toContain('DB_CONNECTION=mysql')
        ->toContain('DB_DATABASE=CHANGE_ME_IMPORTED_SIMDATUK_DATABASE')
        ->toContain('CACHE_STORE=file')
        ->toContain('SESSION_DRIVER=file')
        ->toContain('QUEUE_CONNECTION=sync')
        ->toContain('MAIL_MAILER=smtp')
        ->toContain('SIMSDM_URL=')
        ->toContain('RECAPTCHA_SECRET_KEY=')
        ->toContain('AWS_BUCKET=');
});

it('keeps runtime integration settings compatible with configuration caching', function () {
    $auth = file_get_contents(app_path('Http/Controllers/AuthController.php'));
    $synchronization = file_get_contents(app_path('Http/Controllers/SynchronizationController.php'));

    expect($auth)->not->toContain('env(')
        ->and($synchronization)->not->toContain('env(')
        ->and(config('services.recaptcha'))->toHaveKeys(['secret', 'verify_url'])
        ->and(config('services.simsdm'))->toHaveKeys(['url', 'client_id', 'client_secret']);

    expect(config('trustedproxy.proxies'))->not->toBeEmpty();
});

it('does not write SIMSDM response bodies or credentials to normal failure logs', function () {
    $synchronization = file_get_contents(app_path('Http/Controllers/SynchronizationController.php'));

    expect($synchronization)
        ->not->toContain("['response' => \$response->body()]")
        ->toContain("['status' => \$response->status()]");
});

it('binds worksheet history values instead of interpolating them into SQL', function () {
    $capturedSql = null;
    $capturedBindings = null;

    DB::shouldReceive('select')->once()->withArgs(function (string $sql, array $bindings) use (&$capturedSql, &$capturedBindings): bool {
        $capturedSql = $sql;
        $capturedBindings = $bindings;

        return true;
    })->andReturn([(object) ['id' => 42]]);

    $method = new ReflectionMethod(ImportEmployeeController::class, 'historySave');
    $result = $method->invoke(new ImportEmployeeController, [['name' => "O'Reilly"]], 'training_histories', 0, 'training_history_id');

    expect($capturedSql)->toContain('LOWER(REPLACE(`name`')
        ->not->toContain("O'Reilly")
        ->and($capturedBindings)->toBe(["o'reilly"])
        ->and($result)->toBe([['training_history_id' => 42]]);
});

it('accounts for all application API routes and preserves critical precedence', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => $route->uri() === 'api' || str_starts_with($route->uri(), 'api/'))
        ->values();

    expect($routes)->toHaveCount(120);

    $orderedUris = $routes->pluck('uri')->all();
    $position = fn (string $uri): int => array_search($uri, $orderedUris, true);

    expect($position('api/employees/import'))->toBeLessThan($position('api/employees/{id}'))
        ->and($position('api/employees/synchronization'))->toBeLessThan($position('api/employees/{id}'))
        ->and($position('api/training-histories/groups'))->toBeLessThan($position('api/training-histories/{id}'))
        ->and($position('api/positions/available-order'))->toBeLessThan($position('api/positions/{id}'));

    $route = fn (string $method, string $uri) => $routes->first(
        fn ($route): bool => in_array($method, $route->methods(), true) && $route->uri() === $uri,
    );

    expect($route('GET', 'api')->middleware())->toBe(['api'])
        ->and($route('GET', 'api/image/{path}')->middleware())->toBe(['api'])
        ->and($route('GET', 'api/test-s3')->middleware())->toBe(['api'])
        ->and($route('GET', 'api/employees/{id}')->getActionName())
        ->toBe('App\\Http\\Controllers\\EmployeeController@show')
        ->and($route('GET', 'api/employees/{id}')->middleware())
        ->toBe(['api', 'auth:sanctum', 'role.access']);
});

it('disables the public S3 diagnostic in production without accessing storage', function () {
    $previousEnvironment = $this->app->environment();
    $this->app->instance('env', 'production');
    Storage::shouldReceive('disk')->never();

    try {
        $this->getJson('/api/test-s3')->assertNotFound();
    } finally {
        $this->app->instance('env', $previousEnvironment);
    }
});

it('registers daily session cleanup without executing it', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains($event->command ?? '', 'sessions:clean'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *');
});

it('keeps migrated mailables synchronous for parity', function () {
    expect(is_subclass_of(ForgotPassword::class, ShouldQueue::class))->toBeFalse()
        ->and(is_subclass_of(RegisterVerification::class, ShouldQueue::class))->toBeFalse();
});
