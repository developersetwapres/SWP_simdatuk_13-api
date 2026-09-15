<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'user_id' => 'required|numeric',
            'username' => 'required|min:6|max:30|unique:users,username,'.$this->id,
            'email' => 'required|email|unique:users,email,'.$this->id,
            'role_id' => 'required|numeric',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_id.required' => 'User ID tidak boleh kosong.',
            'user_id.numeric' => 'User ID harus berupa angka.',
            'username.required' => 'Username tidak boleh kosong.',
            'username.min' => 'Username tidak boleh kurang dari 6 karakter',
            'username.max' => 'Username tidak boleh lebih dari 30 karakter',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Email tidak boleh kosong',
            'email.email' => 'Format email tidak sesuai.',
            'email.unique' => 'Email sudah digunakan.',
            'role_id.required' => 'Role id tidak boleh kosong.',
            'role_id.numeric' => 'Role id harus berupa angka.',
        ];
    }
}
