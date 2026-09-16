<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'device_identifier',
    'device_name',
    'ip_address',
    'user_agent',
    'sanctum_token_id',
    'last_activity_at',
])]
class UserDeviceSession extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateDeviceIdentifier(string $userAgent, string $ipAddress): string
    {
        return hash('sha256', $userAgent.'|'.$ipAddress.'|'.config('app.key'));
    }

    public static function isDeviceRegistered(int $userId, string $deviceIdentifier): bool
    {
        return self::query()
            ->where('user_id', $userId)
            ->where('device_identifier', $deviceIdentifier)
            ->exists();
    }

    public static function cleanExpiredSessions(): int
    {
        return self::query()
            ->where('last_activity_at', '<', now()->subDays(30))
            ->delete();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }
}
