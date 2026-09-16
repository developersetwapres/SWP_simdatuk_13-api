<?php

namespace App\Http\Requests\TargetHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTargetHistoryEmployeeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'targets.*.id' => 'numeric|nullable',
            'targets.*.work_behavior_rating' => 'nullable|numeric|in:1,2,3,4,5',
            'targets.*.employee_performance_predicate' => 'nullable|numeric|in:1,2,3,4,5',
            'targets.*.organizational_performance_achievement' => 'nullable|numeric|in:1,2,3,4,5',
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
            'targets.*.id.numeric' => 'ID harus berupa angka.',
            'targets.*.work_behavior_rating.in' => 'Rating perilaku kerja harus diantara 1,2,3',
            'targets.*.work_behavior_rating.numeric' => 'Rating perilaku kerja harus berupa angka.',
            'targets.*.employee_performance_predicate.in' => 'Predikat kinerja pegawai harus diantara 1,2,3,4 dan 5',
            'targets.*.employee_performance_predicate.numeric' => 'Predikat kinerja pegawai harus berupa angka.',
            'targets.*.organizational_performance_achievement.numeric' => 'Capaian kinerja organisasi harus berupa angka.',
            'targets.*.organizational_performance_achievement.in' => 'Capaian kinerja organisasi harus diantara 1,2, dan 3',
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
            'targets.*.id' => [
                'description' => 'Refers to the ID of List Employee Target.',
                'example' => 1,
            ],
            'targets.*.work_behavior_rating' => [
                'description' => 'Refers to the Work Behavior Rating of Employee Target. 1=Diatas Ekspektasi, 2=Sesuai Ekspektasi, 3=Dibawah Ekspektasi',
                'example' => 1,
            ],
            'targets.*.employee_performance_predicate' => [
                'description' => 'Refers to the Employee Performance Predicate of Employee Target. 1=Sangat Baik, 2=Baik, 3=Butuh Perbaikan, 4=Kurang, 5=Sangat Kurang',
                'example' => 1,
            ],
            'targets.*.organizational_performance_achievement' => [
                'description' => 'Refers to the Orginizational Performance Achievement of Employee Target. 1=Sangat Baik, 2=Baik, 3=Cukup',
                'example' => 1,
            ],
        ];
    }
}

