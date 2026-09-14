<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'username', 'password', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function deviceSessions(): HasMany
    {
        return $this->hasMany(UserDeviceSession::class);
    }

    public function revokeOtherDeviceSessions(int|string|null $currentTokenId = null): bool
    {
        $tokensQuery = $this->tokens();

        if ($currentTokenId) {
            $tokensQuery->where('id', '!=', $currentTokenId);
        }

        $tokensQuery->delete();

        $sessionsQuery = $this->deviceSessions();

        if ($currentTokenId) {
            $sessionsQuery->where('sanctum_token_id', '!=', $currentTokenId);
        }

        $sessionsQuery->delete();

        return true;
    }

    public function generateToken(): string
    {
        $code = Str::random(40);
        $verificationCode = DB::table('password_reset_tokens')
            ->where('verification_code', $code)
            ->first();

        return $verificationCode ? $this->generateToken() : $code;
    }

    public function generateOtp(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $verificationCode = DB::table('otps')->where('code', $code)->first();

        return $verificationCode ? $this->generateOtp() : $code;
    }
}
