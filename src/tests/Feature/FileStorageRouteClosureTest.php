<?php

use App\Helpers\Document;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

it('registers the three public Phase 24 routes with source handlers and order', function () {
    $uris = ['api', 'api/image/{path}', 'api/test-s3'];
    $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => in_array($route->uri(), $uris, true))
        ->map(fn ($route): array => [$route->methods()[0], $route->uri(), $route->getActionName(), $route->middleware()])
        ->values()->all();

    expect($routes)->toHaveCount(3)
        ->and($routes[0][0])->toBe('GET')->and($routes[0][1])->toBe('api')->and($routes[0][3])->toBe(['api'])
        ->and($routes[1])->toBe(['GET', 'api/image/{path}', 'App\\Http\\Controllers\\EmployeeController@image', ['api']])
        ->and($routes[2][0])->toBe('GET')->and($routes[2][1])->toBe('api/test-s3')->and($routes[2][3])->toBe(['api']);
});

it('returns the exact public plain-text API probe', function () {
    $this->get('/api')->assertOk()->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertSeeText('api enabled!', false);
    expect($this->get('/api')->getContent())->toBe('api enabled!');
});

it('streams image PDF nested and encoded object paths from fake s3 without disposition', function (string $path, string $mime, string $contents) {
    Storage::fake('s3');
    Storage::disk('s3')->put($path, $contents);
    $url = '/api/image/'.str_replace('%2F', '/', rawurlencode($path));
    $response = $this->get($url)->assertOk()->assertHeader('content-type', $mime);
    expect($response->headers->has('content-disposition'))->toBeFalse()
        ->and($response->streamedContent())->toBe($contents);
})->with([
    'PNG' => ['phase24/image.png', 'image/png', "\x89PNG\r\n\x1a\nphase24"],
    'PDF' => ['phase24/report.pdf', 'application/pdf', '%PDF-1.4 phase24'],
    'nested spaced path' => ['phase24/nested/file name.png', 'image/png', "\x89PNG\r\n\x1a\nspace"],
]);

it('returns 404 for a missing s3 object and rejects traversal-like paths', function () {
    Storage::fake('s3');
    $this->get('/api/image/phase24/missing.png')->assertNotFound();
    expect($this->get('/api/image/../secret')->status())->toBe(404)
        ->and($this->get('/api/image/%2e%2e%2fsecret')->status())->toBe(404);
});

it('characterizes encoded and duplicate separator behavior', function () {
    Storage::fake('s3');
    Storage::disk('s3')->put('phase24/nested/encoded name.png', "\x89PNG\r\n\x1a\nencoded");
    $statuses = [
        'encoded_separator' => $this->get('/api/image/phase24%2Fnested%2Fencoded%20name.png')->getStatusCode(),
        'leading_slash' => $this->get('/api/image//phase24/nested/encoded%20name.png')->getStatusCode(),
        'duplicate_slash' => $this->get('/api/image/phase24//nested/encoded%20name.png')->getStatusCode(),
    ];
    expect($statuses)->toBe(['encoded_separator' => 200, 'leading_slash' => 200, 'duplicate_slash' => 200]);
});

it('preserves the public test-s3 success and exception contracts with no real network', function () {
    Storage::fake('s3');
    $this->getJson('/api/test-s3')->assertOk()->assertExactJson(['status' => true, 'message' => 'S3 connected']);

    Storage::shouldReceive('disk')->once()->with('s3')->andThrow(new RuntimeException('phase24 s3 unavailable'));
    $this->getJson('/api/test-s3')->assertStatus(500)
        ->assertExactJson(['status' => false, 'message' => 'phase24 s3 unavailable']);
});

it('keeps Document generated URLs resolvable through the public image route', function () {
    Storage::fake('s3');
    $paths = [
        'profiles/phase24-profile.png', 'education/phase24-degree.pdf',
        'histories/phase24-decree.pdf', 'training/phase24-certificate.pdf',
        'leave/phase24-leave.pdf', 'assessment/phase24-assessment.pdf',
        'competency/phase24-competency.pdf', 'talent/phase24-talent.pdf',
    ];
    $document = new class
    {
        use Document;

        public function url(?string $path): string
        {
            return $this->getDocument($path);
        }
    };

    foreach ($paths as $path) {
        Storage::disk('s3')->put($path, '%PDF-1.4 '.$path);
        $url = $document->url($path);
        expect($url)->toEndWith('/api/image/'.$path);
        $this->get(parse_url($url, PHP_URL_PATH))->assertOk();
    }
    expect($document->url(null))->toEndWith('/img/profile.jpg');
});

it('documents the complete S3 environment and filesystem configuration contract', function () {
    $example = file_get_contents(base_path('.env.example'));
    $config = file_get_contents(config_path('filesystems.php'));
    foreach (['AWS_ACCESS_KEY_ID=', 'AWS_SECRET_ACCESS_KEY=', 'AWS_DEFAULT_REGION=', 'AWS_BUCKET=', 'AWS_URL=', 'AWS_ENDPOINT=', 'AWS_USE_PATH_STYLE_ENDPOINT='] as $key) {
        expect($example)->toContain($key);
    }
    expect($config)->toContain("'driver' => 's3'")
        ->toContain("env('AWS_URL')")
        ->toContain("env('AWS_ENDPOINT')")
        ->toContain("env('AWS_USE_PATH_STYLE_ENDPOINT', false)")
        ->toContain("'throw' => false");
});
