<?php

namespace App\Http\Requests\TrainingHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingHistoryTechnicalEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'technicals.*.id' => 'numeric|nullable',
            'technicals.*.certificate' => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'technicals.*.delete_certificate' => 'nullable|boolean',
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
            'technicals.*.id.numeric' => 'ID harus berupa angka.',
            'technicals.*.certificate.file' => 'Sertifikat harus berupa file.',
            'technicals.*.certificate.extensions' => 'Sertifikat harus berupa jpg, jpeg atau png.',
            'technicals.*.certificate.max' => 'Ukuran sertifikat tidak boleh lebih dari 2MB.',
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
            'technicals.*.id' => [
                'description' => 'Refers to the User ID of technicals training.',
                'example' => 1,
            ],
            'technicals.*.certificate' => [
                'description' => 'Refers to the Certificate of technicals Training.',
                'example' => public_path('/img/logo.svg'),
            ],
            'technicals.*.delete_certificate' => [
                'description' => 'Refers to the Status of Delete certificate.',
                'example' => false,
            ],
        ];
    }
}

