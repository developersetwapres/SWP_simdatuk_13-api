<?php

namespace App\Http\Requests\DisciplinaryHistory;

use Illuminate\Foundation\Http\FormRequest;

class CreateDisciplinaryHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'period_month' => 'nullable|numeric|digits_between:1,12',
            'period_year' => 'nullable|date_format:Y',
            'name' => 'nullable|max:160',
            'users.*.user_id' => 'required|numeric',
            'users.*.grade' => 'max:160',
            'users.*.position' => 'nullable',
            'users.*.disciplinary_id' => 'required|numeric',
            'users.*.decree_number' => 'max:160',
            'users.*.date_of_decree' => 'nullable|date',
            'users.*.start_date' => 'nullable|date',
            'users.*.end_date' => 'nullable|date',
            'users.*.authorizing_officer' => 'max:160',
            'users.*.name_of_authorizing_officer' => 'max:160',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_month.numeric' => 'Bulan periode riwayat harus berupa angka.',
            'period_month.digits_between' => 'Bulan periode riwayat harus diantara 1 hingga 12.',
            'period_year.date_format' => 'Tahun periode riwayat harus dengan format YYYY.',
            'name.max' => 'Nama tidak boleh lebih dari 160 karakter.',
            'users.*.user_id.required' => 'User ID tidak boleh kosong.',
            'users.*.user_id.numeric' => 'User ID harus berupa angka.',
            'users.*.grade.max' => 'Golongan tidak boleh lebih dari 160 karakter.',
            'users.*.disciplinary_id.required' => 'Jenis hukuman tidak boleh kosong.',
            'users.*.disciplinary_id.numeric' => 'Jenis hukuman harus berupa angka.',
            'users.*.decree_number.max' => 'No SK hukuman tidak boleh lebih dari 160 karakter.',
            'users.*.date_of_decree.date' => 'Tanggal SK harus berupa tanggal.',
            'users.*.start_date.date' => 'Tanggal mulai hukuman harus berupa tanggal.',
            'users.*.end_date.required' => 'Tanggal selesai hukuman tidak boleh kosong.',
            'users.*.authorizing_officer.max' => 'Pejabat berwenang tidak boleh lebih dari 160 karakter.',
            'users.*.name_of_authorizing_officer' => 'Nama pejabat berwenang tidak boleh lebih dari 160 karakter.',
        ];
    }
}
