<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required|numeric',
            'employment_status' => 'required|in:1,2,3,4,5,6,7,8,9,10',
            'quit_date' => 'required_if:employment_status,2,3,4,5,9|date',
        ];
    }

    /**
     * Return custom error response
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'id.required' => 'ID Pegawai tidak boleh kosong.',
            'id.numeric' => 'ID Pegawai harus berupa angka.',
            'employment_status.required' => 'Status pegawai tidak boleh kosong.',
            'employment_status.in' => 'Status pegawai harus diantara 1,2,3,4,5,6,7,8,9,10.',
            'quit_date.required_if' => 'Tanggal berhenti bekerja tidak boleh kosong.',
            'quit_date.date' => 'Tanggal berhenti harus berupa tanggal.',
        ];
    }

    /**
     * Description for scribe
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        return [
            'id' => [
                'description' => 'Refers to the ID of User.',
                'example' => 1,
            ],
            'employment_status' => [
                'description' => 'Refers to the Status of User. 1=Aktif, 2=Pensiun, 3=Berhenti, 4=Meninggal, 5=Alih Status, 6=Aktif Perbantuan Setneg, 7=CLTN, 8=TBLN, 9=Non Aktif, 10=Hukdis',
                'example' => 1,
            ],
            'quit_date' => [
                'description' => 'Refers to the Quit Date of User.',
                'example' => '2019-10-22',
            ],
        ];
    }
}

