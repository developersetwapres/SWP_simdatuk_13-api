<?php

namespace App\Http\Requests\GradeHistory;

class UpdateGradeHistoryEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'grades.*.id'                     => 'nullable|numeric',
            'grades.*.grade_id'               => 'nullable|numeric',
            'grades.*.effective_date'         => 'nullable|date',
            'grades.*.decree_name'            => 'nullable',
            'grades.*.decree_number'          => 'nullable|max:160',
            'grades.*.type_of_decree'         => 'nullable',
            'grades.*.decree_date'            => 'nullable',
            'grades.*.description'            => 'nullable',
            'grades.*.decree_document'        => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'grades.*.status'                 => 'nullable|boolean',
            'grades.*.delete_decree_document' => 'nullable|boolean',
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
            'grades.*.id.numeric' => 'ID harus berupa angka.',
            'grades.*.grade_id.numeric' => 'Golongan harus berupa angka.',
            'grades.*.effective_date.date' => 'Tanggal efektif golongan harus berupa tanggal.',
            'grades.*.decree_number.max' => 'Nomor SK golongan tidak beloh lebih dari 160 karakter.',
            'grades.*.decree_document.file' => 'SK golongan harus berupa file.',
            'grades.*.decree_document.extensions' => 'SK golongan harus berupa jpg, jpeg atau png.',
            'grades.*.decree_document.max' => 'SK golongan tidak boleh lebih dari 2MB.',
            'grades.*.status.boolean' => 'Status harus berupa boolean.',
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
            'grades.*.id' => [
                'description' => 'Refers to the User ID of List Employee Recognition.',
                'example' => 1,
            ],
            'grades.*.grade_id' => [
                'description' => 'Refers to the ID Grade of Employee Grades.',
                'example' => 1,
            ],
            'grades.*.effective_date' => [
                'description' => 'Refers to the Effective Date of Employee Grades.',
                'example' => '2020-10-22',
            ],
            'grades.*.decree_number' => [
                'description' => 'Refers to the Decree Number of Employee Grades.',
                'example' => 'Nomor 50 Tahun 2008',
            ],
            'grades.*.decree_document' => [
                'description' => 'Refers to the Decree Document of Employee Grades.',
                'example' => public_path('/img/logo.svg'),
            ],
            'grades.*.status' => [
                'description' => 'Refers to the Status of Employee Grades.',
                'example' => 1,
            ],
            'grades.*.delete_decree_document' => [
                'description' => 'Refers to the Status of Delete decree document.',
                'example' => false,
            ],
            'grades.*.decree_name' => [
                'description' => 'Refers to the Decree Name of Employee Grades.',
                'example' => 'Nomor 50 Tahun 2008',
            ],
            'grades.*.type_of_decree' => [
                'description' => 'Refers to the Type of Decree of Employee Grades.',
                'example' => 1,
            ],
            'grades.*.decree_date' => [
                'description' => 'Refers to the Decree Date of Employee Grades.',
                'example' => '2020-10-22',
            ],
            'grades.*.description' => [
                'description' => 'Refers to the Description of Employee Grades.',
                'example' => 'Kenaikan Jabatan',
            ],
        ];
    }
}

