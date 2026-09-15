<?php

namespace App\Http\Requests\PositionHistory;

class UpdatePositionHistoryEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'positions.*.id' => 'numeric|nullable',
            'positions.*.position' => 'nullable',
            'positions.*.group_id' => 'nullable|numeric',
            'positions.*.echelon' => 'nullable|numeric|exists:position_history_echelons,id',
            'positions.*.position_status' => 'nullable|numeric|in:1,2,3,4',
            'positions.*.effective_date' => 'date|nullable',
            'positions.*.decree' => 'nullable',
            'positions.*.decree_document' => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'positions.*.type_of_decree' => 'numeric|nullable',
            'positions.*.decree_number' => 'max:160|nullable',
            'positions.*.decree_date' => 'date|nullable',
            'positions.*.termination_date' => 'date|nullable',
            'positions.*.termination_decree' => 'nullable',
            'positions.*.type_of_termination_decree' => 'numeric|nullable',
            'positions.*.termination_decree_number' => 'max:160|nullable',
            'positions.*.termination_decree_date' => 'date|nullable',
            'positions.*.status' => 'nullable|boolean',
            'positions.*.delete_decree_document' => 'nullable|boolean',
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
            'positions.*.id.numeric' => 'Jabatan ID harus berupa angka.',
            'positions.*.group_id.numeric' => 'Rumpun harus berupa angka.',
            'positions.*.echelon.numeric' => 'Eselon harus berupa angka.',
            'positions.*.echelon.exists' => 'Eselon tidak ditemukan.',
            'positions.*.position_status.numeric' => 'Keterangan Jabatan harus berupa angka.',
            'positions.*.position_status.in' => 'Keterangan Jabatan harus diantara 1, 2, 3 atau 4.',
            'positions.*.effective_date.date' => 'Tanggal efektif jabatan harus berupa tanggal.',
            'positions.*.decree_document.file' => 'SK jabatan harus berupa file.',
            'positions.*.decree_document.extensions' => 'SK jabatan harus berupa jpg, jpeg atau png.',
            'positions.*.decree_document.max' => 'SK jabatan tidak boleh lebih dari 2MB.',
            'positions.*.type_of_decree.numeric' => 'Jenis SK jabatan harus berupa angka.',
            'positions.*.decree_number.max' => 'Nomor SK tidak boleh lebih dari 160 karakter.',
            'positions.*.decree_date.date' => 'Tanggal SK jabatan harus berupa tanggal.',
            'positions.*.termination_date.date' => 'Tanggal selesai jabatan harus berupa tanggal.',
            'positions.*.type_of_termination_decree.numeric' => 'Jenis SK SLS harus berupa angka.',
            'positions.*.termination_decree_number.max' => 'Nomor SK SLS tidak boleh lebih dari 160 karakter.',
            'positions.*.termination_decree_date.date' => 'Tanggal SK SLS harus berupa tanggal.',
            'positions.*.status.boolean' => 'Status jabatan harus boolean.',
            'positions.*.delete_decree_document.boolean' => 'Status hapus file SK Jabatan harus boolean.',
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
            'positions.*.id' => [
                'description' => 'Refers to the ID of Positions.',
                'example' => 1,
            ],
            'positions.*.position' => [
                'description' => 'Refers to the ID Position of Employee Position.',
                'example' => 'Staf pada Pembantu Asisten Wakil Presiden Bidang Monitoring dan Kontrol Masyarakat, Asisten Wakil Presiden Bidang Pengawasan (th.1979-1984)',
            ],
            'positions.*.group_id' => [
                'description' => 'Refers to the ID Group of Employee Position.',
                'example' => 1,
            ],
            'positions.*.echelon' => [
                'description' => 'Refers to the ID Echelon of Employee Position. 1=Eselon I, 2=Eselon II, 3=Eselon III, 4=Fungsional, 5=Pelaksana, 6=Staf',
                'example' => 1,
            ],
            'positions.*.position_status' => [
                'description' => 'Refers to the Position Status of Employee Position. 1=Promosi, 2=Mutasi, 3=Inpassing, 4=Konversi',
                'example' => 1,
            ],
            'positions.*.effective_date' => [
                'description' => 'Refers to the Effective Date of Employee Position.',
                'example' => '2019-10-22',
            ],
            'positions.*.decree' => [
                'description' => 'Refers to the Decree of Employee Position.',
                'example' => 'Kepmensesneg, Nomor 334 Tahun 2020',
            ],
            'positions.*.decree_document' => [
                'description' => 'Refers to the Decree Document of Employee Position.',
                'example' => public_path('/img/logo.svg'),
            ],
            'positions.*.type_of_decree' => [
                'description' => 'Refers to the Type of Decree of Employee Position.',
                'example' => 1,
            ],
            'positions.*.decree_number' => [
                'description' => 'Refers to the Decree Number of Employee Position.',
                'example' => 'Nomor 334 Tahun 2020',
            ],
            'positions.*.decree_date' => [
                'description' => 'Refers to the Decree Date of Employee Position.',
                'example' => '2020-10-20',
            ],
            'positions.*.termination_date' => [
                'description' => 'Refers to the Termination Date of Employee Position.',
                'example' => '2020-10-20',
            ],
            'positions.*.termination_decree' => [
                'description' => 'Refers to the Termination Decree of Employee Position.',
                'example' => 'Kepmensesneg, Nomor 334 Tahun 2020',
            ],
            'positions.*.type_of_termination_decree' => [
                'description' => 'Refers to the Type of Termination Decree of Employee Position.',
                'example' => 1,
            ],
            'positions.*.termination_decree_number' => [
                'description' => 'Refers to the Termination Decree Number of Employee Position.',
                'example' => 'Nomor 334 Tahun 2020',
            ],
            'positions.*.termination_decree_date' => [
                'description' => 'Refers to the Termination Decree Date of Employee Position.',
                'example' => '2020-10-22',
            ],
            'positions.*.status' => [
                'description' => 'Refers to the Status of Employee Position.',
                'example' => 1,
            ],
            'positions.*.delete_decree_document' => [
                'description' => 'Refers to the Status of Delete decree document.',
                'example' => false,
            ],
        ];
    }
}

