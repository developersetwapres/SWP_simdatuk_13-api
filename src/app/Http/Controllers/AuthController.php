<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\ForgotPassword;
use App\Models\User;
use App\Models\UserDeviceSession;
use App\Traits\SingleDeviceLogin;
use Exception;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    use SingleDeviceLogin;

    public function __construct(protected Request $request) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('username', $this->request->username)->first();

        if (! $user || ! Hash::check($this->request->password, $user->password)) {
            return $this->response(401, 'Username atau password yang anda masukkan salah.');
        } elseif (is_null($user->role_id)) {
            return $this->response(401, 'Akun anda tidak memiliki peran yang valid, silakan hubungi admin.');
        } elseif ($user->status != true) {
            return $this->response(401, 'Akun anda tidak aktif, silakan hubungi admin.');
        }

        if (config('app.env') === 'production') {
            $recaptchaValidation = $this->recaptchaValidation($this->request->recaptcha_token);

            if ($recaptchaValidation->getStatusCode() !== 200) {
                return $recaptchaValidation;
            }
        }

        $tokenResult = $this->handleSingleDeviceLogin($user, $this->request);
        $userData = $this->getUserData($user->id);

        return response()->json([
            'code' => 200,
            'message' => 'Pengguna berhasil login.',
            'token' => $tokenResult->plainTextToken,
            'user' => $userData,
            'device_info' => [
                'device_type' => $this->getDeviceName($this->request),
                'login_time' => now()->toISOString(),
                'previous_sessions_revoked' => true,
            ],
        ], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = DB::table('users')
            ->select('role_id')
            ->where('email', $this->request->email)
            ->first();

        if (is_null($user->role_id)) {
            return $this->response(404, 'Terjadi kesalahan, silakan coba lagi.');
        }

        $verificationCode = (new User)->generateOtp();
        $this->request->merge(['verification_code' => $verificationCode]);

        DB::table('otps')->insert([
            'email' => $this->request->email,
            'code' => $verificationCode,
            'expire_at' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            Mail::to($this->request->email)->send(new ForgotPassword($this->request));
        } catch (Exception) {
            return $this->response(404, 'Gagal mengirimkan email, silakan hubungi admin.');
        }

        return $this->response(200, 'Email sudah dikirim.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = DB::table('password_reset_tokens')
            ->where('verification_code', $this->request->reset_token)
            ->select('email', 'expire_at')
            ->first();

        if ($user) {
            if ($user->expire_at >= date('Y-m-d H:i:s')) {
                DB::table('users')
                    ->where('email', $user->email)
                    ->update(['password' => Hash::make($this->request->password)]);

                DB::table('password_reset_tokens')
                    ->where('verification_code', $this->request->reset_token)
                    ->delete();

                return $this->response(200, 'Reset password berhasil disimpan.');
            }

            DB::table('password_reset_tokens')
                ->where('expire_at', '<', date('Y-m-d H:i:s'))
                ->delete();

            return $this->response(404, 'Reset token sudah kadaluarsa.');
        }

        return $this->response(404, 'Terjadi kesalahan, silakan coba lagi.');
    }

    public function logout(): JsonResponse
    {
        $user = $this->request->user();
        $currentToken = $user->currentAccessToken();

        UserDeviceSession::query()
            ->where('sanctum_token_id', $currentToken->id)
            ->delete();

        $currentToken->delete();

        return $this->response(200, 'Pengguna berhasil logout.');
    }

    public function logoutAllDevices(): JsonResponse
    {
        $this->request->user()->revokeOtherDeviceSessions();

        return $this->response(200, 'Berhasil logout dari semua perangkat.');
    }

    public function getActiveSessions(): JsonResponse
    {
        $sessions = UserDeviceSession::query()
            ->where('user_id', $this->request->user()->id)
            ->orderBy('last_activity_at', 'desc')
            ->get(['device_name', 'ip_address', 'last_activity_at', 'created_at']);

        return response()->json([
            'code' => 200,
            'message' => 'Berhasil mengambil data sesi aktif.',
            'data' => $sessions,
        ], 200);
    }

    public function verifyOtp(OtpVerifyRequest $request): JsonResponse
    {
        $otp = DB::table('otps')
            ->where('email', $this->request->email)
            ->where('code', $this->request->otp)
            ->select('email', 'expire_at')
            ->first();

        if ($otp) {
            if ($otp->expire_at >= date('Y-m-d H:i:s')) {
                $resetToken = (new User)->generateToken();
                $this->request->merge(['token' => $resetToken]);

                DB::table('password_reset_tokens')->insert([
                    'email' => $otp->email,
                    'verification_code' => $resetToken,
                    'expire_at' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                DB::table('otps')->where('code', $this->request->otp)->delete();

                return response()->json([
                    'code' => 200,
                    'message' => 'Kode OTP berhasil diverifikasi.',
                    'reset_token' => $resetToken,
                ], 200);
            }

            DB::table('otps')
                ->where('expire_at', '<', date('Y-m-d H:i:s'))
                ->delete();

            return $this->response(404, 'Kode OTP sudah kadaluarsa.');
        }

        return $this->response(404, 'Kode OTP tidak ditemukan.');
    }

    private function getUserData(int $userId): object
    {
        $user = DB::table('users')
            ->select(
                'users.id',
                'users.email',
                'users.username',
                'users.photo_profile',
                'users.employee_id_number',
                'users.employee_registration_number',
            )
            ->where('users.id', $userId)
            ->first();

        $user->photo_profile = $this->getDocument($user->photo_profile, true);

        $role = DB::table('roles')
            ->join('users', 'roles.id', '=', 'users.role_id')
            ->select('roles.id', 'roles.name')
            ->where('users.id', $user->id)
            ->first();

        $user->role = $role;

        $user->permissions = DB::table('permissions as p')
            ->leftJoin('role_permissions as rp', function (JoinClause $join) use ($role): void {
                $join->on('p.id', '=', 'rp.permission_id')
                    ->where('rp.role_id', '=', $role->id);
            })
            ->select(
                'p.id',
                'p.name',
                DB::raw('COALESCE(rp.`create`, 0) as `create`'),
                DB::raw('COALESCE(rp.`read`, 0) as `read`'),
                DB::raw('COALESCE(rp.`update`, 0) as `update`'),
                DB::raw('COALESCE(rp.`delete`, 0) as `delete`'),
            )
            ->orderBy('p.id')
            ->get();

        return $user;
    }

    private function recaptchaValidation(mixed $token): JsonResponse
    {
        $response = Http::asForm()->post(config('services.recaptcha.verify_url'), [
            'secret' => config('services.recaptcha.secret'),
            'response' => $token,
        ]);

        return $this->recaptchaResponse($response);
    }

    private function recaptchaResponse(HttpClientResponse $response): JsonResponse
    {
        if (! $response->json('success')) {
            return $this->response(404, 'reCAPTCHA verification failed.');
        }

        return $this->response(200, 'success');
    }
}
