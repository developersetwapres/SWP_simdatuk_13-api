<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $rules = [
            'username' => 'required|exists:users,username',
            'password' => 'required',
        ];

        if (config('app.env') === 'production') {
            $rules['recaptcha_token'] = 'required';
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username tidak boleh kosong.',
            'username.exists' => 'Terjadi kesalahan, silakan coba lagi.',
            'password.required' => 'Kata sandi tidak boleh kosong.',
            'recaptcha_token.required' => 'Token recaptcha tidak boleh kosong.',
        ];
    }
}
