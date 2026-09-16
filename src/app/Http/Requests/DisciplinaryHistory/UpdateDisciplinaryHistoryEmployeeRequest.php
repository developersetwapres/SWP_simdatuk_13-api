<?php

namespace App\Http\Requests\DisciplinaryHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisciplinaryHistoryEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'disciplinaries.*.id' => 'numeric|nullable',
            'disciplinaries.*.grade' => 'nullable|max:160',
            'disciplinaries.*.position' => 'nullable',
            'disciplinaries.*.disciplinary_id' => 'nullable|numeric',
            'disciplinaries.*.decree_number' => 'nullable|max:160',
            'disciplinaries.*.date_of_decree' => 'nullable|date',
            'disciplinaries.*.start_date' => 'nullable|date',
            'disciplinaries.*.end_date' => 'nullable|date',
            'disciplinaries.*.authorizing_officer' => 'nullable|max:160',
            'disciplinaries.*.name_of_authorizing_officer' => 'nullable|max:160',
            'disciplinaries.*.description' => 'nullable',
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
            'disciplinaries.*.id.numeric' => 'ID harus berupa angka.',
            'disciplinaries.*.grade.max' => 'Golongan tidak boleh lebih dari 160 karakter.',
            'disciplinaries.*.disciplinary_id.numeric' => 'Jenis hukuman harus berupa angka.',
            'disciplinaries.*.decree_number.max' => 'No SK hukuman tidak boleh lebih dari 160 karakter.',
            'disciplinaries.*.date_of_decree.date' => 'Tanggal SK harus berupa tanggal.',
            'disciplinaries.*.start_date.date' => 'Tanggal mulai hukuman harus berupa tanggal.',
            'disciplinaries.*.end_date.date' => 'Tanggal selesai hukuman harus berupa tanggal.',
            'disciplinaries.*.authorizing_officer.max' => 'Pejabat berwenang tidak boleh lebih dari 160 karakter.',
            'disciplinaries.*.name_of_authorizing_officer' => 'Nama pejabat berwenang tidak boleh lebih dari 160 karakter.',
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
            'disciplinaries.*.id' => [
                'description' => 'Refers to the ID of Employee Disciplinary.',
                'example' => 1,
            ],
            'disciplinaries.*.grade' => [
                'description' => 'Refers to the Golongan of Employee Disciplinary.',
                'example' => 'Penata (III/c)',
            ],
            'disciplinaries.*.position' => [
                'description' => 'Refers to the Jabatan of Employee Disciplinary.',
                'example' => 'Kepala Subbagian Administrasi',
            ],
            'disciplinaries.*.disciplinary_id' => [
                'description' => 'Refers to the Jenis Hukuman of Employee Disciplinary.',
                'example' => 1,
            ],
            'disciplinaries.*.decree_number' => [
                'description' => 'Refers to the No SK of Employee Disciplinary.',
                'example' => 'Nomor 112 Tahun 2023',
            ],
            'disciplinaries.*.date_of_decree' => [
                'description' => 'Refers to the Tanggal SK of Employee Disciplinary.',
                'example' => '2023-10-22',
            ],
            'disciplinaries.*.start_date' => [
                'description' => 'Refers to the Tanggal Mulai Hukuman of Employee Disciplinary.',
                'example' => '2023-10-22',
            ],
            'disciplinaries.*.end_date' => [
                'description' => 'Refers to the Tanggal Selesai Hukuman of Employee Disciplinary.',
                'example' => '2024-10-22',
            ],
            'disciplinaries.*.authorizing_officer' => [
                'description' => 'Refers to the Pejabat Berwenang of Employee Disciplinary.',
                'example' => 'Deputi Bidang Administrasi',
            ],
            'disciplinaries.*.name_of_authorizing_officer' => [
                'description' => 'Refers to the Nama Pejabat Berwenang of Employee Disciplinary.',
                'example' => 'Sapto Harjono Wahjoe Sedjati, S.Sos., M.A.',
            ],
            'disciplinaries.*.description' => [
                'description' => 'Refers to the Deskripsi of Employee Disciplinary.',
                'example' => 'Lorem ipsum',
            ],
        ];
    }
}

