<?php

namespace App\Http\Requests\Export;

use Illuminate\Foundation\Http\FormRequest;

class ExportZipEmployeesRequest extends FormRequest
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
            'employee_type' => 'array|min:1|nullable',
            'employee_type.*' => 'numeric',
            'deputy' => 'array|min:1|nullable',
            'echelons' => 'array|min:1|nullable',
            'echelons.*' => 'numeric',
            'grades' => 'array|min:1|nullable',
            'grades.*' => 'numeric',
            'job_description' => 'array|min:1|nullable',
            'education' => 'array|min:1|nullable',
            'education.*' => 'numeric',
            'gender' => 'array|min:1|max:2|nullable',
            'min_age' => 'numeric|min:1|nullable',
            'max_age' => 'numeric|min:1|nullable',
            'marital_status' => 'array|min:1|nullable',
            'marital_status.*' => 'numeric',
            'retirement_age' => 'array|min:1|nullable',
            'retirement_age.*' => 'numeric',
            'retirement_year' => 'numeric|min:1|nullable',
            'total_working_duration' => 'array|min:1|nullable',
            'grade_range' => 'array|min:1|nullable',
            'employment_status' => 'array|min:1|nullable',
        ];
    }

    /**
     * Return error messages
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'employee_type.array' => 'Employee type harus berupa array',
            'employee_type.min' => 'Employee type minimal 1 angka didalam array',
            'employee_type.*.numeric' => 'Employee type isi array harus berupa angka',
            'deputy.array' => 'Deputy harus berupa array',
            'deputy.min' => 'Deputy harus memiliki minimal 1 item',
            'echelons.array' => 'Echelons harus berupa array',
            'echelons.min' => 'Echelons minimal 1 angka didalam array',
            'echelons.*.numeric' => 'Echelons isi array harus berupa angka',
            'grades.array' => 'Grades harus berupa array',
            'grades.min' => 'Grades minimal 1 angka didalam array',
            'grades.*.numeric' => 'Grades isi array harus berupa angka',
            'job_description.array' => 'Job description harus berupa array',
            'job_description.min' => 'Job description minimal 1 angka didalam array',
            'education.array' => 'Education harus berupa array',
            'education.min' => 'Education minimal 1 angka didalam array',
            'education.*.numeric' => 'Education isi array harus berupa angka',
            'gender.array' => 'Gender harus berupa array',
            'gender.min' => 'Gender minimal 1 angka didalam array',
            'gender.max' => 'Gender maksimal hanya 2 angka didalam array',
            'gender.*.numeric' => 'Gender isi array harus berupa angka',
            'marital_status.array' => 'Marital status harus berupa array',
            'marital_status.min' => 'Marital status minimal 1 angka didalam array',
            'marital_status.*.numeric' => 'Marital status isi array harus berupa angka',
            'min_age.min' => 'Min age array minimal harus ada 1 value',
            'min_age.numeric' => 'Min age harus berupa angka',
            'max_age.numeric' => 'Max age harus berupa angka',
            'retirement_age.array' => 'Retirement age harus berupa array',
            'retirement_age.min' => 'Retirement age minimal 1 angka didalam array',
            'retirement_age.*.numeric' => 'Retirement age isi array harus berupa angka',
            'retirement_year.min' => 'Retirement year minimal harus ada 1 value',
            'retirement_year.numeric' => 'Retirement year harus berupa angka',
            'grade_range.array' => 'Grade range harus berupa array',
            'grade_range.min' => 'Grade range harus memiliki minimal 1 item',
            'total_working_duration.array' => 'Total working duration harus berupa array',
            'total_working_duration.min' => 'Total working duration harus memiliki minimal 1 item',
            'employment_status.array' => 'Employment status harus berupa array',
            'employment_status.min' => 'Employment status minimal 1 angka didalam array',
            'employment_status.*.numeric' => 'Employment status isi array harus berupa angka',

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
            'employee_type' => [
                'description' => 'Refers to IDs of type of employee (1: ASN, 2: Non ASN, 3: Outsourcing)',
                'example' => [1]
            ],
            'employee_type.*' => [
                'description' => 'Refers to IDs of type of employee (1: ASN, 2: Non ASN, 3: Outsourcing)',
                'example' => [1]
            ],
            'deputy' => [
                'description' => 'Refers to Deputy ids list of employees',
                'example' => [5],
            ],
            'echelons' => [
                'description' => 'Refers to IDs of employee echelons',
                'example' => [1]
            ],
            'echelons.*' => [
                'description' => 'Refers to IDs of employee echelons',
                'example' => [1]
            ],
            'grades' => [
                'description' => 'Refers to IDs of employee grades',
                'example' => [1]
            ],
            'grades.*' => [
                'description' => 'Refers to IDs of employee grades',
                'example' => [1]
            ],
            'job_description' => [
                'description' => 'Refers to IDs of employee position status',
                'example' => [1]
            ],
            'education' => [
                'description' => 'Refers to type of employee education (1=SD/Sederajat, 2=SLTP/Sederajat, 3=SLTA/Sederajat, 4=Akademik/D3/S.Muda, 5=Diploma IV, 6=Strata I, 7=Strata II, 8=Strata III)',
                'example' => [1]
            ],
            'education.*' => [
                'description' => 'Refers to type of employee education (1=SD/Sederajat, 2=SLTP/Sederajat, 3=SLTA/Sederajat, 4=Akademik/D3/S.Muda, 5=Diploma IV, 6=Strata I, 7=Strata II, 8=Strata III)',
                'example' => [1]
            ],
            'gender' => [
                'description' => 'Refers to gender of employee (1: Laki - Laki, 0: Perempuan)',
                'example' => [1]
            ],
            'min_age' => [
                'description' => 'Refers to minimum age of employee',
                'example' => 50
            ],
            'max_age' => [
                'description' => 'Refers to maximum age of employee',
                'example' => 55
            ],
            'marital_status' => [
                'description' => 'Refers to marital status of employee (1=Belum Menikah, 2=Menikah, 3=Cerai Hidup, 4=Cerai Mati)',
                'example' => [1]
            ],
            'marital_status.*' => [
                'description' => 'Refers to marital status of employee (1=Belum Menikah, 2=Menikah, 3=Cerai Hidup, 4=Cerai Mati)',
                'example' => [1]
            ],
            'retirement_age' => [
                'description' => 'Refers to retirement age of employee',
                'example' => [58, 60]
            ],
            'retirement_age.*' => [
                'description' => 'Refers to retirement age of employee',
                'example' => [58, 60]
            ],
            'retirement_year' => [
                'description' => 'Refers to employee retirement year',
                'example' => 2024
            ],
            'total_working_duration' => [
                'description' => 'Refers to total duration of employee employment',
                'example' => ["5-10"],
            ],
            'grade_range' => [
                'description' => 'Refers to duration of grade in years',
                'example' => ["5-10"],
            ],
            'employment_status' => [
                'description' => 'Refers to employment status of employee (1=Aktif, 2=Pensiun, 3=Berhenti, 4=Meninggal, 5=Alih Status ,6=Aktif PS ,7=CLTN ,8=TBLN ,9=Non Aktif)',
                'example' => [1, 3]
            ],
            'employment_status.*' => [
                'description' => 'Refers to employment status of employee (1=Aktif, 2=Pensiun, 3=Berhenti, 4=Meninggal, 5=Alih Status ,6=Aktif PS ,7=CLTN ,8=TBLN ,9=Non Aktif)',
                'example' => [1, 3]
            ],

        ];
    }
}
