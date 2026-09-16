<?php

namespace App\Http\Requests\Role;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'role_id' => 'required|numeric|exists:roles,id',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'role_id.required' => 'Role tidak boleh kosong.',
            'role_id.numeric' => 'Role harus berupa angka.',
            'role_id.exists' => 'Role baru tidak terdaftar.',
        ];
    }
}
