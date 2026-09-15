<?php

namespace App\Http\Requests\TrainingHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingHistoryStructuralEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'structurals.*.id' => 'numeric|nullable',
            'structurals.*.certificate' => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'structurals.*.delete_certificate' => 'nullable|boolean',
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
            'structurals.*.id.numeric' => 'ID harus berupa angka.',
            'structurals.*.certificate.file' => 'Sertifikat harus berupa file.',
            'structurals.*.certificate.extensions' => 'Sertifikat harus berupa jpg, jpeg atau png.',
            'structurals.*.certificate.max' => 'Ukuran sertifikat tidak boleh lebih dari 2MB.',
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
            'structurals.*.id' => [
                'description' => 'Refers to the User ID of structurals training.',
                'example' => 1,
            ],
            'structurals.*.certificate' => [
                'description' => 'Refers to the Certificate of structurals Training.',
                'example' => public_path('/img/logo.svg'),
            ],
            'structurals.*.delete_certificate' => [
                'description' => 'Refers to the Status of Delete certificate.',
                'example' => false,
            ],
        ];
    }
}

