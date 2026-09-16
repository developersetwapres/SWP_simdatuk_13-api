<?php

namespace App\Http\Requests\Role;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => 'required|unique:roles,name,'.$this->id,
            'permissions' => 'required|array',
            'permissions.*.id' => 'required|numeric',
            'permissions.*.permitted_actions' => 'required|string',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tidak boleh kosong.',
            'name.unique' => 'Nama sudah digunakan.',
            'permissions.required' => 'Permission tidak boleh kosong.',
            'permissions.array' => 'Permission harus berupa array.',
            'permissions.*.id.required' => 'Permission id tidak boleh kosong.',
            'permissions.*.id.numeric' => 'Permission id harus berupa angka.',
            'permissions.*.permitted_actions.required' => 'Permission permitted actions tidak boleh kosong.',
            'permissions.*.permitted_actions.string' => 'Permission permitted actions harus berupa string.',
        ];
    }
}
