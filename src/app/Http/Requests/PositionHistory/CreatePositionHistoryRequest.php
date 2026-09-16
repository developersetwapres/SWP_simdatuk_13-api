<?php

namespace App\Http\Requests\PositionHistory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CreatePositionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'period_month' => 'nullable|numeric|digits_between:1,12',
            'period_year' => 'nullable|date_format:Y',
            'name' => 'nullable|max:160',
            'users.*.user_id' => 'required|numeric',
            'users.*.position' => 'nullable',
            'users.*.group_id' => 'nullable|numeric',
            'users.*.echelon' => 'nullable|numeric|exists:position_history_echelons,id',
            'users.*.position_status' => 'nullable|numeric|in:1,2,3,4',
            'users.*.effective_date' => 'nullable|date',
            'users.*.decree' => 'nullable',
            'users.*.decree_document' => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'users.*.type_of_decree' => 'nullable|numeric',
            'users.*.decree_number' => 'nullable|max:160',
            'users.*.decree_date' => 'nullable|date',
            'users.*.termination_date' => 'nullable|date',
            'users.*.termination_decree' => 'nullable',
            'users.*.type_of_termination_decree' => 'nullable|numeric',
            'users.*.termination_decree_number' => 'nullable|max:160',
            'users.*.termination_decree_date' => 'nullable|date',
            'users.*.status' => 'boolean',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period_month.numeric' => 'Bulan periode riwayat harus berupa angka.',
            'period_month.digits_between' => 'Bulan periode riwayat harus diantara 1 hingga 12.',
            'period_year.date_format' => 'Tahun periode riwayat harus dengan format YYYY.',
            'name.max' => 'Nama nilai prestasi tidak boleh lebih dari 160 karakter.',
            'users.*.user_id.required' => 'User ID tidak boleh kosong.',
            'users.*.user_id.numeric' => 'User ID harus berupa angka.',
            'users.*.group_id.numeric' => 'Rumpun harus berupa angka.',
            'users.*.echelon.numeric' => 'Eselon harus berupa angka.',
            'users.*.echelon.exists' => 'Eselon tidak ditemukan.',
            'users.*.position_status.numeric' => 'Keterangan Jabatan harus berupa angka.',
            'users.*.position_status.in' => 'Keterangan Jabatan harus diantara 1, 2, 3 atau 4.',
            'users.*.effective_date.date' => 'Tanggal efektif jabatan harus berupa tanggal.',
            'users.*.decree_document.file' => 'SK jabatan harus berupa file.',
            'users.*.decree_document.extensions' => 'SK jabatan harus berupa jpg, jpeg atau png.',
            'users.*.decree_document.max' => 'SK jabatan tidak boleh lebih dari 2MB.',
            'users.*.type_of_decree.numeric' => 'Jenis SK jabatan harus berupa angka.',
            'users.*.decree_number.max' => 'Nomor SK tidak boleh lebih dari 160 karakter.',
            'users.*.decree_date.date' => 'Tanggal SK jabatan harus berupa tanggal.',
            'users.*.termination_date.date' => 'Tanggal selesai jabatan harus berupa tanggal.',
            'users.*.type_of_termination_decree.numeric' => 'Jenis SK SLS harus berupa angka.',
            'users.*.termination_decree_number.max' => 'Nomor SK SLS tidak boleh lebih dari 160 karakter.',
            'users.*.termination_decree_date.date' => 'Tanggal SK SLS harus berupa tanggal.',
            'users.*.status.numeric' => 'Status jabatan harus berupa angka.',
        ];
    }
}
