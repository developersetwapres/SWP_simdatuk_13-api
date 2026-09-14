<?php

use App\Http\Middleware\ApiRateLimit;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\RoleBasedAccess;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\ValidateDeviceSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $middleware->statefulApi();
        $middleware->throttleApi('api');

        $middleware->alias([
            'api.rate.limit' => ApiRateLimit::class,
            'permission' => CheckPermission::class,
            'role.access' => RoleBasedAccess::class,
            'device.session' => ValidateDeviceSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->expectsJson());

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->wantsJson() || config('app.debug')) {
                return null;
            }

            $data = null;

            if ($exception instanceof QueryException) {
                $status = 400;
                $message = 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!';
            } elseif ($exception instanceof ValidationException) {
                $status = 422;
                $message = $exception->validator->errors()->all()[0];
                $data = $exception->validator->errors();
            } elseif ($exception instanceof AuthorizationException
                || ($exception instanceof AccessDeniedHttpException
                    && $exception->getPrevious() instanceof AuthorizationException)) {
                $status = 401;
                $message = 'Anda tidak memiliki hak akses!';
            } elseif ($exception instanceof AuthenticationException) {
                $status = 401;
                $message = 'Anda harus login terlebih dahulu!';
            } elseif ($exception instanceof NotFoundHttpException) {
                $status = 404;
                $message = 'Endpoint tidak ditemukan!';
            } elseif ($exception instanceof MethodNotAllowedHttpException) {
                $status = 405;
                $message = 'Method yang digunakan salah!';
            } else {
                $status = 400;
                $message = 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!';
            }

            return response()->json([
                'code' => $status,
                'message' => $message,
                'data' => $data,
            ], $status);
        });
    })->create();
