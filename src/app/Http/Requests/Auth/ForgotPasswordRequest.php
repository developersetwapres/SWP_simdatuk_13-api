<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
            'email' => 'required|email|exists:users,email',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email tidak boleh kosong.',
            'email.email' => 'Format email tidak sesuai.',
            'email.exists' => 'Terjadi kesalahan, silakan coba lagi.',
        ];
    }
}
