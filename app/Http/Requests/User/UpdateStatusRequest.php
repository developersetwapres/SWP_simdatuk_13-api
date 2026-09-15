<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'id' => 'required|numeric',
            'status' => 'required|boolean',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'id.required' => 'ID tidak boleh kosong.',
            'id.numeric' => 'ID harus berupa angka.',
            'status.required' => 'Status tidak boleh kosong.',
            'status.boolean' => 'ID harus berupa boolean.',
        ];
    }
}
