<?php

namespace App\Http\Requests\Note;

class CreateNoteRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
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
            'notes.*.description' => [
                'description' => 'Refers to the Description of Employee Note.',
                'example' => 'Catatan',
            ],
        ];
    }
}

