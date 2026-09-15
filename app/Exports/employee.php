<?php

namespace App\Exports;

use App\Support\ExportLogo;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class employee implements FromView, WithDrawings, WithEvents
{
    private array $toggleField, $userIds;

    public function __construct(array $toggleField, array $userIds)
    {
        $this->toggleField = $toggleField;
        $this->userIds = $userIds;
    }

    public function columnFormats(): array
    {
        return [
            'F' => '#0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $alphabet = $event->sheet->getHighestDataColumn();
                $totalRow = $event->sheet->getHighestDataRow();
                $cellRange = 'A3:' . $alphabet . $totalRow;
                $event->sheet->getStyle($cellRange)->getAlignment()->setWrapText(true);
                $event->getDelegate()->getRowDimension('1')->setRowHeight('20');
                $event->sheet->styleCells(
                    'A2:' . $alphabet . '2',
                    [
                        //Set border Style
                        'borders' => [
                            'outline' => [
                                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE,
                            ],

                        ],

                        //Set font style
                        'font' => [
                            'name' => 'Inter',
                            'color' => ['rgb' => 'ffffff'],
                        ],

                        //Set background style
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => [
                                'argb' => '394346',
                            ],
                        ],

                    ]
                );
                $lastColumn = $event->sheet->getHighestColumn();
                $lastColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn);

                // Iterate through columns from L to the last column
                for ($col = 12; $col <= $lastColumnIndex; $col++) {
                    // Get the cell coordinate
                    $cellCoordinate = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '2';

                    // Check if there's data in the cell
                    if (!empty($event->sheet->getCell($cellCoordinate)->getValue())) {
                        // Apply the same style as in row 2, columns A to K
                        $event->sheet->duplicateStyle(
                            $event->sheet->getStyle('A2:B2'),
                            $cellCoordinate
                        );
                    }
                }
                $desiredWidth = 30; // Example width, adjust as needed
                for ($col = 1; $col <= $lastColumnIndex; $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $event->sheet->getColumnDimension($columnLetter)->setWidth($desiredWidth);

                    // Apply text alignment to top center
                    $event->sheet->getStyle($columnLetter . '2:' . $columnLetter . $totalRow)
                        ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
                }
            },

        ];
    }

    public function drawings(): \PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing|array
    {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('This is my logo');
        $drawing->setPath(ExportLogo::path());
        $drawing->setWidthAndHeight(200, 25);
        $drawing->setCoordinates('A1');
        return $drawing;
    }

    public function view(): View
    {
        $userId = collect($this->userIds);
        $userIdsChunk = $userId->chunk(200);
        $results = array();
        foreach ($userIdsChunk as $userId) {
            // Set the group_concat_max_len session variable
            DB::statement("SET SESSION group_concat_max_len = 10000");

            $users = DB::table('users');
            $users->leftJoin('positions', 'users.position_id', '=', 'positions.id');

            if (isset($this->toggleField['isName'])) {
                $users->addSelect('users.name');
            }
            if (isset($this->toggleField['isTitlePrefix'])) {
                $users->addSelect('users.title_prefix');
            }
            if (isset($this->toggleField['isTitleSuffix'])) {
                $users->addSelect('users.title_suffix');
            }
            if (isset($this->toggleField['isNameWithTitle'])) {
                $users->addSelect(DB::raw("CONCAT(COALESCE(users.title_prefix,''),' ',users.name,' ',COALESCE(users.title_suffix,'')) as name_with_title"));
            }
            if (isset($this->toggleField['isNip'])) {
                $users->addSelect('users.employee_id_number', 'users.employee_registration_number');
            }
            if (isset($this->toggleField['isBirthPlaceDate'])) {
                $users->addSelect('users.place_of_birth', DB::raw("DATE_FORMAT(users.date_of_birth, '%d-%m-%Y') as date_of_birth"));
            }
            if (isset($this->toggleField['isAge'])) {
                $users->addSelect(DB::raw("TIMESTAMPDIFF(YEAR, users.date_of_birth, CURDATE()) AS age"));
            }
            if (isset($this->toggleField['isReligion'])) {
                $users->addSelect('users.religion');
            }
            if (isset($this->toggleField['isGender'])) {
                $users->addSelect('users.gender');
            }
            if (isset($this->toggleField['isMaritalStatus'])) {
                $users->addSelect('users.marital_status');
            }
            if (isset($this->toggleField['isMarriageDate'])) {
                $users->addSelect('users.marriage_date');
            }
            if (isset($this->toggleField['isMarriageDescription'])) {
                $users->addSelect('users.marriage_description');
            }
            // if (isset($this->toggleField['isMarriageOtherNotes'])) {
            //     $users->addSelect('users.marriage_other_notes');
            // }
            if (isset($this->toggleField['isPosition'])) {
                $users->addSelect('users.position_id', 'positions.name as position_name', 'positions.type as position_type'); // position id to be used in get hierarchy below
            }
            if (isset($this->toggleField['isPositionDescription'])) {
                $users->addSelect('users.description');
            }
            if (isset($this->toggleField['isEchelons'])) {
                if ($this->toggleField['isPosition'] != 1) { // if isPosition not checked
                    $users->addSelect('users.position_id', 'positions.name as position_name', 'positions.type as position_type'); // Get position id to be used in get hierarchy below
                }
                $users->leftJoin('echelons', 'users.echelon_id', '=', 'echelons.id');
                $users->addSelect('echelons.name as echelons_name');
            }
            if (isset($this->toggleFieldBio['isFullPosition'])) {
                if ($this->toggleFieldBio['isPosition'] != 1) { // if isPosition not checked
                    $users->addSelect('users.position_id', 'positions.name as position_name', 'positions.type as position_type'); // Get position id to be used in get hierarchy below
                }
                if ($this->toggleFieldBio['isEchelons'] != 1) { // if isEchelons not checked
                    $users->addSelect('echelons.name as echelons_name'); // Get echelons to be used in get hierarchy below
                }
            }
            if (isset($this->toggleField['isGrade'])) {
                $users->leftJoin('grades as g', 'users.grade_id', '=', 'g.id');
                $users->addSelect(DB::raw("CONCAT(g.name, ' ', g.code) as grade_name"));
            }
            if (isset($this->toggleField['isEducation'])) {
                $users->addSelect('users.education_level', 'users.education_name', 'users.education_year');
            }
            if ($this->toggleField['isStudyProgram']) {
                $users->addSelect([
                    'study_program' => DB::table('user_educations as ue')
                        ->select('ue.major')
                        ->whereColumn('ue.user_id', 'users.id')
                        ->orderByDesc('ue.level')
                        ->orderByDesc('ue.year_of_graduation')
                        ->orderByDesc('ue.id')
                        ->limit(1),
                ]);
            }
            if (isset($this->toggleField['isEmployeeStatus'])) {
                $users->addSelect('users.employment_status');
            }
            if (isset($this->toggleField['isAgency'])) {
                $users->leftJoin('institutions as i', 'users.institution_id', '=', 'i.id');
                $users->addSelect('i.name as institution_name');
            }
            if (isset($this->toggleField['isNoWorker'])) {
                $users->addSelect('users.employee_id_card_number');
            }
            if (isset($this->toggleField['isGradeDuration'])) {
                $users->addSelect(DB::raw("
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
            if (isset($this->toggleField['isNPWP'])) {
                $users->addSelect('users.id_tax');
            }
            if (isset($this->toggleField['isCurrentAddress'])) {
                $users->addSelect('users.current_address');
            }
            if (isset($this->toggleField['isComplex'])) {
                $users->leftJoin('residences as r', 'users.residence_id', '=', 'r.id');
                $users->addSelect(DB::raw("
                IF(
                    users.residence_id = 1,
                    CONCAT('Luar Komplek', IF(users.residence_description IS NULL,'',CONCAT(' - ',users.residence_description))),
                    COALESCE(r.name,'-')
                ) as residence_name
                "));
            }
            if (isset($this->toggleField['isHomeNumber'])) {
                $users->addSelect('users.home_phone_number');
            }
            if (isset($this->toggleField['isPhoneNumber'])) {
                $users->addSelect('users.mobile_phone');
            }
            if (isset($this->toggleField['isOfficeAddress'])) {
                $users->addSelect('users.office_address');
            }
            if (isset($this->toggleField['isOfficeNumber'])) {
                $users->addSelect('users.office_phone_number');
            }
            if (isset($this->toggleField['isEmail'])) {
                $users->addSelect('users.email');
            }
            if (isset($this->toggleField['isGradeHistory'])) {
                $gradeHistorySubquery = DB::table('grade_history_users as ghu');
                $gradeHistorySubquery->join('grades', 'grades.id', '=', 'ghu.grade_id');
                $gradeHistorySubquery->select('ghu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', grades.name, ' ', grades.code, '</li>') SEPARATOR ' ') as grade_history"));
                $gradeHistorySubquery->whereIn('ghu.user_id', $userId);
                $gradeHistorySubquery->groupBy('ghu.user_id');
                $users->leftJoinSub($gradeHistorySubquery, 'grade_history', function ($join) {
                    $join->on('users.id', '=', 'grade_history.user_id');
                });
                $users->addSelect('grade_history.grade_history');
            }
            if (isset($this->toggleField['isPositionHistory'])) {
                $positionHistorySubquery = DB::table('position_history_users as phu');
                $positionHistorySubquery->select('phu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', phu.position,'</li>') SEPARATOR ' ') as position_history"));
                $positionHistorySubquery->whereIn('phu.user_id', $userId);
                $positionHistorySubquery->groupBy('phu.user_id');
                $users->leftJoinSub($positionHistorySubquery, 'position_history', function ($join) {
                    $join->on('users.id', '=', 'position_history.user_id');
                });
                $users->addSelect('position_history.position_history');
            }
            if (isset($this->toggleField['isTrainingStructural'])) {
                $trainingStructuralSubquery = DB::table('training_histories as t');
                $trainingStructuralSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
                $trainingStructuralSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, ', ', t.level, '</li>') SEPARATOR ' ') as structural_training_history"));
                $trainingStructuralSubquery->whereIn('ut.user_id', $userId);
                $trainingStructuralSubquery->where('t.type', 1);
                $trainingStructuralSubquery->groupBy('ut.user_id');

                $users->leftJoinSub($trainingStructuralSubquery, 'structural_training_history', function ($join) {
                    $join->on('users.id', '=', 'structural_training_history.user_id');
                });

                $users->addSelect('structural_training_history.structural_training_history');
            }

            if (isset($this->toggleField['isTrainingFunctional'])) {
                $trainingFunctionalSubquery = DB::table('training_histories as t');
                $trainingFunctionalSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
                $trainingFunctionalSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, ', ', t.level, '</li>') SEPARATOR ' ') as functional_training_history "));
                $trainingFunctionalSubquery->whereIn('ut.user_id', $userId);
                $trainingFunctionalSubquery->where('t.type', 2);
                $trainingFunctionalSubquery->groupBy('ut.user_id');

                $users->leftJoinSub($trainingFunctionalSubquery, 'functional_training_history', function ($join) {
                    $join->on('users.id', '=', 'functional_training_history.user_id');
                });

                $users->addSelect('functional_training_history.functional_training_history');
            }

            if (isset($this->toggleField['isTrainingTechnique'])) {
                $trainingTechnicSubquery = DB::table('training_histories as t');
                $trainingTechnicSubquery->join('training_history_users as ut', 't.id', '=', 'ut.training_history_id');
                $trainingTechnicSubquery->select('ut.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>', t.name, '</li>') SEPARATOR ' ') as technique_training_history"));
                $trainingTechnicSubquery->whereIn('ut.user_id', $userId);
                $trainingTechnicSubquery->where('t.type', 3);
                $trainingTechnicSubquery->groupBy('ut.user_id');

                $users->leftJoinSub($trainingTechnicSubquery, 'technique_training_history', function ($join) {
                    $join->on('users.id', '=', 'technique_training_history.user_id');
                });

                $users->addSelect('technique_training_history.technique_training_history');
            }
            if (isset($this->toggleField['isRecognition'])) {
                $recognitionSubquery = DB::table('recognition_histories as r');
                $recognitionSubquery->join('recognition_history_users as ur', 'r.id', '=', 'ur.recognition_history_id');
                $recognitionSubquery->join('recognitions', 'r.recognition_id', '=', 'recognitions.id');
                $recognitionSubquery->select('ur.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li>',recognitions.name,'</li>') SEPARATOR ' ') as recognition_history"));
                $recognitionSubquery->whereIn('ur.user_id', $userId);
                $recognitionSubquery->groupBy('ur.user_id');

                $users->leftJoinSub($recognitionSubquery, 'recognition_history', function ($join) {
                    $join->on('users.id', '=', 'recognition_history.user_id');
                });

                $users->addSelect('recognition_history.recognition_history');
            }
            if (isset($this->toggleField['isSKP'])) {
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

                $users->leftJoinSub($skpSubquery, 'skp_history', function ($join) {
                    $join->on('users.id', '=', 'skp_history.user_id');
                });

                $users->addSelect('skp_history.skp_history');
            }
            if (isset($this->toggleField['isCredit'])) {
                $creditSubQuery = DB::table('user_credits as uc');
                $creditSubQuery->select('uc.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> ',uc.position, ', Angka Kredit Terakhir : ', uc.score,'</li>') SEPARATOR ' ') as credit_history"));
                $creditSubQuery->whereIn('uc.user_id', $userId);
                $creditSubQuery->groupBy('uc.user_id');

                $users->leftJoinSub($creditSubQuery, 'credit_history', function ($join) {
                    $join->on('users.id', '=', 'credit_history.user_id');
                });

                $users->addSelect('credit_history.credit_history');
            }
            if (isset($this->toggleField['isPerformance'])) {
                $performanceSubQuery = DB::table('performance_histories as ph');
                $performanceSubQuery->join('performance_history_users as pfhu', 'ph.id', '=', 'pfhu.performance_history_id');
                $performanceSubQuery->select('pfhu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> ',ph.name, ', Nilai Prestasi Kerja : ', pfhu.work_performance_score,'</li>') SEPARATOR ' ') as performance_history"));
                $performanceSubQuery->whereIn('pfhu.user_id', $userId);
                $performanceSubQuery->groupBy('pfhu.user_id');

                $users->leftJoinSub($performanceSubQuery, 'performance_history', function ($join) {
                    $join->on('users.id', '=', 'performance_history.user_id');
                });

                $users->addSelect('performance_history.performance_history');
            }
            if (isset($this->toggleField['isEducationHistory'])) {
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

                $users->leftJoinSub($educationSubquery, 'education_history', function ($join) {
                    $join->on('users.id', '=', 'education_history.user_id');
                });

                $users->addSelect('education_history.education_history');
            }
            if (isset($this->toggleField['isDisciplinary'])) {
                $disciplinarySubquery = DB::table('disciplinary_history_users as dhu')
                    ->join('disciplinary_histories as dh', 'dhu.disciplinary_history_id', '=', 'dh.id')
                    ->join('disciplinaries as d', 'dhu.disciplinary_id', '=', 'd.id')
                    ->select('dhu.user_id', DB::raw("GROUP_CONCAT(CONCAT('<li> Golongan: ', dhu.grade, ' Posisi: ', dhu.position, ' (Periode: ', dh.period_month, ' ', dh.period_year, ', Tanggal Awal: ', DATE_FORMAT(dhu.start_date, '%d-%m-%Y'), ' Tanggal Akhir: ', DATE_FORMAT(dhu.end_date, '%d-%m-%Y'), ') Decree: ', dhu.decree_number, ', Kantor Otorisasi: ', dhu.authorizing_officer, ' Petugas: ', dhu.name_of_authorizing_officer, '</li>') SEPARATOR ' ') as disciplinary_history"))
                    ->whereIn('dhu.user_id', $userId)
                    ->groupBy('dhu.user_id');

                $users->leftJoinSub($disciplinarySubquery, 'disciplinary_history', function ($join) {
                    $join->on('users.id', '=', 'disciplinary_history.user_id');
                });

                $users->addSelect('disciplinary_history.disciplinary_history');
            }
            if (isset($this->toggleField['isFamilyHistory'])) {
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

                $users->leftJoinSub($familyHistory, 'family_history', function ($join) {
                    $join->on('users.id', '=', 'family_history.user_id');
                });

                $users->addSelect('family_history.family_history');
            }
            if (isset($this->toggleField['isLeave'])) {
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

                $users->leftJoinSub($leaveSubquery, 'leave_history', function ($join) {
                    $join->on('users.id', '=', 'leave_history.user_id');
                });

                $users->addSelect('leave_history.leave_history');
            }
            if (isset($this->toggleField['isAssessment'])) {
                $assessmentSubquery = DB::table('user_assessments as ua');
                $assessmentSubquery->select('ua.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Tanggal : ', DATE_FORMAT(ua.event_date, '%d-%m-%Y'), '
                        Point: ', CASE ua.point
                            WHEN 1 THEN 'Kurang Memenuhi Syarat'
                            WHEN 2 THEN 'Masih Memenuhi Syarat'
                            WHEN 3 THEN 'Memenuhi Syarat'
                        END, ' Organizer : ', ua.organizer,'</li>') SEPARATOR ' ') as assessment_history"));
                $assessmentSubquery->whereIn('ua.user_id', $userId);
                $assessmentSubquery->groupBy('ua.user_id');

                $users->leftJoinSub($assessmentSubquery, 'assessment_history', function ($join) {
                    $join->on('users.id', '=', 'assessment_history.user_id');
                });

                $users->addSelect('assessment_history.assessment_history');
            }
            if (isset($this->toggleField['isCompetency'])) {
                $assessmentSubquery = DB::table('user_competencies as ua');
                $assessmentSubquery->select('ua.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Tanggal : ', DATE_FORMAT(ua.event_date, '%d-%m-%Y'), '
                        Point: ', CASE ua.point
                            WHEN 1 THEN 'Lulus'
                            WHEN 2 THEN 'Tidak Lulus'
                        END, ' Organizer : ', ua.organizer,'</li>') SEPARATOR ' ') as competency_history"));
                $assessmentSubquery->whereIn('ua.user_id', $userId);
                $assessmentSubquery->groupBy('ua.user_id');

                $users->leftJoinSub($assessmentSubquery, 'competency_history', function ($join) {
                    $join->on('users.id', '=', 'competency_history.user_id');
                });

                $users->addSelect('competency_history.competency_history');
            }
            if (isset($this->toggleField['isTalentPool'])) {
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

                $users->leftJoinSub($assessmentSubquery, 'talent_pool_history', function ($join) {
                    $join->on('users.id', '=', 'talent_pool_history.user_id');
                });

                $users->addSelect('talent_pool_history.talent_pool_history');
            }
            if (isset($this->toggleField['isNotes'])) {
                $assessmentSubquery = DB::table('user_notes as un');
                $assessmentSubquery->join('users', 'un.giver_id', '=', 'users.id');
                $assessmentSubquery->select('un.user_id', DB::raw("GROUP_CONCAT( CONCAT('<li> Catatan : ', un.description, '
                        Pemberi catatan: ', users.name, ' Tanggal : ', DATE_FORMAT(un.created_at, '%d-%m-%Y %H:%i'),'</li>') SEPARATOR ' ') as notes"));
                $assessmentSubquery->whereIn('un.user_id', $userId);
                $assessmentSubquery->groupBy('un.user_id');

                $users->leftJoinSub($assessmentSubquery, 'notes', function ($join) {
                    $join->on('users.id', '=', 'notes.user_id');
                });

                $users->addSelect('notes.notes');
            }
            if (isset($this->toggleField['isEmployeeType'])) {
                $employmeeType = DB::table('employment_types as et');
                $employmeeType->join('users', 'et.id', '=', 'users.employment_type_id');
                $employmeeType->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as employee_type"));
                $employmeeType->whereIn('users.id', $userId);
                $employmeeType->groupBy('users.id');

                $users->leftJoinSub($employmeeType, 'employee_type', function ($join) {
                    $join->on('users.id', '=', 'employee_type.user_id');
                });

                $users->addSelect('employee_type.employee_type');
            }
            if (isset($this->toggleField['isEchelonDate'])) {
                $users->addSelect(DB::raw("DATE_FORMAT(users.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"));
            }
            if (isset($this->toggleField['isGradeDate'])) {
                $users->addSelect(DB::raw("DATE_FORMAT(users.grade_effective_date, '%d-%m-%Y') as grade_effective_date"));
            }
            if (isset($this->toggleField['isNoFamily'])) {
                $users->addSelect('users.family_registration_number');
            }
            if (isset($this->toggleField['isNIK'])) {
                $users->addSelect('users.id_number');
            }
            if (isset($this->toggleField['isStartDate'])) {
                $users->addSelect(DB::raw("DATE_FORMAT(users.cpns_effective_date, '%d-%m-%Y') as start_date"));
            }
            if (isset($this->toggleField['isEndDate'])) {
                if (!isset($this->toggleField['isEchelons'])) {
                    $users->leftJoin('echelons', 'users.echelon_id', '=', 'echelons.id');
                }
                $users->addSelect(DB::raw("
                            CASE
                                WHEN users.type = 1 && users.echelon_id IS NOT NULL && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL echelons.retirement_age YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                                WHEN users.type = 2 && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL 58 YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                                WHEN users.type = 3 && users.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(users.date_of_birth, INTERVAL 58 YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                                ELSE NULL
                            END AS retirement_effective_date
                        "));
            }
            if (isset($this->toggleField['isDatePNS'])) {
                $users->addSelect(DB::raw("DATE_FORMAT(users.pns_effective_date, '%d-%m-%Y') as pns_effective_date"));
            }
            if (isset($this->toggleField['isDateCPNS'])) {
                $users->addSelect(DB::raw("DATE_FORMAT(users.cpns_effective_date, '%d-%m-%Y') as cpns_effective_date"));
            }
            if (isset($this->toggleField['isDatePosition'])) {
                $users->addSelect(DB::raw("DATE_FORMAT(users.position_effective_date, '%d-%m-%Y') as position_effective_date"));
            }
            if (isset($this->toggleField['isOutsourcingType'])) {
                $outsourcingSubquery = DB::table('employment_types as et');
                $outsourcingSubquery->join('users', 'et.id', '=', 'users.employment_type_id');
                $outsourcingSubquery->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as outsource_type"));
                $outsourcingSubquery->where('et.type', 3);
                $outsourcingSubquery->whereIn('users.id', $userId);
                $outsourcingSubquery->groupBy('users.id');

                $users->leftJoinSub($outsourcingSubquery, 'outsource_type', function ($join) {
                    $join->on('users.id', '=', 'outsource_type.user_id');
                });

                $users->addSelect('outsource_type.outsource_type');
            }
            if (isset($this->toggleField['isAssistanceType'])) {
                $assistanceSubquery = DB::table('employment_types as et');
                $assistanceSubquery->join('users', 'et.id', '=', 'users.employment_type_id');
                $assistanceSubquery->select('users.id as user_id', DB::raw("GROUP_CONCAT( CONCAT('<li>',et.name,'</li>') SEPARATOR '') as assistance_type"));
                $assistanceSubquery->where('et.type', 2);
                $assistanceSubquery->whereIn('users.id', $userId);
                $assistanceSubquery->groupBy('users.id');

                $users->leftJoinSub($assistanceSubquery, 'assistance_type', function ($join) {
                    $join->on('users.id', '=', 'assistance_type.user_id');
                });

                $users->addSelect('assistance_type.assistance_type');
            }
            if (isset($this->toggleField['isOfficeEmail'])) {
                $users->addSelect('users.office_email');
            }
            if (isset($this->toggleField['isKarisu'])) {
                $users->addSelect('users.karisu_number');
            }
            if (isset($this->toggleField['isEmergencyContact'])) {
                $users->addSelect('users.emergency_contact');
            }
            if (isset($this->toggleField['isPensionCap'])) {
                if (!isset($this->toggleField['isEchelons'])) {
                    $users->leftJoin('echelons', 'users.echelon_id', '=', 'echelons.id');
                }
                $users->addSelect('echelons.retirement_age as pension_cap');
            }
            if (isset($this->toggleField['isWorkDuration'])) {
                $users->addSelect(DB::raw("
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
            $users->whereIn('users.id', $userId);
            $users->groupBy('users.id');
            $users = $users->get();
            $chunkResults = $users->map(function ($item) {
                if (isset($this->toggleField['isPosition']) || isset($this->toggleField['isEchelons'])) {
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
        return view('exports.employee', [
            'userData' => collect($results),
            'toggleField' => $this->toggleField,
        ]);
    }
}
