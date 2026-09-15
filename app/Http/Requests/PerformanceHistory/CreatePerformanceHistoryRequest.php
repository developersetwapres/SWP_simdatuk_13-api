<?php

namespace App\Http\Requests\PerformanceHistory;

use Illuminate\Foundation\Http\FormRequest;

class CreatePerformanceHistoryRequest extends FormRequest
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
            'performance_period' => 'nullable|max:160',
            'name' => 'nullable|max:160',
            'users.*.user_id' => 'required|numeric',
            'users.*.work_performance_score' => 'nullable|numeric',
            'users.*.description' => 'numeric|in:1,2,3,4,5|nullable',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_month.numeric' => 'Bulan periode riwayat harus berupa angka.',
            'period_month.digits_between' => 'Bulan periode riwayat harus diantara 1 hingga 12.',
            'period_year.date_format' => 'Tahun periode riwayat harus dengan format YYYY.',
            'performance_period.max' => 'PPK periode tidak boleh lebih dari 160 karakter.',
            'name.max' => 'Nama nilai prestasi tidak boleh lebih dari 160 karakter.',
            'users.*.user_id.required' => 'User ID tidak boleh kosong.',
            'users.*.user_id.numeric' => 'User ID harus berupa angka.',
            'users.*.work_performance_score.numeric' => 'Nilai prestasi kerja harus berupa angka.',
            'users.*.description.numeric' => 'Deskripsi harus berupa angka.',
            'users.*.description.in' => 'Deskripsi harus diantara 1, 2, 3, 4 atau 5.',
        ];
    }
}
