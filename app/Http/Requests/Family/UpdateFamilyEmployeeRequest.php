<?php

namespace App\Http\Requests\Family;

class UpdateFamilyEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'families.*.id'                     => 'nullable|numeric',
            'families.*.card_number'            => 'nullable|max:21',
            'families.*.name'                   => 'nullable|max:160',
            'families.*.id_number'              => 'nullable|max:16',
            'families.*.gender'                 => 'nullable|boolean',
            'families.*.religion'               => 'nullable|numeric|in:1,2,3,4,5,6',
            'families.*.place_of_birth'         => 'nullable|max:160',
            'families.*.date_of_birth'          => 'nullable|date',
            'families.*.name_of_father'         => 'nullable|max:160',
            'families.*.name_of_mother'         => 'nullable|max:160',
            'families.*.relationship_status'    => 'nullable|numeric|in:1,2,3,4,5,6,7,8,9,10,11',
            'families.*.education'              => 'nullable|numeric|in:1,2,3,4,5,6,7,8,9,10',
            'families.*.occupation'             => 'nullable|max:160',
            'families.*.occupation_description' => 'nullable|max:160',
            'families.*.marital_status'         => 'nullable|numeric|in:1,2,3,4,5',
            'families.*.marriage_other_notes'   => 'nullable',
            'families.*.mobile_phone'           => 'nullable|max:16',
            'families.*.sequence_number'        => 'nullable|numeric',
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
            'families.*.id.numeric' => 'Family ID harus berupa angka.',
            'families.*.card_number.max' => 'Nomor kartu keluarga tidak boleh lebih dari 16 digit.',
            'families.*.name.max' => 'Nama tidak boleh lebih dari 16 karakter.',
            'families.*.id_number.max' => 'NIK tidak boleh lebih dari 16 digit.',
            'families.*.gender.boolean' => 'Jenis kelamin harus berupa boolean.',
            'families.*.religion.numeric' => 'Agama harus berupa angka.',
            'families.*.religion.in' => 'Agama harus diantara 1,2,3,4,5 atau 6.',
            'families.*.place_of_birth.max' => 'Tempat lahir tidak boleh lebih dari 160 karakter.',
            'families.*.date_of_birth.date' => 'Tanggal lahir harus berupa tanggal.',
            'families.*.name_of_father.max' => 'Nama bapak tidak boleh lebih dari 160 karakter.',
            'families.*.name_of_mother.max' => 'Nama ibu tidak boleh lebih dari 160 karakter.',
            'families.*.relationship_status.numeric' => 'Hubungan keluarga harus berupa angka.',
            'families.*.relationship_status.in' => 'Hubungan keluarga harus diantara 1,2,3,4,5,6,7,8,9,10 atau 11.',
            'families.*.education.numeric' => 'Pendidikan harus berupa angka.',
            'families.*.education.in' => 'Pendidikan harus diantara 1,2,3,4,5,6,7,8,9 atau 10.',
            'families.*.occupation.max' => 'Jenis pekerjaan tidak boleh lebih dari 160 karakter.',
            'families.*.occupation_description.max' => 'Keterangan pekerjaan tidak boleh lebih dari 160 karakter.',
            'families.*.marital_status.numeric' => 'Status perkawinan harus berupa angka.',
            'families.*.marital_status.in' => 'Status perkawinan harus diantara 1,2,3,4 atau 5.',
            'families.*.mobile_phone.max' => 'Nomor handphone tidak boleh lebih dari 16 karakter.',
            'families.*.sequence_number.numeric' => 'Urutan harus berupa angka.',
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
            'families.*.id' => [
                'description' => 'Refers to the ID of Family.',
                'example' => 1,
            ],
            'families.*.card_number' => [
                'description' => 'Refers to the Card Number of Employee Family.',
                'example' => '1234.5678.9012.3456',
            ],
            'families.*.name' => [
                'description' => 'Refers to the Name of Employee Family.',
                'example' => 'Adi Setiawan',
            ],
            'families.*.id_number' => [
                'description' => 'Refers to the ID Number of Employee Family.',
                'example' => '1234567890123456',
            ],
            'families.*.gender' => [
                'description' => 'Refers to the Gender of Employee Family. true=Pria, false=Wanita',
                'example' => 1,
            ],
            'families.*.religion' => [
                'description' => 'Refers to the Religion of Employee Family. 1=Islam, 2=Kristen, 3=Katolik, 4=Hindu, 5=Buddha, 6=Konghucu',
                'example' => 1,
            ],
            'families.*.place_of_birth' => [
                'description' => 'Refers to the Place of Birth of Employee Family.',
                'example' => 'Jakarta',
            ],
            'families.*.date_of_birth' => [
                'description' => 'Refers to the Date of Birth of Employee Family.',
                'example' => '1985-10-22',
            ],
            'families.*.name_of_father' => [
                'description' => 'Refers to the Name of Father of Employee Family.',
                'example' => 'Sunandar',
            ],
            'families.*.name_of_mother' => [
                'description' => 'Refers to the Name of Mother of Employee Family.',
                'example' => 'Maemunah',
            ],
            'families.*.relationship_status' => [
                'description' => 'Refers to the Relationship Status of Employee Family. 1=Kepala Keluarga, 2=Suami, 3=Istri, 4=Anak, 5=Menantu, 6=Cucu, 7=Orang Tua, 8=Mertua, 9=Famili Lainnya, 10=Pembantu, 11=Lainnya',
                'example' => 1,
            ],
            'families.*.education' => [
                'description' => 'Refers to the Level of Employee Family. 1=Tidak/Belum Sekolah, 2=Belum Tamat SD/Sederajat, 3=Tamat SD/Sederajat, 4=SLTP/Sederajat, 5=SLTA/Sederajat, 6=Diploma I/II, 7=Akademi/Diploma III/Sarjana Muda, 8=Diploma IV/Strata I, 9=Strata II, 10=Strata III',
                'example' => 1,
            ],
            'families.*.occupation' => [
                'description' => 'Refers to the Occupation of Employee Family.',
                'example' => 'Wirausaha',
            ],
            'families.*.occupation_description' => [
                'description' => 'Refers to the Occupation Description of Employee Family.',
                'example' => 'Wirausaha Menengah',
            ],
            'families.*.marital_status' => [
                'description' => 'Refers to the Marital Status of Employee Family. 1=Belum Menikah, 2=Menikah, 3=Cerai Hidup, 4=Cerai Mati',
                'example' => 1,
            ],
            'families.*.marriage_other_notes' => [
                'description' => 'Refers to the Marriage Other Notes of Employee Family',
                'example' => 1,
            ],
            'families.*.mobile_phone' => [
                'description' => 'Refers to the Mobile Phone of Employee Family.',
                'example' => '086552417331',
            ],
            'families.*.sequence_number' => [
                'description' => 'Refers to the Sequence Number of Employee Family.',
                'example' => 3,
            ],
        ];
    }
}

