<?php

namespace App\Http\Middleware;

use App\Models\UserDeviceSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDeviceSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'code' => 401,
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }

        $currentToken = $user->currentAccessToken();

        $deviceSession = UserDeviceSession::where('sanctum_token_id', $currentToken->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $deviceSession) {
            $currentToken->delete();

            return response()->json([
                'code' => 401,
                'message' => 'Anda login di perangkat lain. Silakan login kembali.',
                'data' => null,
                'error_code' => 'SESSION_INVALID',
            ], 401);
        }

        $currentDeviceId = $this->generateDeviceIdentifier($request);

        if ($deviceSession->device_identifier !== $currentDeviceId) {
            $deviceSession->delete();
            $currentToken->delete();

            return response()->json([
                'code' => 401,
                'message' => 'Anda login di perangkat lain. Silakan login kembali.',
                'data' => null,
                'error_code' => 'DEVICE_MISMATCH',
            ], 401);
        }

        $deviceSession->update(['last_activity_at' => now()]);

        return $next($request);
    }

    private function generateDeviceIdentifier(Request $request): string
    {
        return UserDeviceSession::generateDeviceIdentifier(
            $request->userAgent(),
            $this->getRealClientIp($request),
        );
    }

    private function getRealClientIp(Request $request): string
    {
        if ($request->header('CF-Connecting-IP')) {
            return $request->header('CF-Connecting-IP');
        }

        if ($request->header('X-Original-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Original-Forwarded-For'));
            $firstIp = trim($ips[0]);

            if ($this->isValidPublicIP($firstIp)) {
                return $firstIp;
            }
        }

        if ($request->header('X-Real-IP')) {
            $realIp = $request->header('X-Real-IP');

            if ($this->isValidPublicIP($realIp)) {
                return $realIp;
            }
        }

        if ($request->header('X-Forwarded-For')) {
            foreach (explode(',', $request->header('X-Forwarded-For')) as $ip) {
                $cleanIp = trim($ip);

                if ($this->isValidPublicIP($cleanIp)) {
                    return $cleanIp;
                }
            }
        }

        return $request->ip();
    }

    private function isValidPublicIP(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
