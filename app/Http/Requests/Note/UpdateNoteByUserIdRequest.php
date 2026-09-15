<?php

namespace App\Http\Requests\Note;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteByUserIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'notes.*.id' => 'nullable|numeric',
            'notes.*.description' => 'nullable',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'notes.*.id.numeric' => 'Id harus berupa angka.',
        ];
    }
}
