<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/*
 * @group Employee
 */

class SynchronizationController extends Controller
{
    protected Request $request;

    /** @var array<string, mixed> */
    protected array $posted;

    protected string $accessToken;

    /** @var array<string, string> */
    protected array $monthList;

    /** @var array<string, int> */
    protected array $gradeList;

    /** @var array<string, int|null> */
    protected array $educationList;

    /** @var array<string, int|null> */
    protected array $religionList;

    /** @var array<string, int> */
    protected array $employmentTypeList;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
        $this->accessToken = '';
        $this->monthList = [
            '01' => 'Januari',
            '02' => 'Februari',
            '03' => 'Maret',
            '04' => 'April',
            '05' => 'Mei',
            '06' => 'Juni',
            '07' => 'Juli',
            '08' => 'Agustus',
            '09' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
        ];
        $this->gradeList = [
            'IV/e' => 1,
            'IV/d' => 2,
            'IV/c' => 3,
            'IV/b' => 4,
            'IV/a' => 5,
            'III/d' => 6,
            'III/c' => 7,
            'III/b' => 8,
            'III/a' => 9,
            'II/d' => 10,
            'II/c' => 11,
            'II/b' => 12,
            'II/a' => 13,
            'I/d' => 14,
            'I/c' => 15,
            'I/b' => 16,
            'I/a' => 17,
            'XVII' => 18,
            'XVI' => 19,
            'XV' => 20,
            'XIV' => 21,
            'XIII' => 22,
            'XII' => 23,
            'XI' => 24,
            'X' => 25,
            'IX' => 26,
            'VIII' => 27,
            'VII' => 28,
            'VI' => 29,
            'V' => 30,
            'IV' => 31,
            'III' => 32,
            'II' => 33,
            'I' => 34,
        ];
        $this->educationList = [
            'SD' => 1,
            'SMP' => 2,
            'SMA' => 3,
            'SMU' => 3,
            'D-I' => 4,
            'D-II' => 4,
            'D-III' => 5,
            'D-IV' => 6,
            'S1' => 6,
            'S2' => 7,
            'S3' => 8,
            "" => null,
        ];
        $this->religionList = [
            'Islam' => 1,
            'Protestan' => 2,
            'Katolik' => 3,
            'Hindu' => 4,
            'Buddha' => 5,
            'Konghucu' => 6,
            "" => null,
        ];
        $this->employmentTypeList = [
            'TNI & Polri' => 1,
            'Sipil' => 2,
            'Organik' => 3,
            'PPPK' => 4,
        ];
    }

    /**
     * Synchronization Employee Data
     *
     * Synchronization data employee.
     * @group Employee
     * @authenticated
     * @response 200 {"code": 200,"message": "Pegawai berhasil disinkronisasi.","data": null}
     */
    public function index()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        try {
            $this->getAccessToken();
            $this->getPegawai();
            $this->getPosition();
            $this->getGrade();
            $this->getEducation();
            $this->getFamily();
            $this->getTraining();
            return $this->response(200, 'Pegawai berhasil disinkronisasi.');
        } catch (\Throwable $th) {
            Log::warning($th);
            return $this->response(400, 'Gagal melakukan sinkronisasi.');
        }
    }

    /**
     * get access token
     *
     * @return void
     */
    private function getAccessToken()
    {
        $response = Http::asForm()->post(env('SIMSDM_URL') . '/token', [
            'grant_type' => 'client_credentials',
            'client_id' => env('SIMSDM_CLIENT_ID'),
            'client_secret' => env('SIMSDM_CLIENT_SECRET'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->accessToken = $data['access_token'];
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
        }
    }

    /**
     * get pegawai data
     *
     * @return void
     */
    private function getPegawai()
    {
        $response = Http::withToken($this->accessToken)->get(env('SIMSDM_URL') . '/pegawai/v1/simdatuk/pegawaiAll');
        if ($response->successful()) {
            $dataPegawai = $response->json();
            foreach ($dataPegawai['data'] as $item) {
                $user = DB::table('users');
                $user->where('employee_id_number', $item['nipbaru']);
                $user = $user->first();
                if ($user) {
                    // Update Data
                    DB::table('users')->where('employee_id_number', $item['nipbaru'])->updateTs([
                        'employee_registration_number' => $item['niplama'],
                        'employee_id_card_number' => $item['karpeg'],
                        'id_tax' => preg_replace("/[^A-Za-z0-9?]/", "", $item['npwp']),
                        'years_of_service_total' => $item['mkseluruhtahun'],
                        'month_of_service_total' => $item['mkseluruhbulan'],
                        'years_of_service_rank' => $item['mkgoltahun'],
                        'month_of_service_rank' => $item['mkgolbulan'],
                        'office_email' => strtolower($item['email']),
                    ]);
                } else {
                    $dateString = ($item['tgllahir'] == "" || $item['tgllahir'] == "00-00-0000") ? null : $item['tgllahir'];
                    $date = new DateTime($dateString);
                    // Insert Data
                    DB::table('users')->insertTs([
                        'title_prefix' => $item['gelardepan'],
                        'name' => $item['nmpeg'],
                        'title_suffix' => $item['gelarbelakang'],
                        'employee_id_number' => $item['nipbaru'],
                        'employee_registration_number' => $item['niplama'],
                        'place_of_birth' => $item['tempatlahir'],
                        'date_of_birth' => $date,
                        'religion' => $this->religionList[$item['agama']],
                        'gender' => ($item['jeniskelamin'] == 'Perempuan') ? 0 : 1,
                        'marital_status' => ($item['statkwn'] == 'Kawin') ? 2 : 1,
                        'employment_type_id' => $this->employmentTypeList[$item['jenispeg']],
                        'education_level' => $this->educationList[$item['pend_terakhir']],
                        'employee_id_card_number' => $item['karpeg'],
                        'id_tax' => preg_replace("/[^A-Za-z0-9?]/", "", $item['npwp']),
                        'employment_status' => ($item['statuspeg'] == 'Aktif') ? 1 : 9,
                        'id_number' => $item['nik'],
                        'current_address' => $item['alamat'],
                        'mobile_phone' => $item['nohp'],
                        'years_of_service_total' => $item['mkseluruhtahun'],
                        'month_of_service_total' => $item['mkseluruhbulan'],
                        'years_of_service_rank' => $item['mkgoltahun'],
                        'month_of_service_rank' => $item['mkgolbulan'],
                        'office_email' => strtolower($item['email']),
                    ]);
                }
            }
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
        }
    }

    private function getPosition()
    {

        $response = Http::withToken($this->accessToken)->get(env('SIMSDM_URL') . '/pegawai/v1/simdatuk/riwayatJabatan');
        if ($response->successful()) {
            $dataPegawai = $response->json();
            foreach ($dataPegawai['data'] as $item) {
                $user = DB::table('users');
                $user->select('id');
                $user->where('employee_id_number', $item['nipbaru']);
                $user = $user->first();
                if ($user) {
                    $dateString = $item['tmt'];
                    $date = new DateTime($dateString);

                    // Check if history is exist
                    $positionHistoriesId = DB::table('position_histories');
                    $positionHistoriesId->select('id');
                    $positionHistoriesId->where('period_month', $date->format('m'));
                    $positionHistoriesId->where('period_year', $date->format('Y'));
                    $positionHistoriesId = $positionHistoriesId->first();
                    if (!$positionHistoriesId) {
                        $positionHistoriesId = DB::table('position_histories')->insertGetIdTs([
                            'name' => 'Perubahan Jabatan ' . $this->monthList[$date->format('m')] . ' ' . $date->format('Y'),
                            'period_month' => $date->format('m'),
                            'period_year' => $date->format('Y'),
                        ]);
                    } else {
                        $positionHistoriesId = $positionHistoriesId->id;
                    }

                    // Check if history user is exist
                    $positionHistoryUsers = DB::table('position_history_users');
                    $positionHistoryUsers->select('id');
                    $positionHistoryUsers->where('user_id', $user->id);
                    $positionHistoryUsers->where('position_history_id', $positionHistoriesId);
                    $positionHistoryUsers->where('position', $item['nama_jabatan']);
                    $positionHistoryUsers->where('effective_date', $item['tmt']);
                    $positionHistoryUsers->where('decree', $item['nosk']);
                    $positionHistoryUsers = $positionHistoryUsers->first();
                    if (!$positionHistoryUsers) {
                        DB::table('position_history_users')->insertTs([
                            'position_history_id' => $positionHistoriesId,
                            'user_id' => $user->id,
                            'position' => $item['nama_jabatan'],
                            'effective_date' => $item['tmt'],
                            'decree' => $item['nosk'],
                        ]);
                    }
                }
            }
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
        }
    }

    private function getGrade()
    {
        $response = Http::withToken($this->accessToken)->get(env('SIMSDM_URL') . '/pegawai/v1/simdatuk/riwayatGolongan');
        if ($response->successful()) {
            $dataPegawai = $response->json();
            foreach ($dataPegawai['data'] as $item) {
                $user = DB::table('users');
                $user->select('id');
                $user->where('employee_id_number', $item['nipbaru']);
                $user = $user->first();
                if ($user && $item['tmt'] !== '0000-00-00') {
                    $dateString = $item['tmt'];
                    $date = new DateTime($dateString);

                    // Check if history is exist
                    $gradeHistoriesId = DB::table('grade_histories');
                    $gradeHistoriesId->select('id');
                    $gradeHistoriesId->where('period_month', $date->format('m'));
                    $gradeHistoriesId->where('period_year', $date->format('Y'));
                    $gradeHistoriesId = $gradeHistoriesId->first();
                    if (!$gradeHistoriesId) {
                        $gradeHistoriesId = DB::table('grade_histories')->insertGetIdTs([
                            'name' => 'Perubahan Golongan ' . $this->monthList[$date->format('m')] . ' ' . $date->format('Y'),
                            'period_month' => $date->format('m'),
                            'period_year' => $date->format('Y'),
                        ]);
                    } else {
                        $gradeHistoriesId = $gradeHistoriesId->id;
                    }

                    // Check if history user is exist
                    $gradeHistoryUsers = DB::table('grade_history_users');
                    $gradeHistoryUsers->select('id');
                    $gradeHistoryUsers->where('user_id', $user->id);
                    $gradeHistoryUsers->where('grade_history_id', $gradeHistoriesId);
                    $gradeHistoryUsers->where('grade_id', $this->gradeList[$item['golongan']]);
                    $gradeHistoryUsers->where('effective_date', $item['tmt']);
                    $gradeHistoryUsers = $gradeHistoryUsers->first();
                    if (!$gradeHistoryUsers) {
                        DB::table('grade_history_users')->insertTs([
                            'grade_history_id' => $gradeHistoriesId,
                            'user_id' => $user->id,
                            'grade_id' => $this->gradeList[$item['golongan']],
                            'effective_date' => $item['tmt'],
                        ]);
                    }
                }
            }
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
        }
    }

    private function getEducation()
    {
        $response = Http::withToken($this->accessToken)->get(env('SIMSDM_URL') . '/pegawai/v1/simdatuk/riwayatPendidikan');
        if ($response->successful()) {
            $dataPegawai = $response->json();
            foreach ($dataPegawai['data'] as $item) {
                $user = DB::table('users');
                $user->select('id');
                $user->where('employee_id_number', $item['nipbaru']);
                $user = $user->first();

                $educationLevel = (isset($this->educationList[$item['pendidikan_formal']])) ? $this->educationList[$item['pendidikan_formal']] : null;
                $yearOfGraduation = ($item['thn_lulus'] == '') ? null : $item['thn_lulus'];
                if ($user) {
                    $query = DB::table('user_educations');
                    $query->where('user_id', $user->id);
                    $query->where('level', $educationLevel);
                    $query->where('name', $item['nama_alamat_lembaga']);
                    $query->where('faculty', $item['bidang_studi']);
                    $query->where('major', $item['bidang_studi']);
                    $query->where('year_of_graduation', $yearOfGraduation);
                    $query->where('description', $item['nama_alamat_lembaga']);
                    $query = $query->first();
                    if (!$query) {
                        DB::table('user_educations')->insertTs([
                            'user_id' => $user->id,
                            'level' => $educationLevel,
                            'name' => $item['nama_alamat_lembaga'],
                            'faculty' => $item['bidang_studi'],
                            'major' => $item['bidang_studi'],
                            'year_of_graduation' => $yearOfGraduation,
                            'description' => $item['nama_alamat_lembaga'],
                        ]);
                    }
                }
            }
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
        }
    }

    private function getFamily()
    {
        // Suami Istri
        $response = Http::withToken($this->accessToken)->get(env('SIMSDM_URL') . '/pegawai/v1/simdatuk/riwayatIstriSuami');
        if ($response->successful()) {
            $dataPegawai = $response->json();
            foreach ($dataPegawai['data'] as $item) {
                $user = DB::table('users');
                $user->select('id');
                $user->where('employee_id_number', $item['nipbaru']);
                $user = $user->first();

                $gender = ($item['jenis_kelamin'] == 'Perempuan') ? 0 : 1;
                $relationshipStatus = ($gender == 1) ? 2 : 3;
                $maritalStatus = ($item['status_perkawinan'] == 'Menikah') ? 2 : null;
                $dateOfBirth = ($item['tanggal_lahir'] == '0000-00-00') ? null : $item['tanggal_lahir'];
                if ($user) {
                    $query = DB::table('user_families');
                    $query->where('user_id', $user->id);
                    $query->where('name', $item['nama_istri_suami']);
                    $query->where('gender', $gender);
                    $query->where('place_of_birth', $item['tempat_lahir']);
                    $query->where('date_of_birth', $dateOfBirth);
                    $query->where('relationship_status', $relationshipStatus);
                    $query->where('marital_status', $maritalStatus);
                    $query->where('occupation', $item['pekerjaan']);
                    $query = $query->first();
                    if (!$query) {
                        DB::table('user_families')->insertTs([
                            'user_id' => $user->id,
                            'name' => $item['nama_istri_suami'],
                            'gender' => $gender,
                            'place_of_birth' => $item['tempat_lahir'],
                            'date_of_birth' => $dateOfBirth,
                            'relationship_status' => $relationshipStatus,
                            'marital_status' => $maritalStatus,
                            'occupation' => $item['pekerjaan'],
                        ]);
                    }
                }
            }
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
        }

        $response = Http::withToken($this->accessToken)->get(env('SIMSDM_URL') . '/pegawai/v1/simdatuk/riwayatAnak');
        if ($response->successful()) {
            $dataPegawai = $response->json();
            foreach ($dataPegawai['data'] as $item) {
                $user = DB::table('users');
                $user->select('id');
                $user->where('employee_id_number', $item['nipbaru']);
                $user = $user->first();

                $gender = ($item['jenis_kelamin'] == 'Perempuan') ? 0 : 1;
                $relationshipStatus = 4;
                if ($item['status_perkawinan'] == 'Belum Kawin') {
                    $maritalStatus = 1;
                } elseif ($item['status_perkawinan'] == 'Menikah') {
                    $maritalStatus = 2;
                } else {
                    $maritalStatus = null;
                }
                $dateOfBirth = ($item['tanggal_lahir'] == '0000-00-00') ? null : $item['tanggal_lahir'];
                if ($user) {
                    $query = DB::table('user_families');
                    $query->where('user_id', $user->id);
                    $query->where('name', $item['nama_anak']);
                    $query->where('gender', $gender);
                    $query->where('place_of_birth', $item['tempat_lahir']);
                    $query->where('date_of_birth', $dateOfBirth);
                    $query->where('relationship_status', $relationshipStatus);
                    $query->where('marital_status', $maritalStatus);
                    $query->where('occupation', $item['pekerjaan']);
                    $query = $query->first();
                    if (!$query) {
                        DB::table('user_families')->insertTs([
                            'user_id' => $user->id,
                            'name' => $item['nama_anak'],
                            'gender' => $gender,
                            'place_of_birth' => $item['tempat_lahir'],
                            'date_of_birth' => $dateOfBirth,
                            'relationship_status' => $relationshipStatus,
                            'marital_status' => $maritalStatus,
                            'occupation' => $item['pekerjaan'],
                        ]);
                    }
                }
            }
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
        }
    }

    private function getTraining()
    {
        $response = Http::withToken($this->accessToken)->get(env('SIMSDM_URL') . '/pegawai/v1/simdatuk/riwayatDiklatTeknis');
        if ($response->successful()) {
            $dataPegawai = $response->json();
            foreach ($dataPegawai['data'] as $item) {
                $user = DB::table('users');
                $user->select('id');
                $user->where('employee_id_number', $item['nipbaru']);
                $user = $user->first();
                if ($user) {
                    $item['tgl_mulai'] = ($item['tgl_mulai'] == '' || $item['tgl_mulai'] == '0000-00-00') ? null : $item['tgl_mulai'];
                    $item['tgl_selesai'] = ($item['tgl_selesai'] == '' || $item['tgl_selesai'] == '0000-00-00') ? null : $item['tgl_selesai'];
                    $dateString = $item['tgl_mulai'];
                    $date = new DateTime($dateString);

                    // Check if history is exist
                    $trainingHistoriesId = DB::table('training_histories');
                    $trainingHistoriesId->select('id');
                    $trainingHistoriesId->where('name', $item['nama_diklat']);
                    $trainingHistoriesId->where('period_month', $date->format('m'));
                    $trainingHistoriesId->where('period_year', $date->format('Y'));
                    $trainingHistoriesId->where('type', 3);
                    $trainingHistoriesId = $trainingHistoriesId->first();
                    if (!$trainingHistoriesId) {
                        $startDate = new DateTime($item['tgl_mulai']);
                        $endDate = new DateTime($item['tgl_selesai']);
                        $duration = $endDate->diff($endDate);
                        $trainingHistoriesId = DB::table('training_histories')->insertGetIdTs([
                            'name' => $item['nama_diklat'],
                            'period_month' => $date->format('m'),
                            'period_year' => $date->format('Y'),
                            'start_date' => $item['tgl_mulai'],
                            'duration' => $duration->days,
                            'reference_number' => $item['no_sertifikat'],
                            'type' => 3,
                        ]);
                    } else {
                        $trainingHistoriesId = $trainingHistoriesId->id;
                    }

                    // Check if history user is exist
                    $trainingHistoryUsers = DB::table('training_history_users');
                    $trainingHistoryUsers->select('id');
                    $trainingHistoryUsers->where('user_id', $user->id);
                    $trainingHistoryUsers->where('training_history_id', $trainingHistoriesId);
                    $trainingHistoryUsers = $trainingHistoryUsers->first();
                    if (!$trainingHistoryUsers) {
                        DB::table('training_history_users')->insertTs([
                            'user_id' => $user->id,
                            'training_history_id' => $trainingHistoriesId,
                        ]);
                    }
                }
            }
        } else {
            Log::error('Failed to obtain access token', ['response' => $response->body()]);
        }

        // $response = Http::withToken($this->accessToken)->get(env('SIMSDM_URL') . '/pegawai/v1/simdatuk/riwayatDiklat');
        // if ($response->successful()) {
        //     $dataPegawai = $response->json();
        //     foreach ($dataPegawai['data'] as $item) {
        //         $user = DB::table('users');
        //         $user->select('id');
        //         $user->where('employee_id_number', $item['nipbaru']);
        //         $user = $user->first();
        //         if ($user) {
        //             $item['tgl_mulai'] = ($item['tgl_mulai'] == '' || $item['tgl_mulai'] == '0000-00-00') ? null : $item['tgl_mulai'];
        //             $item['tgl_selesai'] = ($item['tgl_selesai'] == '' || $item['tgl_selesai'] == '0000-00-00') ? null : $item['tgl_selesai'];
        //             $dateString = $item['tgl_mulai'];
        //             $date = new DateTime($dateString);

        //             // Check if history is exist
        //             $trainingHistoriesId = DB::table('training_histories');
        //             $trainingHistoriesId->select('id');
        //             $trainingHistoriesId->where('name', $item['nama_diklat']);
        //             $trainingHistoriesId->where('period_month', $date->format('m'));
        //             $trainingHistoriesId->where('period_year', $date->format('Y'));
        //             $trainingHistoriesId->where('type', 1);
        //             $trainingHistoriesId = $trainingHistoriesId->first();
        //             if (!$trainingHistoriesId) {
        //                 $startDate = new DateTime($item['tgl_mulai']);
        //                 $endDate = new DateTime($item['tgl_selesai']);
        //                 $duration = $endDate->diff($endDate);
        //                 $trainingHistoriesId = DB::table('training_histories')->insertGetIdTs([
        //                     'name' => $item['nama_diklat'],
        //                     'period_month' => $date->format('m'),
        //                     'period_year' => $date->format('Y'),
        //                     'start_date' => $item['tgl_mulai'],
        //                     'duration' => $duration->days,
        //                     'organizer' => $item['instansi'],
        //                     'reference_number' => $item['no_sertifikat'],
        //                     'type' => 1,
        //                 ]);
        //             } else {
        //                 $trainingHistoriesId = $trainingHistoriesId->id;
        //             }

        //             // Check if history user is exist
        //             $trainingHistoryUsers = DB::table('training_history_users');
        //             $trainingHistoryUsers->select('id');
        //             $trainingHistoryUsers->where('user_id', $user->id);
        //             $trainingHistoryUsers->where('training_history_id', $trainingHistoriesId);
        //             $trainingHistoryUsers = $trainingHistoryUsers->first();
        //             if (!$trainingHistoryUsers) {
        //                 DB::table('training_history_users')->insertTs([
        //                     'user_id' => $user->id,
        //                     'training_history_id' => $trainingHistoriesId,
        //                 ]);
        //             }
        //         }
        //     }
        // } else {
        //     \Log::error('Failed to obtain access token', ['response' => $response->body()]);
        // }
    }
}
