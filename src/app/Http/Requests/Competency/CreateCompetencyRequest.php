<?php

namespace App\Http\Requests\Competency;

class CreateCompetencyRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'competencies.*.event_date' => 'nullable|date',
            'competencies.*.point' => 'nullable|numeric|in:1,2',
            'competencies.*.organizer' => 'nullable|max:512',
            'competencies.*.competency_document' => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
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
            'competencies.*.event_date.date' => 'Tanggal kompetensi harus berupa tanggal.',
            'competencies.*.point.numeric' => 'Penilaian kompetensi harus berupa angka.',
            'competencies.*.point.in' => 'Penilaian kompetensi harus diantara 1 atau 2.',
            'competencies.*.organizer.max' => 'Penyelenggara tidak boleh lebih dari 512 karakter.',
            'competencies.*.competency_document.file' => 'Sertifikat harus berupa file.',
            'competencies.*.competency_document.extensions' => 'Sertifikat harus berupa jpg, jpeg atau png.',
            'competencies.*.competency_document.max' => 'Ukuran sertifikat tidak boleh lebih dari 2MB.',
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
            'competencies.*.event_date' => [
                'description' => 'Refers to the Date of Employee Competency.',
                'example' => '2020-10-22',
            ],
            'competencies.*.point' => [
                'description' => 'Refers to the Point of Employee Competency. 1=Lulus, 2=Tidak Lulus',
                'example' => 1,
            ],
            'competencies.*.organizer' => [
                'description' => 'Refers to the Organizer of Employee Competency.',
                'example' => 'PPKASN',
            ],
            'competencies.*.competency_document' => [
                'description' => 'Refers to the Document of Employee Competency.',
                'example' => public_path('/img/logo.svg'),
            ],
        ];
    }
}

