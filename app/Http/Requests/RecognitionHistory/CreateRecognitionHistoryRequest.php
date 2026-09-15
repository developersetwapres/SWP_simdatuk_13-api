<?php

namespace App\Http\Requests\RecognitionHistory;

use Illuminate\Foundation\Http\FormRequest;

class CreateRecognitionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'period_month' => 'required|numeric|digits_between:1,12',
            'period_year' => 'required|date_format:Y',
            'recognition_id' => 'required|numeric',
            'description' => 'max:160',
            'type_of_decree' => 'required|numeric',
            'decree_date' => 'required|date',
            'decree_number' => 'required|max:160',
            'decree_year' => 'nullable|date_format:Y',
            'awarding_institution' => 'max:160',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_month.required' => 'Bulan periode riwayat tidak boleh kosong.',
            'period_month.numeric' => 'Bulan periode riwayat harus berupa angka.',
            'period_month.digits_between' => 'Bulan periode riwayat harus diantara 1 hingga 12.',
            'period_year.required' => 'Tahun periode riwayat tidak boleh kosong.',
            'period_year.date_format' => 'Tahun periode riwayat harus dengan format YYYY.',
            'recognition_id.required' => 'Penghargaan tidak boleh kosong.',
            'recognition_id.numeric' => 'Penghargaan harus berupa angka.',
            'description.max' => 'Keterangan penghargaan tidak boleh lebih dari 160 karakter.',
            'type_of_decree.required' => 'Jenis SK tidak boleh kosong.',
            'type_of_decree.numeric' => 'Jenis SK harus berupa angka.',
            'decree_date.required' => 'Tanggal SK tidak boleh kosong.',
            'decree_date.date' => 'Tanggal SK harus berupa tanggal.',
            'decree_number.required' => 'No SK Penghargaan tidak boleh kosong.',
            'decree_number.max' => 'No SK Penghargaan tidak boleh lebih dari 160 karakter.',
            'decree_year.date_format' => 'Tahun SK harus dengan format YYYY.',
            'awarding_institution.max' => 'Instansi pemberi penghargaan tidak boleh lebih dari 160 karakter.',
            'users.*.user_id.required' => 'User ID tidak boleh kosong.',
            'users.*.user_id.numeric' => 'User ID harus berupa angka.',
        ];
    }
}
