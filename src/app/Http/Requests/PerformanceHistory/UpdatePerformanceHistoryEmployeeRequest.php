<?php

namespace App\Http\Requests\PerformanceHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerformanceHistoryEmployeeRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'performances.*.id' => 'numeric|nullable',
            'performances.*.work_performance_score' => 'nullable|numeric',
            'performances.*.description' => 'numeric|in:1,2,3,4,5|nullable',
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
            'performances.*.id.numeric' => 'ID harus berupa angka.',
            'performances.*.work_performance_score.numeric' => 'Nilai prestasi kerja harus berupa angka.',
            'performances.*.description.numeric' => 'Deskripsi harus berupa angka.',
            'performances.*.description.in' => 'Deskripsi harus diantara 1, 2, 3, 4 atau 5.',
        ];
    }

    public static function bodyParameters(): array
    {
        return [
            'performances.*.id' => [
                'description' => 'Refers to the ID of employee',
                'example' => 1,
            ],
            'performances.*.work_performance_score' => [
                'description' => 'Refers to the Work Performance Score of Employee Performance.',
                'example' => 80,
            ],
            'performances.*.description' => [
                'description' => 'Refers to the Description of Employee Performance. 1=Kurang, 2=Sedang, 3=Cukup, 4=Baik, 5=Sangat Baik',
                'example' => 1,
            ],
        ];
    }
}

