<?php

namespace App\Http\Requests\TrainingHistory;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingHistoryFunctionalEmployeeRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public static function rules(): array
    {
        return [
            'functionals.*.id' => 'numeric|nullable',
            'functionals.*.certificate' => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'functionals.*.delete_certificate' => 'nullable|boolean',
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
            'functionals.*.id.numeric' => 'ID harus berupa angka.',
            'functionals.*.certificate.file' => 'Sertifikat harus berupa file.',
            'functionals.*.certificate.extensions' => 'Sertifikat harus berupa jpg, jpeg atau png.',
            'functionals.*.certificate.max' => 'Ukuran sertifikat tidak boleh lebih dari 2MB.',
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
            'functionals.*.id' => [
                'description' => 'Refers to the User ID of functionals training.',
                'example' => 1,
            ],
            'functionals.*.certificate' => [
                'description' => 'Refers to the Certificate of functionals Training.',
                'example' => public_path('/img/logo.svg'),
            ],
            'functionals.*.delete_certificate' => [
                'description' => 'Refers to the Status of Delete certificate.',
                'example' => false,
            ],
        ];
    }
}

