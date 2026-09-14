<?php

namespace App\Http\Requests\EmploymentType;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmploymentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => 'required|max:160',
            'status' => 'required|boolean',
            'type' => 'required|in:1,2,3',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tidak boleh kosong.',
            'name.max' => 'Nama tidak boleh lebih dari 160 karakter.',
            'status.required' => 'Status tidak boleh kosong.',
            'status.boolean' => 'Status harus berupa boolean',
            'type.required' => 'Tipe tidak boleh kosong.',
            'type.in' => 'Tipe harus diantara 1, 2 atau 3.',
        ];
    }
}
