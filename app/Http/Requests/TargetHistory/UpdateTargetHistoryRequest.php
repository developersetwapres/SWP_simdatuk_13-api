<?php

namespace App\Http\Requests\TargetHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTargetHistoryRequest extends FormRequest
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
            'appraisal_period' => 'nullable|in:Q1,Q2,Q3,Q4,Tahunan',
            'year' => 'date_format:Y',
            'users.*.id' => 'numeric|nullable',
            'users.*.user_id' => 'required|numeric',
            'users.*.work_behavior_rating' => 'nullable|numeric|in:1,2,3,4,5',
            'users.*.employee_performance_predicate' => 'nullable|numeric|in:1,2,3,4,5',
            'users.*.organizational_performance_achievement' => 'nullable|numeric|in:1,2,3,4,5',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_month.numeric' => 'Bulan periode riwayat harus berupa angka.',
            'period_month.digits_between' => 'Bulan periode riwayat harus diantara 1 hingga 12.',
            'period_year.date_format' => 'Tahun periode riwayat harus dengan format YYYY.',
            'name.max' => 'Nama target tidak boleh lebih dari 160 karakter.',
            'appraisal_period.in' => 'Periode penilaian harus diantara Q1, Q2, Q3, Q4, Tahunan.',
            'year.date_format' => 'Tahun target harus dengan format YYYY.',
            'users.*.id.numeric' => 'ID harus berupa angka.',
            'users.*.user_id.required' => 'User ID tidak boleh kosong.',
            'users.*.user_id.numeric' => 'User ID harus berupa angka.',
            'users.*.work_behavior_rating.in' => 'Rating perilaku kerja harus diantara 1,2,3',
            'users.*.work_behavior_rating.numeric' => 'Rating perilaku kerja harus berupa angka.',
            'users.*.employee_performance_predicate.in' => 'Predikat kinerja pegawai harus diantara 1,2,3,4 dan 5',
            'users.*.employee_performance_predicate.numeric' => 'Predikat kinerja pegawai harus berupa angka.',
            'users.*.organizational_performance_achievement.numeric' => 'Capaian kinerja organisasi harus berupa angka.',
            'users.*.organizational_performance_achievement.in' => 'Capaian kinerja organisasi harus diantara 1,2, dan 3',
        ];
    }
}
