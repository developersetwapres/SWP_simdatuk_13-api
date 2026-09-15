<?php

namespace App\Http\Requests\Employee;

use App\Http\Requests\Assessment\UpdateAssessmentEmployeeRequest;
use App\Http\Requests\Competency\UpdateCompetencyEmployeeRequest;
use App\Http\Requests\Credit\UpdateCreditEmployeeRequest;
use App\Http\Requests\DisciplinaryHistory\UpdateDisciplinaryHistoryEmployeeRequest;
use App\Http\Requests\Education\UpdateEducationEmployeeRequest;
use App\Http\Requests\Family\UpdateFamilyEmployeeRequest;
use App\Http\Requests\GradeHistory\UpdateGradeHistoryEmployeeRequest;
use App\Http\Requests\Leave\UpdateLeaveEmployeeRequest;
use App\Http\Requests\Note\UpdateNoteEmployeeRequest;
use App\Http\Requests\PerformanceHistory\UpdatePerformanceHistoryEmployeeRequest;
use App\Http\Requests\PositionHistory\UpdatePositionHistoryEmployeeRequest;
use App\Http\Requests\Talent\UpdateTalentEmployeeRequest;
use App\Http\Requests\TargetHistory\UpdateTargetHistoryEmployeeRequest;
use App\Http\Requests\TrainingHistory\UpdateTrainingHistoryFunctionalEmployeeRequest;
use App\Http\Requests\TrainingHistory\UpdateTrainingHistoryStructuralEmployeeRequest;
use App\Http\Requests\TrainingHistory\UpdateTrainingHistoryTechnicalEmployeeRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
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

        $asnRules = [
            'photo_profile'                 => 'nullable|file|extensions:jpg,jpeg,png|max:2048|dimensions:width=350,height=500',
            'name'                          => 'required|max:160',
            'title_prefix'                  => 'nullable|max:160',
            'title_suffix'                  => 'nullable|max:160',
            'employee_id_number'            => 'required|regex:/^[a-zA-Z0-9]*$/|unique:users,employee_id_number,' . $this->id,
            'employee_registration_number'  => 'nullable|numeric|min:00000|max:999999999999999999|unique:users,employee_registration_number,' . $this->id,
            'place_of_birth'                => 'required|max:160',
            'date_of_birth'                 => 'required|date',
            'religion'                      => 'required|in:1,2,3,4,5,6',
            'gender'                        => 'required|boolean',
            'marital_status'                => 'nullable|in:1,2,3,4,5',
            'marriage_date'                 => 'nullable|date',
            'marriage_description'          => 'nullable',
            'employment_type_id'            => 'required',
            'cpns_effective_date'           => 'required|date',
            'position_id'                   => 'nullable|numeric',
            'position_effective_date'       => 'nullable|date',
            'grade_id'                      => 'required|numeric',
            'grade_effective_date'          => 'required|date',
            'echelon_id'                    => 'nullable|numeric',
            'echelon_effective_date'        => 'nullable|date',
            'institution_id'                => 'nullable|numeric',
            'education_level'               => 'required|numeric|in:1,2,3,4,5,6,7,8',
            'education_name'                => 'nullable|max:160',
            'education_year'                => 'nullable|date_format:Y',
            'employee_id_card_number'       => 'nullable|min:00000|max:999999999999999999|unique:users,employee_id_card_number,' . $this->id,
            'employee_id_card'              => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'karisu_number'                 => 'nullable|regex:/^[a-zA-Z0-9]*$/',
            'id_tax'                        => 'nullable|max:16|unique:users,id_tax,' . $this->id,
            'employment_status'             => 'required|numeric',
            'family_registration_number'    => 'nullable|numeric|digits:16',
            'id_number'                     => 'required|numeric|digits:16|unique:users,id_number,' . $this->id,
            'residence_id'                  => 'nullable|numeric',
            'residence_description'         => 'nullable',
            'current_address'               => 'nullable|max:160',
            'home_phone_number'             => 'nullable|numeric|max:99999999999999',
            'mobile_phone'                  => 'nullable|numeric|max:99999999999999',
            'office_address'                => 'nullable|max:160',
            'office_phone_number'           => 'nullable|numeric|max:99999999999999',
            'email'                         => 'nullable|email|unique:users,email,' . $this->id,
            'office_email'                  => 'required|email|unique:users,office_email,' . $this->id,
            'emergency_contact'             => 'required',
            'description'                   => 'nullable|max:160',
            'type'                          => 'required|in:1,2,3',
            'delete_employee_id_card'       => 'required|boolean',
            'quit_date'                     => 'nullable|date',
            'delete_photo_profile'          => 'nullable|boolean',
        ];

        $nonASNRules = [
            'photo_profile'                 => 'nullable|file|extensions:jpg,jpeg,png|max:2048|dimensions:width=350,height=500',
            'name'                          => 'required|max:160',
            'title_prefix'                  => 'nullable|max:160',
            'title_suffix'                  => 'nullable|max:160',
            'employee_id_number'            => 'required|regex:/^[a-zA-Z0-9]*$/|unique:users,employee_id_number,' . $this->id,
            'employee_registration_number'  => 'nullable|numeric|min:00000|max:999999999999999999|unique:users,employee_registration_number,' . $this->id,
            'place_of_birth'                => 'required|max:160',
            'date_of_birth'                 => 'required|date',
            'religion'                      => 'required|in:1,2,3,4,5,6',
            'gender'                        => 'required|boolean',
            'marital_status'                => 'nullable|in:1,2,3,4,5',
            'marriage_date'                 => 'nullable|date',
            'marriage_description'          => 'nullable',
            'employment_type_id'            => 'required',
            'cpns_effective_date'           => 'nullable|date',
            'position_id'                   => 'nullable|numeric',
            'position_effective_date'       => 'nullable|date',
            'grade_id'                      => 'nullable|numeric',
            'grade_effective_date'          => 'nullable|date',
            'echelon_id'                    => 'nullable|numeric',
            'echelon_effective_date'        => 'nullable|date',
            'institution_id'                => 'nullable|numeric',
            'education_level'               => 'required|numeric|in:1,2,3,4,5,6,7,8',
            'education_name'                => 'nullable|max:160',
            'education_year'                => 'nullable|date_format:Y',
            'employee_id_card_number'       => 'nullable|min:00000|max:999999999999999999|unique:users,employee_id_card_number,' . $this->id,
            'employee_id_card'              => 'nullable|file|extensions:jpg,jpeg,png,pdf|max:2048',
            'karisu_number'                 => 'nullable|regex:/^[a-zA-Z0-9]*$/',
            'id_tax'                        => 'nullable|max:16|unique:users,id_tax,' . $this->id,
            'employment_status'             => 'required|numeric',
            'family_registration_number'    => 'nullable|numeric|digits:16',
            'id_number'                     => 'required|numeric|digits:16|unique:users,id_number,' . $this->id,
            'residence_id'                  => 'nullable|numeric',
            'residence_description'         => 'nullable',
            'current_address'               => 'nullable|max:160',
            'home_phone_number'             => 'nullable|numeric|max:99999999999999',
            'mobile_phone'                  => 'nullable|numeric|max:99999999999999',
            'office_address'                => 'nullable|max:160',
            'office_phone_number'           => 'nullable|numeric|max:99999999999999',
            'email'                         => 'nullable|email|unique:users,email,' . $this->id,
            'office_email'                  => 'nullable|email|unique:users,office_email,' . $this->id,
            'emergency_contact'             => 'required',
            'description'                   => 'nullable|max:160',
            'type'                          => 'required|in:1,2,3',
            'delete_employee_id_card'       => 'required|boolean',
            'quit_date'                     => 'nullable|date',
            'delete_photo_profile'          => 'nullable|boolean',
        ];

        $outsourceRules = [
            'photo_profile'                 => 'nullable|file|extensions:jpg,jpeg,png|max:2048|dimensions:width=350,height=500',
            'name'                          => 'required|max:160',
            'employee_id_number'            => 'required|regex:/^[a-zA-Z0-9]*$/|unique:users,employee_id_number,' . $this->id,
            'place_of_birth'                => 'required|max:160',
            'date_of_birth'                 => 'required|date',
            'religion'                      => 'required|in:1,2,3,4,5,6',
            'gender'                        => 'required|boolean',
            'marital_status'                => 'nullable|in:1,2,3,4,5',
            'marriage_date'                 => 'nullable|date',
            'marriage_description'          => 'nullable',
            'employment_type_id'            => 'required',
            'cpns_effective_date'           => 'nullable|date',
            'position_id'                   => 'nullable|numeric',
            'position_effective_date'       => 'nullable|date',
            'education_level'               => 'required|numeric|in:1,2,3,4,5,6,7,8',
            'education_name'                => 'nullable|max:160',
            'education_year'                => 'nullable|date_format:Y',
            'id_tax'                        => 'nullable|max:16|unique:users,id_tax,' . $this->id,
            'employment_status'             => 'required|numeric',
            'family_registration_number'    => 'nullable|numeric|digits:16',
            'id_number'                     => 'required|numeric|digits:16|unique:users,id_number,' . $this->id,
            'residence_id'                  => 'nullable|numeric',
            'residence_description'         => 'nullable',
            'current_address'               => 'nullable|max:160',
            'home_phone_number'             => 'nullable|numeric|max:99999999999999',
            'mobile_phone'                  => 'nullable|numeric|max:99999999999999',
            'office_address'                => 'nullable|max:160',
            'office_phone_number'           => 'nullable|numeric|max:99999999999999',
            'email'                         => 'nullable|email|unique:users,email,' . $this->id,
            'emergency_contact'             => 'required',
            'description'                   => 'nullable|max:160',
            'type'                          => 'required|in:1,2,3',
            'delete_employee_id_card'       => 'required|boolean',
            'quit_date'                     => 'nullable|date',
            'delete_photo_profile'          => 'nullable|boolean',
        ];

        return array_merge(
            $this->type == 3 ? $outsourceRules : ($this->type == 2 ? $nonASNRules : $asnRules),
            UpdateEducationEmployeeRequest::rules(),
            UpdatePositionHistoryEmployeeRequest::rules(),
            UpdateGradeHistoryEmployeeRequest::rules(),
            UpdateTrainingHistoryStructuralEmployeeRequest::rules(),
            UpdateTrainingHistoryFunctionalEmployeeRequest::rules(),
            UpdateTrainingHistoryTechnicalEmployeeRequest::rules(),
            UpdateTargetHistoryEmployeeRequest::rules(),
            UpdatePerformanceHistoryEmployeeRequest::rules(),
            UpdateDisciplinaryHistoryEmployeeRequest::rules(),
            UpdateFamilyEmployeeRequest::rules(),
            UpdateLeaveEmployeeRequest::rules(),
            UpdateNoteEmployeeRequest::rules(),
            UpdateCreditEmployeeRequest::rules(),
            UpdateAssessmentEmployeeRequest::rules(),
            UpdateCompetencyEmployeeRequest::rules(),
            UpdateTalentEmployeeRequest::rules(),
        );
    }

    /**
     * Return error messages
     *
     * @return array
     */
    public function messages(): array
    {
        $userMessages = [
            'email.email' => 'Format email harus sesuai.',
            'email.unique' => 'Email sudah terdaftar.',
            'title_prefix.max' => 'Gelar tidak boleh lebih dari 160 karakter.',
            'name.required' => 'Nama tidak boleh kosong.',
            'name.max' => 'Nama tidak boleh lebih dari 160 karakter.',
            'title_suffix.max' => 'Gelar tidak boleh lebih dari 160 karakter.',
            'photo_profile.file' => 'Foto profil harus berupa file.',
            'photo_profile.extensions' => 'Foto profil harus berupa jpg, jpeg atau png.',
            'photo_profile.max' => 'Ukuran foto profil tidak boleh lebih dari 2MB.',
            'photo_profile.dimensions' => 'Ukuran foto profil harus 350px X 500px.',
            'employee_id_number.required' => 'NIP tidak boleh kosong.',
            'employee_id_number.unique' => 'NIP sudah terdaftar.',
            'employee_id_number.regex' => 'Format NIP yang dikirim tidak sesuai.',
            'employee_registration_number.numeric' => 'NRP harus berupa angka.',
            'employee_registration_number.min' => 'NRP tidak boleh kurang dari 5 digit angka.',
            'employee_registration_number.max' => 'NRP tidak boleh lebih dari 10 digit angka.',
            'employee_registration_number.unique' => 'NRP sudah terdaftar.',
            'place_of_birth.required' => 'Tempat lahir tidak boleh kosong.',
            'place_of_birth.max' => 'Tempat lahir tidak boleh lebih dari 160 karakter.',
            'date_of_birth.required' => 'Tanggal lahir tidak boleh kosong.',
            'date_of_birth.date' => 'Tanggal lahir harus berupa tanggal.',
            'religion.required' => 'Agama tidak boleh kosong.',
            'religion.in' => 'Agama harus diantara 1, 2, 3, 4, 5 atau 6.',
            'gender.required' => 'Jenis kelamin tidak boleh kosong.',
            'gender.boolean' => 'Jenis kelamin harus berupa boolean.',
            'marital_status.in' => 'Status perkawinan harus diantara 1, 2, 3, 4 atau 5.',
            'marriage_date.date' => 'Tanggal Pernikahan harus berupa tanggal.',
            'employment_type_id.required' => 'Jenis pegawai tidak boleh kosong.',
            'cpns_effective_date.required' => 'TMT CPNS tidak boleh kosong.',
            'cpns_effective_date.date' => 'TMT CPNS harus berupa tanggal.',
            'grade_id.required' => 'Golongan tidak boleh kosong.',
            'grade_id.numeric' => 'Golongan harus berupa angka.',
            'grade_effective_date.required' => 'Tanggal efektif golongan tidak boleh kosong.',
            'grade_effective_date.date' => 'Tanggal efektif golongan harus berupa tanggal.',
            'position_id.numeric' => 'Jabatan harus berupa angka.',
            'echelon_id.numeric' => 'Eselon harus berupa angka.',
            'echelon_effective_date.date' => 'Tanggal efektif eselon harus berupa tanggal.',
            'institution_id.required' => 'Institusi tidak boleh kosong.',
            'institution_id.numeric' => 'Institusi harus berupa angka.',
            'education_level.required' => 'Tingkat pendidikan tidak boleh kosong.',
            'education_level.numeric' => 'Tingkat pendidikan harus berupa angka.',
            'education_level.in' => 'Tingkat pendidikan harus diantara 1,2,3,4,5,6,7,8 atau 9',
            'education_name.required' => 'Nama Sekolah/Universitas tidak boleh kosong.',
            'education_name.max' => 'Nama Sekolah/Universitas tidak boleh lebih dari 160 karakter.',
            'education_year.required' => 'Tahun kelulusan tidak boleh kosong.',
            'education_year.date_format' => 'Tahun kelulusan harus dengan format YYYY.',
            'employee_id_card_number.numeric' => 'Nomor kartu pegawai harus berupa angka.',
            'employee_id_card_number.min' => 'Nomor kartu pegawai tidak boleh kurang dari 5 digit angka.',
            'employee_id_card_number.max' => 'Nomor kartu pegawai tidak boleh lebih dari 10 digit angka.',
            'employee_id_card_number.unique' => 'Nomor kartu pegawai sudah terdaftar.',
            'employee_id_card.file' => 'Kartu pegawai harus berupa file.',
            'employee_id_card.extensions' => 'Kartu pegawai harus berupa jpg,jpeg,png atau pdf.',
            'employee_id_card.max' => 'Ukuran kartu pegawai tidak boleh lebih dari 2048kb.',
            'karisu_number.regex' => 'Format nomor kartu suami/istri yang dikirim tidak sesuai.',
            'id_tax.max' => 'NPWP tidak boleh lebih dari 16 digit.',
            'id_tax.unique' => 'NPWP sudah terdaftar.',
            'employment_status.required' => 'Status pegawai tidak boleh kosong.',
            'employment_status.numeric' => 'Status pegawai harus berupa angka.',
            'id_number.numeric' => 'NIK harus berupa angka.',
            'id_number.digits' => 'NIK harus terdiri dari 16 digit angka.',
            'id_number.unique' => 'NIK sudah terdaftar.',
            'family_registration_number.numeric' => 'KK harus berupa angka.',
            'family_registration_number.digits' => 'KK harus terdiri dari 16 digit angka.',
            'residence_id.numeric' => 'Komplek harus berupa angka.',
            'current_address.max' => 'Alamat saat ini tidak boleh lebih dari 160 karakter.',
            'home_phone_number.numeric' => 'Nomor telepon rumah harus berupa angka.',
            'home_phone_number.max' => 'Nomor telepon rumah tidak boleh lebih dari 14 digit angka.',
            'mobile_phone.numeric' => 'Nomor HP harus berupa angka.',
            'mobile_phone.max' => 'Nomor HP tidak boleh lebih dari 14 digit angka.',
            'office_address.max' => 'Alamat kantor tidak boleh lebih dari 160 karakter.',
            'office_phone_number.numeric' => 'Nomor telepon kantor harus berupa angka.',
            'office_phone_number.max' => 'Nomor telepon kantor tidak boleh lebih dari 14 digit angka.',
            'emergency_contact.required' => 'Kontak darurat tidak boleh kosong',
            'description.max' => 'Keterangan tidak boleh lebih dari 160 karakter.',
            'type.required' => 'Tipe pegawai tidak boleh kosong.',
            'type.in' => 'Tipe pegawai diantara 1, 2 atau 3',
            'position_effective_date.date' => 'TMT menjabat harus berupa tanggal.',
            'office_email.email' => 'Format email dinas harus sesuai.',
            'office_email.unique' => 'Email dinas sudah terdaftar.',
            'quit_date.date' => 'Tanggal terakhir bekerja harus berupa tanggal.',
        ];

        return array_merge(
            $userMessages,
            UpdateEducationEmployeeRequest::messages(),
            UpdatePositionHistoryEmployeeRequest::messages(),
            UpdateGradeHistoryEmployeeRequest::messages(),
            UpdateTrainingHistoryStructuralEmployeeRequest::messages(),
            UpdateTrainingHistoryFunctionalEmployeeRequest::messages(),
            UpdateTrainingHistoryTechnicalEmployeeRequest::messages(),
            UpdateTargetHistoryEmployeeRequest::messages(),
            UpdatePerformanceHistoryEmployeeRequest::messages(),
            UpdateDisciplinaryHistoryEmployeeRequest::messages(),
            UpdateFamilyEmployeeRequest::messages(),
            UpdateLeaveEmployeeRequest::messages(),
            UpdateNoteEmployeeRequest::messages(),
            UpdateCreditEmployeeRequest::messages(),
            UpdateAssessmentEmployeeRequest::messages(),
            UpdateCompetencyEmployeeRequest::messages(),
            UpdateTalentEmployeeRequest::messages(),
        );
    }

    /**
     * Description for scribe
     *
     * @return array
     */
    public function bodyParameters(): array
    {
        $userBodyParameters = [
            'email' => [
                'description' => 'Refers to the Email of Employee.',
                'example' => 'padmi@wapresri.go.id',
            ],
            'title_prefix' => [
                'description' => 'Refers to the Title Prefix of Employee.',
                'example' => 'Dr',
            ],
            'name' => [
                'description' => 'Refers to the Name of Employee.',
                'example' => 'Padmi Riyanti',
            ],
            'title_suffix' => [
                'description' => 'Refers to the Title Suffix of Employee.',
                'example' => 'S.sos',
            ],
            'photo_profile' => [
                'description' => 'Refers to the Photo Profile of Employee.',
                'example' => public_path('/img/logo.svg'),
            ],
            'id_number' => [
                'description' => 'Refers to the ID Number of Employee.',
                'example' => '3279034401000001',
            ],
            'employee_id_number' => [
                'description' => 'Refers to the Employee ID Number of Employee.',
                'example' => '00010015',
            ],
            'employee_registration_number' => [
                'description' => 'Refers to the Employee Registration Number of Employee.',
                'example' => '00010015',
            ],
            'place_of_birth' => [
                'description' => 'Refers to the Place of Birth of Employee.',
                'example' => 'Jakarta',
            ],
            'date_of_birth' => [
                'description' => 'Refers to the Date of Birth of Employee.',
                'example' => '1988-12-22',
            ],
            'religion' => [
                'description' => 'Refers to the Religion of Employee. 1=Islam, 2=Kristen, 3=Katolik, 4=Hindu, 5=Buddha, 6=Konghucu',
                'example' => 1,
            ],
            'gender' => [
                'description' => 'Refers to the Gender of Employee. true=laki-laki, false=perempuan',
                'example' => 1,
            ],
            'marital_status' => [
                'description' => 'Refers to the Marital Status of Employee. 1=Belum Menikah, 2=Menikah, 3=Cerai, 4=Janda, 5=Duda',
                'example' => 1,
            ],
            'marriage_date' => [
                'description' => 'Refers to the Marriage Date of Employee',
                'example' => 1,
            ],
            'marriage_description' => [
                'description' => 'Refers to the Marriage Description of Employee',
                'example' => 1,
            ],
            'employment_type_id' => [
                'description' => 'Refers to the Employment Type ID of Employee.',
                'example' => 1,
            ],
            'grade_id' => [
                'description' => 'Refers to the Grade ID of Employee.',
                'example' => 1,
            ],
            'grade_effective_date' => [
                'description' => 'Refers to the Grade Effective Date of Employee.',
                'example' => '2010-10-21',
            ],
            'position_id' => [
                'description' => 'Refers to the Position ID of Employee.',
                'example' => 1,
            ],
            'echelon_id' => [
                'description' => 'Refers to the Echelon ID of Employee.',
                'example' => 1,
            ],
            'echelon_effective_date' => [
                'description' => 'Refers to the Echelon Effective Date of Employee.',
                'example' => '2013-07-23',
            ],
            'institution_id' => [
                'description' => 'Refers to the Institution ID of Employee.',
                'example' => 1,
            ],
            'organization_id' => [
                'description' => 'Refers to the Organization ID of Employee.',
                'example' => 1,
            ],
            'work_unit_id' => [
                'description' => 'Refers to the Work Unit ID of Employee.',
                'example' => 1,
            ],
            'employee_id_card_number' => [
                'description' => 'Refers to the Employee ID Card Number of Employee.',
                'example' => '00010015',
            ],
            'employee_id_card' => [
                'description' => 'Refers to the Employee ID Card of Employee.',
                'example' => public_path('/img/logo.svg'),
            ],
            'karisu_number' => [
                'description' => 'Refers to the Karisu Number of Employee.',
                'example' => '00010015',
            ],
            'id_tax' => [
                'description' => 'Refers to the ID Tax of Employee.',
                'example' => '12345678901234',
            ],
            'employment_status' => [
                'description' => 'Refers to the Employment Status of Employee. true=aktif, false=tidak aktif',
                'example' => 1,
            ],
            'residence_id' => [
                'description' => 'Refers to the Residence ID of Employee.',
                'example' => 1,
            ],
            'residence_description' => [
                'description' => 'Refers to the Residence Description of Employee.',
                'example' => 'Lorem Ipsum',
            ],
            'current_address' => [
                'description' => 'Refers to the Current Address of Employee.',
                'example' => 'Jalan Mawar No. 123, Kelurahan Bunga Indah, Kecamatan Kota Baru, Jakarta, Kode Pos 12345',
            ],
            'home_phone_number' => [
                'description' => 'Refers to the Home Phone Number of Employee.',
                'example' => '02112345678',
            ],
            'mobile_phone' => [
                'description' => 'Refers to the Mobile Phone of Employee.',
                'example' => '6281234567890',
            ],
            'office_address' => [
                'description' => 'Refers to the Office Address of Employee.',
                'example' => 'Jalan Mawar No. 123, Kelurahan Bunga Indah, Kecamatan Kota Baru, Jakarta, Kode Pos 12345',
            ],
            'office_phone_number' => [
                'description' => 'Refers to the Office Phone Number of Employee.',
                'example' => '02112345678',
            ],
            'emergency_contact' => [
                'description' => 'Refers to the Emergency Contact of Employee.',
                'example' => 'Riyanti, 02112345678, Kakak Kandung',
            ],
            'description' => [
                'description' => 'Refers to the Description of Employee.',
                'example' => 'Keterangan pegawai',
            ],
            'type' => [
                'description' => 'Refers to the Type of Employee. 1=ASN, 2=NON-ASN, 3=OUTSOURCE',
                'example' => 1,
            ],
            'delete_employee_id_card' => [
                'description' => 'Refers to the Status delete employee id card.',
                'example' => false,
            ],
            'quit_date' => [
                'description' => 'Refers to the Quit Date of Employee.',
                'example' => '2013-07-23',
            ],
            'cpns_effective_date' => [
                'description' => 'Refers to TMT CPNS / Tanggal Mulai Bekerja',
                'example' => '2023-01-01',
            ],
            'position_effective_date' => [
                'description' => 'Refers to the position effective date / TMT Menjabat',
                'example' => '2023-01-01',
            ],
            'education_level' => [
                'description' => 'Refers to the Level of Education of Employee. 1=SD/Sederajat, 2=SLTP/Sederajat, 3=SLTA/Sederajat, 4=Diploma I/II, 5=Akademik/D3/S.Muda, 6=Diploma IV/Strata I, 7=Strata II, 8=Strata III',
                'example' => 1,
            ],
            'education_name' => [
                'description' => 'Refers to the Name of Education of Employee.',
                'example' => 'Universitas Indonesia',
            ],
            'education_year' => [
                'description' => 'Refers to the Year of Education of Employee.',
                'example' => '1990',
            ],
            'family_registration_number' => [
                'description' => 'Refers to the Family Registration Number of Employee.',
                'example' => '3279034401000001',
            ],
            'office_email' => [
                'description' => 'Refers to the office email / email dinas',
                'example' => 'padmi@wapresri.go.id',
            ],
            'delete_photo_profile' => [
                'description' => 'Refers to the Status delete employee photo profile.',
                'example' => false,
            ],
        ];

        return array_merge(
            $userBodyParameters,
            UpdateEducationEmployeeRequest::bodyParameters(),
            UpdatePositionHistoryEmployeeRequest::bodyParameters(),
            UpdateGradeHistoryEmployeeRequest::bodyParameters(),
            UpdateTrainingHistoryStructuralEmployeeRequest::bodyParameters(),
            UpdateTrainingHistoryFunctionalEmployeeRequest::bodyParameters(),
            UpdateTrainingHistoryTechnicalEmployeeRequest::bodyParameters(),
            UpdateTargetHistoryEmployeeRequest::bodyParameters(),
            UpdatePerformanceHistoryEmployeeRequest::bodyParameters(),
            UpdateDisciplinaryHistoryEmployeeRequest::bodyParameters(),
            UpdateFamilyEmployeeRequest::bodyParameters(),
            UpdateLeaveEmployeeRequest::bodyParameters(),
            UpdateNoteEmployeeRequest::bodyParameters(),
            UpdateCreditEmployeeRequest::bodyParameters(),
            UpdateAssessmentEmployeeRequest::bodyParameters(),
            UpdateCompetencyEmployeeRequest::bodyParameters(),
            UpdateTalentEmployeeRequest::bodyParameters(),
        );
    }
}

