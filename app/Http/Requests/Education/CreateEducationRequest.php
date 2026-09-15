<?php

namespace App\Http\Requests\Education;

class CreateEducationRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'educations.*.level'                   => 'nullable|numeric|in:1,2,3,4,5,6,7,8',
            'educations.*.name'                    => 'nullable|max:160',
            'educations.*.study_area'              => 'nullable|numeric|in:1,2',
            'educations.*.accreditation'           => 'nullable|max:30',
            'educations.*.faculty'                 => 'nullable|max:160',
            'educations.*.major'                   => 'nullable|max:160',
            'educations.*.year_of_graduation'      => 'nullable|date_format:Y',
            'educations.*.description'             => 'nullable|max:160',
            'educations.*.degree_document'         => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'educations.*.study_assignment_letter' => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'educations.*.academic_title_letter'   => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
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
            'educations.*.level.numeric' => 'Tingkat pendidikan harus berupa angka.',
            'educations.*.level.in' => 'Tingkat pendidikan harus diantara 1,2,3,4,5,6,7,8 atau 9',
            'educations.*.name.max' => 'Nama tidak boleh lebih dari 160 karakter.',
            'educations.*.study_area.numeric' => 'Wilayah Sekolah/Kampus harus berupa angka.',
            'educations.*.accreditation.max' => 'Akreditasi tidak boleh lebih dari 30 karakter.',
            'educations.*.faculty.max' => 'Nama fakultas tidak boleh lebih dari 160 karakter.',
            'educations.*.major.max' => 'Jurusan tidak boleh lebih dari 160 karakter.',
            'educations.*.year_of_graduation.date_format' => 'Tahun kelulusan harus dengan format YYYY.',
            'educations.*.description.max' => 'Keterangan tidak boleh lebih dari 160 karakter.',
            'educations.*.degree_document.file' => 'Ijazah harus berupa file.',
            'educations.*.degree_document.extensions' => 'Ijazah harus berupa jpg, jpeg atau png.',
            'educations.*.degree_document.max' => 'Ukuran ijazah tidak boleh lebih dari 2MB.',
            'educations.*.study_assignment_letter.file' => 'Surat Keterangan Tugas Belajar harus berupa file.',
            'educations.*.study_assignment_letter.extensions' => 'Surat Keterangan Tugas Belajar harus berupa jpg, jpeg atau png.',
            'educations.*.study_assignment_letter.max' => 'Ukuran Surat Keterangan Tugas Belajar tidak boleh lebih dari 2MB.',
            'educations.*.academic_title_letter.file' => 'SK Pencantuman Gelar harus berupa file.',
            'educations.*.academic_title_letter.extensions' => 'SK Pencantuman Gelar harus berupa jpg, jpeg atau png.',
            'educations.*.academic_title_letter.max' => 'Ukuran SK Pencantuman Gelar tidak boleh lebih dari 2MB.',
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
            'educations.*.level' => [
                'description' => 'Refers to the Level of Employee Education. 1=SD/Sederajat, 2=SLTP/Sederajat, 3=SLTA/Sederajat, 4=Diploma I/II, 5=Akademik/D3/S.Muda, 6=Diploma IV/Strata I, 7=Strata II, 8=Strata III',
                'example' => 1,
            ],
            'educations.*.name' => [
                'description' => 'Refers to the Name of Employee Education.',
                'example' => 'Universitas Indonesia',
            ],
            'educations.*.study_area' => [
                'description' => 'Refers to the Study Area of Employee Education.',
                'example' => 'Dalam Negeri',
            ],
            'educations.*.accreditation' => [
                'description' => 'Refers to the Accreditation of Employee Education.',
                'example' => 'A',
            ],
            'educations.*.faculty' => [
                'description' => 'Refers to the Faculty of Employee Education.',
                'example' => 'Fakultas Ilmu Komputer',
            ],
            'educations.*.major' => [
                'description' => 'Refers to the Major of Employee Education.',
                'example' => 'Teknik Informatika',
            ],
            'educations.*.year_of_graduation' => [
                'description' => 'Refers to the Year of Graduation of Employee Education.',
                'example' => '1994',
            ],
            'educations.*.description' => [
                'description' => 'Refers to the Description of Employee Education.',
                'example' => 'Keterangan',
            ],
            'educations.*.degree_document' => [
                'description' => 'Refers to the Degree Document of Employee Education.',
                'example' => public_path('/img/logo.svg'),
            ],
            'educations.*.study_assignment_letter' => [
                'description' => 'Refers to the Study Assignment Letter of Employee Education.',
                'example' => public_path('/img/logo.svg'),
            ],
            'educations.*.academic_title_letter' => [
                'description' => 'Refers to the Academic Title Letter of Employee Education.',
                'example' => public_path('/img/logo.svg'),
            ],
        ];
    }
}

