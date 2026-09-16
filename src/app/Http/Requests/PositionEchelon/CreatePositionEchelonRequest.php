<?php

namespace App\Http\Requests\PositionEchelon;

use Illuminate\Contracts\Validation\ValidationRule;

class CreatePositionEchelonRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public static function rules(): array
    {
        return [
            'position_echelons.*.echelon_id' => 'numeric',
            'position_echelons.*.available' => 'numeric',
        ];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'position_echelons.*.echelon_id.numeric' => 'Echelon ID harus berupa angka.',
            'position_echelons.*.available.numeric' => 'Available harus berupa angka.',
        ];
    }
}
