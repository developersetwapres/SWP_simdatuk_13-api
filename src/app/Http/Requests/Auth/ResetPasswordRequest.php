<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
        return [
            'reset_token' => 'required',
            'password' => 'required|min:8|confirmed|regex:/[a-z]/|regex:/[A-Z]/|regex:/[@$!%*#?&]/',
            'password_confirmation' => 'required',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reset_token.required' => 'Reset token tidak boleh kosong.',
            'password.required' => 'Password tidak boleh kosong.',
            'password.min' => 'Password minimal memiliki 8 karakter.',
            'password.regex' => 'Password harus mengandung huruf besar, kecil, dan spesial karakter.',
            'password.confirmed' => 'Konfirmasi password harus sama.',
            'password_confirmation.required' => 'Konfirmasi password tidak boleh kosong.',
        ];
    }
}
