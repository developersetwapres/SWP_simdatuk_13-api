<?php

namespace App\Http\Requests\Leave;

class CreateLeaveRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'leaves.*.start_date'  => 'nullable|date',
            'leaves.*.end_date'    => 'nullable|date',
            'leaves.*.type'        => 'nullable|in:1,2,3,4,5,6',
            'leaves.*.number'      => 'nullable|max:160',
            'leaves.*.description' => 'nullable',
            'leaves.*.letter'      => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
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
            'leaves.*.start_date.date' => 'Periode mulai harus berupa tanggal.',
            'leaves.*.end_date.date' => 'Periode akhir harus berupa tanggal.',
            'leaves.*.type.in' => 'Jenis cuti harus diantara 1,2,3,4,5 atau 6.',
            'leaves.*.number.max' => 'Nomor cuti tidak boleh lebih dari 160 karakter.',
            'leaves.*.purpose.max' => 'Tujuan tidak boleh lebih dari 160 karakter',
            'leaves.*.letter.file' => 'Surat cuti harus berupa file.',
            'leaves.*.letter.extensions' => 'Surat cuti harus berupa jpg, jpeg atau png.',
            'leaves.*.letter.max' => 'Ukuran surat cuti tidak boleh lebih dari 2MB.',
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
            'leaves.*.start_date' => [
                'description' => 'Refers to the Start Date of Employee Leave.',
                'example' => '2020-10-22',
            ],
            'leaves.*.end_date' => [
                'description' => 'Refers to the End Date of Employee Leave.',
                'example' => '2022-10-22',
            ],
            'leaves.*.type' => [
                'description' => 'Refers to the Type of Employee Leave. 1=Cuti diluar Tanggungan Negara, 2=Cuti Sakit, 3=Cuti Besar, 4=Cuti Bersalin, 5=Cuti Belajar Luar Negeri, 6=Cuti Tahunan Luar Negeri',
                'example' => 1,
            ],
            'leaves.*.number' => [
                'description' => 'Refers to the Leave Number of Employee Leave.',
                'example' => 'CT/1000.000.00',
            ],
            'leaves.*.description' => [
                'description' => 'Refers to the Description of Employee Leave.',
                'example' => 'Mudik lebaran',
            ],
            'leaves.*.letter' => [
                'description' => 'Refers to the Letter of Employee Leave.',
                'example' => public_path('/img/logo.svg'),
            ],
        ];
    }
}

