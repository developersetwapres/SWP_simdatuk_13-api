<?php

namespace App\Http\Requests\Credit;

class CreateCreditRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'credits.*.position' => 'nullable|max:160',
            'credits.*.period' => 'nullable|in:1,2,3,4,5',
            'credits.*.year' => 'nullable|date_format:Y',
            'credits.*.score' => 'nullable|between:1.00,100.00',
            'credits.*.start_month' => 'nullable|integer|between:1,12',
            'credits.*.end_month' => 'nullable|integer|between:1,12',
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
            'credits.*.position.max' => 'Jabatan tidak boleh lebih dari 160 karakter.',
            'credits.*.period.in' => 'Periode harus diantara 1, 2, 4, atau 5.',
            'credits.*.year.date_format' => 'Tahun harus dengan format YYYY.',
            'credits.*.score.between' => 'Angka kredit harus diantara 1 hingga 100.',
            'credits.*.start_month.between' => 'Bulan awal harus diantara 1 hingga 12.',
            'credits.*.end_month.between' => 'Bulan awal harus diantara 1 hingga 12.',
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
            'credits.*.position' => [
                'description' => 'Refers to the Position of Employee Credit Score.',
                'example' => 'Pengadministrasi Persuratan',
            ],
            'credits.*.period' => [
                'description' => 'Refers to the Period of Employee Credit Score. 1=Triwulan 1, 2=Triwulan 2, 3=Triwulan 3, 4=Triwulan 4, 5=Tahunan',
                'example' => 1,
            ],
            'credits.*.year' => [
                'description' => 'Refers to the Year of Employee Credit Score.',
                'example' => '2024',
            ],
            'credits.*.score' => [
                'description' => 'Refers to the Score of Employee Credit Score.',
                'example' => '10',
            ],
            'credits.*.start_month' => [
                'description' => 'Refers to the Start month of Employee Credit Score.',
                'example' => '10',
            ],
            'credits.*.end_month' => [
                'description' => 'Refers to the End month of Employee Credit Score.',
                'example' => '10',
            ],
        ];
    }
}

