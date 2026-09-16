<?php

namespace App\Http\Requests\Talent;

class UpdateTalentEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'talents.*.id'                     => 'nullable|numeric',
            'talents.*.event_date'             => 'nullable|date',
            'talents.*.point'                  => 'nullable|numeric|in:1,2,3,4,5,6,7,8,9',
            'talents.*.organizer'              => 'nullable|max:512',
            'talents.*.talent_document'        => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'talents.*.delete_talent_document' => 'nullable|boolean',
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
            'talents.*.id.numeric' => 'Talent ID harus berupa angka.',
            'talents.*.event_date.date' => 'Tanggal talent pool harus berupa tanggal.',
            'talents.*.point.numeric' => 'Penilaian talent pool harus berupa angka.',
            'talents.*.point.in' => 'Penilaian talent pool harus diantara 1 sampai 9.',
            'talents.*.organizer.max' => 'Penyelenggara tidak boleh lebih dari 512 karakter.',
            'talents.*.talent_document.file' => 'Sertifikat harus berupa file.',
            'talents.*.talent_document.extensions' => 'Sertifikat harus berupa jpg, jpeg atau png.',
            'talents.*.talent_document.max' => 'Ukuran sertifikat tidak boleh lebih dari 2MB.',
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
            'talents.*.id' => [
                'description' => 'Refers to the ID of Talent.',
                'example' => 1,
            ],
            'talents.*.event_date' => [
                'description' => 'Refers to the Date of Employee Talent.',
                'example' => '2020-10-22',
            ],
            'talents.*.point' => [
                'description' => 'Refers to the Point of Employee Talent. 1=Kotak 1, 2=Kotak 2, 3=Kotak 3, 4=Kotak 4, 5=Kotak 5, 6=Kotak 6, 7=Kotak 7, 8=Kotak 8, 9=Kotak 9',
                'example' => 1,
            ],
            'talents.*.organizer' => [
                'description' => 'Refers to the Organizer of Employee Talent.',
                'example' => 'PPKASN',
            ],
            'talents.*.talent_document' => [
                'description' => 'Refers to the Document of Employee Talent.',
                'example' => public_path('/img/logo.svg'),
            ],
            'talents.*.delete_talent_document' => [
                'description' => 'Refers to the Status of Delete talent document.',
                'example' => false,
            ],
        ];
    }
}

