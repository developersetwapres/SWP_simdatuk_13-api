<?php

namespace App\Http\Requests\Note;

class UpdateNoteEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'notes.*.id' => 'nullable|numeric',
            'notes.*.description' => 'nullable|max:160',
        ];
    }

    /**
     * Return error messages
     *
     * @return array
     */
    public static function messages(): array
    {
        return [
            'notes.*.id.numeric' => 'Note ID harus berupa angka.',
            'notes.*.description.max' => 'Catatan tidak boleh lebih dari 160 karakter.',
        ];
    }

    /**
     * Description for scribe
     *
     * @return array
     */
    public static function bodyParameters(): array
    {
        return [
            'notes.*.id' => [
                'description' => 'Refers to the ID of Note.',
                'example' => 1,
            ],
            'notes.*.description' => [
                'description' => 'Refers to the Description of Employee Note.',
                'example' => 'Catatan',
            ],
        ];
    }
}

