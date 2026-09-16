<?php

namespace App\Http\Requests\GradeHistory;

class UpdateGradeHistoryRequest extends CreateGradeHistoryRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'period_month' => 'nullable|numeric|digits_between:1,12',
            'period_year' => 'nullable|date_format:Y',
            'name' => 'nullable|max:160',
            'users.*.id' => 'nullable|numeric',
            'users.*.user_id' => 'nullable|numeric',
            'users.*.grade_id' => 'nullable|numeric',
            'users.*.effective_date' => 'nullable|date',
            'users.*.decree_number' => 'nullable|max:160',
            'users.*.status' => 'nullable|boolean',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_month.numeric' => 'Bulan periode riwayat harus berupa angka.',
            'period_month.digits_between' => 'Bulan periode riwayat harus diantara 1 hingga 12.',
            'period_year.date_format' => 'Tahun periode riwayat harus dengan format YYYY.',
            'name.max' => 'Nama riwayat penghargaan tidak boleh lebih dari 160 karakter.',
            'users.*.id.numeric' => 'ID harus berupa angka.',
            'users.*.user_id.numeric' => 'User ID harus berupa angka.',
            'users.*.grade_id.numeric' => 'Golongan harus berupa angka.',
            'users.*.effective_date.date' => 'Tanggal efektif golongan harus berupa tanggal.',
            'users.*.decree_number.max' => 'Nomor SK golongan tidak beloh lebih dari 160 karakter.',
            'users.*.status.boolean' => 'Status harus berupa boolean.',
        ];
    }
}
