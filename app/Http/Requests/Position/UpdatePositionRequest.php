<?php

namespace App\Http\Requests\Position;

use App\Http\Requests\PositionEchelon\UpdatePositionEchelonRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return array_merge([
            'name' => 'required|max:512',
            'parent_id' => 'numeric|nullable',
            'available' => 'numeric',
            'type' => 'required|in:1,2,3',
            'entity' => 'required|in:1,2',
            'order' => 'required|numeric',
            'status' => 'boolean|nullable',
            'deleted_echelon_id' => 'nullable|array',
        ], UpdatePositionEchelonRequest::rules());
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return array_merge([
            'name.required' => 'Nama Jabatan tidak boleh kosong.',
            'name.max' => 'Nama Jabatan tidak boleh lebih dari 512 karakter.',
            'parent_id.numeric' => 'Parent ID harus berupa angka.',
            'available.numeric' => 'Available harus berupa angka.',
            'type.required' => 'Type tidak boleh kosong.',
            'type.in' => 'Type harus diantara 1, 2, atau 3.',
            'entity.required' => 'Entity tidak boleh kosong.',
            'entity.in' => 'Entity harus diantara 1 atau 2.',
            'order.required' => 'Order tidak boleh kosong.',
            'order.numeric' => 'Order harus berupa angka.',
            'status.boolean' => 'Status harus berupa boolean.',
            'deleted_echelon_id.array' => 'Format deleted_echelon_id adalah array, contoh [1,2,3].',
        ], UpdatePositionEchelonRequest::messages());
    }
}
