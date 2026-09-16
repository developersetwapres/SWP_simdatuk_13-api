<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Facades\DB;

class ComparisonRepository
{
    use Document;

    public function getUserByFilter(
        $page = null,
        $limit = null,
        $search = null,
        $groupId = null,
        $echelonId = null,
        $gradeId = null,
        $educationLevel = null,
        $maxAge = null,
        $disciplinaryId = null,
        $targetPredicateId = null,
        $cpnsYear = null,
        $gradeYear = null,
        $creditScore = null,
        $competencyPoint = null,
    ) {
        $users = DB::table('users as u')
            ->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id')
            ->leftJoin('grades as g', 'u.grade_id', '=', 'g.id')
            ->leftJoin('positions as p', 'u.position_id', '=', 'p.id')
            ->select(
                "u.id",
                "u.photo_profile",
                "u.name",
                "u.title_prefix",
                "u.title_suffix",
                "u.employee_id_number",
                "u.employee_registration_number",
                "p.name as position_name",
                "e.name as echelon_name",
                DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
                "g.name as grade_name",
                "g.code as grade_code",
                DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
                "u.type",
            );

        if (isset($search)) {
            $users->where(function ($query) use ($search) {
                $query->where('u.name', 'like', '%' . $search . '%')
                    ->orWhere('u.employee_id_number', 'like', '%' . $search . '%');
            });
        }

        if (isset($groupId)) {
            $users->join('position_history_users as phu', 'phu.user_id', '=', 'u.id');
            $users->where('phu.group_id', '=', $groupId);
        }

        if (isset($echelonId)) {
            $users->where('u.echelon_id', '=', $echelonId);
        }

        if (isset($gradeId)) {
            $users->where('u.grade_id', '=', $gradeId);
        }

        if (isset($educationLevel)) {
            $users->where('u.education_level', '=', $educationLevel);
        }

        if (isset($maxAge)) {
            $date = strtotime(date('Y-m-d') . ' -' . $maxAge . ' year');
            $users->where('u.date_of_birth', '>=', date('Y-m-d', $date));
        }

        if (isset($disciplinaryId)) {
            $users->join('disciplinary_history_users as dhu', 'dhu.user_id', '=', 'u.id');
            $users->where('dhu.disciplinary_id', '=', $disciplinaryId);
        }

        if (isset($targetPredicateId)) {
            $users->join('target_history_users as thu', 'thu.user_id', '=', 'u.id');
            $users->where('thu.employee_performance_predicate', '=', $targetPredicateId);
        }

        if (isset($cpnsYear)) {
            $date = strtotime(date('Y-m-d') . ' -' . $cpnsYear . ' year');
            $users->where('u.cpns_effective_date', '<=', date('Y-m-d', $date));
        }

        if (isset($gradeYear)) {
            $date = strtotime(date('Y-m-d') . ' -' . $gradeYear . ' year');
            $users->where('u.grade_effective_date', '<=', date('Y-m-d', $date));
        }

        if (isset($creditScore)) {
            $users->join('user_credits as uc', 'uc.user_id', '=', 'u.id');
            $users->where('uc.score', '=', $creditScore);
        }

        if (isset($competencyPoint)) {
            $users->join('user_competencies as uco', 'uco.user_id', '=', 'u.id');
            $users->where('uco.point', '=', $competencyPoint);
        }

        $users->whereIn('u.employment_status', [1, 6, 10]);
        $users->where('u.type', 1);
        $users->groupBy('u.id');
        $users->orderBy('u.employment_status', 'asc');
        $users->orderBy('u.echelon_id', 'asc');
        $users->orderBy('u.position_id', 'asc');

        if (isset($limit)) {
            $users = $users->paginate($limit);
        } else {
            $users = $users->get();
        }

        foreach ($users as $user) {
            $user->photo_profile = $this->getDocument($user->photo_profile, true);
        }

        return $users;
    }

    public function getUserByIds($userIds = [], $export = false)
    {
        $users = DB::table('users as u')
            ->select(
                'u.id as user_id',
                "u.photo_profile",
                'u.name as user_name',
                "u.title_prefix",
                "u.title_suffix",
                "u.employee_id_number",
                "u.employee_registration_number",
                "e.id as echelon_id",
                "e.name as echelon_name",
                "g.id as grade_id",
                "g.name as grade_name",
                "g.type as grade_type",
                "u.grade_effective_date",
                "u.cpns_effective_date",
                "u.education_level",
            )
            ->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id')
            ->leftJoin('grades as g', 'u.grade_id', '=', 'g.id')
            ->whereIn('u.id', $userIds)
            ->get();

        //ECHELON SCORES
        $echelonIds = $users->pluck('echelon_id')->toArray();

        // define custom ranks based on the rules
        usort($echelonIds, function ($a, $b) {
            $rank = function ($value) {
                if ($value >= 1 && $value <= 4) {
                    return 1;
                } elseif ($value == 9) {
                    return 2;
                } elseif ($value >= 5 && $value <= 8) {
                    return 3;
                } else {
                    return 4;
                }
            };

            return $rank($a) <=> $rank($b);
        });
        $uniqueEchelon = collect($echelonIds)
            ->unique()
            ->filter(function ($value) {
                return !is_null($value) && $value !== '';
            })
            ->values()
            ->toArray();
        $echelonCount = sizeof($uniqueEchelon);

        foreach ($users as $user) {
            if ($user->echelon_id) {
                $rank = array_search($user->echelon_id, $uniqueEchelon, true);
                $user->echelon_percentage = (int) (100 - ($rank * (100 / $echelonCount)));
            } else {
                $user->echelon_percentage = 0;
            }
        }
        //END OF ECHELON SCORES

        //GRADES SCORES
        $type1Grades = $users->filter(function ($item) {
            return $item->grade_type == 1;
        })->values();
        $type1Grades = $type1Grades->sortBy('grade_id')->pluck('grade_id')->unique()->values()->toArray();

        $type2Grades = $users->filter(function ($item) {
            return $item->grade_type == 2;
        })->values();
        $type2Grades = $type2Grades->sortBy('grade_id')->pluck('grade_id')->unique()->values()->toArray();

        foreach ($users as $user) {
            if ($user->grade_id) {
                if ($user->grade_type == 1) {
                    $rank = array_search($user->grade_id, $type1Grades, true);
                    $user->grade_percentage = (int) (100 - ($rank * (100 / sizeof($type1Grades))));
                } else if ($user->grade_type == 2) {
                    $rank = array_search($user->grade_id, $type2Grades, true);
                    $user->grade_percentage = (int) (100 - ($rank * (100 / sizeof($type2Grades))));
                }
            } else {
                $user->grade_percentage = 0;
            }
        }
        //END OF GRADES SCORES

        //GRADE EFFECTIVE DATE SCORES
        $gradeEffectiveDates = $users
            ->sortBy('grade_effective_date')
            ->pluck('grade_effective_date')
            ->unique()
            ->filter(function ($value) {
                return !is_null($value) && $value !== '';
            })
            ->values()
            ->toArray();

        foreach ($users as $user) {
            if ($user->grade_effective_date) {
                $rank = array_search($user->grade_effective_date, $gradeEffectiveDates, true);
                $user->grade_effective_date_percentage = (int) (100 - ($rank * (100 / sizeof($gradeEffectiveDates))));
            } else {
                $user->grade_effective_date_percentage = 0;
            }
        }
        //END OF GRADE EFFECTIVE DATE SCORES

        //CPNS EFFECTIVE DATE SCORES
        $CPNSEffectiveDates = $users
            ->sortBy('cpns_effective_date')
            ->pluck('cpns_effective_date')
            ->unique()
            ->filter(function ($value) {
                return !is_null($value) && $value !== '';
            })
            ->values()
            ->toArray();

        foreach ($users as $user) {
            if ($user->cpns_effective_date) {
                $rank = array_search($user->cpns_effective_date, $CPNSEffectiveDates, true);
                $user->cpns_effective_date_percentage = (int) (100 - ($rank * (100 / sizeof($CPNSEffectiveDates))));
            } else {
                $user->cpns_effective_date_percentage = 0;
            }
        }
        //END OF CPNS EFFECTIVE DATE SCORES

        //EDUCATION LEVEL SCORES
        $educationLevels = $users
            ->sortByDesc('education_level')
            ->pluck('education_level')
            ->unique()
            ->filter(function ($value) {
                return !is_null($value) && $value !== '';
            })
            ->values()
            ->toArray();

        foreach ($users as $user) {
            if ($user->education_level) {
                $rank = array_search($user->education_level, $educationLevels, true);
                $user->education_level_percentage = (int) (100 - ($rank * (100 / sizeof($educationLevels))));
            } else {
                $user->education_level_percentage = 0;
            }
        }
        //END OF EDUCATION LEVEL SCORES

        //USER NOTES
        foreach ($users as $user) {
            $user->notes = $this->getUserNotes($user->user_id);
        }
        //END OF USER NOTES

        //ASSESSMENT
        $userIds = $users->pluck('user_id')->toArray();

        $assessmentParams = [];
        $assessmentParams[] = now()->year;
        $assessmentParams = array_merge($assessmentParams, $userIds);

        $assessments = DB::select('
            SELECT
                user_id,
                point
            FROM
                user_assessments
            WHERE
                id IN (SELECT
                        MAX(id)
                        FROM user_assessments
                        WHERE
                        YEAR(event_date) = ?
                        AND user_id IN(' . implode(",", array_fill(0, count($userIds), '?')) . ')
                        GROUP BY user_id)
            ORDER BY
                point DESC', $assessmentParams);

        $assessmentPoints = collect($assessments)
            ->pluck('point')
            ->unique()
            ->filter(function ($value) {
                return !is_null($value) && $value !== '';
            })
            ->values()
            ->toArray();

        foreach ($users as $user) {
            $userAssessment = collect($assessments)->filter(function ($data) use ($user) {
                return $data->user_id == $user->user_id;
            })->values();

            if (sizeof($userAssessment)) {
                $user->assessment_point = $userAssessment[0]->point;
                $rank = array_search($userAssessment[0]->point, $assessmentPoints, true);
                $user->assessment_point_percentage = (int) (100 - ($rank * (100 / sizeof($assessmentPoints))));

                switch ($user->assessment_point) {
                    case 1:
                        $user->assessment_point_name = 'Kurang Memenuhi Syarat';
                        break;
                    case 2:
                        $user->assessment_point_name = 'Masih Memenuhi Syarat';
                        break;
                    case 3:
                        $user->assessment_point_name = 'Memenuhi Syarat';
                        break;
                }
            } else {
                $user->assessment_point = '-';
                $user->assessment_point_percentage = 0;
                $user->assessment_point_name = '-';
            }
        }
        //END OF ASSESSMENT

        //COMPETENCY
        $competencyParams = [];
        $competencyParams[] = now()->year;
        $competencyParams = array_merge($competencyParams, $userIds);

        $competencies = DB::select('
            SELECT
                user_id,
                point
            FROM
                user_competencies
            WHERE
                id IN (SELECT
                        MAX(id)
                        FROM user_competencies
                        WHERE
                        YEAR(event_date) = ?
                        AND user_id IN(' . implode(",", array_fill(0, count($userIds), '?')) . ')
                        GROUP BY user_id)
            ORDER BY
                point ASC', $competencyParams);

        $competencyPoints = collect($competencies)
            ->pluck('point')
            ->unique()
            ->filter(function ($value) {
                return !is_null($value) && $value !== '';
            })
            ->values()
            ->toArray();

        foreach ($users as $user) {
            $userCompetency = collect($competencies)->filter(function ($data) use ($user) {
                return $data->user_id == $user->user_id;
            })->values();

            if (sizeof($userCompetency)) {
                $user->competency_point = $userCompetency[0]->point;
                $rank = array_search($userCompetency[0]->point, $competencyPoints, true);
                $user->competency_point_percentage = (int) (100 - ($rank * (100 / sizeof($competencyPoints))));

                switch ($user->competency_point) {
                    case 1:
                        $user->competency_point_name = 'Lulus';
                        break;
                    case 2:
                        $user->competency_point_name = 'Tidak Lulus';
                        break;
                }
            } else {
                $user->competency_point = '-';
                $user->competency_point_percentage = 0;
                $user->competency_point_name = '-';
            }
        }
        //END OF COMPETENCY

        //TALENT POOL
        $talentParams = [];
        $talentParams[] = now()->year;
        $talentParams = array_merge($talentParams, $userIds);

        $talents = DB::select('
            SELECT
                user_id,
                point
            FROM
                user_talents
            WHERE
                id IN (SELECT
                        MAX(id)
                        FROM user_talents
                        WHERE
                        YEAR(event_date) = ?
                        AND user_id IN(' . implode(",", array_fill(0, count($userIds), '?')) . ')
                        GROUP BY user_id)
            ORDER BY
                point DESC', $talentParams);

        $talentPoints = collect($talents)
            ->pluck('point')
            ->unique()
            ->filter(function ($value) {
                return !is_null($value) && $value !== '';
            })
            ->values()
            ->toArray();

        foreach ($users as $user) {
            $userTalent = collect($talents)->filter(function ($data) use ($user) {
                return $data->user_id == $user->user_id;
            })->values();

            if (sizeof($userTalent)) {
                $user->talent_point = $userTalent[0]->point;
                $rank = array_search($userTalent[0]->point, $talentPoints, true);
                $user->talent_point_percentage = (int) (100 - ($rank * (100 / sizeof($talentPoints))));
                $user->talent_point_name = 'Kotak ' . $userTalent[0]->point;
            } else {
                $user->talent_point = '-';
                $user->talent_point_percentage = 0;
                $user->talent_point_name = '-';
            }
        }
        //END OF TALENT POOL

        $returnedData = [];
        foreach ($users as $user) {
            $educationName = '';

            switch ($user->education_level) {
                case 1:
                    $educationName = 'SD/Sederajat';
                    break;
                case 2:
                    $educationName = 'SLTP/Sederajat';
                    break;
                case 3:
                    $educationName = 'SLTA/Sederajat';
                    break;
                case 4:
                    $educationName = 'Diploma I/II';
                    break;
                case 5:
                    $educationName = 'Akademik/D3/S.Muda';
                    break;
                case 6:
                    $educationName = 'Diploma IV/Strata I';
                    break;
                case 7:
                    $educationName = 'Strata II';
                    break;
                case 8:
                    $educationName = 'Strata III';
                    break;
            }

            if ($export == true) {
                $photoProfile = $this->getDocument($user->photo_profile, true, true);
            } else {
                $photoProfile = $this->getDocument($user->photo_profile, true);
            }


            $returnedData[] = (object) [
                'id' => $user->user_id,
                'name' => $user->user_name,
                'title_prefix' => $user->title_prefix,
                'title_suffix' => $user->title_suffix,
                'photo_profile' => $photoProfile,
                'employee_id_number' => $user->employee_id_number,
                'employee_registration_number' => $user->employee_registration_number,
                'echelon' => (object) [
                    'id' => $user->echelon_id,
                    'name' => $user->echelon_name,
                    'percentage' => $user->echelon_percentage,
                ],
                'grade' => (object) [
                    'id' => $user->grade_id,
                    'name' => $user->grade_name,
                    'percentage' => $user->grade_percentage,
                ],
                'grade_effective_date' => (object) [
                    'name' => $user->grade_effective_date,
                    'percentage' => $user->grade_effective_date_percentage,
                ],
                'cpns_effective_date' => (object) [
                    'name' => $user->cpns_effective_date,
                    'percentage' => $user->cpns_effective_date_percentage,
                ],
                'education_level' => (object) [
                    'id' => $user->education_level,
                    'name' => $educationName,
                    'percentage' => $user->education_level_percentage,
                ],
                'assessment' => (object) [
                    'point' => $user->assessment_point,
                    'name' => $user->assessment_point_name,
                    'percentage' => $user->assessment_point_percentage,
                ],
                'competency' => (object) [
                    'point' => $user->competency_point,
                    'name' => $user->competency_point_name,
                    'percentage' => $user->competency_point_percentage,
                ],
                'talent' => (object) [
                    'point' => $user->talent_point,
                    'name' => $user->talent_point_name,
                    'percentage' => $user->talent_point_percentage,
                ],
                'notes' => (object) $user->notes,
            ];
        }

        return $returnedData;
    }

    public function getUserNotes($userId)
    {
        $notes = DB::table('user_notes as un')
            ->leftJoin('users as u', 'un.giver_id', '=', 'u.id')
            ->where('un.user_id', $userId)
            ->select(
                'un.id',
                'un.description',
                'u.name as giver_name',
                'un.created_at',
            )
            ->orderBy('un.created_at', 'desc');
        return $notes = $notes->get();
    }

    public function getDetailUsers($ids, $export = false)
    {
        $users = $this->getUsers($ids, $export);

        // Get Only Available Users
        $ids = array();
        foreach ($users as $item) {
            array_push($ids, $item->id);
        }

        // Collect data
        $positions = $this->getPositions($ids);
        $strukturals = $this->getTrainings($ids, 1);
        $fungsionals = $this->getTrainings($ids, 2);
        $tekniss = $this->getTrainings($ids, 3);
        $targets = $this->getTargets($ids);
        $disciplinaries = $this->getDisciplinaries($ids);
        $notes = $this->getNotes($ids);
        $assessments = $this->getAssesments($ids);
        $competencies = $this->getCompetencies($ids);
        $talents = $this->getTalents($ids);
        $data = [
            'users' => $users,
            'positions' => $positions,
            'strukturals' => $strukturals,
            'fungsionals' => $fungsionals,
            'tekniss' => $tekniss,
            'targets' => $targets,
            'disciplinaries' => $disciplinaries,
            'notes' => $notes,
            'assessments' => $assessments,
            'competencies' => $competencies,
            'talents' => $talents,
        ];
        return $data;
    }

    private function getUsers($ids, $export = false)
    {
        $users = DB::table('users as u');
        $users->leftJoin('positions as p', 'u.position_id', '=', 'p.id');
        $users->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id');
        $users->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
        $users->select(
            'u.id',
            DB::raw("
                CASE
                    WHEN u.title_prefix IS NULL && u.title_suffix IS NULL THEN u.name
                    WHEN u.title_prefix IS NOT NULL && u.title_suffix IS NULL THEN CONCAT(u.title_prefix, ' ', u.name)
                    WHEN u.title_prefix IS NULL && u.title_suffix IS NOT NULL THEN CONCAT(u.name, ' ', u.title_suffix)
                    ELSE CONCAT(u.title_prefix, ' ',u.name, ' ',u.title_suffix)
                END AS name
            "),
            'u.photo_profile',
            'p.name as position_name',
            'e.name as echelon_name',
            DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
            DB::raw("CONCAT(g.name, ' ', g.code) as grade_name"),
            DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
            'u.education_level',
            'u.education_name',
        );
        $users->whereIn('u.id', $ids);
        $users->orderBy('u.echelon_id', 'asc');
        $users->orderBy('u.grade_id', 'asc');
        $users = $users->get();
        foreach ($users as $item) {
            if ($export == true) {
                $item->photo_profile = $this->getDocument($item->photo_profile, true, true);
            } else {
                $item->photo_profile = $this->getDocument($item->photo_profile, true);
            }

            $educationLevel = [
                1 => 'SD/Sederajat',
                2 => 'SLTP/Sederajat',
                3 => 'SLTA/Sederajat',
                4 => 'Diploma I/II',
                5 => 'Akademik/D3/S.Muda',
                6 => 'Diploma IV/Strata I',
                7 => 'Strata II',
                8 => 'Strata III',
            ];

            $item->education_level = $educationLevel[$item->education_level] ?? '';
        }
        return $users;
    }

    private function getPositions($ids)
    {
        $positions = DB::table('users as u');
        $positions->select('phu.id', 'u.id as user_id', 'phu.position');
        $positions->join('position_history_users as phu', 'u.id', '=', 'phu.user_id');
        $positions->whereIn('u.id', $ids);
        $positions = $positions->get();
        return $this->groupingData($ids, $positions);
    }

    private function getTrainings($ids, $type)
    {
        $trainings = DB::table('users as u');
        $trainings->select('thu.id', 'u.id as user_id', 'th.name');
        $trainings->join('training_history_users as thu', 'u.id', '=', 'thu.user_id');
        $trainings->join('training_histories as th', 'thu.training_history_id', '=', 'th.id');
        $trainings->whereIn('u.id', $ids);
        $trainings->where('th.type', $type);
        $trainings = $trainings->get();
        return $this->groupingData($ids, $trainings);
    }

    private function getTargets($ids)
    {
        $targets = DB::table('users as u');
        $targets->select('thu.id', 'u.id as user_id', 'thu.work_behavior_rating', 'thu.employee_performance_predicate', 'thu.organizational_performance_achievement');
        $targets->join('target_history_users as thu', 'u.id', '=', 'thu.user_id');
        $targets->whereIn('u.id', $ids);
        $targets = $targets->get();
        foreach ($targets as $target) {
            $target->work_behavior_rating = $target->work_behavior_rating == 1 ? 'Diatas Ekspektasi' : ($target->work_behavior_rating == 2 ? 'Sesuai Ekspektasi' : ($target->work_behavior_rating == 3 ? 'Dibawah Ekspektasi' : ''));

            $performanceMapping = [
                1 => 'Sangat Baik',
                2 => 'Baik',
                3 => 'Butuh Perbaikan',
                4 => 'Kurang',
                5 => 'Sangat Kurang',
            ];

            $target->employee_performance_predicate = $performanceMapping[$target->employee_performance_predicate] ?? '';
        }
        return $this->groupingData($ids, $targets);
    }

    private function getDisciplinaries($ids)
    {
        $disciplinaries = DB::table('users as u');
        $disciplinaries->select(
            'dhu.id',
            'u.id as user_id',
            'd.description',
            DB::raw("DATE_FORMAT(dhu.start_date, '%d-%m-%Y') as start_date")
        );
        $disciplinaries->join('disciplinary_history_users as dhu', 'u.id', '=', 'dhu.user_id');
        $disciplinaries->join('disciplinaries as d', 'dhu.disciplinary_id', '=', 'd.id');
        $disciplinaries->whereIn('u.id', $ids);
        $disciplinaries = $disciplinaries->get();
        return $this->groupingData($ids, $disciplinaries);
    }

    private function getNotes($ids)
    {
        $notes = DB::table('user_notes as un');
        $notes->leftJoin('users as u1', 'u1.id', '=', 'un.user_id');
        $notes->leftJoin('users as u2', 'u2.id', '=', 'un.giver_id');
        $notes->select(
            'un.id',
            'u1.id as user_id',
            'u2.id as giver_id',
            'u2.name as giver_name',
            'un.description',
            'un.created_at',
        );
        $notes->whereIn('u1.id', $ids);
        $notes = $notes->get();
        return $this->groupingData($ids, $notes);
    }

    private function getAssesments($ids)
    {
        $assessments = DB::table('users as u');
        $assessments->select(
            'ua.id',
            'u.id as user_id',
            DB::raw("DATE_FORMAT(ua.event_date, '%d-%m-%Y') as event_date"),
            'ua.point'
        );
        $assessments->join('user_assessments as ua', 'u.id', '=', 'ua.user_id');
        $assessments->whereIn('u.id', $ids);
        $assessments = $assessments->get();
        foreach ($assessments as $assessment) {
            $assessment->point = $assessment->point == 1 ? 'Kurang Memenuhi Syarat' : ($assessment->point == 2 ? 'Masih Memenuhi Syarat' : ($assessment->point == 3 ? 'Memenuhi Syarat' : ''));
        }
        return $this->groupingData($ids, $assessments);
    }

    private function getCompetencies($ids)
    {
        $competencies = DB::table('users as u');
        $competencies->select(
            'uc.id',
            'u.id as user_id',
            DB::raw("DATE_FORMAT(uc.event_date, '%d-%m-%Y') as event_date"),
            'uc.point'
        );
        $competencies->join('user_competencies as uc', 'u.id', '=', 'uc.user_id');
        $competencies->whereIn('u.id', $ids);
        $competencies = $competencies->get();
        foreach ($competencies as $competency) {
            $competency->point = $competency->point == 1 ? 'Lulus' : (($competency->point == 2) ? 'Tidak Lulus' : '');
        }
        return $this->groupingData($ids, $competencies);
    }

    private function getTalents($ids)
    {
        $talents = DB::table('users as u');
        $talents->select(
            'ut.id',
            'u.id as user_id',
            DB::raw("DATE_FORMAT(ut.event_date, '%d-%m-%Y') as event_date"),
            'ut.point'
        );
        $talents->join('user_talents as ut', 'u.id', '=', 'ut.user_id');
        $talents->whereIn('u.id', $ids);
        $talents = $talents->get();
        return $this->groupingData($ids, $talents);
    }

    private function groupingData($ids, $data)
    {
        // Initialize the grouped data array with empty arrays for each ID
        foreach ($ids as $id) {
            $groupedData[$id] = [];
        }

        // Group the data based on IDs
        foreach (json_decode(json_encode($data), true) as $item) {
            if (in_array($item['user_id'], $ids)) {
                $groupedData[$item['user_id']][] = $item;
            }
        }
        return $groupedData;
    }
}
