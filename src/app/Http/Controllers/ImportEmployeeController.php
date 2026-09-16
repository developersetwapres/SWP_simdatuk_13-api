<?php

namespace App\Http\Controllers;

use App\Imports\EmployeesImport;
use App\Models\User;
use App\Support\ImportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ImportEmployeeController extends Controller
{
    protected $type;

    protected $skippedRow = [];

    /**
     * BEGIN of maps the column names to their respective indexes
     */

    /**
     * Maps the column names to their respective indexes in the `Data Pegawai` Sheet.
     * ASN
     */
    protected $personalInfoPos = [
        'name' => 0,
        'title_prefix' => 1, // Nama Gelar Depan
        'title_suffix' => 2, // Nama Gelar Belakang
        'employee_id_number' => 3, // NIP
        'employee_registration_number' => 4, // NRP
        'place_of_birth' => 5,
        'date_of_birth' => 6,
        'religion' => 7,
        'gender' => 8,
        'marital_status' => 9,
        'marriage_date' => 10, // Tanggal Perkawinan
        'marriage_description' => 11, // Keterangan Perkawinan
        'employment_type' => 12, // ASN = Jenis Pegawai / NON ASN = Jenis Perbantuan
        'cpns_effective_date' => 13, // TMT CPNS
        'pns_effective_date' => 14, // TMT PNS
        'position' => 15, // Jabatan
        'position_effective_date' => 16, // TMT Menjabat
        'grade' => 17, // Golongan
        'grade_effective_date' => 18, // TMT Golongan
        'echelon' => 19, // Eselon
        'echelon_effective_date' => 20, // TMT Eselon
        'institution' => 21, // Instansi Induk
        'education_level' => 22, // Tingkat Pendidikan Akhir
        'education_name' => 23, // Nama Sekolah/Universitas
        'education_year' => 24, // Tahun Lulus
        'employee_id_card_number' => 25, // No Karpeg
        'karisu_number' => 26,
        'years_of_service_total' => 27,
        'month_of_service_total' => 28,
        'years_of_service_rank' => 29,
        'month_of_service_rank' => 30,
        'id_tax' => 31, // NPWP
        'employment_status' => 32, // Status Pegawai
        'family_registration_number' => 33, // No KK
        'id_number' => 34, // NIK
        'residence' => 35, // Komplek
        'residence_description' => 36, // Alamat Tempat Tinggal Saat Ini
        'current_address' => 37, // Alamat Sesuai KTP
        'home_phone_number' => 38, // No Telepon Rumah
        'mobile_phone' => 39, // No HP
        'office_address' => 40, // Alamat Kantor
        'office_phone_number' => 41, // No Telepon Kantor
        'email' => 42,
        'office_email' => 43,
        'emergency_contact' => 44, // Kontak Darurat
    ];

    /**
     * Maps the column names to their respective indexes in the `Data Pegawai` Sheet.
     * NON ASN
     */
    protected $nonAsnPersonalInfoPos = [
        'name' => 0,
        'title_prefix' => 1, // Nama Gelar Depan
        'title_suffix' => 2, // Nama Gelar Belakang
        'employee_id_number' => 3, // NIP
        'employee_registration_number' => 4, // NRP
        'place_of_birth' => 5,
        'date_of_birth' => 6,
        'religion' => 7,
        'gender' => 8,
        'marital_status' => 9,
        'employment_type' => 10, // Jenis Perbantuan
        'cpns_effective_date' => 11, // Tanggal Mulai Bekerja
        'position' => 12, // Jabatan
        'position_effective_date' => 13, // TMT Menjabat
        'grade' => 14, // Golongan
        'grade_effective_date' => 15, // TMT Golongan
        'echelon' => 16, // Eselon
        'echelon_effective_date' => 17, // TMT Eselon
        'institution' => 18, // Instansi Induk
        'education_level' => 19, // Tingkat Pendidikan Akhir
        'education_name' => 20, // Nama Sekolah/Universitas
        'education_year' => 21, // Tahun Lulus
        'id_tax' => 22, // NPWP
        'employment_status' => 23, // Status Pegawai
        'family_registration_number' => 24, // No KK
        'id_number' => 25, // NIK
        'residence_description' => 26, // Alamat Tempat Tinggal Saat Ini
        'current_address' => 27, // Alamat Sesuai KTP
        'home_phone_number' => 28, // No Telepon Rumah
        'mobile_phone' => 29, // No HP
        'office_address' => 30, // Alamat Kantor
        'office_phone_number' => 31, // No Telepon Kantor
        'email' => 32,
        'office_email' => 33,
        'emergency_contact' => 34, // Kontak Darurat
    ];

    /**
     * Maps the column names to their respective indexes in the `Data Pegawai` Sheet.
     * OUTSOURCE
     */
    protected $outsourcePersonalInfoPos = [
        'name' => 0,
        'employee_id_number' => 1, // NIP
        'place_of_birth' => 2,
        'date_of_birth' => 3,
        'religion' => 4,
        'gender' => 5,
        'marital_status' => 6,
        'employment_type' => 7, // Jenis Outsourcing
        'cpns_effective_date' => 8, // Tanggal Mulai Bekerja
        'position' => 9, // Jabatan
        'position_effective_date' => 10, // TMT Menjabat
        'education_level' => 11, // Tingkat Pendidikan Akhir
        'education_name' => 12, // Nama Sekolah/Universitas
        'education_year' => 13, // Tahun Lulus
        'id_tax' => 14, // NPWP
        'employment_status' => 15, // Status Pegawai
        'family_registration_number' => 16, // No KK
        'id_number' => 17, // NIK
        'residence_description' => 18, // Alamat Tempat Tinggal Saat Ini
        'current_address' => 19, // Alamat sesuai KTP
        'home_phone_number' => 20, // No Telepon Rumah
        'mobile_phone' => 21, // No HP
        'office_address' => 22, // Alamat Kantor
        'office_phone_number' => 23, // No Telepon Kantor
        'email' => 24,
        'description' => 25, // Keterangan
        'emergency_contact' => 26, // Kontak Darurat
    ];

    /**
     * Maps the column names to their respective indexes in the `Pendidikan` Sheet.
     */
    protected $educationInfoPos = [
        'nik' => 0,
        'nama' => 1,
        'level' => 2,
        'name' => 3,
        'study_area' => 4,
        'accreditation' => 5,
        'faculty' => 6,
        'major' => 7,
        'year_of_graduation' => 8,
        'description' => 9,
    ];

    /**
     *
     */
    protected $positionInfoPos = [
        'name' => 0, // Nama Riwayat Jabatan
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'nik' => 3,
        'nama' => 4,
        'position' => 5, // Jabatan
        'group' => 6, // Rumpun
        'echelon' => 7, // Jenjang Jabatan
        'position_status' => 8, // Keterangan Jabatan
        'effective_date' => 9, // TMT Jabatan
        'decree' => 10, // SK Jabatan
        'type_of_decree' => 11, //Jenis SK Jabatan
        'decree_number' => 12, // No SK Jabatan
        'decree_date' => 13, // Tanggal SK Jabatan
        'termination_date' => 14, // TMT Selesai
        'termination_decree' => 15, // SK Selesai
        'type_of_termination_decree' => 16, // Jenis SK Selesai
        'termination_decree_number' => 17, // No SK Selesai
        'termination_decree_date' => 18, // Tanggal SK Selesai
    ];

    /**
     *
     */
    protected $gradeInfoPos = [
        'name' => 0, // Nama Riwayat Golongan
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'nik' => 3,
        'nama' => 4,
        'grade' => 5, // Golongan
        'effective_date' => 6, // TMT Golongan
        'decree_name' => 7, // SK Golongan
        'type_of_decree' => 8, // Jenis SK Golongan
        'decree_number' => 9, // No. SK Golongan
        'decree_date' => 10, // Tanggal SK Golongan
        'description' => 11, // Keterangan Golongan
        'status' => 12, // Status Golongan
    ];

    /**
     *
     */
    protected $structuralTrainingInfoPos = [
        'name' => 0, // Nama Diklat
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'reference_number' => 3, // No Surat Perintah
        'level' => 4, // Jenjang
        'start_date' => 5, // Tanggal Pelaksanaan
        'end_date' => 6, // Tanggal Pelaksanaan Selesai
        'duration' => 7, // Jam Pelajaran
        'organizer' => 8, // Penyelenggara
        'description' => 9, // Keterangan
        'nik' => 10,
        'nama' => 11,
    ];

    /**
     *
     */
    protected $functionalTrainingInfoPos = [
        'name' => 0, // Nama Diklat
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'reference_number' => 3, // No Surat Perintah
        'level' => 4, // Jenjang
        'start_date' => 5, // Tanggal Pelaksanaan
        'end_date' => 6, // Tanggal Pelaksanaan Selesai
        'duration' => 7, // Jam Pelajaran
        'organizer' => 8, // Penyelenggara
        'nik' => 9,
        'nama' => 10,
    ];

    /**
     *
     */
    protected $technicalTrainingInfoPos = [
        'name' => 0, // Nama Diklat
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'reference_number' => 3, // No Surat Perintah
        'start_date' => 4, // Tanggal Pelaksanaan
        'end_date' => 5, // Tanggal Pelaksanaan Selesai
        'duration' => 6, // Jam Pelajaran
        'organizer' => 7, // Penyelenggara
        'group' => 8, // Rumpun
        'nik' => 9,
        'nama' => 10
    ];

    /**
     *
     */
    protected $recognitionInfoPos = [
        'recognition' => 0, // Nama Penghargaan
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'description' => 3, // Keterangan Penghargaan
        'type_of_decree' => 4, // Jenis SK
        'decree_date' => 5, // Tanggal SK
        'decree_number' => 6, // Nomor SK Penghargaan
        'decree_year' => 7, // Tahun SK
        'awarding_institution' => 8, // Instansi Pemberi Penghargaan
        'nik' => 9,
        'nama' => 10,
    ];

    /**
     *
     */
    protected $targetInfoPos = [
        'name' => 0, // Nama Riwayat SKP
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'appraisal_period' => 3, // Periode Penilaian
        'year' => 4, // Tahun
        'nik' => 5,
        'nama' => 6,
        'work_behavior_rating' => 7, // Rating Perilaku Kerja
        'employee_performance_predicate' => 8, // Predikat Kinerja Pegawai
        'organizational_performance_achievement' => 9, // Capaian Kinerja Organisasi
    ];

    /**
     *
     */
    protected $performanceInfoPos = [
        'name' => 0, // Nama Riwayat PPK
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'performance_period' => 3, // Periode PPK
        'nik' => 4,
        'nama' => 5,
        'work_performance_score' => 6, // Nilai Prestasi Kerja
        'description' => 7, // Keterangan
    ];

    /**
     *
     */
    protected $disciplinaryInfoPos = [
        'name' => 0, // Nama Riwayat Hukuman Disiplin
        'period_month' => 1, // Bulan Periode Input Riwayat
        'period_year' => 2, // Tahun Periode Input Riwayat
        'nik' => 3,
        'nama' => 4,
        'grade' => 5, // Golongan
        'position' => 6, // Jabatan
        'disciplinary' => 7, // Jenis Hukuman
        'decree_number' => 8, // No. SK Hukuman Disiplin
        'date_of_decree' => 9, // Tanggal SK Hukuman Disiplin
        'start_date' => 10, // Tanggal Awal Hukuman Disiplin
        'end_date' => 11, // Tanggal Akhir Hukuman Disiplin
        'authorizing_officer' => 12, // Pejabat Berwenang
        'name_of_authorizing_officer' => 13, // Nama Pejabat Berwenang
        'description' => 14, // Keterangan

    ];

    /**
     * Maps the column names to their respective indexes in the `Keluarga` Sheet.
     */
    protected $familyInfoPos = [
        'nik' => 0,
        'nama' => 1,
        'card_number' => 2, // No. Kartu Keluarga
        'name' => 3, // Nama Anggota Keluarga
        'id_number' => 4, // No. NIK
        'gender' => 5,
        'religion' => 6,
        'place_of_birth' => 7, // Tempat Lahir
        'date_of_birth' => 8, // Tanggal Lahir
        'name_of_father' => 9, // Nama Bapak
        'name_of_mother' => 10, // Nama Ibu
        'relationship_status' => 11, // Hubungan Keluarga
        'education' => 12, // Pendidikan
        'occupation' => 13, // Jenis Pekerjaan
        'occupation_description' => 14, // Keterangan Pekerjaan
        'marital_status' => 15, // Status Perkawinan
        'marriage_other_notes' => 16, // Keterangan Lainnya
        'mobile_phone' => 17, //No. HP
        'sequence_number' => 18, // Urut Keluarga
    ];

    /**
     * Maps the column names to their respective indexes in the `Cuti` Sheet.
     */
    protected $leaveInfoPos = [
        'nik' => 0,
        'nama' => 1,
        'start_date' => 2, // Tanggal Awal Cuti
        'end_date' => 3, // Tanggal Akhir Cuti
        'type' => 4, // Jenis Cuti
        'number' => 5, // No Cuti
        'description' => 6, // Keterangan
    ];

    /**
     * Maps the column names to their respective indexes in the `Catatan` Sheet.
     */
    protected $noteInfoPos = [
        'nik' => 0,
        'nama' => 1,
        'description' => 2,
    ];

    /**
     * Maps the column names to their respective indexes in the `Hasil Assessment` Sheet.
     */
    protected $assessmentInfoPos = [
        'nik' => 0,
        'nama' => 1,
        'event_date' => 2,
        'point' => 3,
        'organizer' => 4,
    ];

    /**
     * Maps the column names to their respective indexes in the `Hasil Uji Kompetensi` Sheet.
     */
    protected $competencyInfoPos = [
        'nik' => 0,
        'nama' => 1,
        'event_date' => 2,
        'point' => 3,
        'organizer' => 4,
    ];

    /**
     * Maps the column names to their respective indexes in the `Hasil Talent Pool` Sheet.
     */
    protected $talentInfoPos = [
        'nik' => 0,
        'nama' => 1,
        'event_date' => 2,
        'point' => 3,
        'organizer' => 4,
    ];
    /**
     * END of maps the column names to their respective indexes
     */

    /**
     * BEGIN MASTER DATA
     * All MASTER DATA below must be :
     * lowercase
     * number
     * alphabets
     * allowed symbols ()-/,.
     *
     * Other than that replace with empty
     */
    protected $employmentTypes;
    protected $positions;
    protected $grades;
    protected $echelons;
    protected $institutions;
    protected $residences;
    protected $groups;
    protected $decrees;
    protected $recognitions;
    protected $disciplinaries;
    protected $levels;

    // Array mapping gender to their respective numeric codes
    protected $month = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
    ];

    // Array mapping gender to their respective numeric codes
    protected $gender = [
        'perempuan' => 0,
        'laki-laki' => 1,
    ];

    // Array mapping religions to their respective numeric codes
    protected $religions = [
        'islam' => 1,
        'kristen' => 2,
        'katolik' => 3,
        'hindu' => 4,
        'budha' => 5,
        'konghucu' => 6,
    ];

    // Array mapping marital status to their respective numeric codes
    protected $maritalStatus = [
        'belummenikah' => 1,
        'menikah' => 2,
        'cerai' => 3,
        'janda' => 4,
        'duda' => 5,
    ];

    // Array mapping education level to their respective numeric codes
    protected $educationLevel = [
        'sd/sederajat' => 1,
        'sltp/sederajat' => 2,
        'slta/sederajat' => 3,
        'diplomai/ii' => 4,
        'akademik/d3/s.muda' => 5,
        'diplomaiv/stratai' => 6,
        'strataii' => 7,
        'strataiii' => 8,
    ];

    protected $educationStudyArea = [
        'dalamnegeri' => 1,
        'luarnegeri' => 2,
    ];

    protected $positionEchelon = [
        'eseloni' => 1,
        'eselonii' => 2,
        'eseloniii' => 3,
        'eseloniv' => 4,
        'pelaksana' => 5,
        'fungsional' => 6,
        'staf' => 7,
    ];

    protected $positionStatus = [
        'promosi' => 1,
        'mutasi' => 2,
        'inpassing' => 3,
        'konversi' => 4,
    ];

    // Array mapping employment status to their respective numeric codes
    protected $employmentStatus = [
        'aktif' => 1,
        'pensiun' => 2,
        'berhenti' => 3,
        'meninggal' => 4,
        'alihstatus' => 5,
        'aktifperbantuansetneg' => 6,
        'cltn' => 7,
        'tbln' => 8,
        'nonaktif' => 9,
    ];

    protected $workBehaviourRating = [
        'diatasekspektasi' => 1,
        'sesuaiekspektasi' => 2,
        'dibawahekspektasi' => 3,
    ];

    protected $employeePerformancePredicate = [
        'sangatbaik' => 1,
        'baik' => 2,
        'butuhperbaikan' => 3,
        'kurang' => 4,
        'sangatkurang' => 5,
    ];

    protected $organizationalPerformanceAchievement = [
        'sangatbaik' => 1,
        'baik' => 2,
        'cukup' => 3,
    ];

    protected $performanceDescription = [
        'kurang' => 1,
        'sedang' => 2,
        'cukup' => 3,
        'baik' => 4,
        'sangatbaik' => 5,
    ];

    // Array mapping family relationship to their respective numeric codes
    protected $familyRelationship = [
        'kepalakeluarga' => 1,
        'suami' => 2,
        'istri' => 3,
        'anak' => 4,
        'menantu' => 5,
        'cucu' => 6,
        'orangtua' => 7,
        'mertua' => 8,
        'famililainnya' => 9,
        'pembantu' => 10,
        'lainnya' => 11,
    ];

    // Array mapping education to their respective numeric codes (for family info)
    protected $familyEducation = [
        'tidak/belumsekolah' => 1,
        'belumtamatsd/sederajat' => 2,
        'tamatsd/sederajat' => 3,
        'sltp/sederajat' => 4,
        'slta/sederajat' => 5,
        'diplomai/ii' => 6,
        'akademi/diplomaiii/sarjanamuda' => 7,
        'diplomaiv/stratai' => 8,
        'strataii' => 9,
        'strataiii' => 10,
    ];

    // Array mapping marital status to their respective numeric codes (for family info)
    protected $familyMaritalStatus = [
        'belummenikah' => 1,
        'menikah' => 2,
        'ceraihidup' => 3,
        'ceraimati' => 4,
    ];

    // Array mapping leave type to their respective numeric codes
    protected $leaveType = [
        'cutidiluartanggungannegara' => 1,
        'cutisakit' => 2,
        'cutibesar' => 3,
        'cutibersalin' => 4,
        'cutibelajarluarnegeri' => 5,
        'cutitahunanluarnegeri' => 6,
    ];

    // Array mapping assessment point to their respective numeric codes
    protected $assessmentPoint = [
        'kurangmemenuhisyarat' => 1,
        'masihmemenuhisyarat' => 2,
        'memenuhisyarat' => 3,
    ];

    // Array mapping competency point to their respective numeric codes
    protected $competencyPoint = [
        'lulus' => 1,
        'tidaklulus' => 2,
    ];

    // Array mapping talent point to their respective numeric codes
    protected $talentPoint = [
        'kotak1' => 1,
        'kotak2' => 2,
        'kotak3' => 3,
        'kotak4' => 4,
        'kotak5' => 5,
        'kotak6' => 6,
        'kotak7' => 7,
        'kotak8' => 8,
        'kotak9' => 9,
    ];
    /**
     * END OF MASTER DATA
     */

    /**
     * Import Employee with .XLSX
     *
     * Import bulk employee with .XLSX file. This endpoint have type with 1=ASN, 2=Non ASN, 3=Outsource
     * @group Employee
     * @authenticated
     * @response 400 {"code": 400,"message": "Import pegawai gagal","data": {"log_id": 6}}
     * @response 200 {"code": 200,"message": "Import pegawai berhasil.","data": null}
     */
    public function import(Request $request)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        // Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
            'type' => 'required|in:1,2,3',
        ], [
            'type' => [
                'in' => 'Tipe Pegawai tidak dikenali.',
            ],
        ]);

        $this->type = $request->type;

        $employeesImport = new EmployeesImport();
        $extension = $request->file('file')->extension();
        // Import sheets
        if ($extension == 'csv') {
            Excel::import($employeesImport, $request->file('file')->path(), null, \Maatwebsite\Excel\Excel::CSV);
        } else {
            Excel::import($employeesImport, $request->file('file')->path(), null, \Maatwebsite\Excel\Excel::XLSX);
        }

        // Extract data from imports
        $employeesData = $employeesImport->data;

        if ($request->type == 3) { // Outsource
            $personalInfo = $this->removeNullRows($employeesData[0]) ?? []; // Sheet 1 : Data Pegawai
            $educationInfo = $this->removeNullRows($employeesData[1]) ?? []; // Sheet 2 : Riwayat Pendidikan
            $positionInfo = $this->removeNullRows($employeesData[2]) ?? []; // Sheet 3 : Riwayat Jabatan
            $technicalTrainingInfo = $this->removeNullRows($employeesData[3]) ?? []; // Sheet 4 : Riwayat Pelatihan Teknis
            $familyInfo = $this->removeNullRows($employeesData[4]) ?? []; // Sheet 5 : Riwayat Keluarga
            $noteInfo = $this->removeNullRows($employeesData[5]) ?? []; // Sheet 6 : Riwayat Catatan
        } else if ($request->type == 2) { // NON ASN
            $personalInfo = $this->removeNullRows($employeesData[0]) ?? []; // Sheet 1 : Data Pegawai
            $positionInfo = $this->removeNullRows($employeesData[1]) ?? []; // Sheet 3 : Riwayat Jabatan

        } else { // ASN
            $personalInfo = $this->removeNullRows($employeesData[0]) ?? []; // Sheet 1 : Data Pegawai
            $educationInfo = $this->removeNullRows($employeesData[1]) ?? []; // Sheet 2 : Riwayat Pendidikan
            $positionInfo = $this->removeNullRows($employeesData[2]) ?? []; // Sheet 3 : Riwayat Jabatan
            $gradeInfo = $this->removeNullRows($employeesData[3]) ?? []; // Sheet 4 : Riwayat Golongan
            $structuralTrainingInfo = $this->removeNullRows($employeesData[4]) ?? []; // Sheet 5 : Riwayat Pelatihan Struktural
            $functionalTrainingInfo = $this->removeNullRows($employeesData[5]) ?? []; // Sheet 6 : Riwayat Pelatihan Fungsional
            $technicalTrainingInfo = $this->removeNullRows($employeesData[6]) ?? []; // Sheet 7 : Riwayat Pelatihan Teknis
            $recognitionInfo = $this->removeNullRows($employeesData[7]) ?? []; // Sheet 8 : Riwayat Penghaargaan
            $targetInfo = $this->removeNullRows($employeesData[8]) ?? []; // Sheet 9 : Riwayat SKP
            $performanceInfo = $this->removeNullRows($employeesData[9]) ?? []; // Sheet 10 : Penilaian Prestasi Kerja
            $disciplinaryInfo = $this->removeNullRows($employeesData[10]) ?? []; // Sheet 11 : Riwayat Hukuman Disiplin
            $familyInfo = $this->removeNullRows($employeesData[11]) ?? []; // Sheet 12 : Riwayat Keluarga
            $leaveInfo = $this->removeNullRows($employeesData[12]) ?? []; // Sheet 13 : Riwayat Cuti
            $noteInfo = $this->removeNullRows($employeesData[13]) ?? []; // Sheet 14 : Riwayat Catatan
            $assessmentInfo = $this->removeNullRows($employeesData[14]) ?? []; // Sheet 15 : Hasil Assessment
            $competencyInfo = $this->removeNullRows($employeesData[15]) ?? []; // Sheet 16 : Hasil Uji Kompetensi
            $talentInfo = $this->removeNullRows($employeesData[16]) ?? []; // Sheet 17 : Hasil Talent Pool
        }

        if ($this->type == 2) {
            $lastEl = end($this->nonAsnPersonalInfoPos);
        } else if ($this->type == 3) {
            $lastEl = end($this->outsourcePersonalInfoPos);
        } else {
            $lastEl = end($this->personalInfoPos);
        }

        if (count($personalInfo) == 0 || !isset($personalInfo[0][$lastEl])) { // Sheet Tidak sesuai

            if ($this->type == 1) {
                $logType = 'add-bulk-asn';
                $logDescription = 'Tambah Massal Data Pegawai ASN';
            } else if ($this->type == 2) {
                $logType = 'add-bulk-non-asn';
                $logDescription = 'Tambah Massal Data Pegawai NON ASN';
            } else if ($this->type == 3) {
                $logType = 'add-bulk-outsource';
                $logDescription = 'Tambah Massal Data Pegawai OUTSOURCE';
            }
            $insertedId = DB::table('activity_logs')->insertGetId([
                'user_id' => $request->user()->id,
                'type' => $logType,
                'description' => $logDescription,
                'status' => 'failed',
                'log' => json_encode(['Data Pegawai' => ['Sheet Data Pegawai tidak sesuai atau kosong']]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return $this->response(400, 'Import pegawai gagal', ['log_id' => $insertedId]);
        }

        // Map Data Master
        $this->employmentTypes = $this->getNameIdMapping('employment_types', ['status' => 1]);
        $positions = DB::table('positions')->select("id", "parent_id", DB::raw("LOWER(REPLACE(name, ' ', '')) as name"))->orderBy('parent_id')->get()->toArray();
        $this->positions = $this->buildHierarchy($positions);
        $this->grades = $this->getNameIdMapping('grades');
        $this->echelons = $this->getNameIdMapping('echelons');
        $this->institutions = $this->getNameIdMapping('institutions');
        $this->residences = $this->getNameIdMapping('residences');
        $this->groups = $this->getNameIdMapping('groups', ['type' => 2]);
        $this->decrees = $this->getNameIdMapping('decrees');
        $this->recognitions = $this->getNameIdMapping('recognitions');
        $this->disciplinaries = $this->getNameIdMapping('disciplinaries');
        $this->levels = $this->getNameIdMapping('training_levels', [], 'level_name');

        if ($request->type == 3) { // Outsource
            // Process personal info
            $personalInfo = $this->personalInfo($personalInfo);

            // Process education info
            if (count($educationInfo) > 0 && isset($educationInfo[0][end($this->educationInfoPos)])) {
                $personalInfo = $this->educationInfo($educationInfo, $personalInfo);
            }

            // Process position info
            if (count($positionInfo) > 0 && isset($positionInfo[0][end($this->positionInfoPos)])) {
                $personalInfo = $this->positionInfo($positionInfo, $personalInfo);
            }

            // Process technical training info
            if (count($technicalTrainingInfo) > 0 && isset($technicalTrainingInfo[0][end($this->technicalTrainingInfoPos)])) {
                $personalInfo = $this->trainingInfo($technicalTrainingInfo, $personalInfo, 3);
            }

            // Process family info
            if (count($familyInfo) > 0 && isset($familyInfo[0][end($this->familyInfoPos)])) {
                $personalInfo = $this->familyInfo($familyInfo, $personalInfo);
            }

            // Process note info
            if (count($noteInfo) > 0 && isset($noteInfo[0][end($this->noteInfoPos)])) {
                $personalInfo = $this->noteInfo($noteInfo, $personalInfo, $request->user()->id);
            }
        } else if ($request->type == 2) { // NON ASN
            // Process personal info
            $personalInfo = $this->personalInfo($personalInfo);

            // Process position info
            if (count($positionInfo) > 0 && isset($positionInfo[0][end($this->positionInfoPos)])) {
                $personalInfo = $this->positionInfo($positionInfo, $personalInfo);
            }
        } else { // ASN
            // Process personal info
            $personalInfo = $this->personalInfo($personalInfo);

            // Process education info
            if (count($educationInfo) > 0 && isset($educationInfo[0][end($this->educationInfoPos)])) {
                $personalInfo = $this->educationInfo($educationInfo, $personalInfo);
            }

            // Process position info
            if (count($positionInfo) > 0 && isset($positionInfo[0][end($this->positionInfoPos)])) {
                $personalInfo = $this->positionInfo($positionInfo, $personalInfo);
            }

            // Process grade info
            if (count($positionInfo) > 0 && isset($positionInfo[0][end($this->positionInfoPos)])) {
                $personalInfo = $this->gradeInfo($gradeInfo, $personalInfo);
            }

            // Process structural training info
            if (count($structuralTrainingInfo) > 0 && isset($structuralTrainingInfo[0][end($this->structuralTrainingInfoPos)])) {
                $personalInfo = $this->trainingInfo($structuralTrainingInfo, $personalInfo, 1);
            }

            // Process functional training info
            if (count($functionalTrainingInfo) > 0 && isset($functionalTrainingInfo[0][end($this->functionalTrainingInfoPos)])) {
                $personalInfo = $this->trainingInfo($functionalTrainingInfo, $personalInfo, 2);
            }

            // Process technical training info
            if (count($technicalTrainingInfo) > 0 && isset($technicalTrainingInfo[0][end($this->technicalTrainingInfoPos)])) {
                $personalInfo = $this->trainingInfo($technicalTrainingInfo, $personalInfo, 3);
            }

            // Process recognition info
            if (count($recognitionInfo) > 0 && isset($recognitionInfo[0][end($this->recognitionInfoPos)])) {
                $personalInfo = $this->recognitionInfo($recognitionInfo, $personalInfo);
            }

            // Process target info
            if (count($targetInfo) > 0 && isset($targetInfo[0][end($this->targetInfoPos)])) {
                $personalInfo = $this->targetInfo($targetInfo, $personalInfo);
            }

            // Process performance info
            if (count($performanceInfo) > 0 && isset($performanceInfo[0][end($this->performanceInfoPos)])) {
                $personalInfo = $this->performanceInfo($performanceInfo, $personalInfo);
            }

            // Process disciplinary info
            if (count($disciplinaryInfo) > 0 && isset($disciplinaryInfo[0][end($this->disciplinaryInfoPos)])) {
                $personalInfo = $this->disciplinaryInfo($disciplinaryInfo, $personalInfo);
            }

            // Process family info
            if (count($familyInfo) > 0 && isset($familyInfo[0][end($this->familyInfoPos)])) {
                $personalInfo = $this->familyInfo($familyInfo, $personalInfo);
            }

            // Process leave info
            if (count($leaveInfo) > 0 && isset($leaveInfo[0][end($this->leaveInfoPos)])) {
                $personalInfo = $this->leaveInfo($leaveInfo, $personalInfo);
            }

            // Process note info
            if (count($noteInfo) > 0 && isset($noteInfo[0][end($this->noteInfoPos)])) {
                $personalInfo = $this->noteInfo($noteInfo, $personalInfo, $request->user()->id);
            }

            // Process assessment info
            if (count($assessmentInfo) > 0 && isset($assessmentInfo[0][end($this->assessmentInfoPos)])) {
                $personalInfo = $this->assessmentInfo($assessmentInfo, $personalInfo);
            }

            // Process competency info
            if (count($competencyInfo) > 0 && isset($competencyInfo[0][end($this->competencyInfoPos)])) {
                $personalInfo = $this->competencyInfo($competencyInfo, $personalInfo);
            }

            // Process talent info
            if (count($talentInfo) > 0 && isset($talentInfo[0][end($this->talentInfoPos)])) {
                $personalInfo = $this->talentInfo($talentInfo, $personalInfo);
            }
        }

        return $this->save($personalInfo, $request->user()->id);
    }

    private function save($personalInfo, $logUserID)
    {
        $logType = '';
        $logDescription = '';
        if ($this->type == 1) {
            $logType = 'add-bulk-asn';
            $logDescription = 'Tambah Massal Data Pegawai ASN';
        } else if ($this->type == 2) {
            $logType = 'add-bulk-non-asn';
            $logDescription = 'Tambah Massal Data Pegawai NON ASN';
        } else if ($this->type == 3) {
            $logType = 'add-bulk-outsource';
            $logDescription = 'Tambah Massal Data Pegawai OUTSOURCE';
        }

        if (sizeOf($this->skippedRow) > 0) {
            $errorLog = [];
            foreach ($this->skippedRow as $key => $value) {
                $errorLog[$value['sheet']][] = 'Baris ' . $value['row'] . ' ' . $value['reason'];
            }

            $insertedId = DB::table('activity_logs')->insertGetId([
                'user_id' => $logUserID,
                'type' => $logType,
                'description' => $logDescription,
                'status' => 'failed',
                'log' => json_encode($errorLog),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            return $this->response(400, 'Import pegawai gagal', ['log_id' => $insertedId]);
        }

        foreach ($personalInfo as $nik => $data) {
            // Skip. If personal info is empty
            if (!isset($personalInfo[$nik]['personal_info'])) {
                continue;
            }

            try {
                DB::beginTransaction();

                // Save user
                $data['personal_info']['created_at'] = date('Y-m-d');
                $userID = DB::table('users')->insertGetId($data['personal_info']);

                $additionalInfo = [
                    'user_id' => $userID,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                if ($this->type == 3) { // OUTSOURCE
                    // Save Education
                    if (isset($data['education'])) {
                        $data['education'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['education']);
                        DB::table('user_educations')->insert($data['education']);
                    }

                    // Save Position
                    if (isset($data['position'])) {
                        $data['position'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['position']);
                        $data['position'] = $this->historySave($data['position'], 'position_histories', 3, 'position_history_id');
                        DB::table('position_history_users')->insert($data['position']);
                    }

                    // Save Training
                    if (isset($data['training'])) {
                        $data['training'] = $this->historySave($data['training'], 'training_histories', 0, 'training_history_id');
                        $data['training'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['training']);
                        DB::table('training_history_users')->insert($data['training']);
                    }

                    // Save Family
                    if (isset($data['family'])) {
                        $data['family'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['family']);
                        DB::table('user_families')->insert($data['family']);
                    }

                    // Save Notes
                    if (isset($data['note'])) {
                        $data['note'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['note']);
                        DB::table('user_notes')->insert($data['note']);
                    }
                } else if ($this->type == 2) { // NON ASN
                    // Save Position
                    if (isset($data['position'])) {
                        $data['position'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['position']);
                        $data['position'] = $this->historySave($data['position'], 'position_histories', 3, 'position_history_id');
                        DB::table('position_history_users')->insert($data['position']);
                    }
                } else { // ASN

                    // Save Education
                    if (isset($data['education'])) {
                        $data['education'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['education']);
                        DB::table('user_educations')->insert($data['education']);
                    }

                    // Save Position
                    if (isset($data['position'])) {
                        $data['position'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['position']);
                        $data['position'] = $this->historySave($data['position'], 'position_histories', 3, 'position_history_id');
                        DB::table('position_history_users')->insert($data['position']);
                    }

                    // Save Grade
                    if (isset($data['grade'])) {
                        $data['grade'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['grade']);
                        $data['grade'] = $this->historySave($data['grade'], 'grade_histories', 3, 'grade_history_id');
                        DB::table('grade_history_users')->insert($data['grade']);
                    }

                    // Save Training
                    if (isset($data['training'])) {
                        $data['training'] = $this->historySave($data['training'], 'training_histories', 0, 'training_history_id');
                        $data['training'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['training']);
                        DB::table('training_history_users')->insert($data['training']);
                    }

                    // Save Recognition
                    if (isset($data['recognition'])) {
                        $data['recognition'] = $this->historySave($data['recognition'], 'recognition_histories', 0, 'recognition_history_id');
                        $data['recognition'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['recognition']);
                        DB::table('recognition_history_users')->insert($data['recognition']);
                    }

                    // Save Target
                    if (isset($data['target'])) {
                        $data['target'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['target']);
                        $data['target'] = $this->historySave($data['target'], 'target_histories', 5, 'target_history_id');
                        DB::table('target_history_users')->insert($data['target']);
                    }

                    // Save Performance
                    if (isset($data['performance'])) {
                        $data['performance'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['performance']);
                        $data['performance'] = $this->historySave($data['performance'], 'performance_histories', 4, 'performance_history_id');
                        DB::table('performance_history_users')->insert($data['performance']);
                    }

                    // Save Discipliary
                    if (isset($data['disciplinary'])) {
                        $data['disciplinary'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['disciplinary']);
                        $data['disciplinary'] = $this->historySave($data['disciplinary'], 'disciplinary_histories', 3, 'disciplinary_history_id');
                        DB::table('disciplinary_history_users')->insert($data['disciplinary']);
                    }

                    // Save Family
                    if (isset($data['family'])) {
                        $data['family'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['family']);
                        DB::table('user_families')->insert($data['family']);
                    }

                    // Save Leave
                    if (isset($data['leave'])) {
                        $data['leave'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['leave']);
                        DB::table('user_leaves')->insert($data['leave']);
                    }

                    // Save Notes
                    if (isset($data['note'])) {
                        $data['note'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['note']);
                        DB::table('user_notes')->insert($data['note']);
                    }

                    // Save Assessment
                    if (isset($data['assessment'])) {
                        $data['assessment'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['assessment']);
                        DB::table('user_assessments')->insert($data['assessment']);
                    }

                    // Save Competencies
                    if (isset($data['competency'])) {
                        $data['competency'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['competency']);
                        DB::table('user_competencies')->insert($data['competency']);
                    }

                    // Save Talents
                    if (isset($data['talent'])) {
                        $data['talent'] = $this->mergeValuesIntoArrayElements($additionalInfo, $data['talent']);
                        DB::table('user_talents')->insert($data['talent']);
                    }
                }

                DB::commit();
            } catch (\Throwable $th) {
                DB::rollback();

                Log::warning($th);
                return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
            }
        }

        DB::table('activity_logs')->insert([
            'user_id' => $logUserID,
            'type' => $logType,
            'description' => $logDescription,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->response(200, 'Import pegawai berhasil');
    }

    /**
     * Save history records and update relation field with history ID.
     *
     * @param array $records Array of records to process.
     * @param string $tableName Name of the table to query/insert history.
     * @param int $cutOffIndex Index to determine how many parts of the primary key to use.
     * @param string $relationFieldName Field name to update with the history ID.
     * @return array Updated records with history IDs.
     */
    private function historySave(array $records, string $tableName, int $cutOffIndex, string $relationFieldName): array
    {
        $history = [];

        foreach ($records as $recordIndex => $record) {
            // Determine primary key parts based on cut-off index
            $primaryKeyParts = ($cutOffIndex == 0) ? array_splice($record, 0, count($record)) : array_splice($record, 0, $cutOffIndex);

            $whereClause = '';
            $conditionCount = 0;
            $primaryKeyValues = [];
            $bindings = [];

            foreach ($primaryKeyParts as $field => $value) {
                // Build WHERE clause for SQL query
                if ($conditionCount > 0) {
                    $whereClause .= " AND ";
                }

                $value = str_replace(' ', '', strtolower($value));
                $primaryKeyValues[] = $value;
                $bindings[] = $value;
                $whereClause .= "LOWER(REPLACE(`" . $field . "`,' ','')) = ?";
                $conditionCount++;
            }

            // Generate a unique key for history lookup
            $historyKey = implode('-', $primaryKeyValues);
            $historyId = array_search($historyKey, $history);

            if ($historyId === false) {
                // Query the database for existing history record
                $historyRecord = DB::select("SELECT id FROM `" . $tableName . "` WHERE " . $whereClause, $bindings);
                $historyId = (count($historyRecord) > 0) ? $historyRecord[0]->id : null;

                if (is_null($historyId)) {
                    // Insert a new history record if none found
                    $primaryKeyParts['created_at'] = date('Y-m-d H:i:s');
                    $historyId = DB::table($tableName)->insertGetId($primaryKeyParts);
                }

                // Store the history ID and key for future reference
                $history[$historyId] = $historyKey;
            }

            // Update the relation field with the history ID
            $record[$relationFieldName] = $historyId;
            $records[$recordIndex] = $record;
        }

        return $records;
    }

    private function personalInfo($personalInfo)
    {
        $result = [];
        foreach ($personalInfo as $personalInfoKey => $personalInfoRow) {
            if ($personalInfoKey == 0) {
                continue;
            }
            // Skip header row

            /**
             * Check Required Field.
             * Based on rules in App\Http\Requests\Employee\CreateEmployeeRequest
             */
            if ($this->type == 3) { // OUTSOURCE
                $personalInfoPos = $this->outsourcePersonalInfoPos;
                $requiredFields = [
                    'id_number',
                    'name',
                    'employee_id_number',
                    'place_of_birth',
                    'date_of_birth',
                    'religion',
                    'gender',
                    'employment_type',
                    'education_level',
                    'employment_status',
                    'emergency_contact'
                ];
            } else if ($this->type == 2) { // NON ASN
                $personalInfoPos = $this->nonAsnPersonalInfoPos;
                $requiredFields = [
                    'id_number',
                    'name',
                    'employee_id_number',
                    'place_of_birth',
                    'date_of_birth',
                    'religion',
                    'gender',
                    'employment_type',
                    'education_level',
                    'employment_status',
                    'emergency_contact'
                ];
            } else { // ASN
                $personalInfoPos = $this->personalInfoPos;
                $requiredFields = [
                    'id_number',
                    'name',
                    'employee_id_number',
                    'place_of_birth',
                    'date_of_birth',
                    'religion',
                    'gender',
                    'employment_type',
                    'cpns_effective_date',
                    'grade',
                    'grade_effective_date',
                    'education_level',
                    'employment_status',
                    'office_email',
                    'emergency_contact'
                ];
            }

            foreach ($requiredFields as $key => $field) {
                if (empty($personalInfoRow[$personalInfoPos[$field]])) {
                    $this->skippedRow('Data Pegawai', $personalInfoKey, 'Kolom ' . $personalInfo[0][$personalInfoPos[$field]] . ' harus diisi');
                }
            }

            // Check Unique
            if ($this->type == 3) { // OUTSOURCE
                $user = User::where('id_number', '=', $personalInfoRow[$personalInfoPos['id_number']]);
                if (!empty($personalInfoRow[$personalInfoPos['employee_id_number']])) {
                    $user->orWhere('employee_id_number', '=', $personalInfoRow[$personalInfoPos['employee_id_number']]);
                }
                if (!empty($personalInfoRow[$personalInfoPos['id_tax']])) {
                    $user->orWhere('id_tax', '=', $personalInfoRow[$personalInfoPos['id_tax']]);
                }
                if (!empty($personalInfoRow[$personalInfoPos['email']])) {
                    $user->orWhere('email', '=', $personalInfoRow[$personalInfoPos['email']]);
                }
                $user = $user->first();
            } else { // ASN & NON ASN
                $user = User::where('id_number', '=', $personalInfoRow[$personalInfoPos['id_number']]);
                if (!empty($personalInfoRow[$personalInfoPos['employee_id_number']])) {
                    $user->orWhere('employee_id_number', '=', $personalInfoRow[$personalInfoPos['employee_id_number']]);
                }
                if (!empty($personalInfoRow[$personalInfoPos['employee_registration_number']])) {
                    $user->orWhere('employee_registration_number', '=', $personalInfoRow[$personalInfoPos['employee_registration_number']]);
                }
                if (!empty($personalInfoRow[$personalInfoPos['id_tax']])) {
                    $user->orWhere('id_tax', '=', $personalInfoRow[$personalInfoPos['id_tax']]);
                }
                if (!empty($personalInfoRow[$personalInfoPos['email']])) {
                    $user->orWhere('email', '=', $personalInfoRow[$personalInfoPos['email']]);
                }
                if ($this->type == 1) {
                    if (!empty($personalInfoRow[$personalInfoPos['employee_id_card_number']])) {
                        $user->orWhere('employee_id_card_number', '=', $personalInfoRow[$personalInfoPos['employee_id_card_number']]);
                    }
                    if (!empty($personalInfoRow[$personalInfoPos['karisu_number']])) {
                        $user->orWhere('karisu_number', '=', $personalInfoRow[$personalInfoPos['karisu_number']]);
                    }
                }
                $user = $user->first();
            }
            if ($user !== null) {
                $nonUnique = '';
                if (!is_null($personalInfoRow[$personalInfoPos['email']]) && $user->email == $personalInfoRow[$personalInfoPos['email']]) {
                    $nonUnique .= $personalInfo[0][$personalInfoPos['email']] . ' = ' . $personalInfoRow[$personalInfoPos['email']] . ',';
                }
                if (!is_null($personalInfoRow[$personalInfoPos['employee_id_number']]) && $user->employee_id_number == $personalInfoRow[$personalInfoPos['employee_id_number']]) {
                    $nonUnique .= $personalInfo[0][$personalInfoPos['employee_id_number']] . ' = ' . $personalInfoRow[$personalInfoPos['employee_id_number']] . ',';
                }
                if (!is_null($personalInfoRow[$personalInfoPos['id_tax']]) && $user->id_tax == $personalInfoRow[$personalInfoPos['id_tax']]) {
                    $nonUnique .= $personalInfo[0][$personalInfoPos['id_tax']] . ' = ' . $personalInfoRow[$personalInfoPos['id_tax']] . ',';
                }
                if (!is_null($personalInfoRow[$personalInfoPos['id_number']]) && $user->id_number == $personalInfoRow[$personalInfoPos['id_number']]) {
                    $nonUnique .= $personalInfo[0][$personalInfoPos['id_number']] . ' = ' . $personalInfoRow[$personalInfoPos['id_number']] . ',';
                }
                if ($this->type == 1 || $this->type == 2) { // ASN & NON ASN
                    if (!is_null($personalInfoRow[$personalInfoPos['employee_registration_number']]) && $user->employee_registration_number == $personalInfoRow[$personalInfoPos['employee_registration_number']]) {
                        $nonUnique .= $personalInfo[0][$personalInfoPos['employee_registration_number']] . ' = ' . $personalInfoRow[$personalInfoPos['employee_registration_number']] . ',';
                    }
                    if ($this->type == 1) {
                        if (!is_null($personalInfoRow[$personalInfoPos['employee_id_card_number']]) && $user->employee_id_card_number == $personalInfoRow[$personalInfoPos['employee_id_card_number']]) {
                            $nonUnique .= $personalInfo[0][$personalInfoPos['employee_id_card_number']] . ' = ' . $personalInfoRow[$personalInfoPos['employee_id_card_number']] . ',';
                        }
                        if (!is_null($personalInfoRow[$personalInfoPos['karisu_number']]) && $user->karisu_number == $personalInfoRow[$personalInfoPos['karisu_number']]) {
                            $nonUnique .= $personalInfo[0][$personalInfoPos['karisu_number']] . ' = ' . $personalInfoRow[$personalInfoPos['karisu_number']] . ',';
                        }
                    }
                }
                if ($nonUnique !== '') {
                    $this->skippedRow('Data Pegawai', $personalInfoKey, 'Pegawai dengan ' . $nonUnique . ' sudah ada');
                }
            }


            if (strlen($personalInfoRow[$personalInfoPos['id_tax']]) > 16) {
                $this->skippedRow('Data Pegawai', $personalInfoKey, 'NPWP Pegawai ' . $personalInfoRow[$personalInfoPos['id_tax']] . ' melebihi limit 16 digit');
            }
            if (strlen($personalInfoRow[$personalInfoPos['mobile_phone']]) > 20) {
                $this->skippedRow('Data Pegawai', $personalInfoKey, 'No. HP Pegawai ' . $personalInfoRow[$personalInfoPos['mobile_phone']] . ' melebihi limit 20 digit');
            }

            // Get ID
            $employmentTypeID = $this->findInArray($personalInfoRow[$personalInfoPos['employment_type']], $this->employmentTypes, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['employment_type']]);
            $positionID = $this->getPositionID($personalInfoRow[$personalInfoPos['position']], $personalInfoKey);
            $religionID = $this->findInArray($personalInfoRow[$personalInfoPos['religion']], $this->religions, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['religion']]);
            $maritalStatusID = $this->findInArray($personalInfoRow[$personalInfoPos['marital_status']], $this->maritalStatus, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['marital_status']]);
            $educationLevelID = $this->findInArray($personalInfoRow[$personalInfoPos['education_level']], $this->educationLevel, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['education_level']]);
            $employmentStatusID = $this->findInArray($personalInfoRow[$personalInfoPos['employment_status']], $this->employmentStatus, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['employment_status']]);
            $gender = $this->findInArray($personalInfoRow[$personalInfoPos['gender']], $this->gender, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['employment_status']]);

            if ($this->type == 1 || $this->type == 2) { // ASN & NON ASN
                $gradeID = $this->findInArray($personalInfoRow[$personalInfoPos['grade']], $this->grades, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['grade']]);
                $echelonID = $this->findInArray($personalInfoRow[$personalInfoPos['echelon']], $this->echelons, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['echelon']]);
                $institutionID = $this->findInArray($personalInfoRow[$personalInfoPos['institution']], $this->institutions, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['institution']]);
            }

            // Format Date & Gender
            $dateOfBirth = $this->formatDate($personalInfoRow[$personalInfoPos['date_of_birth']], 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['date_of_birth']]);
            $cpnsEffectiveDate = $this->formatDate($personalInfoRow[$personalInfoPos['cpns_effective_date']], 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['cpns_effective_date']]);
            $positionEffectiveDate = $this->formatDate($personalInfoRow[$personalInfoPos['position_effective_date']], 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['position_effective_date']]);

            if ($this->type == 1) {
                $residenceID = $this->findInArray($personalInfoRow[$personalInfoPos['residence']], $this->residences, 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['residence']]);
                $pnsEffectiveDate = $this->formatDate($personalInfoRow[$personalInfoPos['pns_effective_date']], 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['pns_effective_date']]);
                $maritalDate = $this->formatDate($personalInfoRow[$personalInfoPos['marriage_date']], 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['marriage_date']]);
                $masaKerjaKeseluruhanJumlahTahun = !is_null($personalInfoRow[$personalInfoPos['years_of_service_total']]) ? (int) preg_replace('/\D/', '', $personalInfoRow[$personalInfoPos['years_of_service_total']]) : null;
                $masaKerjaKeseluruhanJumlahBulan = !is_null($personalInfoRow[$personalInfoPos['month_of_service_total']]) ? (int) preg_replace('/\D/', '', $personalInfoRow[$personalInfoPos['month_of_service_total']]) : null;
                $masaKerjaGolonganJumlahTahun = !is_null($personalInfoRow[$personalInfoPos['years_of_service_rank']]) ? (int) preg_replace('/\D/', '', $personalInfoRow[$personalInfoPos['years_of_service_rank']]) : null;
                $masaKerjaGolonganJumlahBulan = !is_null($personalInfoRow[$personalInfoPos['month_of_service_rank']]) ? (int) preg_replace('/\D/', '', $personalInfoRow[$personalInfoPos['month_of_service_rank']]) : null;
            }

            if ($this->type == 1 || $this->type == 2) { // ASN & NON ASN
                $gradeEffectiveDate = $this->formatDate($personalInfoRow[$personalInfoPos['grade_effective_date']], 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['grade_effective_date']]);
                $echelonEffectiveDate = $this->formatDate($personalInfoRow[$personalInfoPos['echelon_effective_date']], 'Data Pegawai', $personalInfoKey, $personalInfo[0][$personalInfoPos['echelon_effective_date']]);
            }


            if ($this->type == 3) { // OUTSOURCE
                $result[$personalInfoRow[$personalInfoPos['id_number']]]['personal_info'] = [
                    'email' => $personalInfoRow[$personalInfoPos['email']],
                    'name' => $personalInfoRow[$personalInfoPos['name']],
                    'employee_id_number' => $personalInfoRow[$personalInfoPos['employee_id_number']],
                    'place_of_birth' => $personalInfoRow[$personalInfoPos['place_of_birth']],
                    'date_of_birth' => $dateOfBirth,
                    'religion' => $religionID,
                    'gender' => $gender,
                    'marital_status' => $maritalStatusID,
                    'employment_type_id' => $employmentTypeID,
                    'cpns_effective_date' => $cpnsEffectiveDate,
                    'position_id' => $positionID,
                    'position_effective_date' => $positionEffectiveDate,
                    'education_level' => $educationLevelID,
                    'education_name' => $personalInfoRow[$personalInfoPos['education_name']],
                    'education_year' => $personalInfoRow[$personalInfoPos['education_year']],
                    'id_tax' => $personalInfoRow[$personalInfoPos['id_tax']],
                    'employment_status' => $employmentStatusID,
                    'id_number' => $personalInfoRow[$personalInfoPos['id_number']],
                    'family_registration_number' => $personalInfoRow[$personalInfoPos['family_registration_number']],
                    'residence_description' => $personalInfoRow[$personalInfoPos['residence_description']],
                    'current_address' => $personalInfoRow[$personalInfoPos['current_address']],
                    'home_phone_number' => $personalInfoRow[$personalInfoPos['home_phone_number']],
                    'mobile_phone' => $personalInfoRow[$personalInfoPos['mobile_phone']],
                    'office_address' => $personalInfoRow[$personalInfoPos['office_address']],
                    'office_phone_number' => $personalInfoRow[$personalInfoPos['office_phone_number']],
                    'description' => $personalInfoRow[$personalInfoPos['description']],
                    'emergency_contact' => $personalInfoRow[$personalInfoPos['emergency_contact']],
                    'type' => $this->type,
                ];
            } else if ($this->type == 2) { // NON ASN
                $result[$personalInfoRow[$personalInfoPos['id_number']]]['personal_info'] = [
                    'email' => $personalInfoRow[$personalInfoPos['email']],
                    'title_prefix' => $personalInfoRow[$personalInfoPos['title_prefix']],
                    'name' => $personalInfoRow[$personalInfoPos['name']],
                    'title_suffix' => $personalInfoRow[$personalInfoPos['title_suffix']],
                    'employee_id_number' => $personalInfoRow[$personalInfoPos['employee_id_number']],
                    'employee_registration_number' => $personalInfoRow[$personalInfoPos['employee_registration_number']],
                    'place_of_birth' => $personalInfoRow[$personalInfoPos['place_of_birth']],
                    'date_of_birth' => $dateOfBirth,
                    'religion' => $religionID,
                    'gender' => $gender,
                    'marital_status' => $maritalStatusID,
                    'employment_type_id' => $employmentTypeID,
                    'cpns_effective_date' => $cpnsEffectiveDate,
                    'position_id' => $positionID,
                    'position_effective_date' => $positionEffectiveDate,
                    'grade_id' => $gradeID,
                    'grade_effective_date' => $gradeEffectiveDate,
                    'echelon_id' => $echelonID,
                    'echelon_effective_date' => $echelonEffectiveDate,
                    'institution_id' => $institutionID,
                    'education_level' => $educationLevelID,
                    'education_name' => $personalInfoRow[$personalInfoPos['education_name']],
                    'education_year' => $personalInfoRow[$personalInfoPos['education_year']],
                    'id_tax' => $personalInfoRow[$personalInfoPos['id_tax']],
                    'employment_status' => $employmentStatusID,
                    'id_number' => $personalInfoRow[$personalInfoPos['id_number']],
                    'family_registration_number' => $personalInfoRow[$personalInfoPos['family_registration_number']],
                    'residence_description' => $personalInfoRow[$personalInfoPos['residence_description']],
                    'current_address' => $personalInfoRow[$personalInfoPos['current_address']],
                    'home_phone_number' => $personalInfoRow[$personalInfoPos['home_phone_number']],
                    'mobile_phone' => $personalInfoRow[$personalInfoPos['mobile_phone']],
                    'office_address' => $personalInfoRow[$personalInfoPos['office_address']],
                    'office_phone_number' => $personalInfoRow[$personalInfoPos['office_phone_number']],
                    'office_email' => $personalInfoRow[$personalInfoPos['office_email']],
                    'emergency_contact' => $personalInfoRow[$personalInfoPos['emergency_contact']],
                    'type' => $this->type,
                ];
            } else { // ASN
                $result[$personalInfoRow[$personalInfoPos['id_number']]]['personal_info'] = [
                    'email' => $personalInfoRow[$personalInfoPos['email']],
                    'title_prefix' => $personalInfoRow[$personalInfoPos['title_prefix']],
                    'name' => $personalInfoRow[$personalInfoPos['name']],
                    'title_suffix' => $personalInfoRow[$personalInfoPos['title_suffix']],
                    'employee_id_number' => $personalInfoRow[$personalInfoPos['employee_id_number']],
                    'employee_registration_number' => $personalInfoRow[$personalInfoPos['employee_registration_number']],
                    'place_of_birth' => $personalInfoRow[$personalInfoPos['place_of_birth']],
                    'date_of_birth' => $dateOfBirth,
                    'religion' => $religionID,
                    'gender' => $gender,
                    'marital_status' => $maritalStatusID,
                    'marriage_date' => $maritalDate,
                    'marriage_description' => $personalInfoRow[$personalInfoPos['marriage_description']],
                    // 'marriage_other_notes' => $personalInfoRow[$personalInfoPos['marriage_other_notes']],
                    'employment_type_id' => $employmentTypeID,
                    'cpns_effective_date' => $cpnsEffectiveDate,
                    'pns_effective_date' => $pnsEffectiveDate,
                    'position_id' => $positionID,
                    'position_effective_date' => $positionEffectiveDate,
                    'grade_id' => $gradeID,
                    'grade_effective_date' => $gradeEffectiveDate,
                    'echelon_id' => $echelonID,
                    'echelon_effective_date' => $echelonEffectiveDate,
                    'institution_id' => $institutionID,
                    'education_level' => $educationLevelID,
                    'education_name' => $personalInfoRow[$personalInfoPos['education_name']],
                    'education_year' => $personalInfoRow[$personalInfoPos['education_year']],
                    'employee_id_card_number' => $personalInfoRow[$personalInfoPos['employee_id_card_number']],
                    'karisu_number' => $personalInfoRow[$personalInfoPos['karisu_number']],
                    'years_of_service_total' => $masaKerjaKeseluruhanJumlahTahun,
                    'month_of_service_total' => $masaKerjaKeseluruhanJumlahBulan,
                    'years_of_service_rank' => $masaKerjaGolonganJumlahTahun,
                    'month_of_service_rank' => $masaKerjaGolonganJumlahBulan,
                    'id_tax' => $personalInfoRow[$personalInfoPos['id_tax']],
                    'employment_status' => $employmentStatusID,
                    'id_number' => $personalInfoRow[$personalInfoPos['id_number']],
                    'family_registration_number' => $personalInfoRow[$personalInfoPos['family_registration_number']],
                    'residence_id' => $residenceID,
                    'residence_description' => $personalInfoRow[$personalInfoPos['residence_description']],
                    'current_address' => $personalInfoRow[$personalInfoPos['current_address']],
                    'home_phone_number' => $personalInfoRow[$personalInfoPos['home_phone_number']],
                    'mobile_phone' => $personalInfoRow[$personalInfoPos['mobile_phone']],
                    'office_address' => $personalInfoRow[$personalInfoPos['office_address']],
                    'office_phone_number' => $personalInfoRow[$personalInfoPos['office_phone_number']],
                    'office_email' => $personalInfoRow[$personalInfoPos['office_email']],
                    'emergency_contact' => $personalInfoRow[$personalInfoPos['emergency_contact']],
                    'type' => $this->type,
                ];
            }
        }

        return $result;
    }

    private function educationInfo($educationInfo, $personalInfo)
    {
        foreach ($educationInfo as $educationKey => $educationRow) {
            if ($educationKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($educationRow[$this->educationInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat Pendidikan', $educationKey, 'Kolom ' . $educationInfo[0][$this->educationInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $levelID = $this->findInArray($educationRow[$this->educationInfoPos['level']], $this->educationLevel, 'Riwayat Pendidikan', $educationKey, $educationInfo[0][$this->educationInfoPos['level']]);
            $studyAreaID = $this->findInArray($educationRow[$this->educationInfoPos['study_area']], $this->educationStudyArea, 'Riwayat Pendidikan', $educationKey, $educationInfo[0][$this->educationInfoPos['study_area']]);

            $personalInfo[$educationRow[$this->educationInfoPos['nik']]]['education'][] = [
                'level' => $levelID,
                'name' => $educationRow[$this->educationInfoPos['name']],
                'study_area' => $studyAreaID,
                'accreditation' => $educationRow[$this->educationInfoPos['accreditation']],
                'faculty' => $educationRow[$this->educationInfoPos['faculty']],
                'major' => $educationRow[$this->educationInfoPos['major']],
                'year_of_graduation' => $educationRow[$this->educationInfoPos['year_of_graduation']],
                'description' => $educationRow[$this->educationInfoPos['description']],
            ];
        }

        return $personalInfo;
    }

    private function positionInfo($positionInfo, $personalInfo)
    {
        foreach ($positionInfo as $positionKey => $positionRow) {
            if ($positionKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($positionRow[$this->positionInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat Jabatan', $positionKey, 'Kolom ' . $positionInfo[0][$this->positionInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $groupID = $this->findInArray($positionRow[$this->positionInfoPos['group']], $this->groups, 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['group']]);
            $monthID = $this->findInArray($positionRow[$this->positionInfoPos['period_month']], $this->month, 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['period_month']]);
            $positionEchelon = $this->findInArray($positionRow[$this->positionInfoPos['echelon']], $this->positionEchelon, 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['echelon']]);
            $positionStatusID = $this->findInArray($positionRow[$this->positionInfoPos['position_status']], $this->positionStatus, 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['position_status']]);
            $decreeID = $this->findInArray($positionRow[$this->positionInfoPos['type_of_decree']], $this->decrees, 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['type_of_decree']]);
            $terminationDecreeID = $this->findInArray($positionRow[$this->positionInfoPos['type_of_termination_decree']], $this->decrees, 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['type_of_termination_decree']]);

            $effectiveDate = $this->formatDate($positionRow[$this->positionInfoPos['effective_date']], 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['effective_date']]);
            $decreeDate = $this->formatDate($positionRow[$this->positionInfoPos['decree_date']], 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['decree_date']]);
            $terminationDate = $this->formatDate($positionRow[$this->positionInfoPos['termination_date']], 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['termination_date']]);
            $terminationDecreeDate = $this->formatDate($positionRow[$this->positionInfoPos['termination_decree_date']], 'Riwayat Jabatan', $positionKey, $positionInfo[0][$this->positionInfoPos['termination_decree_date']]);

            $personalInfo[$positionRow[$this->positionInfoPos['nik']]]['position'][] = [
                'name' => $positionRow[$this->positionInfoPos['name']],
                'period_month' => $monthID,
                'period_year' => $positionRow[$this->positionInfoPos['period_year']],
                'position' => $positionRow[$this->positionInfoPos['position']],
                'group_id' => $groupID,
                'echelon' => $positionEchelon,
                'position_status' => $positionStatusID,
                'effective_date' => $effectiveDate,
                'decree' => $positionRow[$this->positionInfoPos['decree']],
                'type_of_decree' => $decreeID,
                'decree_number' => $positionRow[$this->positionInfoPos['decree_number']],
                'decree_date' => $decreeDate,
                'termination_date' => $terminationDate,
                'termination_decree' => $positionRow[$this->positionInfoPos['termination_decree']],
                'type_of_termination_decree' => $terminationDecreeID,
                'termination_decree_number' => $positionRow[$this->positionInfoPos['termination_decree_number']],
                'termination_decree_date' => $terminationDecreeDate,
            ];
        }

        return $personalInfo;
    }

    private function gradeInfo($gradeInfo, $personalInfo)
    {
        foreach ($gradeInfo as $gradeKey => $gradeRow) {
            if ($gradeKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($gradeRow[$this->gradeInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat Golongan', $gradeKey, 'Kolom ' . $gradeInfo[0][$this->gradeInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $gradeID = $this->findInArray($gradeRow[$this->gradeInfoPos['grade']], $this->grades, 'Riwayat Golongan', $gradeKey, $gradeInfo[0][$this->gradeInfoPos['grade']]);
            $decreeID = $this->findInArray($gradeRow[$this->gradeInfoPos['type_of_decree']], $this->decrees, 'Riwayat Golongan', $gradeKey, $gradeInfo[0][$this->gradeInfoPos['type_of_decree']]);
            $monthID = $this->findInArray($gradeRow[$this->gradeInfoPos['period_month']], $this->month, 'Riwayat Golongan', $gradeKey, $gradeInfo[0][$this->gradeInfoPos['period_month']]);

            $effectiveDate = $this->formatDate($gradeRow[$this->gradeInfoPos['effective_date']], 'Riwayat Golongan', $gradeKey, $gradeInfo[0][$this->gradeInfoPos['effective_date']]);
            $decreeDate = $this->formatDate($gradeRow[$this->gradeInfoPos['decree_date']], 'Riwayat Golongan', $gradeKey, $gradeInfo[0][$this->gradeInfoPos['decree_date']]);
            $status = 1; //Default 1 = Aktif
            if ($gradeRow[$this->gradeInfoPos['status']] == 'Aktif') {
                $status = 1;
            } else if ($gradeRow[$this->gradeInfoPos['status']] == 'Tidak Aktif') {
                $status = 0;
            }

            $personalInfo[$gradeRow[$this->gradeInfoPos['nik']]]['grade'][] = [
                'name' => $gradeRow[$this->gradeInfoPos['name']],
                'period_month' => $monthID,
                'period_year' => $gradeRow[$this->gradeInfoPos['period_year']],
                'grade_id' => $gradeID,
                'effective_date' => $effectiveDate,
                'decree_name' => $gradeRow[$this->gradeInfoPos['decree_name']],
                'type_of_decree' => $decreeID,
                'decree_number' => $gradeRow[$this->gradeInfoPos['decree_number']],
                'decree_date' => $decreeDate,
                'description' => $gradeRow[$this->gradeInfoPos['description']],
                'status' => $status,
            ];
        }

        return $personalInfo;
    }

    private function trainingInfo($trainingInfo, $personalInfo, $trainingType)
    {
        foreach ($trainingInfo as $trainingKey => $trainingRow) {
            if ($trainingKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            if ($trainingType == 3) { // Pelatihan Teknis
                $startDate = $this->formatDate($trainingRow[$this->technicalTrainingInfoPos['start_date']], 'Riwayat Pelatihan Teknis', $trainingKey, $trainingInfo[0][$this->technicalTrainingInfoPos['start_date']]);
                $endDate = $this->formatDate($trainingRow[$this->technicalTrainingInfoPos['end_date']], 'Riwayat Pelatihan Teknis', $trainingKey, $trainingInfo[0][$this->technicalTrainingInfoPos['end_date']]);

                $requiredFieldFilled = true;
                foreach ($requiredFields as $key => $field) {
                    if (empty($trainingRow[$this->technicalTrainingInfoPos[$field]])) {
                        $requiredFieldFilled = false;

                        $this->skippedRow('Riwayat Pelatihan Teknis', $trainingKey, 'Kolom ' . $trainingInfo[0][$this->technicalTrainingInfoPos[$field]] . ' harus diisi');
                    }
                }
                if (!$requiredFieldFilled) {
                    continue;
                }
                // Skip jika ada required field yang tidak diisi

                $groupID = $this->findInArray($trainingRow[$this->technicalTrainingInfoPos['group']], $this->groups, 'Riwayat Pelatihan Teknis', $trainingKey, $trainingInfo[0][$this->technicalTrainingInfoPos['group']]);
                $monthID = $this->findInArray($trainingRow[$this->technicalTrainingInfoPos['period_month']], $this->month, 'Riwayat Pelatihan Teknis', $trainingKey, $trainingInfo[0][$this->technicalTrainingInfoPos['period_month']]);

                $personalInfo[$trainingRow[$this->technicalTrainingInfoPos['nik']]]['training'][] = [
                    'name' => $trainingRow[$this->technicalTrainingInfoPos['name']],
                    'period_month' => $monthID,
                    'period_year' => $trainingRow[$this->technicalTrainingInfoPos['period_year']],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'duration' => $trainingRow[$this->technicalTrainingInfoPos['duration']],
                    'reference_number' => $trainingRow[$this->technicalTrainingInfoPos['reference_number']],
                    'group_id' => $groupID,
                    'type' => $trainingType,
                ];
            } else if ($trainingType == 2) { // Pelatihan Fungsional
                $sheet = 'Riwayat Pelatihan Fungsional';
                $startDate = $this->formatDate($trainingRow[$this->functionalTrainingInfoPos['start_date']], $sheet, $trainingKey, $trainingInfo[0][$this->functionalTrainingInfoPos['start_date']]);
                $endDate = $this->formatDate($trainingRow[$this->functionalTrainingInfoPos['end_date']], $sheet, $trainingKey, $trainingInfo[0][$this->functionalTrainingInfoPos['end_date']]);

                $requiredFieldFilled = true;
                foreach ($requiredFields as $key => $field) {
                    if (empty($trainingRow[$this->functionalTrainingInfoPos[$field]])) {
                        $requiredFieldFilled = false;

                        $this->skippedRow($sheet, $trainingKey, 'Kolom ' . $trainingInfo[0][$this->functionalTrainingInfoPos[$field]] . ' harus diisi');
                    }
                }
                if (!$requiredFieldFilled) {
                    continue;
                }
                // Skip jika ada required field yang tidak diisi

                $monthID = $this->findInArray($trainingRow[$this->functionalTrainingInfoPos['period_month']], $this->month, $sheet, $trainingKey, $trainingInfo[0][$this->functionalTrainingInfoPos['period_month']]);
                $levelID = $this->findInArray($trainingRow[$this->functionalTrainingInfoPos['level']], $this->levels, $sheet, $trainingKey, $trainingInfo[0][$this->functionalTrainingInfoPos['level']]);

                $duration = !is_null($trainingRow[$this->functionalTrainingInfoPos['duration']]) ? (int) preg_replace('/\D/', '', $trainingRow[$this->functionalTrainingInfoPos['duration']]) : null;

                $personalInfo[$trainingRow[$this->functionalTrainingInfoPos['nik']]]['training'][] = [
                    'name' => $trainingRow[$this->functionalTrainingInfoPos['name']],
                    'period_month' => $monthID,
                    'period_year' => $trainingRow[$this->functionalTrainingInfoPos['period_year']],
                    'level' => $levelID,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'duration' => $duration,
                    'organizer' => $trainingRow[$this->functionalTrainingInfoPos['organizer']],
                    'reference_number' => $trainingRow[$this->functionalTrainingInfoPos['reference_number']],
                    'type' => $trainingType,
                ];
            } else { // Pelatihan Struktural              
                $sheet = 'Riwayat Pelatihan Struktural';
                $startDate = $this->formatDate($trainingRow[$this->structuralTrainingInfoPos['start_date']], $sheet, $trainingKey, $trainingInfo[0][$this->structuralTrainingInfoPos['start_date']]);
                $endDate = $this->formatDate($trainingRow[$this->structuralTrainingInfoPos['end_date']], $sheet, $trainingKey, $trainingInfo[0][$this->structuralTrainingInfoPos['end_date']]);

                $requiredFieldFilled = true;
                foreach ($requiredFields as $key => $field) {
                    if (empty($trainingRow[$this->structuralTrainingInfoPos[$field]])) {
                        $requiredFieldFilled = false;

                        $this->skippedRow($sheet, $trainingKey, 'Kolom ' . $trainingInfo[0][$this->structuralTrainingInfoPos[$field]] . ' harus diisi');
                    }
                }
                if (!$requiredFieldFilled) {
                    continue;
                }
                // Skip jika ada required field yang tidak diisi

                $monthID = $this->findInArray($trainingRow[$this->structuralTrainingInfoPos['period_month']], $this->month, $sheet, $trainingKey, $trainingInfo[0][$this->structuralTrainingInfoPos['period_month']]);
                $levelID = $this->findInArray($trainingRow[$this->structuralTrainingInfoPos['level']], $this->levels, $sheet, $trainingKey, $trainingInfo[0][$this->structuralTrainingInfoPos['level']]);

                $duration = !is_null($trainingRow[$this->structuralTrainingInfoPos['duration']]) ? (int) preg_replace('/\D/', '', $trainingRow[$this->structuralTrainingInfoPos['duration']]) : null;

                $personalInfo[$trainingRow[$this->structuralTrainingInfoPos['nik']]]['training'][] = [
                    'name' => $trainingRow[$this->structuralTrainingInfoPos['name']],
                    'period_month' => $monthID,
                    'period_year' => $trainingRow[$this->structuralTrainingInfoPos['period_year']],
                    'level' => $levelID,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'duration' => $duration,
                    'organizer' => $trainingRow[$this->structuralTrainingInfoPos['organizer']],
                    'reference_number' => $trainingRow[$this->structuralTrainingInfoPos['reference_number']],
                    'description' => $trainingRow[$this->structuralTrainingInfoPos['description']],
                    'type' => $trainingType,
                ];
            }
        }

        return $personalInfo;
    }

    private function recognitionInfo($recognitionInfo, $personalInfo)
    {
        foreach ($recognitionInfo as $recognitionKey => $recognitionRow) {
            if ($recognitionKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($recognitionRow[$this->recognitionInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat Penghargaan', $recognitionKey, 'Kolom ' . $recognitionInfo[0][$this->recognitionInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $recognitionID = $this->findInArray($recognitionRow[$this->recognitionInfoPos['recognition']], $this->recognitions, 'Riwayat Penghargaan', $recognitionKey, $recognitionInfo[0][$this->recognitionInfoPos['recognition']]);
            $decreeID = $this->findInArray($recognitionRow[$this->recognitionInfoPos['type_of_decree']], $this->decrees, 'Riwayat Penghargaan', $recognitionKey, $recognitionInfo[0][$this->recognitionInfoPos['type_of_decree']]);
            $monthID = $this->findInArray($recognitionRow[$this->recognitionInfoPos['period_month']], $this->month, 'Riwayat Penghargaan', $recognitionKey, $recognitionInfo[0][$this->recognitionInfoPos['period_month']]);

            $decreeDate = $this->formatDate($recognitionRow[$this->recognitionInfoPos['decree_date']], 'Riwayat Penghargaan', $recognitionKey, $recognitionInfo[0][$this->recognitionInfoPos['decree_date']]);

            $personalInfo[$recognitionRow[$this->recognitionInfoPos['nik']]]['recognition'][] = [
                'recognition_id' => $recognitionID,
                'period_month' => $monthID,
                'period_year' => $recognitionRow[$this->recognitionInfoPos['period_year']],
                'description' => $recognitionRow[$this->recognitionInfoPos['description']],
                'type_of_decree' => $decreeID,
                'decree_date' => $decreeDate,
                'decree_number' => $recognitionRow[$this->recognitionInfoPos['decree_number']],
                'decree_year' => $recognitionRow[$this->recognitionInfoPos['decree_year']],
                'awarding_institution' => $recognitionRow[$this->recognitionInfoPos['awarding_institution']],
            ];
        }

        return $personalInfo;
    }

    private function targetInfo($targetInfo, $personalInfo)
    {
        foreach ($targetInfo as $targetKey => $targetRow) {
            if ($targetKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($targetRow[$this->targetInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat SKP', $targetKey, 'Kolom ' . $targetInfo[0][$this->targetInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $monthID = $this->findInArray($targetRow[$this->targetInfoPos['period_month']], $this->month, 'Riwayat SKP', $targetKey, $targetInfo[0][$this->targetInfoPos['period_month']]);
            $workBehaviourRatingID = $this->findInArray($targetRow[$this->targetInfoPos['work_behavior_rating']], $this->workBehaviourRating, 'Riwayat SKP', $targetKey, $targetInfo[0][$this->targetInfoPos['work_behavior_rating']]);
            $employeePerformancePredicateID = $this->findInArray($targetRow[$this->targetInfoPos['employee_performance_predicate']], $this->employeePerformancePredicate, 'Riwayat SKP', $targetKey, $targetInfo[0][$this->targetInfoPos['employee_performance_predicate']]);
            $organizationalPerformanceAchievementID = $this->findInArray($targetRow[$this->targetInfoPos['organizational_performance_achievement']], $this->organizationalPerformanceAchievement, 'Riwayat SKP', $targetKey, $targetInfo[0][$this->targetInfoPos['organizational_performance_achievement']]);

            $personalInfo[$targetRow[$this->targetInfoPos['nik']]]['target'][] = [
                'name' => $targetRow[$this->targetInfoPos['name']],
                'period_month' => $monthID,
                'period_year' => $targetRow[$this->targetInfoPos['period_year']],
                'appraisal_period' => $targetRow[$this->targetInfoPos['appraisal_period']],
                'year' => $targetRow[$this->targetInfoPos['year']],
                'work_behavior_rating' => $workBehaviourRatingID,
                'employee_performance_predicate' => $employeePerformancePredicateID,
                'organizational_performance_achievement' => $organizationalPerformanceAchievementID,
            ];
        }

        return $personalInfo;
    }

    private function performanceInfo($performanceInfo, $personalInfo)
    {
        foreach ($performanceInfo as $performanceKey => $performanceRow) {
            if ($performanceKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($performanceRow[$this->performanceInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Penilaian Prestasi Kerja', $performanceKey, 'Kolom ' . $performanceInfo[0][$this->performanceInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $monthID = $this->findInArray($performanceRow[$this->performanceInfoPos['period_month']], $this->month, 'Penilaian Prestasi Kerja', $performanceKey, $performanceInfo[0][$this->performanceInfoPos['period_month']]);
            $performanceDescriptionID = $this->findInArray($performanceRow[$this->performanceInfoPos['description']], $this->performanceDescription, 'Penilaian Prestasi Kerja', $performanceKey, $performanceInfo[0][$this->performanceInfoPos['description']]);

            $personalInfo[$performanceRow[$this->performanceInfoPos['nik']]]['performance'][] = [
                'name' => $performanceRow[$this->performanceInfoPos['name']],
                'period_month' => $monthID,
                'period_year' => $performanceRow[$this->performanceInfoPos['period_year']],
                'performance_period' => $performanceRow[$this->performanceInfoPos['performance_period']],
                'work_performance_score' => $performanceRow[$this->performanceInfoPos['work_performance_score']],
                'description' => $performanceDescriptionID,
            ];
        }

        return $personalInfo;
    }

    private function disciplinaryInfo($disciplinaryInfo, $personalInfo)
    {
        foreach ($disciplinaryInfo as $disciplinaryKey => $disciplinaryRow) {
            if ($disciplinaryKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($disciplinaryRow[$this->disciplinaryInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat Hukuman Disiplin', $disciplinaryKey, 'Kolom ' . $disciplinaryInfo[0][$this->disciplinaryInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $disciplinaryID = $this->findInArray($disciplinaryRow[$this->disciplinaryInfoPos['disciplinary']], $this->disciplinaries, 'Riwayat Hukuman Disiplin', $disciplinaryKey, $disciplinaryInfo[0][$this->disciplinaryInfoPos['disciplinary']]);
            $monthID = $this->findInArray($disciplinaryRow[$this->disciplinaryInfoPos['period_month']], $this->month, 'Riwayat Hukuman Disiplin', $disciplinaryKey, $disciplinaryInfo[0][$this->disciplinaryInfoPos['period_month']]);

            $decreeDate = $this->formatDate($disciplinaryRow[$this->disciplinaryInfoPos['date_of_decree']], 'Riwayat Hukuman Disiplin', $disciplinaryKey, $disciplinaryInfo[0][$this->disciplinaryInfoPos['date_of_decree']]);
            $startDate = $this->formatDate($disciplinaryRow[$this->disciplinaryInfoPos['start_date']], 'Riwayat Hukuman Disiplin', $disciplinaryKey, $disciplinaryInfo[0][$this->disciplinaryInfoPos['start_date']]);
            $endDate = $this->formatDate($disciplinaryRow[$this->disciplinaryInfoPos['end_date']], 'Riwayat Hukuman Disiplin', $disciplinaryKey, $disciplinaryInfo[0][$this->disciplinaryInfoPos['end_date']]);

            $personalInfo[$disciplinaryRow[$this->disciplinaryInfoPos['nik']]]['disciplinary'][] = [
                'name' => $disciplinaryRow[$this->disciplinaryInfoPos['name']],
                'period_month' => $monthID,
                'period_year' => $disciplinaryRow[$this->disciplinaryInfoPos['period_year']],
                'disciplinary_id' => $disciplinaryID,
                'grade' => $disciplinaryRow[$this->disciplinaryInfoPos['grade']],
                'position' => $disciplinaryRow[$this->disciplinaryInfoPos['position']],
                'decree_number' => $disciplinaryRow[$this->disciplinaryInfoPos['decree_number']],
                'date_of_decree' => $decreeDate,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'authorizing_officer' => $disciplinaryRow[$this->disciplinaryInfoPos['authorizing_officer']],
                'name_of_authorizing_officer' => $disciplinaryRow[$this->disciplinaryInfoPos['name_of_authorizing_officer']],
                'description' => $disciplinaryRow[$this->disciplinaryInfoPos['description']],

            ];
        }

        return $personalInfo;
    }

    private function familyInfo($familyInfo, $personalInfo)
    {
        foreach ($familyInfo as $familyKey => $familyRow) {
            if ($familyKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($familyRow[$this->familyInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat Keluarga', $familyKey, 'Kolom ' . $familyInfo[0][$this->familyInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $genderID = $this->findInArray($familyRow[$this->familyInfoPos['gender']], $this->gender, 'Riwayat Keluarga', $familyKey, $familyInfo[0][$this->familyInfoPos['gender']]);
            $religionID = $this->findInArray($familyRow[$this->familyInfoPos['religion']], $this->religions, 'Riwayat Keluarga', $familyKey, $familyInfo[0][$this->familyInfoPos['religion']]);
            $familyRelationshipID = $this->findInArray($familyRow[$this->familyInfoPos['relationship_status']], $this->familyRelationship, 'Riwayat Keluarga', $familyKey, $familyInfo[0][$this->familyInfoPos['relationship_status']]);
            $familyEducationID = $this->findInArray($familyRow[$this->familyInfoPos['education']], $this->familyEducation, 'Riwayat Keluarga', $familyKey, $familyInfo[0][$this->familyInfoPos['education']]);
            $familyMaritalStatusID = $this->findInArray($familyRow[$this->familyInfoPos['marital_status']], $this->familyMaritalStatus, 'Riwayat Keluarga', $familyKey, $familyInfo[0][$this->familyInfoPos['marital_status']]);

            $birthDate = $this->formatDate($familyRow[$this->familyInfoPos['date_of_birth']], 'Riwayat Keluarga', $familyKey, $familyInfo[0][$this->familyInfoPos['date_of_birth']]);

            $personalInfo[$familyRow[$this->familyInfoPos['nik']]]['family'][] = [
                'card_number' => $familyRow[$this->familyInfoPos['card_number']],
                'name' => $familyRow[$this->familyInfoPos['name']],
                'id_number' => $familyRow[$this->familyInfoPos['id_number']],
                'gender' => $genderID,
                'religion' => $religionID,
                'place_of_birth' => $familyRow[$this->familyInfoPos['place_of_birth']],
                'date_of_birth' => $birthDate,
                'name_of_father' => $familyRow[$this->familyInfoPos['name_of_father']],
                'name_of_mother' => $familyRow[$this->familyInfoPos['name_of_mother']],
                'relationship_status' => $familyRelationshipID,
                'education' => $familyEducationID,
                'occupation' => $familyRow[$this->familyInfoPos['occupation']],
                'occupation_description' => $familyRow[$this->familyInfoPos['occupation_description']],
                'marital_status' => $familyMaritalStatusID,
                'marriage_other_notes' => $familyRow[$this->familyInfoPos['marriage_other_notes']],
                'mobile_phone' => $familyRow[$this->familyInfoPos['mobile_phone']],
                'sequence_number' => $familyRow[$this->familyInfoPos['sequence_number']],
            ];
        }

        return $personalInfo;
    }

    private function leaveInfo($leaveInfo, $personalInfo)
    {
        foreach ($leaveInfo as $leaveKey => $leaveRow) {
            if ($leaveKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($leaveRow[$this->leaveInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat Cuti', $leaveKey, 'Kolom ' . $leaveInfo[0][$this->leaveInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $leaveTypeID = $this->findInArray($leaveRow[$this->leaveInfoPos['type']], $this->leaveType, 'Riwayat Cuti', $leaveKey, $leaveInfo[0][$this->leaveInfoPos['type']]);

            $startDate = $this->formatDate($leaveRow[$this->leaveInfoPos['start_date']], 'Riwayat Cuti', $leaveKey, $leaveInfo[0][$this->leaveInfoPos['start_date']]);
            $endDate = $this->formatDate($leaveRow[$this->leaveInfoPos['end_date']], 'Riwayat Cuti', $leaveKey, $leaveInfo[0][$this->leaveInfoPos['end_date']]);

            $personalInfo[$leaveRow[$this->leaveInfoPos['nik']]]['leave'][] = [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $leaveTypeID,
                'number' => $leaveRow[$this->leaveInfoPos['number']],
                'description' => $leaveRow[$this->leaveInfoPos['description']],
            ];
        }

        return $personalInfo;
    }

    private function noteInfo($noteInfo, $personalInfo, $giverID)
    {
        foreach ($noteInfo as $noteKey => $noteRow) {
            if ($noteKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($noteRow[$this->noteInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Riwayat Catatan', $noteKey, 'Kolom ' . $noteInfo[0][$this->noteInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $personalInfo[$noteRow[$this->noteInfoPos['nik']]]['note'][] = [
                'giver_id' => $giverID,
                'description' => $noteRow[$this->noteInfoPos['description']],
            ];
        }

        return $personalInfo;
    }

    private function assessmentInfo($assessmentInfo, $personalInfo)
    {
        foreach ($assessmentInfo as $assessmentKey => $assessmentRow) {
            if ($assessmentKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($assessmentRow[$this->assessmentInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Hasil Assessment', $assessmentKey, 'Kolom ' . $assessmentInfo[0][$this->assessmentInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $assessmentPointID = $this->findInArray($assessmentRow[$this->assessmentInfoPos['point']], $this->assessmentPoint, 'Hasil Assessment', $assessmentKey, $assessmentInfo[0][$this->assessmentInfoPos['point']]);

            $eventDate = $this->formatDate($assessmentRow[$this->assessmentInfoPos['event_date']], 'Hasil Assessment', $assessmentKey, $assessmentInfo[0][$this->assessmentInfoPos['event_date']]);

            $personalInfo[$assessmentRow[$this->assessmentInfoPos['nik']]]['assessment'][] = [
                'event_date' => $eventDate,
                'point' => $assessmentPointID,
                'organizer' => $assessmentRow[$this->assessmentInfoPos['organizer']],
            ];
        }

        return $personalInfo;
    }

    private function competencyInfo($competencyInfo, $personalInfo)
    {
        foreach ($competencyInfo as $competencyKey => $competencyRow) {
            if ($competencyKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($competencyRow[$this->competencyInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Hasil Uji Kompetensi', $competencyKey, 'Kolom ' . $competencyInfo[0][$this->competencyInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $competencyPointID = $this->findInArray($competencyRow[$this->competencyInfoPos['point']], $this->competencyPoint, 'Hasil Uji Kompetensi', $competencyKey, $competencyInfo[0][$this->competencyInfoPos['point']]);

            $eventDate = $this->formatDate($competencyRow[$this->competencyInfoPos['event_date']], 'Hasil Competency', $competencyKey, $competencyInfo[0][$this->competencyInfoPos['event_date']]);

            $personalInfo[$competencyRow[$this->competencyInfoPos['nik']]]['competency'][] = [
                'event_date' => $eventDate,
                'point' => $competencyPointID,
                'organizer' => $competencyRow[$this->competencyInfoPos['organizer']],
            ];
        }

        return $personalInfo;
    }

    private function talentInfo($talentInfo, $personalInfo)
    {
        foreach ($talentInfo as $talentKey => $talentRow) {
            if ($talentKey == 0) {
                continue;
            }
            // Skip header row

            $requiredFields = [
                'nik',
            ];

            $requiredFieldFilled = true;
            foreach ($requiredFields as $key => $field) {
                if (empty($talentRow[$this->talentInfoPos[$field]])) {
                    $requiredFieldFilled = false;

                    $this->skippedRow('Hasil Talent Pool', $talentKey, 'Kolom ' . $talentInfo[0][$this->talentInfoPos[$field]] . ' harus diisi');
                }
            }
            if (!$requiredFieldFilled) {
                continue;
            }
            // Skip jika ada required field yang tidak diisi

            $talentPointID = $this->findInArray($talentRow[$this->talentInfoPos['point']], $this->talentPoint, 'Hasil Talent Pool', $talentKey, $talentInfo[0][$this->talentInfoPos['point']]);

            $eventDate = $this->formatDate($talentRow[$this->talentInfoPos['event_date']], 'Hasil Talent', $talentKey, $talentInfo[0][$this->talentInfoPos['event_date']]);

            $personalInfo[$talentRow[$this->talentInfoPos['nik']]]['talent'][] = [
                'event_date' => $eventDate,
                'point' => $talentPointID,
                'organizer' => $talentRow[$this->talentInfoPos['organizer']],
            ];
        }

        return $personalInfo;
    }

    /**
     * Finds a value in an array based on a given key.
     *
     * This function converts the provided key to lowercase,
     * replaces spaces with empty, and then searches for
     * this key in the given array. If the key is found, it returns
     * the associated value; otherwise, it returns null.
     *
     * @param string $find The key to search for.
     * @param array $array The array to search in.
     * @param string $sheet Excel sheet where the data located. For skippedRow if not found
     * @param string $row row where the data located. For skippedRow if not found
     * @param string $column column where the data located. For skippedRow if not found
     * @return mixed|null The value associated with the key, or null if not found.
     */
    private function findInArray($find, $array, $sheet, $row, $column)
    {
        if (empty($find)) { // Misal kosong atau tidak diisi
            return null;
        }
        // replace characters in a string that are not numbers, alphabets, or the specified symbols ()-/,. with empty
        $key = preg_replace('/[^a-zA-Z0-9\(\)\-\,\.\/]/', '', strtolower($find));

        $id = $array[$key] ?? null;

        if (is_null($id)) {
            $this->skippedRow($sheet, $row, $column . ' "' . $find . '", tidak ditemukan');
        }

        return $id;
    }

    /**
     * Merges an array of values into each element of another array.
     *
     * @param array $injectValue The array of values to inject into each element of the main array.
     * @param array $array The main array whose elements will be modified.
     * @return array The modified array with injected values.
     */
    private function mergeValuesIntoArrayElements(array $injectValue, array $array)
    {
        foreach ($array as $key => $value) {
            // Merge $injectValue into each element of $array and assign it back to the corresponding key
            $array[$key] = array_merge($value, $injectValue);
        }
        return $array;
    }

    /**
     * Fetches a mapping of names to IDs from a specified table, with optional conditions.
     *
     * @param string $tableName The name of the table to query.
     * @param array $conditions (Optional) An associative array of conditions for the query.
     * @return array An associative array where the keys are the processed names and the values are the IDs.
     */
    private function getNameIdMapping($tableName, $conditions = [], $name = 'name')
    {
        // Query the specified table and select the id and the processed name
        $query = DB::table($tableName);
        $query->select("id", DB::raw("LOWER(REPLACE(" . $name . ", ' ', '')) as name"));

        // Apply conditions if provided
        if (count($conditions) > 0) {
            $query->where($conditions);
        }

        // Execute the query and get the results
        $results = $query->get();

        // Initialize an array to hold the name to id mapping
        $nameIdMapping = [];

        // Populate the array with processed names as keys and their corresponding ids as values
        foreach ($results as $row) {
            $nameIdMapping[$row->name] = $row->id;
        }

        // Return the mapping array
        return $nameIdMapping;
    }

    private function buildHierarchy(array $elements, $parentId = null)
    {
        $branch = [];

        foreach ($elements as $element) {
            $element = (array) $element;
            if ($element['parent_id'] === $parentId) {
                $children = $this->buildHierarchy($elements, $element['id']);
                if ($children) {
                    $element['child'] = $children;
                }
                $branch[$element['name']] = [
                    'id' => $element['id'],
                ];
                if (isset($element['child'])) {
                    $branch[$element['name']]['child'] = $element['child'];
                }
            }
        }

        return $branch;
    }

    private function skippedRow($sheet, $row, $reason = '')
    {
        $this->skippedRow[] = [
            'sheet' => $sheet,
            'row' => $row + 1,
            'reason' => str_replace('*', '', $reason),
        ];
    }

    private function getPositionID($stringPosition, $row)
    {
        $find = explode('>', $stringPosition);

        $count = count($find) - 1;
        $positions = $this->positions;
        foreach ($find as $key => $segment) {
            $jabatan = strtolower(str_replace(' ', '', $segment));
            if (isset($positions[$jabatan])) {
                $positions = $positions[$jabatan];
                if (isset($positions['child']) && $key < $count) {
                    $positions = $positions['child'];
                }
            } else {
                $this->skippedRow('Data Pegawai', $row, 'Jabatan "' . $segment . '" tidak ditemukan');
                return null;
            }
        }

        return isset($positions['id']) ? $positions['id'] : null;
    }

    // Filter out rows where all elements are null
    private function removeNullRows($data)
    {
        return array_filter($data, function ($row) {
            return !empty(array_filter($row, function ($value) {
                return !is_null($value);
            }));
        });
    }

    private function formatDate($stringDate, $sheet, $row, $colName)
    {
        $formatedDate = null;
        if (empty($stringDate)) return $formatedDate;

        // Regular expression to match the date format dd/mm/yyyy
        $pattern = '/^\d{2}\/\d{2}\/\d{4}$/';

        // Check if the value matches the pattern
        if (preg_match($pattern, $stringDate)) {
            $formatedDate = Carbon::createFromFormat('d/m/Y', $stringDate)->format('Y-m-d');
        } else {
            $this->skippedRow($sheet, $row, $colName . ' "' . $stringDate . '" tidak sesuai. Pastikan format tanggal dd/mm/yyyy dan excel cell format adalah TEXT');
        }
        return $formatedDate;
    }

    /**
     * Download .XLSX Template for Import Employee
     *
     * Download .XLSX Template for import ASN/NON-ASN/OUTSOURCE employee.
     * @group Employee
     * @authenticated
     * @urlParam type integer Refers to the type of employee 1=ASN 2=NON ASN 3=OUTSOURCE. Example: 1
     * @response 404 {"code": 404,"message": "File not found.","data": null}
     * @response 200 file downloaded
     */
    public function downloadTemplate($type)
    {
        if ($type == 1) {
            $fileName = "DATA PEGAWAI ASN";
        } else if ($type == 2) {
            $fileName = "DATA PEGAWAI NON ASN";
        } else if ($type == 3) {
            $fileName = "DATA PEGAWAI OUTSOURCE";
        } else {
            return $this->response(404, 'File not found.');
        }

        $filePath = ImportTemplate::path($fileName);

        if (file_exists($filePath)) {
            return response()->download($filePath, $fileName . '.xlsx');
        } else {
            return $this->response(404, 'File not found.');
        }
    }

    /**
     * Get List of Import Histories
     *
     * Retrieve all Import Histories.
     * @group Employee
     * Below are the Endpoints for retrieving import histories:
     * @authenticated
     * @queryParam type integer Refers to the type of employee 1=ASN 2=NON ASN 3=OUTSOURCE. Example: 1
     * @queryParam page integer Refers to the current page of results being displayed. Default is '1'. Example: 1
     * @queryParam limit integer Refers to the maximum number of items to be displayed per page. Defaults is '10'. Example: 10
     * @response 200 {"code": 200,"message": "success","data": [{"id":1,"user_id": 1,"name": "administrator","type": "add-bulk-asn","description": "Tambah Massal Data Pegawai ASN","status":"success","created_at": "2024-07-02 00:00:00"}],"pagination": {"total": 1,"count": 1,"per_page": 5,"current_page": 1,"total_pages": 1,"links": {"first_page": "http://localhost/api/employees/import-histories?page=1","last_page": "http://localhost/api/employees/import-histories?page=1","next_page": null,"prev_page": null}}}
     */
    public function getRiwayatImport(Request $request)
    {
        $messages = [
            'type.required' => 'Type harus diisi.',
            'type.numeric' => 'Type harus berupa angka.',
            'type.in' => 'Type tidak dikenali.',
            'page.numeric' => 'Page harus berupa angka.',
            'page.min' => 'Page minimal harus 1 atau lebih.',
            'limit.numeric' => 'Limit harus berupa angka.',
            'limit.min' => 'Limit minimal harus 1 atau lebih.',
        ];

        $request->validate([
            'type' => 'required|numeric|in:1,2,3',
            'page' => 'nullable|numeric|min:1',
            'limit' => 'nullable|numeric|min:1',
        ], $messages);

        if ($request->type == 1) {
            $type = "add-bulk-asn";
        } else if ($request->type == 2) {
            $type = "add-bulk-non-asn";
        } else if ($request->type == 3) {
            $type = "add-bulk-outsource";
        }

        $riwayat = DB::table('activity_logs as a')
            ->select('a.id', 'a.user_id', 'u.name', 'a.type', 'a.description', 'a.status', 'a.created_at')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.type', $type)
            ->orderBy('a.created_at', 'desc');

        if (is_null($request->limit)) {
            $riwayat = $riwayat->get();
            $message = (count($riwayat) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $riwayat);
        } else {
            $riwayat = $riwayat->paginate($request->limit)->withQueryString();
            $message = ($riwayat->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->paginateResponse(200, $message, $riwayat);
        }
    }

    /**
     * Download the error log of a specific import.
     * @group Employee
     * @authenticated
     * @queryParam id integer The ID of the import activity log.
     * @response 200 PDF File Downloaded
     * @response 400 {"code": 400, "message": "Hasil error import tidak ditemukan","data": null}
     */
    public function downloadImportErrorLog($id)
    {
        // Check if the provided ID is numeric
        if (!is_numeric($id)) {
            return $this->response(400, 'Hasil error import tidak ditemukan'); // Return a 400 response if the ID is not valid
        }

        // Retrieve the error log from the activity logs table
        $errorLog = DB::table('activity_logs')
            ->select('log', 'created_at')
            ->where('id', $id)
            ->where('status', 'failed')
            ->whereNotNull('log')
            ->first();

        // Check if the error log exists
        if (!$errorLog) {
            return $this->response(400, 'Hasil error import tidak ditemukan'); // Return a 400 response if the log is not found
        }

        // Extract the log and creation date from the retrieved error log
        $logData = $errorLog->log;
        $createdAt = $errorLog->created_at;

        // Define an array to convert month numbers to Indonesian month names
        $indonesianMonths = [
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

        // Format the date into Indonesian date format
        $day = date('d', strtotime($createdAt));
        $month = $indonesianMonths[date('n', strtotime($createdAt))];
        $year = date('Y', strtotime($createdAt));
        $formattedDate =  $day . ' ' . $month . ' ' . $year;

        // Set up temporary directory for PDF generation
        $temporaryDirectory = sys_get_temp_dir();

        // Load and configure the PDF with the error log data and formatted date
        $pdf = Pdf::loadview('imports/error', ['tanggal' => $formattedDate, 'errors' => json_decode($logData)]);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_paper("A4", "portrait");
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('fontDir', $temporaryDirectory);
        $pdf->set_option('fontCache', $temporaryDirectory);
        $pdf->set_option('tempDir', $temporaryDirectory);

        // Download the generated PDF with a formatted filename
        return $pdf->download('Hasil error import excel - ' . $formattedDate . '.pdf');
    }
}
