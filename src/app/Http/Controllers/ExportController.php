<?php

namespace App\Http\Controllers;

use App\Exports\employee;
use App\Http\Requests\Export\ExportEmployeesRequest;
use App\Http\Requests\Export\ExportZipEmployeesRequest;
use App\Http\Requests\Export\PreviewExportEmployeesRequest;
use App\Repositories\AssessmentRepository;
use App\Repositories\CompetencyRepository;
use App\Repositories\CreditRepository;
use App\Repositories\DisciplinaryRepository;
use App\Repositories\EducationRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\FamilyRepository;
use App\Repositories\GradeRepository;
use App\Repositories\LeaveRepository;
use App\Repositories\NoteRepository;
use App\Repositories\PerformanceRepository;
use App\Repositories\PositionRepository;
use App\Repositories\RecognitionRepository;
use App\Repositories\TalentRepository;
use App\Repositories\TargetRepository;
use App\Repositories\TrainingRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use ZipArchive;

/**
 * @group Export
 */
class ExportController extends Controller
{
    protected $employeeRepository;
    protected $educationRepository;
    protected $familyRepository;
    protected $positionRepository;
    protected $gradeRepository;
    protected $trainingRepository;
    protected $recognitionRepository;
    protected $targetRepository;
    protected $performanceRepository;
    protected $disciplinaryRepository;
    protected $leaveRepository;
    protected $noteRepository;
    protected $creditRepository;
    protected $assessmentRepository;
    protected $competencyRepository;
    protected $talentRepository;

    protected $request;
    protected $posted;

    public function __construct(
        Request $request,
        EmployeeRepository $employeeRepository,
        EducationRepository $educationRepository,
        FamilyRepository $familyRepository,
        PositionRepository $positionRepository,
        GradeRepository $gradeRepository,
        TrainingRepository $trainingRepository,
        RecognitionRepository $recognitionRepository,
        TargetRepository $targetRepository,
        PerformanceRepository $performanceRepository,
        DisciplinaryRepository $disciplinaryRepository,
        LeaveRepository $leaveRepository,
        NoteRepository $noteRepository,
        CreditRepository $creditRepository,
        AssessmentRepository $assessmentRepository,
        CompetencyRepository $competencyRepository,
        TalentRepository $talentRepository,
    ) {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');

        $this->employeeRepository = $employeeRepository;
        $this->educationRepository = $educationRepository;
        $this->familyRepository = $familyRepository;
        $this->positionRepository = $positionRepository;
        $this->gradeRepository = $gradeRepository;
        $this->trainingRepository = $trainingRepository;
        $this->recognitionRepository = $recognitionRepository;
        $this->targetRepository = $targetRepository;
        $this->performanceRepository = $performanceRepository;
        $this->disciplinaryRepository = $disciplinaryRepository;
        $this->leaveRepository = $leaveRepository;
        $this->noteRepository = $noteRepository;
        $this->creditRepository = $creditRepository;
        $this->assessmentRepository = $assessmentRepository;
        $this->competencyRepository = $competencyRepository;
        $this->talentRepository = $talentRepository;
    }

    /**
     * Export Detail DRH Employee
     *
     * Export detail employee data to .PDF.
     * @urlParam id Refers to the ID of Employee. Example:: 1
     * @group Export
     * @authenticated
     */
    public function detailEmployee($employeeId = null)
    {
        $employeeId = $employeeId ?? $this->request->id;
        if (empty($employeeId)) {
            return response()->json(['error' => 'No employee ID provided'], 400);
        }
        $tmp = sys_get_temp_dir();
        $employee = $this->employeeRepository->getDetail($employeeId, true);
        if (!$employee) {
            return $this->response(404, 'Pegawai tidak ditemukan.');
        }

        $educations = $this->educationRepository->getDetail($employeeId);
        $families = $this->familyRepository->getDetail($employeeId);
        $positions = $this->positionRepository->getDetail($employeeId);
        $grades = $this->gradeRepository->getDetail($employeeId);
        $structurals = $this->trainingRepository->getDetail($employeeId, 1);
        $functionals = $this->trainingRepository->getDetail($employeeId, 2);
        $technicals = $this->trainingRepository->getDetail($employeeId, 3);
        $recognitions = $this->recognitionRepository->getDetail($employeeId); //penghargaan
        $targets = $this->targetRepository->getDetail($employeeId); //SKP
        $performances = $this->performanceRepository->getDetail($employeeId); //prestasi kerja
        $disciplinaries = $this->disciplinaryRepository->getDetail($employeeId); //hukdis
        $leaves = $this->leaveRepository->getDetail($employeeId);
        $notes = $this->noteRepository->getDetail($employeeId);
        $credits = $this->creditRepository->getDetail($employeeId);
        $assessments = $this->assessmentRepository->getDetail($employeeId);
        $competencies = $this->competencyRepository->getDetail($employeeId);
        $talents = $this->talentRepository->getDetail($employeeId);

        $religion = match ($employee->religion) {
            1 => 'Islam',
            2 => 'Kristen',
            3 => 'Katolik',
            4 => 'Hindu',
            5 => 'Budha',
            6 => 'Konghucu',
            default => '-',
        };
        $maritalStatus = match ($employee->marital_status) {
            1 => 'Belum Menikah',
            2 => 'Menikah',
            3 => 'Cerai',
            4 => 'Janda',
            5 => 'Duda',
            default => '-',
        };
        $employeeType = match ($employee->type) {
            1 => 'ASN',
            2 => 'NON ASN',
            3 => 'OUTSOURCING',
            default => '-',
        };
        $educationLevel = match ($employee->education_level) {
            1 => 'SD/Sederajat',
            2 => 'SLTP/Sederajat',
            3 => 'SLTA/Sederajat',
            4 => 'Diploma I/II',
            5 => 'Akademik/D3/S.Muda',
            6 => 'Diploma IV/Strata I',
            7 => 'Strata II',
            8 => 'Strata III',
            default => '-',
        };
        $employmentStatus = match ($employee->employment_status) {
            1 => 'Aktif',
            2 => 'Pensiun',
            3 => 'Berhenti',
            4 => 'Meninggal',
            5 => 'Alih Status',
            6 => 'Aktif PS',
            7 => 'CLTN',
            8 => 'TBLN',
            9 => 'Non Aktif',
            10 => 'Hukuman Disiplin',
            default => '-',
        };

        // Batas Usia Pensiun
        $indonesianMonth = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        foreach ($credits as $key => $value) {
            $credits[$key]->start_month_name = ($value->start_month) ? $indonesianMonth[$value->start_month] : '';
            $credits[$key]->end_month_name = ($value->end_month) ? $indonesianMonth[$value->end_month] : '';
        }

        $masaKerjaKeseluruhan = '-';
        if ($employee->years_of_service_total > 0 || $employee->month_of_service_total > 0) {
            $masaKerjaKeseluruhan = (($employee->years_of_service_total > 0) ? $employee->years_of_service_total : 0) . " Tahun, " . (($employee->month_of_service_total > 0) ? $employee->month_of_service_total : 0) . " Bulan";
        }
        $masaKerjaGolongan = '-';
        if ($employee->years_of_service_rank > 0 || $employee->month_of_service_rank > 0) {
            $masaKerjaGolongan = (($employee->years_of_service_rank > 0) ? $employee->years_of_service_rank : 0) . " Tahun, " . (($employee->month_of_service_rank > 0) ? $employee->month_of_service_rank : 0) . " Bulan";
        }
        $retirementAge = '';
        if (!is_null($employee->retirement_age)) {
            $month = date('n', strtotime($employee->retirement_age));
            $year = date('Y', strtotime($employee->retirement_age));
            $retirementAge = $indonesianMonth[$month] . ' ' . $year . ' (' . $employee->retirement_age_years . ' Tahun)';
        }

        $exportData = [
            'currentPosition' => ($employee->position_merged ?? '-'),
            'photoProfile' => $employee->photo_profile,
            'userNIP' => $employee->employee_id_number,
            'userName' => $employee->title_prefix . ' ' . $employee->name . ' ' . $employee->title_suffix,
            'userEchelons' => ($employee->echelon_name ?? '') . ', ' . ($employee->echelon_effective_date ?? ' '),
            'userCurrentGrade' => ($employee->grade_name ?? '') . '(' . ($employee->grade_code ?? '') . '), ' . ($employee->grade_effective_date ?? ''),
            'userType' => $employee->type
        ];
        if ($employee->type == 1) {
            $userProfile = [
                'Tempat, Tanggal Lahir' => $employee->place_of_birth . ', ' . $employee->date_of_birth,
                'Agama' => $religion,
                'Jenis Kelamin' => ($employee->gender ? 'Pria' : 'Wanita'),
                'Status Perkawinan' => $maritalStatus,
                'Tanggal Perkawinan' => $employee->marriage_date,
                'Keterangan Perkawinan' => $employee->marriage_description,
                // 'Keterangan Lainnya' => $employee->marriage_other_notes,
                'Jenis Pegawai' => $employeeType,
                'TMT CPNS' => $employee->cpns_effective_date,
                'TMT PNS' => $employee->pns_effective_date,
                'TMT Menjabat' => ($employee->position_effective_date ?? '-'),
                'Instansi Induk' => ($employee->institution_name ?? '-'),
                'Tingkat Pendidikan Akhir' => $educationLevel,
                'Nama Sekolah/Universitas' => $employee->education_name,
                'Tahun Lulus' => $employee->education_year,
                'No. Karpeg/No. Karisu' => $employee->employee_id_card_number . ' / ' . $employee->karisu_number,
                'Masa Kerja Keseluruhan' => $masaKerjaKeseluruhan,
                'Masa Kerja Golongan' => $masaKerjaGolongan,
                'NPWP' => $employee->id_tax,
                'Status Pegawai' => $employmentStatus,
                'No NIK' => $employee->id_number,
                'Komplek' => $employee->residence_name,
                'Alamat Tempat Tinggal Saat Ini' => $employee->residence_description,
                'Alamat Sesuai KTP' => $employee->current_address,
                'No. Telepon Rumah' => $employee->home_phone_number,
                'No. HP' => $employee->mobile_phone,
                'Alamat Kantor' => $employee->office_address,
                'No. Telepon Kantor' => $employee->office_phone_number,
                'Email' => $employee->email,
                'Email Dinas' => $employee->office_email,
                'Kontak Darurat' => $employee->emergency_contact,
                'Batas Usia Pensiun' => $retirementAge,
            ];
            $exportData['userProfile'] = $userProfile;
            $exportData['userCollege'] = $educations;
            $exportData['userPosition'] = $positions;
            $exportData['userGrade'] = $grades;
            $exportData['userTrainingStructural'] = $structurals;
            $exportData['userTrainingFunctional'] = $functionals;
            $exportData['userTrainingTechnical'] = $technicals;
            $exportData['userAward'] = $recognitions;
            $exportData['userSKP'] = $targets;
            $exportData['userCredit'] = $credits;
            $exportData['userPerformance'] = $performances;
            $exportData['userPunishment'] = $disciplinaries;
            $exportData['userFamily'] = $families;
            $exportData['userLeave'] = $leaves;
            $exportData['userNotes'] = $notes;
            $exportData['userAssessment'] = $assessments;
            $exportData['userAssessmentCompetency'] = $competencies;
            $exportData['userAssessmentTalent'] = $talents;
        } else if ($employee->type == 2) {
            $userProfile = [
                'Tempat, Tanggal Lahir' => $employee->place_of_birth . ', ' . $employee->date_of_birth,
                'Agama' => $religion,
                'Jenis Kelamin' => ($employee->gender ? 'Pria' : 'Wanita'),
                'Status Perkawinan' => $maritalStatus,
                'Jenis Pegawai' => $employeeType,
                'Tanggal Mulai Bekerja di Sekretariat Wakil Presiden' => $employee->cpns_effective_date,
                'TMT Menjabat' => ($employee->position_effective_date ?? '-'),
                'Instansi Induk' => ($employee->institution_name ?? '-'),
                'Tingkat Pendidikan Akhir' => $educationLevel,
                'Nama Sekolah/Universitas' => $employee->education_name,
                'Tahun Lulus' => $employee->education_year,
                'NPWP' => $employee->id_tax,
                'Status Pegawai' => $employmentStatus,
                'No NIK' => $employee->id_number,
                'Alamat Tempat Tinggal Saat Ini' => $employee->residence_description,
                'Alamat Sesuai KTP' => $employee->current_address,
                'No. Telepon Rumah' => $employee->home_phone_number,
                'No. HP' => $employee->mobile_phone,
                'Alamat Kantor' => $employee->office_address,
                'No. Telepon Kantor' => $employee->office_phone_number,
                'Email' => $employee->email,
                'Email Dinas' => $employee->office_email,
                'Kontak Darurat' => $employee->emergency_contact,
            ];
            $exportData['userProfile'] = $userProfile;
            $exportData['userPosition'] = $positions;
        } else {
            $userProfile = [
                'Tempat, Tanggal Lahir' => $employee->place_of_birth . ', ' . $employee->date_of_birth,
                'Agama' => $religion,
                'Jenis Kelamin' => ($employee->gender ? 'Pria' : 'Wanita'),
                'Status Perkawinan' => $maritalStatus,
                'Jenis Pegawai' => $employeeType,
                'Tanggal Mulai Bekerja' => $employee->cpns_effective_date,
                'TMT Menjabat' => ($employee->position_effective_date ?? '-'),
                'Tingkat Pendidikan Akhir' => $educationLevel,
                'Nama Sekolah/Universitas' => $employee->education_name,
                'Tahun Lulus' => $employee->education_year,
                'NPWP' => $employee->id_tax,
                'Status Pegawai' => $employmentStatus,
                'No NIK' => $employee->id_number,
                'Alamat Tempat Tinggal Saat Ini' => $employee->residence_description,
                'Alamat Sesuai KTP' => $employee->current_address,
                'No. Telepon Rumah' => $employee->home_phone_number,
                'No. HP' => $employee->mobile_phone,
                'Alamat Kantor' => $employee->office_address,
                'No. Telepon Kantor' => $employee->office_phone_number,
                'Email' => $employee->email,
                'Email Dinas' => $employee->office_email,
                'Kontak Darurat' => $employee->emergency_contact,
            ];
            $exportData['userProfile'] = $userProfile;
            $exportData['userCollege'] = $educations;
            $exportData['userPosition'] = $positions;
            $exportData['userTrainingTechnical'] = $technicals;
            $exportData['userFamily'] = $families;
            $exportData['userNotes'] = $notes;
        }

        // $pdf = Pdf::loadview('exports/user', [
        //     'userProfile' => $userProfile,
        //     'currentPosition' => ($employee->position_merged ?? '-'),
        //     'photoProfile' => $employee->photo_profile,
        //     'userNIP' => $employee->employee_id_number,
        //     'userName' => $employee->name,
        //     'userEchelons' => ($employee->echelon_name ?? '') . ', ' . ($employee->echelon_effective_date ?? ' '),
        //     'userCurrentGrade' => ($employee->grade_name ?? '') . '(' . ($employee->grade_code ?? '') . '), ' . ($employee->grade_effective_date ?? ''),

        // ]);

        $exportData['permissions'] = $this->getUserViewPermissions();

        $pdf = Pdf::loadview('exports/user', $exportData);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_paper("A4", "portrait");
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('fontDir', $tmp);
        $pdf->set_option('fontCache', $tmp);
        $pdf->set_option('tempDir', $tmp);
        return $pdf->download($employee->name . '_' . $employee->employee_id_number . '.pdf');
    }
    /**
     * Export Detail DRH Employee
     *
     * Export detail of multiple employees DRH data to .PDF inside a zip file.
     * @group Export
     * @authenticated
     *
     * @bodyParam employee_type int[] Refers to IDs of type of employee (1: ASN, 2: Non ASN, 3: Outsourcing). Example: [1, 3]
     * @bodyParam deputy int[] List of employee's deputy. Example: [1, 3]
     * @bodyParam echelons int[] Refers to IDs of employee echelons. Example: [1, 3]
     * @bodyParam grades int[] Refers to IDs of employee grades. Example: [1, 3]
     * @bodyParam position_status int[] Refers to IDs of employee position status. Example: [1, 3]
     * @bodyParam education int[] Refers to type of employee education (1=SD/Sederajat, 2=SLTP/Sederajat, 3=SLTA/Sederajat, 4=Akademik/D3/S.Muda, 5=Diploma IV, 6=Strata I, 7=Strata II, 8=Strata III). Example: [1, 3]
     * @bodyParam gender int[] Refers to gender of employee (1: Laki - Laki, 0: Perempuan). Example: [1]
     * @bodyParam min_age int Refers to minimum age of employee. Example: 50
     * @bodyParam max_age int Refers to maximum age of employee. Example: 55
     * @bodyParam marital_status int[] Refers to marital status of employee (1=Belum Menikah, 2=Menikah, 3=Cerai Hidup, 4=Cerai Mati). Example: [1, 3]
     * @bodyParam retirement_age int[] Refers to retirement year of employee. Example: [58,60]
     * @bodyParam retirement_year int Refers to retirement year of employee. Example: 2024
     * @bodyParam total_working_duration int[] Refers to total duration of employee employment. Example: ["5-10"]
     * @bodyParam grade_range int[] Refers to duration of grade in years. Example: ["5-10"]
     * @bodyParam employment_status int[] Refers to employment status of employee (1=Aktif, 2=Pensiun, 3=Berhenti, 4=Meninggal, 5=Alih Status ,6=Aktif PS ,7=CLTN ,8=TBLN ,9=Non Aktif). Example: [1, 3]
     **/
    public function zipDetailEmployee(ExportZipEmployeesRequest $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        $user = DB::table('users');
        $user->leftJoin('echelons', 'echelons.id', '=', 'users.echelon_id');
        if (isset($request->employee_type)) {
            $user->whereIn('users.type', $request->employee_type);
        }
        if (isset($request->echelons)) {
            $user->whereIn('echelons.id', $request->echelons);
        }
        if (isset($request->grades)) {
            $user->whereIn('users.grade_id', $request->grades);
        }
        if (isset($request->job_description)) {
            $user->whereIn('users.description', $request->job_description);
        }
        if (isset($request->education)) {
            $user->whereIn('users.education_level', $request->education);
        }
        if (isset($request->gender)) {
            $user->whereIn('users.gender', $request->gender);
        }
        if (isset($request->min_age)) {
            $minAge = $request->input('min_age');
            $user->where(DB::raw('TIMESTAMPDIFF(YEAR, users.date_of_birth, NOW())'), '>=', $minAge);
        }
        if (isset($request->max_age)) {
            $maxAge = $request->input('max_age');
            $user->where(DB::raw('TIMESTAMPDIFF(YEAR, users.date_of_birth, NOW())'), '<=', $maxAge);
        }
        if (isset($request->marital_status)) {
            $user->whereIn('users.marital_status', $request->marital_status);
        }
        if (isset($request->grade_range)) {
            $gradeRanges = $request->input('grade_range', []);

            $user->where(function ($query) use ($gradeRanges) {
                foreach ($gradeRanges as $range) {
                    [$minYears, $maxYears] = explode('-', $range);

                    if ($minYears == "0") {
                        $query->whereRaw(DB::raw("
                        (
                            ((years_of_service_rank = 0 OR years_of_service_rank IS NULL) AND month_of_service_rank > 0)
                            OR (years_of_service_rank = " . $maxYears . " AND (month_of_service_rank IS NULL OR month_of_service_rank = 0))
                            OR (years_of_service_rank > 0 AND years_of_service_rank < " . $maxYears . ")
                         )"));
                    } else {
                        $query->whereRaw(DB::raw("
                        (
                            years_of_service_rank = " . $minYears . " AND month_of_service_rank > 0
                            OR (years_of_service_rank = " . $maxYears . " AND (month_of_service_rank IS NULL OR month_of_service_rank = 0))
                            OR (years_of_service_rank > " . $minYears . " AND years_of_service_rank < " . $maxYears . ")
                         )"));
                    }
                }
            });
        }
        if (isset($request->employment_status)) {
            $user->whereIn('users.employment_status', $request->employment_status);
        }
        if (isset($request->retirement_age) && isset($request->retirement_year)) { // Age and year
            $user->where(function ($query) use ($request) {
                foreach ($request->retirement_age as $age) {
                    $query->orWhere(DB::raw("YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + $age"), $request->retirement_year);
                }
            });
        }
        if (isset($request->retirement_age) && !isset($request->retirement_year)) { // Age only
            $user->whereIn('echelons.retirement_age', $request->retirement_age);
        }
        if (!isset($request->retirement_age) && isset($request->retirement_year)) { // Year only
            $user->whereRaw("
                CASE
                    WHEN users.type = 1 AND users.echelon_id IS NOT NULL AND date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + echelons.retirement_age = ?
                    WHEN users.type = 2 AND users.date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + 58 = ?
                    WHEN users.type = 3 AND users.date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + 58 = ?
                END
            ", [$request->retirement_year, $request->retirement_year, $request->retirement_year]);
        }
        if (isset($request->total_working_duration)) {
            $gradeRanges = $request->input('total_working_duration', []);

            $user->where(function ($query) use ($gradeRanges) {
                foreach ($gradeRanges as $range) {
                    [$minYears, $maxYears] = explode('-', $range);

                    if ($minYears == "0") {
                        $query->whereRaw(DB::raw("
                        (
                            ((years_of_service_total = 0 OR years_of_service_total IS NULL) AND month_of_service_total > 0)
                            OR (years_of_service_total = " . $maxYears . " AND (month_of_service_total IS NULL OR month_of_service_total = 0))
                            OR (years_of_service_total > 0 AND years_of_service_total < " . $maxYears . ")
                         )"));
                    } else {
                        $query->whereRaw(DB::raw("
                        (
                            years_of_service_total = " . $minYears . " AND month_of_service_total > 0
                            OR (years_of_service_total = " . $maxYears . " AND (month_of_service_total IS NULL OR month_of_service_total = 0))
                            OR (years_of_service_total > " . $minYears . " AND years_of_service_total < " . $maxYears . ")
                         )"));
                    }
                }
            });
        }
        if (isset($request->deputy)) {
            $sql = "
                WITH RECURSIVE hierarchy AS (
                    -- Anchor member: Select the initial parent row
                    SELECT
                        po.id,
                        po.name,
                        po.parent_id
                    FROM
                        positions po
                    WHERE
                        po.id IN (" . implode(',', $request->deputy) . ") -- Replace ? with the specific parent id

                    UNION DISTINCT

                    -- Recursive member: Select the child row
                    SELECT
                        p.id,
                        p.name,
                        p.parent_id
                    FROM
                        positions p
                    INNER JOIN
                        hierarchy h ON p.parent_id = h.id
                )
                SELECT id FROM hierarchy;";

            $ids = DB::select($sql);
            $positionIds = [];
            foreach ($ids as $key => $value) {
                $positionIds[] = $value->id;
            }
            $user->whereIn('users.position_id', $positionIds);
        }

        $userIds = $user->pluck('users.id')->toArray();
        if (!$userIds) {
            return $this->response(400, 'Data pegawai tidak ditemukan');
        }

        $employee = $this->employeeRepository->getDetailBulkUser($userIds, true);
        $families = $this->familyRepository->getDetailBulkUser($userIds);
        $educations = $this->educationRepository->getDetailBulkUser($userIds);
        $positions = $this->positionRepository->getDetailBulkUser($userIds);
        $grades = $this->gradeRepository->getDetailBulkUser($userIds);
        $structurals = $this->trainingRepository->getDetailBulkUser($userIds, 1);
        $functionals = $this->trainingRepository->getDetailBulkUser($userIds, 2);
        $technicals = $this->trainingRepository->getDetailBulkUser($userIds, 3);
        $recognitions = $this->recognitionRepository->getDetailBulkUser($userIds);
        $targets = $this->targetRepository->getDetailBulkUser($userIds);
        $performances = $this->performanceRepository->getDetailBulkUser($userIds);
        $disciplinaries = $this->disciplinaryRepository->getDetailBulkUser($userIds);
        $leaves = $this->leaveRepository->getDetailBulkUser($userIds);
        $notes = $this->noteRepository->getDetailBulkUser($userIds);
        $credits = $this->creditRepository->getDetailBulkUser($userIds);
        $assessments = $this->assessmentRepository->getDetailBulkUser($userIds);
        $competencies = $this->competencyRepository->getDetailBulkUser($userIds);
        $talents = $this->talentRepository->getDetailBulkUser($userIds);

        $zipFileName = "Employee-" . Carbon::now()->format('Y-m-d_H-i-s') . ".zip";
        $publicDisk = Storage::disk('public');
        $directory = 'document';
        if (! $publicDisk->exists($directory)) {
            $publicDisk->makeDirectory($directory);
        }
        $zipFileLocation = $publicDisk->path($directory.'/'.$zipFileName);
        $zip = new ZipArchive;
        if ($zip->open($zipFileLocation, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create Employee ZIP archive.');
        }
        set_time_limit(0);
        $pdfFiles = [];
        foreach ($userIds as $employeeId) {

            $religion = match ($employee[$employeeId]->religion) {
                1 => 'Islam',
                2 => 'Kristen',
                3 => 'Katolik',
                4 => 'Hindu',
                5 => 'Budha',
                6 => 'Konghucu',
                default => '-',
            };
            $maritalStatus = match ($employee[$employeeId]->marital_status) {
                1 => 'Belum Menikah',
                2 => 'Menikah',
                3 => 'Cerai',
                4 => 'Janda',
                5 => 'Duda',
                default => '-',
            };
            $employeeType = match ($employee[$employeeId]->type) {
                1 => 'ASN',
                2 => 'NON ASN',
                3 => 'OUTSOURCING',
                default => '-',
            };
            $educationLevel = match ($employee[$employeeId]->education_level) {
                1 => 'SD/Sederajat',
                2 => 'SLTP/Sederajat',
                3 => 'SLTA/Sederajat',
                4 => 'Diploma I/II',
                5 => 'Akademik/D3/S.Muda',
                6 => 'Diploma IV/Strata I',
                7 => 'Strata II',
                8 => 'Strata III',
                default => '-',
            };
            $employmentStatus = match ($employee[$employeeId]->employment_status) {
                1 => 'Aktif',
                2 => 'Pensiun',
                3 => 'Berhenti',
                4 => 'Meninggal',
                5 => 'Alih Status',
                6 => 'Aktif PS',
                7 => 'CLTN',
                8 => 'TBLN',
                9 => 'Non Aktif',
                10 => 'Hukuman Disiplin',
                default => '-',
            };

            // Batas Usia Pensiun
            $indonesianMonth = [
                1 => 'Januari',
                2 => 'Februari',
                3 => 'Maret',
                4 => 'April',
                5 => 'Mei',
                6 => 'Juni',
                7 => 'Juli',
                8 => 'Agustus',
                9 => 'September',
                10 => 'Oktober',
                11 => 'November',
                12 => 'Desember',
            ];
            if (isset($credits[$employeeId])) {
                foreach ($credits[$employeeId] as $key => $value) {
                    $credits[$employeeId][$key]->start_month_name = ($value->start_month) ? $indonesianMonth[$value->start_month] : '';
                    $credits[$employeeId][$key]->end_month_name = ($value->end_month) ? $indonesianMonth[$value->end_month] : '';
                }
            }

            $masaKerjaKeseluruhan = '-';
            if ($employee[$employeeId]->years_of_service_total > 0 || $employee[$employeeId]->month_of_service_total > 0) {
                $masaKerjaKeseluruhan = (($employee[$employeeId]->years_of_service_total > 0) ? $employee[$employeeId]->years_of_service_total : 0) . " Tahun, " . (($employee[$employeeId]->month_of_service_total > 0) ? $employee[$employeeId]->month_of_service_total : 0) . " Bulan";
            }
            $masaKerjaGolongan = '-';
            if ($employee[$employeeId]->years_of_service_rank > 0 || $employee[$employeeId]->month_of_service_rank > 0) {
                $masaKerjaGolongan = (($employee[$employeeId]->years_of_service_rank > 0) ? $employee[$employeeId]->years_of_service_rank : 0) . " Tahun, " . (($employee[$employeeId]->month_of_service_rank > 0) ? $employee[$employeeId]->month_of_service_rank : 0) . " Bulan";
            }
            $retirementAge = '';
            if (!is_null($employee[$employeeId]->retirement_age)) {
                $month = date('n', strtotime($employee[$employeeId]->retirement_age));
                $year = date('Y', strtotime($employee[$employeeId]->retirement_age));
                $retirementAge = $indonesianMonth[$month] . ' ' . $year . ' (' . $employee[$employeeId]->retirement_age_years . ' Tahun)';
            }

            if ($employee[$employeeId]->type == 1) {
                $userProfile = [
                    'Tempat, Tanggal Lahir' => $employee[$employeeId]->place_of_birth . ', ' . $employee[$employeeId]->date_of_birth,
                    'Agama' => $religion,
                    'Jenis Kelamin' => ($employee[$employeeId]->gender ? 'Pria' : 'Wanita'),
                    'Status Perkawinan' => $maritalStatus,
                    'Tanggal Perkawinan' => $employee[$employeeId]->marriage_date,
                    'Keterangan Perkawinan' => $employee[$employeeId]->marriage_description,
                    // 'Keterangan Lainnya' => $employee[$employeeId]->marriage_other_notes,
                    'Jenis Pegawai' => $employeeType,
                    'TMT CPNS' => $employee[$employeeId]->cpns_effective_date,
                    'TMT PNS' => $employee[$employeeId]->pns_effective_date,
                    'TMT Menjabat' => ($employee[$employeeId]->position_effective_date ?? '-'),
                    'Instansi Induk' => ($employee[$employeeId]->institution_name ?? '-'),
                    'Tingkat Pendidikan Akhir' => $educationLevel,
                    'Nama Sekolah/Universitas' => $employee[$employeeId]->education_name,
                    'Tahun Lulus' => $employee[$employeeId]->education_year,
                    'No. Karpeg/No. Karisu' => $employee[$employeeId]->employee_id_card_number . ' / ' . $employee[$employeeId]->karisu_number,
                    'Masa Kerja Keseluruhan' => $masaKerjaKeseluruhan,
                    'Masa Kerja Golongan' => $masaKerjaGolongan,
                    'NPWP' => $employee[$employeeId]->id_tax,
                    'Status Pegawai' => $employmentStatus,
                    'No NIK' => $employee[$employeeId]->id_number,
                    'Komplek' => $employee[$employeeId]->residence_name,
                    'Alamat Tempat Tinggal Saat Ini' => $employee[$employeeId]->residence_description,
                    'Alamat Sesuai KTP' => $employee[$employeeId]->current_address,
                    'No. Telepon Rumah' => $employee[$employeeId]->home_phone_number,
                    'No. HP' => $employee[$employeeId]->mobile_phone,
                    'Alamat Kantor' => $employee[$employeeId]->office_address,
                    'No. Telepon Kantor' => $employee[$employeeId]->office_phone_number,
                    'Email' => $employee[$employeeId]->email,
                    'Email Dinas' => $employee[$employeeId]->office_email,
                    'Kontak Darurat' => $employee[$employeeId]->emergency_contact,
                    'Batas Usia Pensiun' => $retirementAge,
                ];
            } else if ($employee[$employeeId]->type == 2) {
                $userProfile = [
                    'Tempat, Tanggal Lahir' => $employee[$employeeId]->place_of_birth . ', ' . $employee[$employeeId]->date_of_birth,
                    'Agama' => $religion,
                    'Jenis Kelamin' => ($employee[$employeeId]->gender ? 'Pria' : 'Wanita'),
                    'Status Perkawinan' => $maritalStatus,
                    'Jenis Pegawai' => $employeeType,
                    'Tanggal Mulai Bekerja di Sekretariat Wakil Presiden' => $employee[$employeeId]->cpns_effective_date,
                    'TMT Menjabat' => ($employee[$employeeId]->position_effective_date ?? '-'),
                    'Instansi Induk' => ($employee[$employeeId]->institution_name ?? '-'),
                    'Tingkat Pedidikan Akhir' => $educationLevel,
                    'Nama Sekolah/Universitas' => $employee[$employeeId]->education_name,
                    'Tahun Lulus' => $employee[$employeeId]->education_year,
                    'NPWP' => $employee[$employeeId]->id_tax,
                    'Status Pegawai' => $employmentStatus,
                    'No NIK' => $employee[$employeeId]->id_number,
                    'Alamat Tempat Tinggal Saat Ini' => $employee[$employeeId]->residence_description,
                    'Alamat Sesuai KTP' => $employee[$employeeId]->current_address,
                    'No. Telepon Rumah' => $employee[$employeeId]->home_phone_number,
                    'No. HP' => $employee[$employeeId]->mobile_phone,
                    'Alamat Kantor' => $employee[$employeeId]->office_address,
                    'No. Telepon Kantor' => $employee[$employeeId]->office_phone_number,
                    'Email' => $employee[$employeeId]->email,
                    'Email Dinas' => $employee[$employeeId]->office_email,
                    'Kontak Darurat' => $employee[$employeeId]->emergency_contact,
                ];
            } else {
                $userProfile = [
                    'Tempat, Tanggal Lahir' => $employee[$employeeId]->place_of_birth . ', ' . $employee[$employeeId]->date_of_birth,
                    'Agama' => $religion,
                    'Jenis Kelamin' => ($employee[$employeeId]->gender ? 'Pria' : 'Wanita'),
                    'Status Perkawinan' => $maritalStatus,
                    'Jenis Pegawai' => $employeeType,
                    'Tanggal Mulai Bekerja' => $employee[$employeeId]->cpns_effective_date,
                    'TMT Menjabat' => ($employee[$employeeId]->position_effective_date ?? '-'),
                    'Instansi Induk' => ($employee[$employeeId]->institution_name ?? '-'),
                    'Tingkat Pendidikan Akhir' => $educationLevel,
                    'Nama Sekolah/Universitas' => $employee[$employeeId]->education_name,
                    'Tahun Lulus' => $employee[$employeeId]->education_year,
                    'NPWP' => $employee[$employeeId]->id_tax,
                    'Status Pegawai' => $employmentStatus,
                    'No NIK' => $employee[$employeeId]->id_number,
                    'Alamat Tempat Tinggal Saat Ini' => $employee[$employeeId]->residence_description,
                    'Alamat Sesuai KTP' => $employee[$employeeId]->current_address,
                    'No. Telepon Rumah' => $employee[$employeeId]->home_phone_number,
                    'No. HP' => $employee[$employeeId]->mobile_phone,
                    'Alamat Kantor' => $employee[$employeeId]->office_address,
                    'No. Telepon Kantor' => $employee[$employeeId]->office_phone_number,
                    'Email' => $employee[$employeeId]->email,
                    'Email Dinas' => $employee[$employeeId]->office_email,
                    'Kontak Darurat' => $employee[$employeeId]->emergency_contact,
                ];
            }

            $tmp = sys_get_temp_dir();
            $pdf = Pdf::loadview('exports/user', [
                'userProfile' => $userProfile,
                'currentPosition' => ($employee[$employeeId]->position_merged ?? '-'),
                'photoProfile' => $employee[$employeeId]->photo_profile,
                'userNIP' => $employee[$employeeId]->employee_id_number,
                'userName' => $employee[$employeeId]->title_prefix . ' ' . $employee[$employeeId]->name . ' ' . $employee[$employeeId]->title_suffix,
                'userEchelons' => ($employee[$employeeId]->echelon_name ?? '') . ', ' . ($employee[$employeeId]->echelon_effective_date ?? ' '),
                'userCurrentGrade' => ($employee[$employeeId]->grade_name ?? '') . '(' . ($employee[$employeeId]->grade_code ?? '') . '), ' . ($employee[$employeeId]->grade_effective_date ?? ''),
                'userType' => $employee[$employeeId]->type,
                'userCollege' => $educations[$employeeId] ?? [],
                'userPosition' => $positions[$employeeId] ?? [],
                'userGrade' => $grades[$employeeId] ?? [],
                'userTrainingStructural' => $structurals[$employeeId] ?? [],
                'userTrainingFunctional' => $functionals[$employeeId] ?? [],
                'userTrainingTechnical' => $technicals[$employeeId] ?? [],
                'userAward' => $recognitions[$employeeId] ?? [],
                'userSKP' => $targets[$employeeId] ?? [],
                'userCredit' => $credits[$employeeId] ?? [],
                'userPerformance' => $performances[$employeeId] ?? [],
                'userPunishment' => $disciplinaries[$employeeId] ?? [],
                'userFamily' => $families[$employeeId] ?? [],
                'userLeave' => $leaves[$employeeId] ?? [],
                'userNotes' => $notes[$employeeId] ?? [],
                'userAssessment' => $assessments[$employeeId] ?? [],
                'userAssessmentCompetency' => $competencies[$employeeId] ?? [],
                'userAssessmentTalent' => $talents[$employeeId] ?? [],
                'permissions' => $this->getUserViewPermissions()
            ]);
            $pdf->set_option('isHtml5ParserEnabled', true);
            $pdf->set_paper("A4", "portrait");
            $pdf->set_option('isRemoteEnabled', true);
            $pdf->set_option('fontDir', $tmp);
            $pdf->set_option('fontCache', $tmp);
            $pdf->set_option('tempDir', $tmp);
            $pdfContent = $pdf->output();

            $pdfFileName = $employee[$employeeId]->name . '_' . $employee[$employeeId]->employee_id_number . '.pdf';
            $pdfFilePath = 'public/document/' . $pdfFileName;
            $publicDisk->put('document/'.$pdfFileName, $pdfContent);
            $pdfFiles[] = $pdfFilePath;
            $zip->addFile($publicDisk->path('document/'.$pdfFileName), $pdfFileName);
        }
        $zip->close();
        foreach ($pdfFiles as $pdfFile) {
            $publicDisk->delete(str_replace('public/', '', $pdfFile));
        }

        if (file_exists($zipFileLocation)) {
            $headers = [
                'Content-Type: application/zip',
                'Content-Disposition: attachment; filename="' . $zipFileName . '"',
                'Content-Length: ' . filesize($zipFileLocation),
            ];
            return response()->download($zipFileLocation, $zipFileName, $headers)->deleteFileAfterSend(true);
        } else {
            return response()->json(['error' => 'Zip file not found'], 404);
        }
    }

    /**
     * Get Study Program Options
     *
     * Retrieve the distinct program-study values from each employee's latest education.
     * @group Export
     * @authenticated
     */
    public function studyPrograms()
    {
        $studyPrograms = DB::table('user_educations as ue');
        $this->applyLatestEducationConstraint($studyPrograms, 'ue');

        $studyPrograms = $studyPrograms
            ->whereNotNull('ue.major')
            ->where('ue.major', '<>', '')
            ->distinct()
            ->orderBy('ue.major')
            ->pluck('ue.major');

        return $this->response(200, 'success', $studyPrograms);
    }

    /**
     * Export List of Employees
     *
     * Export list of employees data to .CSV, .XLSX or .PDF
     * @group Export
     * @authenticated
     * @urlParam type string required file type of the document. Example: csv
     * @bodyParam employee_type int[] Refers to IDs of type of employee (1: ASN, 2: Non ASN, 3: Outsourcing). Example: [1, 3]
     * @bodyParam deputy int[] List of employee's deputy. Example: [1, 3]
     * @bodyParam echelons int[] Refers to IDs of employee echelons. Example: [1, 3]
     * @bodyParam grades int[] Refers to IDs of employee grades. Example: [1, 3]
     * @bodyParam position_status int[] Refers to IDs of employee position status. Example: [1, 3]
     * @bodyParam education int[] Refers to type of employee education (1=SD/Sederajat, 2=SLTP/Sederajat, 3=SLTA/Sederajat, 4=Akademik/D3/S.Muda, 5=Diploma IV, 6=Strata I, 7=Strata II, 8=Strata III). Example: [1, 3]
     * @bodyParam study_programs string[] Program-study values from employee education histories. Example: ["Teknik Informatika"]
     * @bodyParam gender int[] Refers to gender of employee (1: Laki - Laki, 0: Perempuan). Example: [1]
     * @bodyParam min_age int Refers to minimum age of employee. Example: 50
     * @bodyParam max_age int Refers to maximum age of employee. Example: 55
     * @bodyParam marital_status int[] Refers to marital status of employee (1=Belum Menikah, 2=Menikah, 3=Cerai Hidup, 4=Cerai Mati). Example: [1, 3]
     * @bodyParam retirement_age int[] Refers to retirement year of employee. Example: [58,60]
     * @bodyParam retirement_year int Refers to retirement year of employee. Example: 2024
     * @bodyParam total_working_duration int[] Refers to total duration of employee employment. Example: ["5-10"]
     * @bodyParam grade_range int[] Refers to duration of grade in years. Example: ["5-10"]
     * @bodyParam employment_status int[] Refers to employment status of employee (1=Aktif, 2=Pensiun, 3=Berhenti, 4=Meninggal, 5=Alih Status ,6=Aktif PS ,7=CLTN ,8=TBLN ,9=Non Aktif). Example: [1, 3]
     * @bodyParam target_period string[] Refers to employees Target appraisal period ("Q1","Q2","Q3","Q4","Tahunan"). Example: ["Q1"]
     * @bodyParam target_year string Refers to employees Target year period. Example: "2024"
     * @bodyParam work_behavior_rating int[] Refers to employees work behavior rating (1=Diatas Ekspektasi, 2=Sesuai Ekspektasi, 3=Dibawah Ekspektasi). Example: [1, 3]
     * @bodyParam employee_performance_predicate int[] Refers to employees performance predicate (1=Sangat Baik, 2=Baik, 3=Butuh Perbaikan, 4=Kurang, 5=Sangat Kurang). Example: [3]
     * @bodyParam organizational_performance_achievement int[] Refers to employees organizational performance achievement (1=Sangat Baik, 2=Baik, 3=Cukup). Example: [1, 3]
     * @bodyParam credit_period int[] Refers to employees credit period (1=Triwulan 1, 2=Triwulan 2, 3=Triwulan 3, 4=Triwulan 4, 5=Tahunan). Example: [1, 3]
     * @bodyParam credit_year string Refers to employees credit year period. Example: "2024"
     * @bodyParam isName int Indicates whether the name field is included in the request. Example: 1
     * @bodyParam isTitlePrefix int Indicates whether the prefix title field is included in the request. Example: 1
     * @bodyParam isTitleSuffix int Indicates whether the suffix title field is included in the request. Example: 1
     * @bodyParam isNameWithTitle int Indicates whether the name with title field is included in the request. Example: 1
     * @bodyParam isNip int Indicates whether the NIP (National Identification Number) field is included in the request. Example: 1
     * @bodyParam isBirthPlaceDate int Indicates whether the birth place and date field is included in the request. Example: 1
     * @bodyParam isAge int Indicates whether the age field is included in the request. Example: 1
     * @bodyParam isReligion int Indicates whether the religion field is included in the request. Example: 1
     * @bodyParam isGender int Indicates whether the gender field is included in the request. Example: 1
     * @bodyParam isMaritalStatus int Indicates whether the marital status field is included in the request. Example: 1
     * @bodyParam isMarriageDate int Indicates whether the marriage date field is included in the request. Example: 1
     * @bodyParam isMarriageDescription int Indicates whether the marriage description field is included in the request. Example: 1
     * @bodyParam isMarriageOtherNotes int Indicates whether the marriage other notes field is included in the request. Example: 1
     * @bodyParam isEmployeeType int Indicates whether the employee type field is included in the output document. Example: 1
     * @bodyParam isAssistanceType int Indicates whether the employee type assistance field is included in the output document. Example: 1
     * @bodyParam isOutsourcingType int Indicates whether the employee type outsourcing field is included in the output document. Example: 1
     * @bodyParam isDatePNS int Indicates whether the PNS Start date field is included in the request. Example: 1
     * @bodyParam isDateCPNS int Indicates whether the CPNS Start date field is included in the request. Example: 1
     * @bodyParam isStartDate int Indicates whether the employment start date field is included in the request. Example: 1
     * @bodyParam isEndDate int Indicates whether the employment end date field is included in the request. Example: 1
     * @bodyParam isWorkDuration int Indicates the duration of work. Example: 1
     * @bodyParam isGradeDuration int Indicates whether the grade duration field is included in the request. Example: 1
     * @bodyParam isPosition int Indicates whether the position field is included in the request. Example: 1
     * @bodyParam isFullPosition int Indicates whether the full position field is included in the request. Example: 1
     * @bodyParam isDatePosition int Indicates whether the position start date field is included in the request. Example: 1
     * @bodyParam isEchelons int Indicates whether the echelons field is included in the request. Example: 1
     * @bodyParam isEchelonDate int Indicates whether the echelon start date field is included in the request. Example: 1
     * @bodyParam isGrade int Indicates whether the grade field is included in the request. Example: 1
     * @bodyParam isGradeDate int Indicates whether the grade start date field is included in the request. Example: 1
     * @bodyParam isAgency int Indicates whether the agency field is included in the request. Example: 1
     * @bodyParam isNoWorker int Indicates whether the worker number field is included in the request. Example: 1
     * @bodyParam isKarisu int Indicates whether the Number Karisu field is included in the request. Example: 1
     * @bodyParam isNPWP int Indicates whether the NPWP (Tax Identification Number) field is included in the request. Example: 1
     * @bodyParam isEmployeeStatus int Indicates whether the employee status field is included in the request. Example: 1
     * @bodyParam isNoFamily int Indicates whether the Number family field is included in the request. Example: 1
     * @bodyParam isNIK int Indicates whether the NIK field is included in the request. Example: 1
     * @bodyParam isCurrentAddress int Indicates whether the current address field is included in the request. Example: 1
     * @bodyParam isComplex int Indicates whether the complex field is included in the request. Example: 1
     * @bodyParam isIDCardAddress int Indicates whether the id card address field is included in the request. Example: 1
     * @bodyParam isHomeNumber int Indicates whether the home number field is included in the request. Example: 1
     * @bodyParam isPhoneNumber int Indicates whether the phone number field is included in the request. Example: 1
     * @bodyParam isOfficeAddress int Indicates whether the office address field is included in the request. Example: 1
     * @bodyParam isOfficeNumber int Indicates whether the office number field is included in the request. Example: 1
     * @bodyParam isEmail int Indicates whether the email field is included in the request. Example: 1
     * @bodyParam isOfficeEmail int Indicates whether the office email field is included in the request. Example: 1
     * @bodyParam isPositionDescription int Indicates whether the position description field is included in the request. Example: 1
     * @bodyParam isEmergencyContact int Indicates whether the emergency contact field is included in the request. Example: 1
     * @bodyParam isPensionCap int Indicates whether the pension cap field is included in the request. Example: 1
     * @bodyParam isEducationHistory int Indicates whether the education history field is included in the request. Example: 1
     * @bodyParam isPositionHistory int Indicates whether the position history field is included in the request. Example: 1
     * @bodyParam isGradeHistory int Indicates whether the grade history field is included in the request. Example: 1
     * @bodyParam isTrainingStructural int Indicates whether the structural training field is included in the request. Example: 1
     * @bodyParam isTrainingFunctional int Indicates whether the functional training field is included in the request. Example: 1
     * @bodyParam isTrainingTechnique int Indicates whether the technique training field is included in the request. Example: 1
     * @bodyParam isRecognition int Indicates whether the recognition field is included in the request. Example: 1
     * @bodyParam isSKP int Indicates whether the SKP (Employee Performance Target) field is included in the request. Example: 1
     * @bodyParam isCredit int Indicates whether the PAK history field is included in the request. Example: 1
     * @bodyParam isPerformance int Indicates whether the PPK history field is included in the request. Example: 1
     * @bodyParam isDisciplinary int Indicates whether the disciplinary field is included in the request. Example: 1
     * @bodyParam isFamilyHistory int Indicates whether the family history field is included in the request. Example: 1
     * @bodyParam isLeave int Indicates whether the leave field is included in the request. Example: 1
     * @bodyParam isNotes int Indicates whether the notes field is included in the request. Example: 1
     * @bodyParam isAssessment int Indicates whether the assessment field is included in the request. Example: 1
     * @bodyParam isCompetency int Indicates whether the competency field is included in the request. Example: 1
     * @bodyParam isTalentPool int Indicates whether the talent pool field is included in the request. Example: 1
     **/
    public function employees(ExportEmployeesRequest $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        // filter user to get ids
        $users = DB::table('users')
            ->leftJoin('echelons', 'users.echelon_id', '=', 'echelons.id')
            ->leftjoin('user_credits', 'users.id', '=', 'user_credits.user_id')
            ->leftjoin('target_history_users', 'users.id', '=', 'target_history_users.user_id')
            ->leftJoin('target_histories', 'target_history_users.target_history_id', '=', 'target_histories.id')
            ->select('users.id');
        if (isset($request->employee_type)) {
            $users->whereIn('users.type', $request->employee_type);
        }
        if (isset($request->deputy)) {
            $sql = "
                WITH RECURSIVE hierarchy AS (
                    -- Anchor member: Select the initial parent row
                    SELECT
                        po.id,
                        po.name,
                        po.parent_id
                    FROM
                        positions po
                    WHERE
                        po.id IN (" . implode(',', $request->deputy) . ") -- Replace ? with the specific parent id

                    UNION DISTINCT

                    -- Recursive member: Select the child row
                    SELECT
                        p.id,
                        p.name,
                        p.parent_id
                    FROM
                        positions p
                    INNER JOIN
                        hierarchy h ON p.parent_id = h.id
                )
                SELECT id FROM hierarchy;";

            $ids = DB::select($sql);
            $positionIds = [];
            foreach ($ids as $key => $value) {
                $positionIds[] = $value->id;
            }

            $users->whereIn('users.position_id', $positionIds);
        }
        if (isset($request->echelons)) {
            $users->whereIn('echelons.id', $request->echelons);
        }
        if (isset($request->grades)) {
            $users->whereIn('users.grade_id', $request->grades);
        }
        if (isset($request->education)) {
            $users->whereIn('users.education_level', $request->education);
        }
        if (isset($request->study_programs)) {
            $users->whereExists(function ($query) use ($request) {
                $query->select(DB::raw(1))
                    ->from('user_educations as ue')
                    ->whereColumn('ue.user_id', 'users.id')
                    ->whereIn('ue.major', $request->study_programs);

                $this->applyLatestEducationConstraint($query, 'ue');
            });
        }
        if (isset($request->position_status)) {
            $users->leftJoin('position_history_users', 'users.id', '=', 'position_history_users.user_id');
            $users->whereIn('position_history_users.position_status', $request->position_status);
        }
        if (isset($request->education_level)) {
            $users->whereIn('users.education_level', $request->education_level);
        }
        if (isset($request->gender)) {
            $users->whereIn('users.gender', $request->gender);
        }
        if (isset($request->min_age)) {
            $minAge = $request->input('min_age');
            $users->where(DB::raw('TIMESTAMPDIFF(YEAR, users.date_of_birth, NOW())'), '>=', $minAge);
        }
        if (isset($request->max_age)) {
            $maxAge = $request->input('max_age');
            $users->where(DB::raw('TIMESTAMPDIFF(YEAR, users.date_of_birth, NOW())'), '<=', $maxAge);
        }
        if (isset($request->marital_status)) {
            $users->whereIn('users.marital_status', $request->marital_status);
        }
        if (isset($request->retirement_age) && isset($request->retirement_year)) { // Age and year
            $users->where(function ($query) use ($request) {
                foreach ($request->retirement_age as $age) {
                    $query->orWhere(DB::raw("YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + $age"), $request->retirement_year);
                }
            });
        }
        if (isset($request->retirement_age) && !isset($request->retirement_year)) { // Age only
            $users->whereIn('echelons.retirement_age', $request->retirement_age);
        }
        if (!isset($request->retirement_age) && isset($request->retirement_year)) { // Year only
            $users->whereRaw("
            CASE
                WHEN users.type = 1 AND users.echelon_id IS NOT NULL AND date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + echelons.retirement_age = ?
                WHEN users.type = 2 AND users.date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + 58 = ?
                WHEN users.type = 3 AND users.date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + 58 = ?
            END
        ", [$request->retirement_year, $request->retirement_year, $request->retirement_year]);
        }

        if (isset($request->grade_range)) {
            $gradeRanges = $request->input('grade_range', []);

            $users->where(function ($query) use ($gradeRanges) {
                foreach ($gradeRanges as $range) {
                    [$minYears, $maxYears] = explode('-', $range);

                    if ($minYears == "0") {
                        $query->whereRaw(DB::raw("
                        (
                            ((years_of_service_rank = 0 OR years_of_service_rank IS NULL) AND month_of_service_rank > 0)
                            OR (years_of_service_rank = " . $maxYears . " AND (month_of_service_rank IS NULL OR month_of_service_rank = 0))
                            OR (years_of_service_rank > 0 AND years_of_service_rank < " . $maxYears . ")
                         )"));
                    } else {
                        $query->whereRaw(DB::raw("
                        (
                            years_of_service_rank = " . $minYears . " AND month_of_service_rank > 0
                            OR (years_of_service_rank = " . $maxYears . " AND (month_of_service_rank IS NULL OR month_of_service_rank = 0))
                            OR (years_of_service_rank > " . $minYears . " AND years_of_service_rank < " . $maxYears . ")
                         )"));
                    }
                }
            });
        }
        if (isset($request->employment_status)) {
            $users->whereIn('users.employment_status', $request->employment_status);
        }
        if (isset($request->total_working_duration)) {
            $gradeRanges = $request->input('total_working_duration', []);

            $users->where(function ($query) use ($gradeRanges) {
                foreach ($gradeRanges as $range) {
                    [$minYears, $maxYears] = explode('-', $range);

                    if ($minYears == "0") {
                        $query->whereRaw(DB::raw("
                        (
                            ((years_of_service_total = 0 OR years_of_service_total IS NULL) AND month_of_service_total > 0)
                            OR (years_of_service_total = " . $maxYears . " AND (month_of_service_total IS NULL OR month_of_service_total = 0))
                            OR (years_of_service_total > 0 AND years_of_service_total < " . $maxYears . ")
                         )"));
                    } else {
                        $query->whereRaw(DB::raw("
                        (
                            years_of_service_total = " . $minYears . " AND month_of_service_total > 0
                            OR (years_of_service_total = " . $maxYears . " AND (month_of_service_total IS NULL OR month_of_service_total = 0))
                            OR (years_of_service_total > " . $minYears . " AND years_of_service_total < " . $maxYears . ")
                         )"));
                    }
                }
            });
        }
        if (isset($request->credit_period)) {
            $users->whereIn('user_credits.period', $request->credit_period);
        }
        if (isset($request->credit_year)) {
            $users->where('user_credits.year', $request->credit_year);
        }
        if (isset($request->target_period)) {
            $users->whereIn('target_histories.appraisal_period', $request->target_period);
        }
        if (isset($request->target_year)) {
            $users->where('target_histories.period_year', $request->target_year);
        }
        if (isset($request->work_behavior_rating)) {
            $users->whereIn('target_history_users.work_behavior_rating', $request->work_behavior_rating);
        }
        if (isset($request->employee_performance_predicate)) {
            $users->whereIn('target_history_users.employee_performance_predicate', $request->employee_performance_predicate);
        }
        if (isset($request->organizational_performance_achievement)) {
            $users->whereIn('target_history_users.organizational_performance_achievement', $request->organizational_performance_achievement);
        }

        $userIds = $users->pluck('users.id')->toArray();
        if (!$userIds) {
            return $this->response(400, 'Data pegawai tidak ditemukan');
        }

        // toggle field
        $toggleFieldBio = array();
        $toggleFieldBio['isName'] = $request->isName == 1;
        $toggleFieldBio['isTitlePrefix'] = $request->isTitlePrefix == 1;
        $toggleFieldBio['isTitleSuffix'] = $request->isTitleSuffix == 1;
        $toggleFieldBio['isNameWithTitle'] = $request->isNameWithTitle == 1;
        $toggleFieldBio['isNip'] = $request->isNip == 1;
        $toggleFieldBio['isBirthPlaceDate'] = $request->isBirthPlaceDate == 1;
        $toggleFieldBio['isAge'] = $request->isAge == 1;
        $toggleFieldBio['isReligion'] = $request->isReligion == 1;
        $toggleFieldBio['isGender'] = $request->isGender == 1;
        $toggleFieldBio['isMaritalStatus'] = $request->isMaritalStatus == 1;
        $toggleFieldBio['isMarriageDate'] = $request->isMarriageDate == 1;
        $toggleFieldBio['isMarriageDescription'] = $request->isMarriageDescription == 1;
        $toggleFieldBio['isMarriageOtherNotes'] = $request->isMarriageOtherNotes == 1;
        $toggleFieldBio['isEmployeeType'] = $request->isEmployeeType == 1;
        $toggleFieldBio['isAssistanceType'] = $request->isAssistanceType == 1;
        $toggleFieldBio['isOutsourcingType'] = $request->isOutsourcingType == 1;
        $toggleFieldBio['isDatePNS'] = $request->isDatePNS == 1;
        $toggleFieldBio['isDateCPNS'] = $request->isDateCPNS == 1;
        $toggleFieldBio['isStartDate'] = $request->isStartDate == 1;
        $toggleFieldBio['isEndDate'] = $request->isEndDate == 1;
        $toggleFieldBio['isWorkDuration'] = $request->isWorkDuration == 1; //note
        $toggleFieldBio['isGradeDuration'] = $request->isGradeDuration == 1;
        $toggleFieldBio['isPosition'] = $request->isPosition == 1;
        $toggleFieldBio['isFullPosition'] = $request->isFullPosition == 1;
        $toggleFieldBio['isDatePosition'] = $request->isDatePosition == 1;
        $toggleFieldBio['isEchelons'] = $request->isEchelons == 1;
        $toggleFieldBio['isEchelonDate'] = $request->isEchelonDate == 1;
        $toggleFieldBio['isPositionDescription'] = $request->isPositionDescription == 1; //keterangan
        $toggleFieldBio['isGrade'] = $request->isGrade == 1;
        $toggleFieldBio['isGradeDate'] = $request->isGradeDate == 1;
        $toggleFieldBio['isEducation'] = $request->isEducation == 1;
        $toggleFieldBio['isStudyProgram'] = $request->isStudyProgram == 1;
        $toggleFieldBio['isAgency'] = $request->isAgency == 1;
        $toggleFieldBio['isNoWorker'] = $request->isNoWorker == 1;
        $toggleFieldBio['isKarisu'] = $request->isKarisu == 1;
        $toggleFieldBio['isNPWP'] = $request->isNPWP == 1;
        $toggleFieldBio['isEmployeeStatus'] = $request->isEmployeeStatus == 1;
        $toggleFieldBio['isNoFamily'] = $request->isNoFamily == 1;
        $toggleFieldBio['isNIK'] = $request->isNIK == 1;
        $toggleFieldBio['isCurrentAddress'] = $request->isCurrentAddress == 1;
        $toggleFieldBio['isComplex'] = $request->isComplex == 1;
        $toggleFieldBio['isIDCardAddress'] = $request->isIDCardAddress == 1;
        $toggleFieldBio['isHomeNumber'] = $request->isHomeNumber == 1;
        $toggleFieldBio['isPhoneNumber'] = $request->isPhoneNumber == 1;
        $toggleFieldBio['isOfficeAddress'] = $request->isOfficeAddress == 1;
        $toggleFieldBio['isOfficeNumber'] = $request->isOfficeNumber == 1;
        $toggleFieldBio['isEmail'] = $request->isEmail == 1;
        $toggleFieldBio['isOfficeEmail'] = $request->isOfficeEmail == 1;
        $toggleFieldBio['isEmergencyContact'] = $request->isEmergencyContact == 1;
        $toggleFieldBio['isPensionCap'] = $request->isPensionCap == 1; //not ready
        $toggleFieldBio['isPositionHistory'] = $request->isPositionHistory == 1;
        $toggleFieldBio['isGradeHistory'] = $request->isGradeHistory == 1;
        $toggleFieldBio['isTrainingStructural'] = $request->isTrainingStructural == 1;
        $toggleFieldBio['isTrainingFunctional'] = $request->isTrainingFunctional == 1;
        $toggleFieldBio['isTrainingTechnique'] = $request->isTrainingTechnique == 1;
        $toggleFieldBio['isSKP'] = $request->isSKP == 1;
        $toggleFieldBio['isCredit'] = $request->isCredit == 1;
        $toggleFieldBio['isPerformance'] = $request->isPerformance == 1;
        $toggleFieldBio['isRecognition'] = $request->isRecognition == 1;
        $toggleFieldBio['isNotes'] = $request->isNotes == 1;
        $toggleFieldBio['isEducationHistory'] = $request->isEducationHistory == 1;
        $toggleFieldBio['isDisciplinary'] = $request->isDisciplinary == 1;
        $toggleFieldBio['isFamilyHistory'] = $request->isFamilyHistory == 1;
        $toggleFieldBio['isLeave'] = $request->isLeave == 1;
        $toggleFieldBio['isAssessment'] = $request->isAssessment == 1;
        $toggleFieldBio['isCompetency'] = $request->isCompetency == 1;
        $toggleFieldBio['isTalentPool'] = $request->isTalentPool == 1;

        if ($request->type == "csv") {
            return Excel::download(new employee($toggleFieldBio, $userIds), 'Employees-' . Carbon::now() . '.csv', \Maatwebsite\Excel\Excel::CSV);
        } else if ($request->type == "xlsx") {
            return Excel::download(new employee($toggleFieldBio, $userIds), 'Employees-' . Carbon::now() . '.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        } else if ($request->type == "pdf") {
            $tmp = sys_get_temp_dir();
            $userIdArray = collect($userIds);
            $userIdsChunk = $userIdArray->chunk(200);
            $results = array();
            foreach ($userIdsChunk as $userId) {
                // Set the group_concat_max_len session variable
                DB::statement("SET SESSION group_concat_max_len = 10000");

                $usersData = DB::table('users')->select('users.type');
                $usersData->leftJoin('echelons', 'users.echelon_id', '=', 'echelons.id');
                $usersData->leftJoin('positions', 'users.position_id', '=', 'positions.id');
                if ($toggleFieldBio['isName']) {
                    $usersData->addSelect('users.name');
                }
                if ($toggleFieldBio['isTitlePrefix']) {
                    $usersData->addSelect('users.title_prefix');
                }
                if ($toggleFieldBio['isTitleSuffix']) {
                    $usersData->addSelect('users.title_suffix');
                }
                if ($toggleFieldBio['isNameWithTitle']) {
                    $usersData->addSelect(DB::raw("CONCAT(COALESCE(users.title_prefix,''),' ',users.name,' ',COALESCE(users.title_suffix,'')) as name_with_title"));
                }
                if ($toggleFieldBio['isNip']) {
                    $usersData->addSelect('users.employee_id_number', 'users.employee_registration_number');
                }
                if ($toggleFieldBio['isBirthPlaceDate']) {
                    $usersData->addSelect('users.place_of_birth', DB::raw("DATE_FORMAT(users.date_of_birth, '%d-%m-%Y') as date_of_birth"));
                }
                if ($toggleFieldBio['isAge']) {
                    $usersData->addSelect(DB::raw("TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) AS age"));
                }
                if ($toggleFieldBio['isReligion']) {
                    $usersData->addSelect('users.religion');
                }
                if ($toggleFieldBio['isGender']) {
                    $usersData->addSelect('users.gender');
                }
                if ($toggleFieldBio['isMaritalStatus']) {
                    $usersData->addSelect('users.marital_status');
                }
                if ($toggleFieldBio['isMarriageDate']) {
                    $usersData->addSelect('users.marriage_date');
                }
                if ($toggleFieldBio['isMarriageDescription']) {
                    $usersData->addSelect('users.marriage_description');
                }
                // if ($toggleFieldBio['isMarriageOtherNotes']) {
                //     $usersData->addSelect('users.marriage_other_notes');
                // }
                if ($toggleFieldBio['isPosition']) {
                    $usersData->addSelect('users.position_id', 'positions.name as position_name', 'positions.type as position_type'); // position id to be used in get hierarchy below
                }
                if ($toggleFieldBio['isPositionDescription']) {
                    $usersData->addSelect('users.description');
                }
                if ($toggleFieldBio['isEchelons']) {
                    if ($toggleFieldBio['isPosition'] != 1) { // if isPosition not checked
                        $usersData->addSelect('users.position_id', 'positions.name as position_name', 'positions.type as position_type'); // Get position id to be used in get hierarchy below
                    }
                    $usersData->addSelect('echelons.name as echelons_name');
                }
                if ($toggleFieldBio['isFullPosition']) {
                    if ($toggleFieldBio['isPosition'] != 1) { // if isPosition not checked
                        $usersData->addSelect('users.position_id', 'positions.name as position_name', 'positions.type as position_type'); // Get position id to be used in get hierarchy below
                    }
                    if ($toggleFieldBio['isEchelons'] != 1) { // if isEchelons not checked
                        $usersData->addSelect('echelons.name as echelons_name'); // Get echelons to be used in get hierarchy below
                    }
                }
                if ($toggleFieldBio['isGrade']) {
                    $usersData->leftJoin('grades as g', 'users.grade_id', '=', 'g.id');
                    $usersData->addSelect(DB::raw("CONCAT(g.name, ' ', g.code) as grade_name"));
                }
                if ($toggleFieldBio['isEducation']) {
                    $usersData->addSelect('users.education_level', 'users.education_name', 'users.education_year');
                }
                if ($toggleFieldBio['isStudyProgram']) {
                    $usersData->addSelect([
                        'study_program' => DB::table('user_educations as ue')
                            ->select('ue.major')
                            ->whereColumn('ue.user_id', 'users.id')
                            ->orderByDesc('ue.level')
                            ->orderByDesc('ue.year_of_graduation')
                            ->orderByDesc('ue.id')
                            ->limit(1),
                    ]);
                }
                if ($toggleFieldBio['isEmployeeStatus']) {
                    $usersData->addSelect('users.employment_status');
                }
                if ($toggleFieldBio['isAgency']) {
                    $usersData->leftJoin('institutions as i', 'users.institution_id', '=', 'i.id');
                    $usersData->addSelect('i.name as institution_name');
                }
                if ($toggleFieldBio['isNoWorker']) {
                    $usersData->addSelect('users.employee_id_card_number');
                }
                if ($toggleFieldBio['isGradeDuration']) {
                    $usersData->addSelect(DB::raw("
                    CASE
                        WHEN users.years_of_service_rank > 0 OR users.month_of_service_rank > 0
                        THEN CONCAT(
                            IF(users.years_of_service_rank > 0, users.years_of_service_rank, 0), ' Tahun, ',
                            IF(users.month_of_service_rank > 0, users.month_of_service_rank, 0), ' Bulan'
                        )
                        ELSE '-'
                    END as grade_duration
                "));
                }
                if ($toggleFieldBio['isNPWP']) {
                    $usersData->addSelect('users.id_tax');
                }
                if ($toggleFieldBio['isCurrentAddress']) {
                    $usersData->addSelect('users.current_address');
                }
                if ($toggleFieldBio['isComplex']) {
                    $usersData->leftJoin('residences as r', 'users.residence_id', '=', 'r.id');
                    $usersData->addSelect(DB::raw("r.name as residence_name"));
                }
                if ($toggleFieldBio['isIDCardAddress']) {
                    $usersData->addSelect('users.residence_description');
                }
                if ($toggleFieldBio['isHomeNumber']) {
                    $usersData->addSelect('users.home_phone_number');
                }
                if ($toggleFieldBio['isPhoneNumber']) {
                    $usersData->addSelect('users.mobile_phone');
                }
                if ($toggleFieldBio['isOfficeAddress']) {
                    $usersData->addSelect('users.office_address');
                }
                if ($toggleFieldBio['isOfficeNumber']) {
                    $usersData->addSelect('users.office_phone_number');
                }
                if ($toggleFieldBio['isEmail']) {
                    $usersData->addSelect('users.email');
                }
                if ($toggleFieldBio['isGradeHistory']) {
                    $gradeHistorySubquery = DB::table('grade_history_users as ghu');
                    $gradeHistorySubquery->join('grades', 'grades.id', '=', 'ghu.grade_id');
                    $gradeHistorySubquery->select('ghu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', grades.name, ' ', grades.code, '</li>') SEPARATOR ' ') as grade_history"));
                    $gradeHistorySubquery->whereIn('ghu.user_id', $userId);
                    $gradeHistorySubquery->groupBy('ghu.user_id');
                    $usersData->leftJoinSub($gradeHistorySubquery, 'grade_history', function ($join) {
                        $join->on('users.id', '=', 'grade_history.user_id');
                    });
                    $usersData->addSelect('grade_history.grade_history');
                }
                if ($toggleFieldBio['isPositionHistory']) {
                    $positionHistorySubquery = DB::table('position_history_users as phu');
                    $positionHistorySubquery->select('phu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', phu.position,'</li>') SEPARATOR ' ') as position_history"));
                    $positionHistorySubquery->whereIn('phu.user_id', $userId);
                    $positionHistorySubquery->groupBy('phu.user_id');
                    $usersData->leftJoinSub($positionHistorySubquery, 'position_history', function ($join) {
                        $join->on('users.id', '=', 'position_history.user_id');
                    });
                    $usersData->addSelect('position_history.position_history');
                }
                if ($toggleFieldBio['isTrainingStructural']) {
                    $trainingStructuralSubquery = DB::table('training_histories as t');
                    $trainingStructuralSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
                    $trainingStructuralSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, ', ', t.level, '</li>') SEPARATOR ' ') as structural_training_history"));
                    $trainingStructuralSubquery->whereIn('ut.user_id', $userId);
                    $trainingStructuralSubquery->where('t.type', 1);
                    $trainingStructuralSubquery->groupBy('ut.user_id');

                    $usersData->leftJoinSub($trainingStructuralSubquery, 'structural_training_history', function ($join) {
                        $join->on('users.id', '=', 'structural_training_history.user_id');
                    });

                    $usersData->addSelect('structural_training_history.structural_training_history');
                }

                if ($toggleFieldBio['isTrainingFunctional']) {
                    $trainingFunctionalSubquery = DB::table('training_histories as t');
                    $trainingFunctionalSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
                    $trainingFunctionalSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, ', ', t.level, '</li>') SEPARATOR ' ') as functional_training_history "));
                    $trainingFunctionalSubquery->whereIn('ut.user_id', $userId);
                    $trainingFunctionalSubquery->where('t.type', 2);
                    $trainingFunctionalSubquery->groupBy('ut.user_id');

                    $usersData->leftJoinSub($trainingFunctionalSubquery, 'functional_training_history', function ($join) {
                        $join->on('users.id', '=', 'functional_training_history.user_id');
                    });

                    $usersData->addSelect('functional_training_history.functional_training_history');
                }

                if ($toggleFieldBio['isTrainingTechnique']) {
                    $trainingTechnicSubquery = DB::table('training_histories as t');
                    $trainingTechnicSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
                    $trainingTechnicSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, '</li>') SEPARATOR ' ') as technique_training_history"));
                    $trainingTechnicSubquery->whereIn('ut.user_id', $userId);
                    $trainingTechnicSubquery->where('t.type', 3);
                    $trainingTechnicSubquery->groupBy('ut.user_id');

                    $usersData->leftJoinSub($trainingTechnicSubquery, 'technique_training_history', function ($join) {
                        $join->on('users.id', '=', 'technique_training_history.user_id');
                    });

                    $usersData->addSelect('technique_training_history.technique_training_history');
                }
                if ($toggleFieldBio['isRecognition']) {
                    $recognitionSubquery = DB::table('recognition_histories as r');
                    $recognitionSubquery->join('recognition_history_users as ur', 'r.id', '=', 'ur.recognition_history_id');
                    $recognitionSubquery->join('recognitions', 'r.recognition_id', '=', 'recognitions.id');
                    $recognitionSubquery->select('ur.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>',recognitions.name,'</li>') SEPARATOR ' ') as recognition_history"));
                    $recognitionSubquery->whereIn('ur.user_id', $userId);
                    $recognitionSubquery->groupBy('ur.user_id');

                    $usersData->leftJoinSub($recognitionSubquery, 'recognition_history', function ($join) {
                        $join->on('users.id', '=', 'recognition_history.user_id');
                    });

                    $usersData->addSelect('recognition_history.recognition_history');
                }
                if ($toggleFieldBio['isSKP']) {
                    $skpSubquery = DB::table('target_histories as t');
                    $skpSubquery->join('target_history_users as ut', 't.id', '=', 'ut.target_history_id');
                    $skpSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>',t.name, ' Penilaian Perilaku : ',
                             CASE ut.work_behavior_rating
                                    WHEN 1 THEN 'Diatas Ekspektasi'
                                    WHEN 2 THEN 'Sesuai Ekspektasi'
                                    WHEN 3 THEN 'Dibawah Ekspektasi'
                             END
                            , ', Penilaian Predikat Performa : ',
                            CASE ut.employee_performance_predicate
                                    WHEN 1 THEN 'Sangat Baik'
                                    WHEN 2 THEN 'Baik'
                                    WHEN 3 THEN 'Butuh Perbaikan'
                                    WHEN 4 THEN 'Kurang'
                                    WHEN 5 THEN 'Sangat Kurang'
                             END
                             ,', Penilaian Performa Organisasi : ',
                             CASE ut.employee_performance_predicate
                                    WHEN 1 THEN 'Sangat Baik'
                                    WHEN 2 THEN 'Baik'
                                    WHEN 3 THEN 'Cukup'
                             END, '</li>') SEPARATOR ' ') as skp_history"));
                    $skpSubquery->whereIn('ut.user_id', $userId);
                    $skpSubquery->groupBy('ut.user_id');

                    $usersData->leftJoinSub($skpSubquery, 'skp_history', function ($join) {
                        $join->on('users.id', '=', 'skp_history.user_id');
                    });

                    $usersData->addSelect('skp_history.skp_history');
                }
                if ($toggleFieldBio['isCredit']) {
                    $creditSubQuery = DB::table('user_credits as uc');
                    $creditSubQuery->select('uc.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> ',uc.position, ', Angka Kredit Terakhir : ', uc.score,'</li>') SEPARATOR ' ') as credit_history"));
                    $creditSubQuery->whereIn('uc.user_id', $userId);
                    $creditSubQuery->groupBy('uc.user_id');

                    $usersData->leftJoinSub($creditSubQuery, 'credit_history', function ($join) {
                        $join->on('users.id', '=', 'credit_history.user_id');
                    });

                    $usersData->addSelect('credit_history.credit_history');
                }
                if ($toggleFieldBio['isPerformance']) {
                    $performanceSubQuery = DB::table('performance_histories as ph');
                    $performanceSubQuery->join('performance_history_users as pfhu', 'ph.id', '=', 'pfhu.performance_history_id');
                    $performanceSubQuery->select('pfhu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> ',ph.name, ', Nilai Prestasi Kerja : ', pfhu.work_performance_score,'</li>') SEPARATOR ' ') as performance_history"));
                    $performanceSubQuery->whereIn('pfhu.user_id', $userId);
                    $performanceSubQuery->groupBy('pfhu.user_id');

                    $usersData->leftJoinSub($performanceSubQuery, 'performance_history', function ($join) {
                        $join->on('users.id', '=', 'performance_history.user_id');
                    });

                    $usersData->addSelect('performance_history.performance_history');
                }
                if ($toggleFieldBio['isEducationHistory']) {
                    $educationSubquery = DB::table('user_educations as ut');
                    $educationSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> ',
                             CASE ut.level
                                    WHEN 1 THEN 'SD/Sederajat'
                                    WHEN 2 THEN 'SLTP/Sederajat'
                                    WHEN 3 THEN 'SLTA/Sederajat'
                                    WHEN 4 THEN 'Diploma I/II'
                                    WHEN 5 THEN 'Akademik/D3/S.Muda'
                                    WHEN 6 THEN 'Diploma IV/Strata I'
                                    WHEN 7 THEN 'Strata II'
                                    WHEN 8 THEN 'Strata III'
                             END
                            ,', ',  ut.major , '</li>') SEPARATOR ' ') as education_history"));
                    $educationSubquery->whereIn('ut.user_id', $userId);
                    $educationSubquery->groupBy('ut.user_id');

                    $usersData->leftJoinSub($educationSubquery, 'education_history', function ($join) {
                        $join->on('users.id', '=', 'education_history.user_id');
                    });

                    $usersData->addSelect('education_history.education_history');
                }
                if ($toggleFieldBio['isDisciplinary']) {
                    $disciplinarySubquery = DB::table('disciplinary_history_users as dhu')
                        ->join('disciplinary_histories as dh', 'dhu.disciplinary_history_id', '=', 'dh.id')
                        ->join('disciplinaries as d', 'dhu.disciplinary_id', '=', 'd.id')
                        ->select('dhu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> Golongan: ', dhu.grade, ' Posisi: ', dhu.position, ' (Periode: ', dh.period_month, ' ', dh.period_year, ', Tanggal Awal: ', DATE_FORMAT(dhu.start_date, '%d-%m-%Y'), ' Tanggal Akhir: ', DATE_FORMAT(dhu.end_date, '%d-%m-%Y'), ') Decree: ', dhu.decree_number, ', Kantor Otorisasi: ', dhu.authorizing_officer, ' Petugas: ', dhu.name_of_authorizing_officer, '</li>') SEPARATOR ' ') as disciplinary_history"))
                        ->whereIn('dhu.user_id', $userId)
                        ->groupBy('dhu.user_id');

                    $usersData->leftJoinSub($disciplinarySubquery, 'disciplinary_history', function ($join) {
                        $join->on('users.id', '=', 'disciplinary_history.user_id');
                    });

                    $usersData->addSelect('disciplinary_history.disciplinary_history');
                }
                if ($toggleFieldBio['isFamilyHistory']) {
                    $familyHistory = DB::table('user_families as uf');
                    $familyHistory->select('uf.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> Nama : ',uf.name, '
                            Nomor KTP: ', uf.id_number, ' Nomor KK: ', uf.card_number, ', Tempat Tanggal Lahir: ', uf.place_of_birth, ', ', DATE_FORMAT(uf.date_of_birth, '%d-%m-%Y') ,' Agama: ',
                            CASE uf.religion
                                WHEN 1 THEN 'Islam'
                                WHEN 2 THEN 'Kristen'
                                WHEN 3 THEN 'Katolik'
                                WHEN 4 THEN 'Hindu'
                                WHEN 5 THEN 'Buddha'
                                WHEN 6 THEN 'Konghucu'
                            END
                            , ', Jenis Kelamin: ',
                            CASE uf.gender
                                WHEN 1 THEN 'Pria'
                                WHEN 2 THEN 'Wanita'
                            END
                            ,', Nama Ayah: ', uf.name_of_father , ' Nama Ibu:', uf.name_of_mother,
                            ' Relasi Keluarga : ',
                            CASE uf.relationship_status
                                WHEN 1 THEN 'Kepala Keluarga'
                                WHEN 2 THEN 'Suami'
                                WHEN 3 THEN 'Istri'
                                WHEN 4 THEN 'Anak'
                                WHEN 5 THEN 'Menantu'
                                WHEN 6 THEN 'Cucu'
                                WHEN 7 THEN 'Orang Tua'
                                WHEN 8 THEN 'Mertua'
                                WHEN 9 THEN 'Famili Lainnya'
                                WHEN 10 THEN 'Pembantu'
                                WHEN 11 THEN 'Lainnya'
                            END
                            ,' Edukasi: ',
                            CASE uf.education
                                WHEN 1 THEN 'Kepala Keluarga'
                                WHEN 2 THEN 'Suami'
                                WHEN 3 THEN 'Istri'
                                WHEN 4 THEN 'Anak'
                                WHEN 5 THEN 'Menantu'
                                WHEN 6 THEN 'Cucu'
                                WHEN 7 THEN 'Orang Tua'
                                WHEN 8 THEN 'Mertua'
                                WHEN 9 THEN 'Famili Lainnya'
                                WHEN 10 THEN 'Pembantu'
                                WHEN 11 THEN 'Lainnya'
                            END
                            ,' Okupasi: ', uf.occupation,' Status Perkawinan',
                            CASE uf.marital_status
                                WHEN 1 THEN 'Belum Menikah'
                                WHEN 2 THEN 'Menikah'
                                WHEN 3 THEN 'Cerai Hidup'
                                WHEN 4 THEN 'Cerai Mati'
                            END
                            ,' Nomor Handphone', uf.mobile_phone,'</li>') SEPARATOR ' ') as family_history"));
                    $familyHistory->whereIn('uf.user_id', $userId);
                    $familyHistory->groupBy('uf.user_id');

                    $usersData->leftJoinSub($familyHistory, 'family_history', function ($join) {
                        $join->on('users.id', '=', 'family_history.user_id');
                    });

                    $usersData->addSelect('family_history.family_history');
                }
                if ($toggleFieldBio['isLeave']) {
                    $leaveSubquery = DB::table('user_leaves as ul');
                    $leaveSubquery->join('users', 'users.id', '=', 'ul.user_id');
                    $leaveSubquery->join('grades', 'grades.id', '=', 'users.grade_id');
                    $leaveSubquery->join('positions', 'positions.id', '=', 'users.position_id');
                    $leaveSubquery->select('ul.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> Golongan : ',grades.name, '
                            Jabatan: ', positions.name, ' Tanggal Mulai: ', DATE_FORMAT(ul.start_date, '%d-%m-%Y'), ', Tanggal Selesai: ', DATE_FORMAT(ul.end_date, '%d-%m-%Y'), ' Alasan: ',
                            CASE ul.type
                                WHEN 1 THEN 'Cuti diluar Tanggungan Negara'
                                WHEN 2 THEN 'Cuti Sakit'
                                WHEN 3 THEN 'Cuti Besar'
                                WHEN 4 THEN 'Cuti Bersalin'
                                WHEN 5 THEN 'Cuti Belajar Luar Negeri'
                                WHEN 6 THEN 'Cuti Tahunan Luar Negeri'
                            END
                             , ', Tujuan: ', ul.description,', Nomor: ', ul.number , '</li>') SEPARATOR ' ') as leave_history"));
                    $leaveSubquery->whereIn('ul.user_id', $userId);
                    $leaveSubquery->groupBy('ul.user_id');

                    $usersData->leftJoinSub($leaveSubquery, 'leave_history', function ($join) {
                        $join->on('users.id', '=', 'leave_history.user_id');
                    });

                    $usersData->addSelect('leave_history.leave_history');
                }
                if ($toggleFieldBio['isAssessment']) {
                    $assessmentSubquery = DB::table('user_assessments as ua');
                    $assessmentSubquery->select('ua.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Tanggal : ', DATE_FORMAT(ua.event_date, '%d-%m-%Y'), '
                            Point: ', CASE ua.point
                                WHEN 1 THEN 'Kurang Memenuhi Syarat'
                                WHEN 2 THEN 'Masih Memenuhi Syarat'
                                WHEN 3 THEN 'Memenuhi Syarat'
                            END, ' Organizer : ', ua.organizer,'</li>') SEPARATOR ' ') as assessment_history"));
                    $assessmentSubquery->whereIn('ua.user_id', $userId);
                    $assessmentSubquery->groupBy('ua.user_id');

                    $usersData->leftJoinSub($assessmentSubquery, 'assessment_history', function ($join) {
                        $join->on('users.id', '=', 'assessment_history.user_id');
                    });

                    $usersData->addSelect('assessment_history.assessment_history');
                }
                if ($toggleFieldBio['isCompetency']) {
                    $assessmentSubquery = DB::table('user_competencies as ua');
                    $assessmentSubquery->select('ua.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Tanggal : ', DATE_FORMAT(ua.event_date, '%d-%m-%Y'), '
                            Point: ', CASE ua.point
                                WHEN 1 THEN 'Lulus'
                                WHEN 2 THEN 'Tidak Lulus'
                            END, ' Organizer : ', ua.organizer,'</li>') SEPARATOR ' ') as competency_history"));
                    $assessmentSubquery->whereIn('ua.user_id', $userId);
                    $assessmentSubquery->groupBy('ua.user_id');

                    $usersData->leftJoinSub($assessmentSubquery, 'competency_history', function ($join) {
                        $join->on('users.id', '=', 'competency_history.user_id');
                    });

                    $usersData->addSelect('competency_history.competency_history');
                }
                if ($toggleFieldBio['isTalentPool']) {
                    $assessmentSubquery = DB::table('user_talents as ua');
                    $assessmentSubquery->select('ua.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Tanggal : ', DATE_FORMAT(ua.event_date, '%d-%m-%Y'), '
                            Point: ', CASE ua.point
                                WHEN 1 THEN 'Kotak 1'
                                WHEN 2 THEN 'Kotak 2'
                                WHEN 3 THEN 'Kotak 3'
                                WHEN 4 THEN 'Kotak 4'
                                WHEN 5 THEN 'Kotak 5'
                                WHEN 6 THEN 'Kotak 6'
                                WHEN 7 THEN 'Kotak 7'
                                WHEN 8 THEN 'Kotak 8'
                                WHEN 9 THEN 'Kotak 9'
                            END, ' Organizer : ', ua.organizer,'</li>') SEPARATOR ' ') as talent_pool_history"));
                    $assessmentSubquery->whereIn('ua.user_id', $userId);
                    $assessmentSubquery->groupBy('ua.user_id');

                    $usersData->leftJoinSub($assessmentSubquery, 'talent_pool_history', function ($join) {
                        $join->on('users.id', '=', 'talent_pool_history.user_id');
                    });

                    $usersData->addSelect('talent_pool_history.talent_pool_history');
                }
                if ($toggleFieldBio['isNotes']) {
                    $assessmentSubquery = DB::table('user_notes as un');
                    $assessmentSubquery->join('users', 'un.giver_id', '=', 'users.id');
                    $assessmentSubquery->select('un.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Catatan : ', un.description, '
                            Pemberi catatan: ', users.name, ' Tanggal : ', DATE_FORMAT(un.created_at, '%d-%m-%Y %H:%i'),'</li>') SEPARATOR ' ') as notes"));
                    $assessmentSubquery->whereIn('un.user_id', $userId);
                    $assessmentSubquery->groupBy('un.user_id');

                    $usersData->leftJoinSub($assessmentSubquery, 'notes', function ($join) {
                        $join->on('users.id', '=', 'notes.user_id');
                    });

                    $usersData->addSelect('notes.notes');
                }
                if ($toggleFieldBio['isEmployeeType']) {
                    $employmeeType = DB::table('employment_types as et');
                    $employmeeType->join('users', 'et.id', '=', 'users.employment_type_id');
                    $employmeeType->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as employee_type"));
                    $employmeeType->whereIn('users.id', $userId);
                    $employmeeType->groupBy('users.id');

                    $usersData->leftJoinSub($employmeeType, 'employee_type', function ($join) {
                        $join->on('users.id', '=', 'employee_type.user_id');
                    });

                    $usersData->addSelect('employee_type.employee_type');
                }
                if ($toggleFieldBio['isEchelonDate']) {
                    $usersData->addSelect(DB::raw("DATE_FORMAT(users.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"));
                }
                if ($toggleFieldBio['isGradeDate']) {
                    $usersData->addSelect(DB::raw("DATE_FORMAT(users.grade_effective_date, '%d-%m-%Y') as grade_effective_date"));
                }
                if ($toggleFieldBio['isNoFamily']) {
                    $usersData->addSelect('users.family_registration_number');
                }
                if ($toggleFieldBio['isNIK']) {
                    $usersData->addSelect('users.id_number');
                }
                if ($toggleFieldBio['isStartDate']) {
                    $usersData->addSelect(DB::raw("DATE_FORMAT(users.cpns_effective_date, '%d-%m-%Y') as start_date"));
                }
                if ($toggleFieldBio['isEndDate']) {
                    $usersData->addSelect(DB::raw("
                                CASE
                                    WHEN users.type = 1 && users.echelon_id IS NOT NULL && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL echelons.retirement_age YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                                    WHEN users.type = 2 && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL 58 YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                                    WHEN users.type = 3 && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL 58 YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                                    ELSE NULL
                                END AS retirement_effective_date
                            "));
                }
                if ($toggleFieldBio['isDatePNS']) {
                    $usersData->addSelect(DB::raw("DATE_FORMAT(users.pns_effective_date, '%d-%m-%Y') as pns_effective_date"));
                }
                if ($toggleFieldBio['isDateCPNS']) {
                    $usersData->addSelect(DB::raw("DATE_FORMAT(users.cpns_effective_date, '%d-%m-%Y') as cpns_effective_date"));
                }
                if ($toggleFieldBio['isDatePosition']) {
                    $usersData->addSelect(DB::raw("DATE_FORMAT(users.position_effective_date, '%d-%m-%Y') as position_effective_date"));
                }
                if ($toggleFieldBio['isOutsourcingType']) {
                    $outsourcingSubquery = DB::table('employment_types as et');
                    $outsourcingSubquery->join('users', 'et.id', '=', 'users.employment_type_id');
                    $outsourcingSubquery->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as outsource_type"));
                    $outsourcingSubquery->where('et.type', 3);
                    $outsourcingSubquery->whereIn('users.id', $userId);
                    $outsourcingSubquery->groupBy('users.id');

                    $usersData->leftJoinSub($outsourcingSubquery, 'outsource_type', function ($join) {
                        $join->on('users.id', '=', 'outsource_type.user_id');
                    });

                    $usersData->addSelect('outsource_type.outsource_type');
                }
                if ($toggleFieldBio['isAssistanceType']) {
                    $assistanceSubquery = DB::table('employment_types as et');
                    $assistanceSubquery->join('users', 'et.id', '=', 'users.employment_type_id');
                    $assistanceSubquery->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as assistance_type"));
                    $assistanceSubquery->where('et.type', 2);
                    $assistanceSubquery->whereIn('users.id', $userId);
                    $assistanceSubquery->groupBy('users.id');

                    $usersData->leftJoinSub($assistanceSubquery, 'assistance_type', function ($join) {
                        $join->on('users.id', '=', 'assistance_type.user_id');
                    });

                    $usersData->addSelect('assistance_type.assistance_type');
                }
                if ($toggleFieldBio['isOfficeEmail']) {
                    $usersData->addSelect('users.office_email');
                }
                if ($toggleFieldBio['isKarisu']) {
                    $usersData->addSelect('users.karisu_number');
                }
                if ($toggleFieldBio['isEmergencyContact']) {
                    $usersData->addSelect('users.emergency_contact');
                }
                if ($toggleFieldBio['isPensionCap']) {
                    $usersData->addSelect('echelons.retirement_age as pension_cap');
                }
                if ($toggleFieldBio['isWorkDuration']) {
                    $usersData->addSelect(DB::raw("
                    CASE
                        WHEN users.years_of_service_total > 0 OR users.month_of_service_total > 0
                        THEN CONCAT(
                            IF(users.years_of_service_total > 0, users.years_of_service_total, 0), ' Tahun, ',
                            IF(users.month_of_service_total > 0, users.month_of_service_total, 0), ' Bulan'
                        )
                        ELSE '-'
                    END as work_duration
                "));
                }
                $usersData->whereIn('users.id', $userId);
                $usersData->groupBy('users.id');
                $usersData = $usersData->get();

                $chunkResults = $usersData->map(function ($item) use ($toggleFieldBio) {
                    if ($toggleFieldBio['isPosition'] || $toggleFieldBio['isEchelons'] || $toggleFieldBio['isFullPosition']) {
                        $sql =
                            "WITH RECURSIVE hierarchy AS (
                            -- Anchor member: Select the initial child row
                            SELECT
                                id,
                                name,
                                parent_id
                            FROM
                                positions
                            WHERE
                                id = '" . $item->position_id . "' -- Replace ? with the specific child employee_id

                            UNION DISTINCT

                            -- Recursive member: Select the parent row
                            SELECT
                                p.id,
                                p.name,
                                p.parent_id
                            FROM
                                positions p
                            INNER JOIN
                                hierarchy h ON p.id = h.parent_id
                            WHERE
                                p.entity = 1
                                        AND p.parent_id IS NOT NULL
                        )
                        SELECT
                            *
                        FROM
                            hierarchy WHERE id != '" . $item->position_id . "' ORDER BY id ASC;";

                        $hierarchy = DB::select($sql);
                        $add = [];
                        if (count($hierarchy) > 0) {
                            foreach ($hierarchy as $key => $value) {
                                $name = str_replace('Kepala ', '', $value->name);
                                $e = "echelon_" . $key + 1;
                                $item->$e = $name;
                                $add[] = $name;
                            }
                        }

                        if ($item->position_type == 2) {
                            $add[] = $item->position_name . ' ' . $item->echelons_name;
                        } else {
                            $add[] = $item->position_name;
                        }
                        $item->full_position = implode(', ', array_reverse($add));
                    }
                    return (array) $item;
                })->toArray();

                $results = array_merge($results, $chunkResults);
            }

            $pdf = Pdf::loadView('exports/employee-excel-pdf', [
                'userData' => collect($results),
                'toggleField' => $toggleFieldBio,
            ]);
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setPaper("A4", "landscape");
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->set_option('fontDir', $tmp);
            $pdf->set_option('fontCache', $tmp);
            $pdf->set_option('tempDir', $tmp);
            return $pdf->download('Employees-' . Carbon::now() . '.pdf');
        }

        return $this->response('400', 'File type provided is incorrect');
    }

    /**
     * Preview Export of Employees
     *
     * Preview Export of employees data preview
     * @group Export
     * @authenticated
     *
     * @queryParam page integer Refers to the current page of results being displayed. Default is '1'. Example: 1
     * @queryParam limit integer Refers to the maximum number of items to be displayed per page. Defaults is '10'. Example: 10
     * @bodyParam employee_type int[] Refers to IDs of type of employee (1: ASN, 2: Non ASN, 3: Outsourcing). Example: [1, 3]
     * @bodyParam deputy int[] List of employee's deputy. Example: [1, 3]
     * @bodyParam echelons int[] Refers to IDs of employee echelons. Example: [1, 3]
     * @bodyParam grades int[] Refers to IDs of employee grades. Example: [1, 3]
     * @bodyParam position_status int[] Refers to IDs of employee position status. Example: [1, 3]
     * @bodyParam education int[] Refers to type of employee education (1=SD/Sederajat, 2=SLTP/Sederajat, 3=SLTA/Sederajat, 4=Akademik/D3/S.Muda, 5=Diploma IV, 6=Strata I, 7=Strata II, 8=Strata III). Example: [1, 3]
     * @bodyParam study_programs string[] Program-study values from employee education histories. Example: ["Teknik Informatika"]
     * @bodyParam gender int[] Refers to gender of employee (1: Laki - Laki, 0: Perempuan). Example: [1]
     * @bodyParam min_age int Refers to minimum age of employee. Example: 50
     * @bodyParam max_age int Refers to maximum age of employee. Example: 55
     * @bodyParam marital_status int[] Refers to marital status of employee (1=Belum Menikah, 2=Menikah, 3=Cerai Hidup, 4=Cerai Mati). Example: [1, 3]
     * @bodyParam retirement_age int[] Refers to retirement year of employee. Example: [58,60]
     * @bodyParam retirement_year int Refers to retirement year of employee. Example: 2024
     * @bodyParam total_working_duration int[] Refers to total duration of employee employment. Example: ["5-10"]
     * @bodyParam grade_range int[] Refers to duration of grade in years. Example: ["5-10"]
     * @bodyParam employment_status int[] Refers to employment status of employee (1=Aktif, 2=Pensiun, 3=Berhenti, 4=Meninggal, 5=Alih Status ,6=Aktif PS ,7=CLTN ,8=TBLN ,9=Non Aktif). Example: [1, 3]
     * @bodyParam target_period string[] Refers to employees Target appraisal period ("Q1","Q2","Q3","Q4","Tahunan"). Example: ["Q1"]
     * @bodyParam target_year string Refers to employees Target year period. Example: "2024"
     * @bodyParam work_behavior_rating int[] Refers to employees work behavior rating (1=Diatas Ekspektasi, 2=Sesuai Ekspektasi, 3=Dibawah Ekspektasi). Example: [1, 3]
     * @bodyParam employee_performance_predicate int[] Refers to employees performance predicate (1=Sangat Baik, 2=Baik, 3=Butuh Perbaikan, 4=Kurang, 5=Sangat Kurang). Example: [3]
     * @bodyParam organizational_performance_achievement int[] Refers to employees organizational performance achievement (1=Sangat Baik, 2=Baik, 3=Cukup). Example: [1, 3]
     * @bodyParam credit_period int[] Refers to employees credit period (1=Triwulan 1, 2=Triwulan 2, 3=Triwulan 3, 4=Triwulan 4, 5=Tahunan). Example: [1, 3]
     * @bodyParam credit_year string Refers to employees credit year period. Example: "2024"
     * @bodyParam isName int Indicates whether the name field is included in the request. Example: 1
     * @bodyParam isTitlePrefix int Indicates whether the prefix title field is included in the request. Example: 1
     * @bodyParam isTitleSuffix int Indicates whether the suffix title field is included in the request. Example: 1
     * @bodyParam isNameWithTitle int Indicates whether the name with title field is included in the request. Example: 1
     * @bodyParam isNip int Indicates whether the NIP (National Identification Number) field is included in the request. Example: 1
     * @bodyParam isBirthPlaceDate int Indicates whether the birth place and date field is included in the request. Example: 1
     * @bodyParam isAge int Indicates whether the age field is included in the request. Example: 1
     * @bodyParam isReligion int Indicates whether the religion field is included in the request. Example: 1
     * @bodyParam isGender int Indicates whether the gender field is included in the request. Example: 1
     * @bodyParam isMaritalStatus int Indicates whether the marital status field is included in the request. Example: 1
     * @bodyParam isMarriageDate int Indicates whether the marriage date field is included in the request. Example: 1
     * @bodyParam isMarriageDescription int Indicates whether the marriage description field is included in the request. Example: 1
     * @bodyParam isMarriageOtherNotes int Indicates whether the marriage other notes field is included in the request. Example: 1
     * @bodyParam isEmployeeType int Indicates whether the employee type field is included in the output document. Example: 1
     * @bodyParam isAssistanceType int Indicates whether the employee type assistance field is included in the output document. Example: 1
     * @bodyParam isOutsourcingType int Indicates whether the employee type outsourcing field is included in the output document. Example: 1
     * @bodyParam isDatePNS int Indicates whether the CPNS Start date field is included in the request. Example: 1
     * @bodyParam isDateCPNS int Indicates whether the CPNS Start date field is included in the request. Example: 1
     * @bodyParam isStartDate int Indicates whether the employment start date field is included in the request. Example: 1
     * @bodyParam isEndDate int Indicates whether the employment end date field is included in the request. Example: 1
     * @bodyParam isWorkDuration int Indicates the duration of work. Example: 1
     * @bodyParam isGradeDuration int Indicates whether the grade duration field is included in the request. Example: 1
     * @bodyParam isPosition int Indicates whether the position field is included in the request. Example: 1
     * @bodyParam isFullPosition int Indicates whether the full position field is included in the request. Example: 1
     * @bodyParam isDatePosition int Indicates whether the position start date field is included in the request. Example: 1
     * @bodyParam isEchelons int Indicates whether the echelons field is included in the request. Example: 1
     * @bodyParam isEchelonDate int Indicates whether the echelon start date field is included in the request. Example: 1
     * @bodyParam isGrade int Indicates whether the grade field is included in the request. Example: 1
     * @bodyParam isGradeDate int Indicates whether the grade start date field is included in the request. Example: 1
     * @bodyParam isAgency int Indicates whether the agency field is included in the request. Example: 1
     * @bodyParam isNoWorker int Indicates whether the worker number field is included in the request. Example: 1
     * @bodyParam isKarisu int Indicates whether the Number Karisu field is included in the request. Example: 1
     * @bodyParam isNPWP int Indicates whether the NPWP (Tax Identification Number) field is included in the request. Example: 1
     * @bodyParam isEmployeeStatus int Indicates whether the employee status field is included in the request. Example: 1
     * @bodyParam isNoFamily int Indicates whether the Number family field is included in the request. Example: 1
     * @bodyParam isNIK int Indicates whether the NIK field is included in the request. Example: 1
     * @bodyParam isCurrentAddress int Indicates whether the current address field is included in the request. Example: 1
     * @bodyParam isComplex int Indicates whether the complex field is included in the request. Example: 1
     * @bodyParam isIDCardAddress int Indicates whether the ID Card address is included in the request. Example: 1
     * @bodyParam isHomeNumber int Indicates whether the home number field is included in the request. Example: 1
     * @bodyParam isPhoneNumber int Indicates whether the phone number field is included in the request. Example: 1
     * @bodyParam isOfficeAddress int Indicates whether the office address field is included in the request. Example: 1
     * @bodyParam isOfficeNumber int Indicates whether the office number field is included in the request. Example: 1
     * @bodyParam isEmail int Indicates whether the email field is included in the request. Example: 1
     * @bodyParam isOfficeEmail int Indicates whether the office email field is included in the request. Example: 1
     * @bodyParam isPositionDescription int Indicates whether the position description field is included in the request. Example: 1
     * @bodyParam isEmergencyContact int Indicates whether the emergency contact field is included in the request. Example: 1
     * @bodyParam isPensionCap int Indicates whether the pension cap field is included in the request. Example: 1
     * @bodyParam isEducationHistory int Indicates whether the education history field is included in the request. Example: 1
     * @bodyParam isPositionHistory int Indicates whether the position history field is included in the request. Example: 1
     * @bodyParam isGradeHistory int Indicates whether the grade history field is included in the request. Example: 1
     * @bodyParam isTrainingStructural int Indicates whether the structural training field is included in the request. Example: 1
     * @bodyParam isTrainingFunctional int Indicates whether the functional training field is included in the request. Example: 1
     * @bodyParam isTrainingTechnique int Indicates whether the technique training field is included in the request. Example: 1
     * @bodyParam isRecognition int Indicates whether the recognition field is included in the request. Example: 1
     * @bodyParam isSKP int Indicates whether the SKP (Employee Performance Target) field is included in the request. Example: 1
     * @bodyParam isCredit int Indicates whether the PAK history field is included in the request. Example: 1
     * @bodyParam isPerformance int Indicates whether the PPK history field is included in the request. Example: 1
     * @bodyParam isDisciplinary int Indicates whether the disciplinary field is included in the request. Example: 1
     * @bodyParam isFamilyHistory int Indicates whether the family history field is included in the request. Example: 1
     * @bodyParam isLeave int Indicates whether the leave field is included in the request. Example: 1
     * @bodyParam isNotes int Indicates whether the notes field is included in the request. Example: 1
     * @bodyParam isAssessment int Indicates whether the assessment field is included in the request. Example: 1
     * @bodyParam isCompetency int Indicates whether the competency field is included in the request. Example: 1
     * @bodyParam isTalentPool int Indicates whether the talent pool field is included in the request. Example: 1
     **/
    public function exportExcelsPreview(PreviewExportEmployeesRequest $request)
    {
        // filter user to get ids
        $users = DB::table('users')
            ->leftJoin('user_credits', 'users.id', '=', 'user_credits.user_id')
            ->leftJoin('echelons', 'users.echelon_id', '=', 'echelons.id')
            ->leftJoin('target_history_users', 'users.id', '=', 'target_history_users.user_id')
            ->leftJoin('target_histories', 'target_history_users.target_history_id', '=', 'target_histories.id')
            ->select('users.id');
        if (isset($request->employee_type)) {
            $users->whereIn('users.type', $request->employee_type);
        }
        if (isset($request->deputy)) {
            $sql = "
                WITH RECURSIVE hierarchy AS (
                    -- Anchor member: Select the initial parent row
                    SELECT
                        po.id,
                        po.name,
                        po.parent_id
                    FROM
                        positions po
                    WHERE
                        po.id IN (" . implode(',', $request->deputy) . ") -- Replace ? with the specific parent id

                    UNION DISTINCT

                    -- Recursive member: Select the child row
                    SELECT
                        p.id,
                        p.name,
                        p.parent_id
                    FROM
                        positions p
                    INNER JOIN
                        hierarchy h ON p.parent_id = h.id
                )
                SELECT id FROM hierarchy;";

            $ids = DB::select($sql);
            $positionIds = [];
            foreach ($ids as $key => $value) {
                $positionIds[] = $value->id;
            }
            $users->whereIn('users.position_id', $positionIds);
        }
        if (isset($request->echelons)) {
            $users->whereIn('echelons.id', $request->echelons);
        }
        if (isset($request->grades)) {
            $users->whereIn('users.grade_id', $request->grades);
        }
        if (isset($request->education)) {
            $users->whereIn('users.education_level', $request->education);
        }
        if (isset($request->study_programs)) {
            $users->whereExists(function ($query) use ($request) {
                $query->select(DB::raw(1))
                    ->from('user_educations as ue')
                    ->whereColumn('ue.user_id', 'users.id')
                    ->whereIn('ue.major', $request->study_programs);

                $this->applyLatestEducationConstraint($query, 'ue');
            });
        }
        if (isset($request->gender)) {
            $users->whereIn('users.gender', $request->gender);
        }
        if (isset($request->position_status)) {
            $users->leftJoin('position_history_users', 'users.id', '=', 'position_history_users.user_id');
            $users->whereIn('position_history_users.position_status', $request->position_status);
        }
        if (isset($request->min_age)) {
            $minAge = $request->input('min_age');
            $users->where(DB::raw('TIMESTAMPDIFF(YEAR, users.date_of_birth, NOW())'), '>=', $minAge);
        }
        if (isset($request->max_age)) {
            $maxAge = $request->input('max_age');
            $users->where(DB::raw('TIMESTAMPDIFF(YEAR, users.date_of_birth, NOW())'), '<=', $maxAge);
        }
        if (isset($request->marital_status)) {
            $users->whereIn('users.marital_status', $request->marital_status);
        }
        if (isset($request->retirement_age) && isset($request->retirement_year)) { // Age and year
            $users->where(function ($query) use ($request) {
                foreach ($request->retirement_age as $age) {
                    $query->orWhere(DB::raw("YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + $age"), $request->retirement_year);
                }
            });
        }
        if (isset($request->retirement_age) && !isset($request->retirement_year)) { // Age only
            $users->whereIn('echelons.retirement_age', $request->retirement_age);
        }
        if (!isset($request->retirement_age) && isset($request->retirement_year)) { // Year only
            $users->whereRaw("
            CASE
                WHEN users.type = 1 AND users.echelon_id IS NOT NULL AND date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + echelons.retirement_age = ?
                WHEN users.type = 2 AND users.date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + 58 = ?
                WHEN users.type = 3 AND users.date_of_birth IS NOT NULL THEN YEAR(DATE_ADD(date_of_birth, INTERVAL 1 MONTH)) + 58 = ?
            END
        ", [$request->retirement_year, $request->retirement_year, $request->retirement_year]);
        }
        if (isset($request->grade_range)) {
            $gradeRanges = $request->input('grade_range', []);
            $now = Carbon::now();

            $users->where(function ($query) use ($gradeRanges, $now) {
                foreach ($gradeRanges as $range) {
                    [$minYears, $maxYears] = explode('-', $range);

                    if ($minYears == "0") {
                        $query->whereRaw(DB::raw("
                        (
                            ((years_of_service_rank = 0 OR years_of_service_rank IS NULL) AND month_of_service_rank > 0)
                            OR (years_of_service_rank = " . $maxYears . " AND (month_of_service_rank IS NULL OR month_of_service_rank = 0))
                            OR (years_of_service_rank > 0 AND years_of_service_rank < " . $maxYears . ")
                         )"));
                    } else {
                        $query->whereRaw(DB::raw("
                        (
                            years_of_service_rank = " . $minYears . " AND month_of_service_rank > 0
                            OR (years_of_service_rank = " . $maxYears . " AND (month_of_service_rank IS NULL OR month_of_service_rank = 0))
                            OR (years_of_service_rank > " . $minYears . " AND years_of_service_rank < " . $maxYears . ")
                         )"));
                    }
                }
            });
        }
        if (isset($request->employment_status)) {
            $users->whereIn('users.employment_status', $request->employment_status);
        }
        if (isset($request->total_working_duration)) {
            $gradeRanges = $request->input('total_working_duration', []);
            $now = Carbon::now();

            $users->where(function ($query) use ($gradeRanges, $now) {
                foreach ($gradeRanges as $range) {
                    [$minYears, $maxYears] = explode('-', $range);

                    if ($minYears == "0") {
                        $query->whereRaw(DB::raw("
                        (
                            ((years_of_service_total = 0 OR years_of_service_total IS NULL) AND month_of_service_total > 0)
                            OR (years_of_service_total = " . $maxYears . " AND (month_of_service_total IS NULL OR month_of_service_total = 0))
                            OR (years_of_service_total > 0 AND years_of_service_total < " . $maxYears . ")
                         )"));
                    } else {
                        $query->whereRaw(DB::raw("
                        (
                            years_of_service_total = " . $minYears . " AND month_of_service_total > 0
                            OR (years_of_service_total = " . $maxYears . " AND (month_of_service_total IS NULL OR month_of_service_total = 0))
                            OR (years_of_service_total > " . $minYears . " AND years_of_service_total < " . $maxYears . ")
                         )"));
                    }
                }
            });
        }
        if (isset($request->credit_period)) {
            $users->where('user_credits.period', $request->credit_period);
        }
        if (isset($request->credit_year)) {
            $users->where('user_credits.year', $request->credit_year);
        }
        if (isset($request->target_period)) {
            $users->where('target_histories.appraisal_period', $request->target_period);
        }
        if (isset($request->target_year)) {
            $users->where('target_histories.period_year', $request->target_year);
        }
        if (isset($request->work_behavior_rating)) {
            $users->where('target_history_users.work_behavior_rating', $request->work_behavior_rating);
        }
        if (isset($request->employee_performance_predicate)) {
            $users->where('target_history_users.employee_performance_predicate', $request->employee_performance_predicate);
        }
        if (isset($request->organizational_performance_achievement)) {
            $users->where('target_history_users.organizational_performance_achievement', $request->organizational_performance_achievement);
        }

        $userIds = $users->pluck('users.id')->toArray();
        if (!$userIds) {
            return $this->response(400, 'Data pegawai tidak ditemukan');
        }

        $toggleFieldBio = array();
        $userId = collect($userIds);
        // $userIdsChunk = $userId->chunk(200);
        $results = collect();
        // Set the group_concat_max_len session variable
        DB::statement("SET SESSION group_concat_max_len = 10000");

        $usersPreview = DB::table('users')->select('users.id');
        $usersPreview->leftJoin('echelons', 'echelons.id', '=', 'users.echelon_id');
        $usersPreview->leftJoin('positions', 'users.position_id', '=', 'positions.id');
        if ($this->request->isName == 1) {
            $usersPreview->addSelect('users.name');
        }
        if ($this->request->isTitlePrefix) {
            $usersPreview->addSelect('users.title_prefix');
        }
        if ($this->request->isTitleSuffix) {
            $usersPreview->addSelect('users.title_suffix');
        }
        if ($this->request->isNameWithTitle) {
            $usersPreview->addSelect(DB::raw("CONCAT(COALESCE(users.title_prefix,''),' ',users.name,' ',COALESCE(users.title_suffix,'')) as name_with_title"));
        }
        if ($this->request->isNip == 1) {
            $usersPreview->addSelect('users.employee_id_number', 'users.employee_registration_number');
        }
        if ($this->request->isBirthPlaceDate == 1) {
            $usersPreview->addSelect('users.place_of_birth', DB::raw("DATE_FORMAT(users.date_of_birth, '%d-%m-%Y') as date_of_birth"));
        }
        if ($this->request->isAge == 1) {
            $usersPreview->addSelect(DB::raw("TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) AS age"));
        }
        if ($this->request->isReligion == 1) {
            $usersPreview->addSelect('users.religion');
        }
        if ($this->request->isGender == 1) {
            $usersPreview->addSelect('users.gender');
        }
        if ($this->request->isMaritalStatus == 1) {
            $usersPreview->addSelect('users.marital_status');
        }
        if ($this->request->isMarriageDate == 1) {
            $usersPreview->addSelect('users.marriage_date');
        }
        if ($this->request->isMarriageDescription == 1) {
            $usersPreview->addSelect('users.marriage_description');
        }
        // if ($this->request->isMarriageOtherNotes == 1) {
        //     $usersPreview->addSelect('users.marriage_other_notes');
        // }
        if ($this->request->isPosition == 1) {
            $usersPreview->addSelect('positions.name as position_name');
        }
        if ($this->request->isFullPosition == 1) {
            $usersPreview->addSelect('positions.id as position_id');
            $usersPreview->addSelect(DB::raw("
                CASE
                    WHEN positions.type = 2 THEN CONCAT(positions.name, ' ',COALESCE(echelons.name,''))
                    ELSE positions.name
                END as full_position
            "));
        }
        if ($this->request->isPositionDescription == 1) {
            $usersPreview->addSelect('users.description');
        }
        if ($this->request->isEchelons == 1) {
            $usersPreview->addSelect('echelons.name as echelons_name');
        }
        if ($this->request->isGrade == 1) {
            $usersPreview->leftJoin('grades as g', 'users.grade_id', '=', 'g.id');
            $usersPreview->addSelect(DB::raw("CONCAT(g.name, ' ', g.code) as grade_name"));
        }
        if ($this->request->isEducation == 1) {
            $usersPreview->addSelect('users.education_level', 'users.education_name', 'users.education_year');
        }
        if ($this->request->isStudyProgram == 1) {
            $usersPreview->addSelect([
                'study_program' => DB::table('user_educations as ue')
                    ->select('ue.major')
                    ->whereColumn('ue.user_id', 'users.id')
                    ->orderByDesc('ue.level')
                    ->orderByDesc('ue.year_of_graduation')
                    ->orderByDesc('ue.id')
                    ->limit(1),
            ]);
        }
        if ($this->request->isEmployeeStatus == 1) {
            $usersPreview->addSelect('users.employment_status');
        }
        if ($this->request->isAgency == 1) {
            $usersPreview->leftJoin('institutions as i', 'users.institution_id', '=', 'i.id');
            $usersPreview->addSelect('i.name as institution_name');
        }
        if ($this->request->isNoWorker == 1) {
            $usersPreview->addSelect('users.employee_id_card_number');
        }
        if ($this->request->isGradeDuration == 1) {
            $usersPreview->addSelect(DB::raw("
            CASE
                WHEN users.years_of_service_rank > 0 OR users.month_of_service_rank > 0
                THEN CONCAT(
                    IF(users.years_of_service_rank > 0, users.years_of_service_rank, 0), ' Tahun, ',
                    IF(users.month_of_service_rank > 0, users.month_of_service_rank, 0), ' Bulan'
                )
                ELSE '-'
            END as grade_duration
        "));
        }
        if ($this->request->isNPWP == 1) {
            $usersPreview->addSelect('users.id_tax');
        }
        if ($this->request->isCurrentAddress == 1) {
            $usersPreview->addSelect('users.current_address');
        }
        if ($this->request->isComplex == 1) {
            $usersPreview->leftJoin('residences as r', 'users.residence_id', '=', 'r.id');
            $usersPreview->addSelect(DB::raw("r.name as residence_name"));
        }
        if ($this->request->isIDCardAddress == 1) {
            $usersPreview->addSelect('users.residence_description');
        }
        if ($this->request->isHomeNumber == 1) {
            $usersPreview->addSelect('users.home_phone_number');
        }
        if ($this->request->isPhoneNumber == 1) {
            $usersPreview->addSelect('users.mobile_phone');
        }
        if ($this->request->isOfficeAddress == 1) {
            $usersPreview->addSelect('users.office_address');
        }
        if ($this->request->isOfficeNumber == 1) {
            $usersPreview->addSelect('users.office_phone_number');
        }
        if ($this->request->isEmail == 1) {
            $usersPreview->addSelect('users.email');
        }
        if ($this->request->isGradeHistory == 1) {
            $gradeHistorySubquery = DB::table('grade_history_users as ghu');
            $gradeHistorySubquery->join('grades', 'grades.id', '=', 'ghu.grade_id');
            $gradeHistorySubquery->select('ghu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', grades.name, ' ', grades.code, '</li>') SEPARATOR ' ') as grade_history"));
            $gradeHistorySubquery->whereIn('ghu.user_id', $userId);
            $gradeHistorySubquery->groupBy('ghu.user_id');
            $usersPreview->leftJoinSub($gradeHistorySubquery, 'grade_history', function ($join) {
                $join->on('users.id', '=', 'grade_history.user_id');
            });
            $usersPreview->addSelect('grade_history.grade_history');
        }
        if ($this->request->isPositionHistory == 1) {
            $positionHistorySubquery = DB::table('position_history_users as phu');
            $positionHistorySubquery->select('phu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', phu.position,'</li>') SEPARATOR ' ') as position_history"));
            $positionHistorySubquery->whereIn('phu.user_id', $userId);
            $positionHistorySubquery->groupBy('phu.user_id');
            $usersPreview->leftJoinSub($positionHistorySubquery, 'position_history', function ($join) {
                $join->on('users.id', '=', 'position_history.user_id');
            });
            $usersPreview->addSelect('position_history.position_history');
        }
        if ($this->request->isTrainingStructural == 1) {
            $trainingStructuralSubquery = DB::table('training_histories as t');
            $trainingStructuralSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
            $trainingStructuralSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, ', ', t.level, '</li>') SEPARATOR ' ') as structural_training_history"));
            $trainingStructuralSubquery->whereIn('ut.user_id', $userId);
            $trainingStructuralSubquery->where('t.type', 1);
            $trainingStructuralSubquery->groupBy('ut.user_id');

            $usersPreview->leftJoinSub($trainingStructuralSubquery, 'structural_training_history', function ($join) {
                $join->on('users.id', '=', 'structural_training_history.user_id');
            });

            $usersPreview->addSelect('structural_training_history.structural_training_history');
        }

        if ($this->request->isTrainingFunctional == 1) {
            $trainingFunctionalSubquery = DB::table('training_histories as t');
            $trainingFunctionalSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
            $trainingFunctionalSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, ', ', t.level, '</li>') SEPARATOR ' ') as functional_training_history "));
            $trainingFunctionalSubquery->whereIn('ut.user_id', $userId);
            $trainingFunctionalSubquery->where('t.type', 2);
            $trainingFunctionalSubquery->groupBy('ut.user_id');

            $usersPreview->leftJoinSub($trainingFunctionalSubquery, 'functional_training_history', function ($join) {
                $join->on('users.id', '=', 'functional_training_history.user_id');
            });

            $usersPreview->addSelect('functional_training_history.functional_training_history');
        }

        if ($this->request->isTrainingTechnique == 1) {
            $trainingTechnicSubquery = DB::table('training_histories as t');
            $trainingTechnicSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
            $trainingTechnicSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, '</li>') SEPARATOR ' ') as technique_training_history"));
            $trainingTechnicSubquery->whereIn('ut.user_id', $userId);
            $trainingTechnicSubquery->where('t.type', 3);
            $trainingTechnicSubquery->groupBy('ut.user_id');

            $usersPreview->leftJoinSub($trainingTechnicSubquery, 'technique_training_history', function ($join) {
                $join->on('users.id', '=', 'technique_training_history.user_id');
            });

            $usersPreview->addSelect('technique_training_history.technique_training_history');
        }
        if ($this->request->isRecognition == 1) {
            $recognitionSubquery = DB::table('recognition_histories as r');
            $recognitionSubquery->join('recognition_history_users as ur', 'r.id', '=', 'ur.recognition_history_id');
            $recognitionSubquery->join('recognitions', 'r.recognition_id', '=', 'recognitions.id');
            $recognitionSubquery->select('ur.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>',recognitions.name,'</li>') SEPARATOR ' ') as recognition_history"));
            $recognitionSubquery->whereIn('ur.user_id', $userId);
            $recognitionSubquery->groupBy('ur.user_id');

            $usersPreview->leftJoinSub($recognitionSubquery, 'recognition_history', function ($join) {
                $join->on('users.id', '=', 'recognition_history.user_id');
            });

            $usersPreview->addSelect('recognition_history.recognition_history');
        }
        if ($this->request->isSKP == 1) {
            $skpSubquery = DB::table('target_histories as t');
            $skpSubquery->join('target_history_users as ut', 't.id', '=', 'ut.target_history_id');
            $skpSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>',t.name, ' Penilaian Perilaku : ',
                 CASE ut.work_behavior_rating
                        WHEN 1 THEN 'Diatas Ekspektasi'
                        WHEN 2 THEN 'Sesuai Ekspektasi'
                        WHEN 3 THEN 'Dibawah Ekspektasi'
                 END
                , ', Penilaian Predikat Performa : ',
                CASE ut.employee_performance_predicate
                        WHEN 1 THEN 'Sangat Baik'
                        WHEN 2 THEN 'Baik'
                        WHEN 3 THEN 'Butuh Perbaikan'
                        WHEN 4 THEN 'Kurang'
                        WHEN 5 THEN 'Sangat Kurang'
                 END
                 ,', Penilaian Performa Organisasi : ',
                 CASE ut.employee_performance_predicate
                        WHEN 1 THEN 'Sangat Baik'
                        WHEN 2 THEN 'Baik'
                        WHEN 3 THEN 'Cukup'
                 END, '</li>') SEPARATOR ' ') as skp_history"));
            $skpSubquery->whereIn('ut.user_id', $userId);
            $skpSubquery->groupBy('ut.user_id');

            $usersPreview->leftJoinSub($skpSubquery, 'skp_history', function ($join) {
                $join->on('users.id', '=', 'skp_history.user_id');
            });

            $usersPreview->addSelect('skp_history.skp_history');
        }
        if ($this->request->isCredit == 1) {
            $creditSubQuery = DB::table('user_credits as uc');
            $creditSubQuery->select('uc.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> ',uc.position, ', Angka Kredit Terakhir : ', uc.score,'</li>') SEPARATOR ' ') as credit_history"));
            $creditSubQuery->whereIn('uc.user_id', $userId);
            $creditSubQuery->groupBy('uc.user_id');

            $usersPreview->leftJoinSub($creditSubQuery, 'credit_history', function ($join) {
                $join->on('users.id', '=', 'credit_history.user_id');
            });

            $usersPreview->addSelect('credit_history.credit_history');
        }
        if ($this->request->isPerformance == 1) {
            $performanceSubQuery = DB::table('performance_histories as ph');
            $performanceSubQuery->join('performance_history_users as pfhu', 'ph.id', '=', 'pfhu.performance_history_id');
            $performanceSubQuery->select('pfhu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> ',ph.name, ', Nilai Prestasi Kerja : ', pfhu.work_performance_score,'</li>') SEPARATOR ' ') as performance_history"));
            $performanceSubQuery->whereIn('pfhu.user_id', $userId);
            $performanceSubQuery->groupBy('pfhu.user_id');

            $usersPreview->leftJoinSub($performanceSubQuery, 'performance_history', function ($join) {
                $join->on('users.id', '=', 'performance_history.user_id');
            });

            $usersPreview->addSelect('performance_history.performance_history');
        }
        if ($this->request->isEducationHistory == 1) {
            $educationSubquery = DB::table('user_educations as ut');
            $educationSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> ',
                 CASE ut.level
                        WHEN 1 THEN 'SD/Sederajat'
                        WHEN 2 THEN 'SLTP/Sederajat'
                        WHEN 3 THEN 'SLTA/Sederajat'
                        WHEN 4 THEN 'Diploma I/II'
                        WHEN 5 THEN 'Akademik/D3/S.Muda'
                        WHEN 6 THEN 'Diploma IV/Strata I'
                        WHEN 7 THEN 'Strata II'
                        WHEN 8 THEN 'Strata III'
                 END
                ,', ',  ut.major , '</li>') SEPARATOR ' ') as education_history"));
            $educationSubquery->whereIn('ut.user_id', $userId);
            $educationSubquery->groupBy('ut.user_id');

            $usersPreview->leftJoinSub($educationSubquery, 'education_history', function ($join) {
                $join->on('users.id', '=', 'education_history.user_id');
            });

            $usersPreview->addSelect('education_history.education_history');
        }
        if ($this->request->isDisciplinary == 1) {
            $disciplinarySubquery = DB::table('disciplinary_history_users as dhu')
                ->join('disciplinary_histories as dh', 'dhu.disciplinary_history_id', '=', 'dh.id')
                ->join('disciplinaries as d', 'dhu.disciplinary_id', '=', 'd.id')
                ->select('dhu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> Golongan: ', dhu.grade, ' Posisi: ', dhu.position, ' (Periode: ', dh.period_month, ' ', dh.period_year, ', Tanggal Awal: ', DATE_FORMAT(dhu.start_date, '%d-%m-%Y'), ' Tanggal Akhir: ', DATE_FORMAT(dhu.end_date, '%d-%m-%Y'), ') Decree: ', dhu.decree_number, ', Kantor Otorisasi: ', dhu.authorizing_officer, ' Petugas: ', dhu.name_of_authorizing_officer, '</li>') SEPARATOR ' ') as disciplinary_history"))
                ->whereIn('dhu.user_id', $userId)
                ->groupBy('dhu.user_id');

            $usersPreview->leftJoinSub($disciplinarySubquery, 'disciplinary_history', function ($join) {
                $join->on('users.id', '=', 'disciplinary_history.user_id');
            });

            $usersPreview->addSelect('disciplinary_history.disciplinary_history');
        }
        if ($this->request->isFamilyHistory == 1) {
            $familyHistory = DB::table('user_families as uf');
            $familyHistory->select('uf.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> Nama : ',uf.name, '
                Nomor KTP: ', uf.id_number, ' Nomor KK: ', uf.card_number, ', Tempat Tanggal Lahir: ', uf.place_of_birth, ', ', DATE_FORMAT(uf.date_of_birth, '%d-%m-%Y') ,' Agama: ',
                CASE uf.religion
                    WHEN 1 THEN 'Islam'
                    WHEN 2 THEN 'Kristen'
                    WHEN 3 THEN 'Katolik'
                    WHEN 4 THEN 'Hindu'
                    WHEN 5 THEN 'Buddha'
                    WHEN 6 THEN 'Konghucu'
                END
                , ', Jenis Kelamin: ',
                CASE uf.gender
                    WHEN 1 THEN 'Pria'
                    WHEN 2 THEN 'Wanita'
                END
                ,', Nama Ayah: ', uf.name_of_father , ' Nama Ibu:', uf.name_of_mother,
                ' Relasi Keluarga : ',
                CASE uf.relationship_status
                    WHEN 1 THEN 'Kepala Keluarga'
                    WHEN 2 THEN 'Suami'
                    WHEN 3 THEN 'Istri'
                    WHEN 4 THEN 'Anak'
                    WHEN 5 THEN 'Menantu'
                    WHEN 6 THEN 'Cucu'
                    WHEN 7 THEN 'Orang Tua'
                    WHEN 8 THEN 'Mertua'
                    WHEN 9 THEN 'Famili Lainnya'
                    WHEN 10 THEN 'Pembantu'
                    WHEN 11 THEN 'Lainnya'
                END
                ,' Edukasi: ',
                CASE uf.education
                    WHEN 1 THEN 'Kepala Keluarga'
                    WHEN 2 THEN 'Suami'
                    WHEN 3 THEN 'Istri'
                    WHEN 4 THEN 'Anak'
                    WHEN 5 THEN 'Menantu'
                    WHEN 6 THEN 'Cucu'
                    WHEN 7 THEN 'Orang Tua'
                    WHEN 8 THEN 'Mertua'
                    WHEN 9 THEN 'Famili Lainnya'
                    WHEN 10 THEN 'Pembantu'
                    WHEN 11 THEN 'Lainnya'
                END
                ,' Okupasi: ', uf.occupation,' Status Perkawinan',
                CASE uf.marital_status
                    WHEN 1 THEN 'Belum Menikah'
                    WHEN 2 THEN 'Menikah'
                    WHEN 3 THEN 'Cerai Hidup'
                    WHEN 4 THEN 'Cerai Mati'
                END
                ,' Nomor Handphone', uf.mobile_phone,'</li>') SEPARATOR ' ') as family_history"));
            $familyHistory->whereIn('uf.user_id', $userId);
            $familyHistory->groupBy('uf.user_id');

            $usersPreview->leftJoinSub($familyHistory, 'family_history', function ($join) {
                $join->on('users.id', '=', 'family_history.user_id');
            });

            $usersPreview->addSelect('family_history.family_history');
        }
        if ($this->request->isLeave == 1) {
            $leaveSubquery = DB::table('user_leaves as ul');
            $leaveSubquery->join('users', 'users.id', '=', 'ul.user_id');
            $leaveSubquery->join('grades', 'grades.id', '=', 'users.grade_id');
            $leaveSubquery->join('positions', 'positions.id', '=', 'users.position_id');
            $leaveSubquery->select('ul.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> Golongan : ',grades.name, '
                Jabatan: ', positions.name, ' Tanggal Mulai: ', DATE_FORMAT(ul.start_date, '%d-%m-%Y'), ', Tanggal Selesai: ', DATE_FORMAT(ul.end_date, '%d-%m-%Y'), ' Alasan: ',
                CASE ul.type
                    WHEN 1 THEN 'Cuti diluar Tanggungan Negara'
                    WHEN 2 THEN 'Cuti Sakit'
                    WHEN 3 THEN 'Cuti Besar'
                    WHEN 4 THEN 'Cuti Bersalin'
                    WHEN 5 THEN 'Cuti Belajar Luar Negeri'
                    WHEN 6 THEN 'Cuti Tahunan Luar Negeri'
                END
                 , ', Tujuan: ', ul.description,', Nomor: ', ul.number , '</li>') SEPARATOR ' ') as leave_history"));
            $leaveSubquery->whereIn('ul.user_id', $userId);
            $leaveSubquery->groupBy('ul.user_id');

            $usersPreview->leftJoinSub($leaveSubquery, 'leave_history', function ($join) {
                $join->on('users.id', '=', 'leave_history.user_id');
            });

            $usersPreview->addSelect('leave_history.leave_history');
        }
        if ($this->request->isAssessment == 1) {
            $assessmentSubquery = DB::table('user_assessments as ua');
            $assessmentSubquery->select('ua.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Tanggal : ', DATE_FORMAT(ua.event_date, '%d-%m-%Y'), '
                Point: ', CASE ua.point
                    WHEN 1 THEN 'Kurang Memenuhi Syarat'
                    WHEN 2 THEN 'Masih Memenuhi Syarat'
                    WHEN 3 THEN 'Memenuhi Syarat'
                END, ' Organizer : ', ua.organizer,'</li>') SEPARATOR ' ') as assessment_history"));
            $assessmentSubquery->whereIn('ua.user_id', $userId);
            $assessmentSubquery->groupBy('ua.user_id');

            $usersPreview->leftJoinSub($assessmentSubquery, 'assessment_history', function ($join) {
                $join->on('users.id', '=', 'assessment_history.user_id');
            });

            $usersPreview->addSelect('assessment_history.assessment_history');
        }
        if ($this->request->isCompetency == 1) {
            $assessmentSubquery = DB::table('user_competencies as ua');
            $assessmentSubquery->select('ua.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Tanggal : ', DATE_FORMAT(ua.event_date, '%d-%m-%Y'), '
                Point: ', CASE ua.point
                    WHEN 1 THEN 'Lulus'
                    WHEN 2 THEN 'Tidak Lulus'
                END, ' Organizer : ', ua.organizer,'</li>') SEPARATOR ' ') as competency_history"));
            $assessmentSubquery->whereIn('ua.user_id', $userId);
            $assessmentSubquery->groupBy('ua.user_id');

            $usersPreview->leftJoinSub($assessmentSubquery, 'competency_history', function ($join) {
                $join->on('users.id', '=', 'competency_history.user_id');
            });

            $usersPreview->addSelect('competency_history.competency_history');
        }
        if ($this->request->isTalentPool == 1) {
            $assessmentSubquery = DB::table('user_talents as ua');
            $assessmentSubquery->select('ua.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Tanggal : ', DATE_FORMAT(ua.event_date, '%d-%m-%Y'), '
                Point: ', CASE ua.point
                    WHEN 1 THEN 'Kotak 1'
                    WHEN 2 THEN 'Kotak 2'
                    WHEN 3 THEN 'Kotak 3'
                    WHEN 4 THEN 'Kotak 4'
                    WHEN 5 THEN 'Kotak 5'
                    WHEN 6 THEN 'Kotak 6'
                    WHEN 7 THEN 'Kotak 7'
                    WHEN 8 THEN 'Kotak 8'
                    WHEN 9 THEN 'Kotak 9'
                END, ' Organizer : ', ua.organizer,'</li>') SEPARATOR ' ') as talent_pool_history"));
            $assessmentSubquery->whereIn('ua.user_id', $userId);
            $assessmentSubquery->groupBy('ua.user_id');

            $usersPreview->leftJoinSub($assessmentSubquery, 'talent_pool_history', function ($join) {
                $join->on('users.id', '=', 'talent_pool_history.user_id');
            });

            $usersPreview->addSelect('talent_pool_history.talent_pool_history');
        }
        if ($this->request->isNotes == 1) {
            $assessmentSubquery = DB::table('user_notes as un');
            $assessmentSubquery->join('users', 'un.giver_id', '=', 'users.id');
            $assessmentSubquery->select('un.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Catatan : ', un.description, '
                Pemberi catatan: ', users.name, ' Tanggal : ', DATE_FORMAT(un.created_at, '%d-%m-%Y %H:%i'),'</li>') SEPARATOR ' ') as notes"));
            $assessmentSubquery->whereIn('un.user_id', $userId);
            $assessmentSubquery->groupBy('un.user_id');

            $usersPreview->leftJoinSub($assessmentSubquery, 'notes', function ($join) {
                $join->on('users.id', '=', 'notes.user_id');
            });

            $usersPreview->addSelect('notes.notes');
        }
        if ($this->request->isEmployeeType == 1) {
            $employmeeType = DB::table('employment_types as et');
            $employmeeType->join('users', 'et.id', '=', 'users.employment_type_id');
            $employmeeType->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as employee_type"));
            $employmeeType->where('et.type', 1);
            $employmeeType->whereIn('users.id', $userId);
            $employmeeType->groupBy('users.id');

            $usersPreview->leftJoinSub($employmeeType, 'employee_type', function ($join) {
                $join->on('users.id', '=', 'employee_type.user_id');
            });

            $usersPreview->addSelect('employee_type.employee_type');
        }
        if ($this->request->isEchelonDate == 1) {
            $usersPreview->addSelect(DB::raw("DATE_FORMAT(users.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"));
        }
        if ($this->request->isGradeDate == 1) {
            $usersPreview->addSelect(DB::raw("DATE_FORMAT(users.grade_effective_date, '%d-%m-%Y') as grade_effective_date"));
        }
        if ($this->request->isNoFamily == 1) {
            $usersPreview->addSelect('users.family_registration_number');
        }
        if ($this->request->isNIK == 1) {
            $usersPreview->addSelect('users.id_number');
        }
        if ($this->request->isStartDate == 1) {
            $usersPreview->addSelect(DB::raw("DATE_FORMAT(users.cpns_effective_date, '%d-%m-%Y') as start_date"));
        }
        if ($this->request->isEndDate == 1) {
            $usersPreview->addSelect(DB::raw("
                    CASE
                        WHEN users.type = 1 && users.echelon_id IS NOT NULL && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL echelons.retirement_age YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                        WHEN users.type = 2 && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL 58 YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                        WHEN users.type = 3 && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL 58 YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                        ELSE NULL
                    END AS retirement_effective_date
                "));
        }
        if ($this->request->isDatePNS == 1) {
            $usersPreview->addSelect(DB::raw("DATE_FORMAT(users.pns_effective_date, '%d-%m-%Y') as pns_effective_date"));
        }
        if ($this->request->isDateCPNS == 1) {
            $usersPreview->addSelect(DB::raw("DATE_FORMAT(users.cpns_effective_date, '%d-%m-%Y') as cpns_effective_date"));
        }
        if ($this->request->isDatePosition == 1) {
            $usersPreview->addSelect(DB::raw("DATE_FORMAT(users.position_effective_date, '%d-%m-%Y') as position_effective_date"));
        }
        if ($this->request->isOutsourcingType == 1) {
            $outsourcingSubquery = DB::table('employment_types as et');
            $outsourcingSubquery->join('users', 'et.id', '=', 'users.employment_type_id');
            $outsourcingSubquery->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as outsource_type"));
            $outsourcingSubquery->where('et.type', 3);
            $outsourcingSubquery->whereIn('users.id', $userId);
            $outsourcingSubquery->groupBy('users.id');

            $usersPreview->leftJoinSub($outsourcingSubquery, 'outsource_type', function ($join) {
                $join->on('users.id', '=', 'outsource_type.user_id');
            });

            $usersPreview->addSelect('outsource_type.outsource_type');
        }
        if ($this->request->isAssistanceType == 1) {
            $assistanceSubquery = DB::table('employment_types as et');
            $assistanceSubquery->join('users', 'et.id', '=', 'users.employment_type_id');
            $assistanceSubquery->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as assistance_type"));
            $assistanceSubquery->where('et.type', 2);
            $assistanceSubquery->whereIn('users.id', $userId);
            $assistanceSubquery->groupBy('users.id');

            $usersPreview->leftJoinSub($assistanceSubquery, 'assistance_type', function ($join) {
                $join->on('users.id', '=', 'assistance_type.user_id');
            });

            $usersPreview->addSelect('assistance_type.assistance_type');
        }
        if ($this->request->isOfficeEmail == 1) {
            $usersPreview->addSelect('users.office_email');
        }
        if ($this->request->isKarisu == 1) {
            $usersPreview->addSelect('users.karisu_number');
        }
        if ($this->request->isEmergencyContact == 1) {
            $usersPreview->addSelect('users.emergency_contact');
        }
        if ($this->request->isPensionCap == 1) {
            $usersPreview->addSelect('echelons.retirement_age as pension_cap');
        }
        if ($this->request->isWorkDuration == 1) {
            $usersPreview->addSelect(DB::raw("
            CASE
                WHEN users.years_of_service_total > 0 OR users.month_of_service_total > 0
                THEN CONCAT(
                    IF(users.years_of_service_total > 0, users.years_of_service_total, 0), ' Tahun, ',
                    IF(users.month_of_service_total > 0, users.month_of_service_total, 0), ' Bulan'
                )
                ELSE '-'
            END as work_duration
        "));
        }
        $usersPreview->whereIn('users.id', $userId);
        $usersPreview->groupBy('users.id');

        $usersPreview = $usersPreview->paginate($this->request->limit ?? 10);
        if ($this->request->isFullPosition == 1) {
            $usersPreview->getCollection()->transform(function ($value) {
                if (!is_null($value->position_id)) {
                    // Your code here
                    $sql =
                        "WITH RECURSIVE hierarchy AS (
                        -- Anchor member: Select the initial child row
                        SELECT
                            id,
                            name,
                            parent_id
                        FROM
                            positions
                        WHERE
                            id = " . $value->position_id . " -- Replace ? with the specific child employee_id

                        UNION DISTINCT

                        -- Recursive member: Select the parent row
                        SELECT
                            p.id,
                            p.name,
                            p.parent_id
                        FROM
                            positions p
                        INNER JOIN
                            hierarchy h ON p.id = h.parent_id
                        WHERE
                            p.entity = 1
                    )
                    SELECT
                        *
                    FROM
                        hierarchy WHERE id != " . $value->position_id . " AND parent_id IS NOT NULL;";

                    $position = DB::select($sql);
                    $names = array_column($position, 'name');

                    // Remove the text "Kepala" from each string in the array
                    foreach ($names as $index => $name) {
                        $names[$index] = str_replace("Kepala ", "", $name);
                    }
                    $parents = implode(', ', $names);
                    if (!empty($parents)) {
                        $value->full_position = $value->full_position . ', ' . $parents;
                    }
                }
                unset($value->position_id);

                return $value;
            });
        }

        $message = ($usersPreview->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';
        return $this->paginateResponse(200, $message, $usersPreview);
    }

    private function applyLatestEducationConstraint($query, string $educationAlias): void
    {
        $query->whereNotExists(function ($latestEducation) use ($educationAlias) {
            $latestEducation->select(DB::raw(1))
                ->from('user_educations as latest_education')
                ->whereColumn('latest_education.user_id', $educationAlias . '.user_id')
                ->where(function ($query) use ($educationAlias) {
                    $query->whereRaw(
                        "COALESCE(latest_education.level, 0) > COALESCE({$educationAlias}.level, 0)"
                    )->orWhere(function ($query) use ($educationAlias) {
                        $query->whereRaw(
                            "COALESCE(latest_education.level, 0) = COALESCE({$educationAlias}.level, 0)"
                        )->whereRaw(
                            "COALESCE(latest_education.year_of_graduation, 0) > COALESCE({$educationAlias}.year_of_graduation, 0)"
                        );
                    })->orWhere(function ($query) use ($educationAlias) {
                        $query->whereRaw(
                            "COALESCE(latest_education.level, 0) = COALESCE({$educationAlias}.level, 0)"
                        )->whereRaw(
                            "COALESCE(latest_education.year_of_graduation, 0) = COALESCE({$educationAlias}.year_of_graduation, 0)"
                        )->whereColumn('latest_education.id', '>', $educationAlias . '.id');
                    });
                });
        });
    }

    private function getUserViewPermissions()
    {
        $userRoleID = Auth::user()->role_id;
        $permissions = DB::table('permissions as p');
        $permissions->join('role_permissions as rp', 'p.id', 'rp.permission_id');
        $permissions->join('roles as r', 'rp.role_id', 'r.id');
        $permissions->where('rp.role_id', $userRoleID);
        $permissions->select('p.id', 'p.name', 'rp.create', 'rp.read', 'rp.update', 'rp.delete');
        $permissions = $permissions->get();

        $userViewPermissions = [];
        if (!$permissions->isEmpty()) {
            foreach ($permissions as $key => $value) {
                $userViewPermissions[$value->id] = $value->read;
            }
        }

        return $userViewPermissions;
    }
}
