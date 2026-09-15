<?php

namespace App\Http\Requests\Assessment;

class UpdateAssessmentEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'assessments.*.id'                         => 'nullable|numeric',
            'assessments.*.event_date'                 => 'nullable|date',
            'assessments.*.point'                      => 'nullable|numeric|in:1,2,3',
            'assessments.*.organizer'                  => 'nullable|max:512',
            'assessments.*.assessment_document'        => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'assessments.*.delete_assessment_document' => 'nullable|boolean',
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
            'assessments.*.id.numeric' => 'Assessments ID harus berupa angka.',
            'assessments.*.event_date.date' => 'Tanggal assessment harus berupa tanggal.',
            'assessments.*.point.numeric' => 'Penilaian assessment harus berupa angka.',
            'assessments.*.point.in' => 'Penilaian assessment harus diantara 1, 2 atau 3.',
            'assessments.*.organizer.max' => 'Penyelenggara tidak boleh lebih dari 512 karakter.',
            'assessments.*.assessment_document.file' => 'Sertifikat harus berupa file.',
            'assessments.*.assessment_document.extensions' => 'Sertifikat harus berupa jpg, jpeg atau png.',
            'assessments.*.assessment_document.max' => 'Ukuran sertifikat tidak boleh lebih dari 2MB.',
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
            'assessments.*.id' => [
                'description' => 'Refers to the ID of Assessment.',
                'example' => 1,
            ],
            'assessments.*.event_date' => [
                'description' => 'Refers to the Date of Employee Assessment.',
                'example' => '2020-10-22',
            ],
            'assessments.*.point' => [
                'description' => 'Refers to the Point of Employee Assessment. 1=Kurang Memenuhi Syarat, 2=Masih Memenuhi Syarat, 3=Memenuhi Syarat',
                'example' => 1,
            ],
            'assessments.*.organizer' => [
                'description' => 'Refers to the Organizer of Employee Assessment.',
                'example' => 'PPKASN',
            ],
            'assessments.*.assessment_document' => [
                'description' => 'Refers to the Document of Employee Assessment.',
                'example' => public_path('/img/logo.svg'),
            ],
            'assessments.*.delete_assessment_document' => [
                'description' => 'Refers to the Status of Delete assessment document.',
                'example' => false,
            ],
        ];
    }
}

