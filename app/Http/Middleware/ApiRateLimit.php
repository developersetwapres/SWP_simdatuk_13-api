<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    public function __construct(protected RateLimiter $limiter) {}

    public function handle(
        Request $request,
        Closure $next,
        ?int $maxAttempts = null,
        ?int $decayMinutes = null,
    ): Response {
        $maxAttempts ??= config('app.rate_limit_api_attempts', 60);
        $decayMinutes ??= config('app.rate_limit_api_decay_minutes', 1);

        $ip = $this->getRealIpAddress($request);
        $key = $this->resolveRequestSignature($request, $ip);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildException($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
        );
    }

    protected function getRealIpAddress(Request $request): string
    {
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        if ($request->header('X-Real-IP')) {
            return $request->header('X-Real-IP');
        }

        if ($request->header('X-Forwarded-For')) {
            return trim(explode(',', $request->header('X-Forwarded-For'))[0]);
        }

        if ($request->header('Client-IP')) {
            return $request->header('Client-IP');
        }

        return $request->ip();
    }

    protected function resolveRequestSignature(Request $request, string $ip): string
    {
        return sha1($request->method().'|'.$request->server('SERVER_NAME').'|'.$ip);
    }

    protected function buildException(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'code' => 429,
            'message' => 'Terlalu banyak permintaan. Silakan coba lagi dalam '.$retryAfter.' detik.',
            'data' => null,
        ], 429, [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);

        return $response;
    }

    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }
}
