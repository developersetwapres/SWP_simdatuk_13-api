<?php

namespace App\Traits;

use App\Models\User;
use App\Models\UserDeviceSession;
use Illuminate\Http\Request;
use Laravel\Sanctum\NewAccessToken;

trait SingleDeviceLogin
{
    public function handleSingleDeviceLogin(User $user, Request $request): NewAccessToken
    {
        $deviceIdentifier = $this->generateDeviceIdentifier($request);

        $user->revokeOtherDeviceSessions();

        $tokenResult = $user->createToken('web');

        UserDeviceSession::create([
            'user_id' => $user->id,
            'device_identifier' => $deviceIdentifier,
            'device_name' => $this->getDeviceName($request),
            'ip_address' => $this->getRealClientIp($request),
            'user_agent' => $request->userAgent(),
            'sanctum_token_id' => $tokenResult->accessToken->getKey(),
            'last_activity_at' => now(),
        ]);

        return $tokenResult;
    }

    public function debugIPHeaders(Request $request): array
    {
        return [
            'request_ip' => $request->ip(),
            'cf_connecting_ip' => $request->header('CF-Connecting-IP'),
            'x_real_ip' => $request->header('X-Real-IP'),
            'x_forwarded_for' => $request->header('X-Forwarded-For'),
            'x_original_forwarded_for' => $request->header('X-Original-Forwarded-For'),
            'remote_addr' => $request->server('REMOTE_ADDR'),
            'http_client_ip' => $request->server('HTTP_CLIENT_IP'),
            'detected_real_ip' => $this->getRealClientIp($request),
        ];
    }

    public function updateDeviceActivity(Request $request, int|string $tokenId): int
    {
        return UserDeviceSession::query()
            ->where('sanctum_token_id', $tokenId)
            ->update(['last_activity_at' => now()]);
    }

    public function isDeviceSessionValid(User $user, Request $request): bool
    {
        $deviceIdentifier = $this->generateDeviceIdentifier($request);

        return UserDeviceSession::query()
            ->where('user_id', $user->id)
            ->where('device_identifier', $deviceIdentifier)
            ->exists();
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

    private function generateDeviceIdentifier(Request $request): string
    {
        return UserDeviceSession::generateDeviceIdentifier(
            (string) $request->userAgent(),
            $this->getRealClientIp($request),
        );
    }

    private function getDeviceName(Request $request): string
    {
        $userAgent = (string) $request->userAgent();

        if (str_contains($userAgent, 'Mobile')) {
            return 'Mobile Device';
        }

        if (str_contains($userAgent, 'Tablet')) {
            return 'Tablet Device';
        }

        return 'Desktop/Laptop';
    }
}
