<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\CreateEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Employee\UpdateStatusRequest;
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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /** @var array<string, mixed> */
    protected array $posted;

    public function __construct(
        protected Request $request,
        protected EmployeeRepository $employeeRepository,
        protected EducationRepository $educationRepository,
        protected FamilyRepository $familyRepository,
        protected PositionRepository $positionRepository,
        protected GradeRepository $gradeRepository,
        protected TrainingRepository $trainingRepository,
        protected RecognitionRepository $recognitionRepository,
        protected TargetRepository $targetRepository,
        protected PerformanceRepository $performanceRepository,
        protected DisciplinaryRepository $disciplinaryRepository,
        protected LeaveRepository $leaveRepository,
        protected NoteRepository $noteRepository,
        protected CreditRepository $creditRepository,
        protected AssessmentRepository $assessmentRepository,
        protected CompetencyRepository $competencyRepository,
        protected TalentRepository $talentRepository,
    ) {
        $this->posted = $request->except(
            '_token',
            '_method',
            'educations',
            'positions',
            'grades',
            'families',
            'leaves',
            'notes',
            'credits',
            'assessments',
            'competencies',
            'talents',
            'structurals',
            'functionals',
            'technicals',
            'recognitions',
            'targets',
            'performances',
            'disciplinaries',
        );
    }

    public function index(): JsonResponse
    {
        $messages = [
            'page.numeric' => 'Page harus berupa angka.',
            'page.min' => 'Page minimal harus 1 atau lebih.',
            'limit.numeric' => 'Limit harus berupa angka.',
            'limit.min' => 'Limit minimal harus 1 atau lebih.',
        ];

        $this->request->validate([
            'page' => 'nullable|numeric|min:1',
            'limit' => 'nullable|numeric|min:1',
        ], $messages);

        $users = DB::table('users as u');
        $users->leftJoin('positions as p', 'u.position_id', '=', 'p.id');
        $users->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
        $users->leftJoin('employment_types as et', 'u.employment_type_id', '=', 'et.id');
        $users->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id');
        $users->select(
            'u.id',
            'u.photo_profile',
            DB::raw("
                CASE
                    WHEN u.title_prefix IS NULL && u.title_suffix IS NULL THEN u.name
                    WHEN u.title_prefix IS NOT NULL && u.title_suffix IS NULL THEN CONCAT(u.title_prefix, ' ', u.name)
                    WHEN u.title_prefix IS NULL && u.title_suffix IS NOT NULL THEN CONCAT(u.name, ' ', u.title_suffix)
                    ELSE CONCAT(u.title_prefix, ' ',u.name, ' ',u.title_suffix)
                END AS name
            "),
            'u.employee_id_number',
            'u.employee_registration_number',
            'p.name as position_name',
            'e.name as echelon_name',
            DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
            DB::raw("CONCAT(g.name, ' ', g.code) as grade_name"),
            DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
            'et.name as employment_type',
            'u.description',
        );

        $users->where(function ($query): void {
            $query->where('u.name', 'like', '%'.$this->request->search.'%')
                ->orWhere('u.employee_id_number', 'like', '%'.$this->request->search.'%');
        });

        if (! is_null($this->request->type)) {
            $users->where('u.type', $this->request->type);
        }

        if (! is_null($this->request->position_id)) {
            $users->where('u.position_id', $this->request->position_id);
        }

        if (! is_null($this->request->grade_id)) {
            $users->where('u.grade_id', $this->request->grade_id);
        }

        if (! is_null($this->request->echelon_id)) {
            $users->where('u.echelon_id', $this->request->echelon_id);
        }

        if (! is_null($this->request->employment_type_id)) {
            $users->where('u.employment_type_id', $this->request->employment_type_id);
        }

        if (! is_null($this->request->religion)) {
            $users->where('u.religion', $this->request->religion);
        }

        if (! is_null($this->request->employment_status)) {
            $users->where('u.employment_status', $this->request->employment_status);
        }

        if (! is_null($this->request->month_of_birth)) {
            $users->whereMonth('u.date_of_birth', $this->request->month_of_birth);
        }

        if (! is_null($this->request->gender)) {
            $users->where('u.gender', $this->request->gender);
        }

        if (isset($this->request->min_age)) {
            $maxDate = now()->subYears($this->request->min_age)->format('Y-m-d');
            $users->whereDate('u.date_of_birth', '<=', $maxDate);
        }

        if (isset($this->request->max_age)) {
            $minDate = now()->subYears($this->request->max_age)->format('Y-m-d');
            $users->whereDate('u.date_of_birth', '>=', $minDate);
        }

        if (! is_null($this->request->education_level)) {
            $users->where('u.education_level', $this->request->education_level);
        }

        $users->orderBy('u.employment_status', 'asc');
        $users->orderBy('u.echelon_id', 'asc');
        $users->orderBy('u.position_id', 'asc');

        if (is_null($this->request->limit)) {
            $users = $users->get();
            $message = count($users) < 1 ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            foreach ($users as $item) {
                $item->photo_profile = $this->getDocument($item->photo_profile, true);
            }

            return $this->response(200, $message, $users);
        }

        $users = $users->paginate($this->request->limit);
        $message = $users->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        foreach ($users->items() as $item) {
            $item->photo_profile = $this->getDocument($item->photo_profile, true);
        }

        return $this->paginateResponse(200, $message, $users);
    }

    public function show(): JsonResponse
    {
        $employee = $this->employeeRepository->getDetail($this->request->id);

        if (! $employee) {
            return $this->response(404, 'Pegawai tidak ditemukan.');
        }

        $educations = $this->educationRepository->getDetail($this->request->id);
        $families = $this->familyRepository->getDetail($this->request->id);
        $positions = $this->positionRepository->getDetail($this->request->id);
        $grades = $this->gradeRepository->getDetail($this->request->id);
        $structurals = $this->trainingRepository->getDetail($this->request->id, 1);
        $functionals = $this->trainingRepository->getDetail($this->request->id, 2);
        $technicals = $this->trainingRepository->getDetail($this->request->id, 3);
        $recognitions = $this->recognitionRepository->getDetail($this->request->id);
        $targets = $this->targetRepository->getDetail($this->request->id);
        $performances = $this->performanceRepository->getDetail($this->request->id);
        $disciplinaries = $this->disciplinaryRepository->getDetail($this->request->id);
        $leaves = $this->leaveRepository->getDetail($this->request->id);
        $notes = $this->noteRepository->getDetail($this->request->id);
        $credits = $this->creditRepository->getDetail($this->request->id);
        $assessments = $this->assessmentRepository->getDetail($this->request->id);
        $competencies = $this->competencyRepository->getDetail($this->request->id);
        $talents = $this->talentRepository->getDetail($this->request->id);
        $position = array_reverse((array) $this->positionRepository->getRecursivePosition($employee->position_id));

        $employee->position = $position;
        $employee->educations = $educations;
        $employee->families = $families;
        $employee->positions = $positions;
        $employee->grades = $grades;
        $employee->structurals = $structurals;
        $employee->functionals = $functionals;
        $employee->technicals = $technicals;
        $employee->recognitions = $recognitions;
        $employee->targets = $targets;
        $employee->performances = $performances;
        $employee->disciplinaries = $disciplinaries;
        $employee->leaves = $leaves;
        $employee->notes = $notes;
        $employee->credits = $credits;
        $employee->assessments = $assessments;
        $employee->competencies = $competencies;
        $employee->talents = $talents;

        return $this->response(200, 'success', $employee);
    }
    public function create(CreateEmployeeRequest $request)
    {
        $employmentType = DB::table('employment_types');
        $employmentType->where('id', $this->request->employment_type_id);
        $employmentType->where('status', true);
        $employmentType->where('type', $this->request->type);

        $employmentType = $employmentType->first();

        if (!$employmentType) {
            return $this->response(404, 'Jenis pegawai tidak ditemukan.');
        }

        if (isset($this->request->institution_id)) {
            $institution = DB::table('institutions');
            $institution->where('id', $this->request->institution_id);
            $institution = $institution->first();
            if (!$institution) {
                return $this->response(404, 'Institusi tidak ditemukan.');
            }
        }

        try {
            DB::beginTransaction();
            if (isset($this->posted['position_id'])) {

                $existsPosition = DB::table('positions')
                    ->where('id', $this->posted['position_id'])
                    ->first();

                if (!$existsPosition) {
                    return $this->response(404, 'Jabatan tidak ditemukan.');
                }

                $availablePosition = $existsPosition->available;

                if (!isset($this->posted['echelon_id']) && $availablePosition == 0) {
                    $existsPositionEchelon = DB::table('position_echelons')
                        ->where('position_id', $this->posted['position_id'])
                        ->count();

                    if ($existsPositionEchelon > 0) {
                        return $this->response(404, 'Masukan eselon terlebih dahulu untuk mengisi posisi ini.');
                    }
                }

                $countExistsPosition = DB::table('users')
                    ->where('position_id', $this->posted['position_id'])
                    ->whereIn('employment_status', [1, 6, 10])
                    ->whereNot('employment_type_id', 16) // Exclude employment_type_id = 16 (TNP2K) when count
                    ->where('id', '!=', $this->request->id);

                if (isset($this->posted['echelon_id']) && $availablePosition == 0) {
                    $existsPositionEchelon = DB::table('position_echelons')
                        ->where('position_id', $this->posted['position_id'])
                        ->where('echelon_id', $this->posted['echelon_id'])
                        ->first();

                    if (!$existsPositionEchelon) {
                        return $this->response(404, 'Jabatan untuk eselon ini tidak ditemukan!');
                    } else if ($existsPositionEchelon->available == 0) {
                        return $this->response(404, 'Jabatan tidak tersedia.');
                    }

                    $availablePosition = $existsPositionEchelon->available;

                    $countExistsPosition = $countExistsPosition->where('echelon_id', $this->posted['echelon_id']);
                }

                $countExistsPosition = $countExistsPosition->count();

                if ($availablePosition <= $countExistsPosition) {
                    return $this->response(404, 'Jabatan sudah terisi seluruhnya.');
                }
            }

            if ($this->request->hasFile('photo_profile')) {
                $path = $this->uploadDocument($this->request->file('photo_profile'), 'photo_profile', $this->request->employee_id_number);
                $this->posted['photo_profile'] = $path;
            }

            if ($this->request->hasFile('employee_id_card')) {
                $path = $this->uploadDocument($this->request->file('employee_id_card'), 'employee_id_card', $this->request->employee_id_number);
                $this->posted['employee_id_card'] = $path;
            }

            // Set position_id null if employment status is not 1 or 6 or 10
            if ($this->posted['employment_status'] != 1 && $this->posted['employment_status'] != 6 && $this->posted['employment_status'] != 10) {
                $this->posted['position_id'] = null;
            }

            $userId = DB::table('users')->insertGetIdTs($this->posted);

            // Insert Educations
            if (isset($this->request->educations)) {
                $educations = array();
                foreach ($this->request->educations as $education) {
                    if (isset($education['degree_document']) && is_file($education['degree_document'])) {
                        $education['degree_document'] = $this->uploadDocument($education['degree_document'], 'degree_document');
                    }
                    if (isset($education['study_assignment_letter']) && is_file($education['study_assignment_letter'])) {
                        $education['study_assignment_letter'] = $this->uploadDocument($education['study_assignment_letter'], 'study_assignment_letter');
                    }
                    if (isset($education['academic_title_letter']) && is_file($education['academic_title_letter'])) {
                        $education['academic_title_letter'] = $this->uploadDocument($education['academic_title_letter'], 'academic_title_letter');
                    }
                    $education['user_id'] = $userId;
                    array_push($educations, $education);
                }
                DB::table('user_educations')->insertTs($educations);
            }

            // Insert Families
            if (isset($this->request->families)) {
                $families = array();
                foreach ($this->request->families as $family) {
                    $family['user_id'] = $userId;
                    array_push($families, $family);
                }
                DB::table('user_families')->insertTs($families);
            }

            // Insert Leaves
            if (isset($this->request->leaves)) {
                $leaves = array();
                foreach ($this->request->leaves as $leave) {
                    if (isset($leave['letter']) && is_file($leave['letter'])) {
                        $leave['letter'] = $this->uploadDocument($leave['letter'], 'letter');
                    }
                    $leave['user_id'] = $userId;
                    array_push($leaves, $leave);
                }
                DB::table('user_leaves')->insertTs($leaves);
            }

            // Insert Notes
            if (isset($this->request->notes)) {
                $notes = array();
                foreach ($this->request->notes as $note) {
                    $note['user_id'] = $userId;
                    $note['giver_id'] = $this->request->user()->id;
                    array_push($notes, $note);
                }
                DB::table('user_notes')->insertTs($notes);
            }

            // Insert Scores
            if (isset($this->request->credits)) {
                $credits = array();
                foreach ($this->request->credits as $credit) {
                    $credit['user_id'] = $userId;
                    array_push($credits, $credit);
                }
                DB::table('user_credits')->insertTs($credits);
            }

            // Insert Assessments
            if (isset($this->request->assessments)) {
                $assessments = array();
                foreach ($this->request->assessments as $assessment) {
                    if (isset($assessment['assessment_document']) && is_file($assessment['assessment_document'])) {
                        $assessment['assessment_document'] = $this->uploadDocument($assessment['assessment_document'], 'assessment_document');
                    }
                    $assessment['user_id'] = $userId;
                    array_push($assessments, $assessment);
                }
                DB::table('user_assessments')->insertTs($assessments);
            }

            // Insert Competencies
            if (isset($this->request->competencies)) {
                $competencies = array();
                foreach ($this->request->competencies as $competency) {
                    if (isset($competency['competency_document']) && is_file($competency['competency_document'])) {
                        $competency['competency_document'] = $this->uploadDocument($competency['competency_document'], 'competency_document');
                    }
                    $competency['user_id'] = $userId;
                    array_push($competencies, $competency);
                }
                DB::table('user_competencies')->insertTs($competencies);
            }

            // Insert Talents
            if (isset($this->request->talents)) {
                $talents = array();
                foreach ($this->request->talents as $talent) {
                    if (isset($talent['talent_document']) && is_file($talent['talent_document'])) {
                        $talent['talent_document'] = $this->uploadDocument($talent['talent_document'], 'talent_document');
                    }
                    $talent['user_id'] = $userId;
                    array_push($talents, $talent);
                }
                DB::table('user_talents')->insertTs($talents);
            }

            DB::commit();
            return $this->response(200, 'Pegawai berhasil ditambah.');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::warning($th);
            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function update(UpdateEmployeeRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = DB::table('users')
                ->where('id', $this->request->id)
                ->first();

            if (!$user) {
                return $this->response(404, 'Pegawai tidak ditemukan.');
            }

            if (isset($this->posted['employee_id_card']) && is_file($this->posted['employee_id_card'])) {
                $this->posted['employee_id_card'] = $this->uploadDocument($this->posted['employee_id_card'], 'employee_id_card', $this->request->employee_id_number);
                unset($this->posted['delete_employee_id_card']);
            } else if ($this->posted['delete_employee_id_card'] == true) {
                $this->posted['employee_id_card'] = null;
                unset($this->posted['delete_employee_id_card']);
            } else {
                unset($this->posted['delete_employee_id_card']);
                unset($this->posted['employee_id_card']);
            }

            if (isset($this->posted['position_id']) && !is_null($this->posted['position_id']) && $this->posted['position_id'] != $user->position_id) {
                $existsPosition = DB::table('positions')
                    ->where('id', $this->posted['position_id'])
                    ->first();

                if (!$existsPosition) {
                    return $this->response(404, 'Jabatan tidak ditemukan.');
                }

                $availablePosition = $existsPosition->available;

                if (!isset($this->posted['echelon_id']) && $availablePosition == 0) {
                    $existsPositionEchelon = DB::table('position_echelons')
                        ->where('position_id', $this->posted['position_id'])
                        ->count();

                    if ($existsPositionEchelon > 0) {
                        return $this->response(404, 'Masukan eselon terlebih dahulu untuk mengisi posisi ini.');
                    }
                }

                $countExistsPosition = DB::table('users')
                    ->where('position_id', $this->posted['position_id'])
                    ->whereIn('employment_status', [1, 6, 10])
                    ->whereNot('employment_type_id', 16) // Exclude employment_type_id = 16 (TNP2K) when count
                    ->where('id', '!=', $this->request->id);

                if (isset($this->posted['echelon_id']) && $availablePosition == 0) {
                    $existsPositionEchelon = DB::table('position_echelons')
                        ->where('position_id', $this->posted['position_id'])
                        ->where('echelon_id', $this->posted['echelon_id'])
                        ->first();

                    if (!$existsPositionEchelon) {
                        return $this->response(404, 'Jabatan untuk eselon ini tidak ditemukan!');
                    } else if ($existsPositionEchelon->available == 0) {
                        return $this->response(404, 'Jabatan tidak tersedia.');
                    }

                    $availablePosition = $existsPositionEchelon->available;

                    $countExistsPosition = $countExistsPosition
                        ->where('echelon_id', $this->posted['echelon_id']);
                }

                $countExistsPosition = $countExistsPosition->count();

                if ($availablePosition <= $countExistsPosition) {
                    return $this->response(404, 'Jabatan sudah terisi seluruhnya.');
                }
            }

            if (isset($this->posted['photo_profile']) && is_file($this->posted['photo_profile'])) {
                $this->posted['photo_profile'] = $this->uploadDocument($this->posted['photo_profile'], 'photo_profile', $this->request->employee_id_number);
                unset($this->posted['delete_photo_profile']);
            } else if (isset($this->posted['delete_photo_profile']) && $this->posted['delete_photo_profile'] == true) {
                $this->posted['photo_profile'] = null;
                unset($this->posted['delete_photo_profile']);
            } else {
                unset($this->posted['delete_photo_profile']);
                unset($this->posted['photo_profile']);
            }

            $user = DB::table('users');
            $user->where('id', $this->request->id);

            // Set position_id null if employment status is not 1 or 6 or 10
            if ($this->posted['employment_status'] != 1 && $this->posted['employment_status'] != 6 && $this->posted['employment_status'] != 10) {
                $this->posted['position_id'] = null;
            }

            $user = $user->updateTs($this->posted);

            if (isset($this->request->educations)) {
                $educations = array();
                // Get existing data
                $userEducations = DB::table('user_educations')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userEducations = $userEducations->get();

                // Delete data
                $array1 = Arr::pluck($userEducations, 'id');
                $array2 = Arr::pluck($this->request->educations, 'id');
                $result = array_diff($array1, $array2);
                DB::table('user_educations')->whereIn('id', $result)->delete();

                foreach ($this->request->educations as $education) {
                    if (isset($education['degree_document']) && is_file($education['degree_document'])) {
                        $education['degree_document'] = $this->uploadDocument($education['degree_document'], 'degree_document');
                    } else if ($education['delete_degree_document'] == true) {
                        $education['degree_document'] = null;
                    } else {
                        unset($education['degree_document']);
                    }
                    unset($education['delete_degree_document']);

                    if (isset($education['study_assignment_letter']) && is_file($education['study_assignment_letter'])) {
                        $education['study_assignment_letter'] = $this->uploadDocument($education['study_assignment_letter'], 'study_assignment_letter');
                    } else if ($education['delete_study_assignment_letter'] == true) {
                        $education['study_assignment_letter'] = null;
                    } else {
                        unset($education['study_assignment_letter']);
                    }
                    unset($education['delete_study_assignment_letter']);

                    if (isset($education['academic_title_letter']) && is_file($education['academic_title_letter'])) {
                        $education['academic_title_letter'] = $this->uploadDocument($education['academic_title_letter'], 'academic_title_letter');
                    } else if ($education['delete_academic_title_letter'] == true) {
                        $education['academic_title_letter'] = null;
                    } else {
                        unset($education['academic_title_letter']);
                    }
                    unset($education['delete_academic_title_letter']);

                    if (isset($education['id'])) {
                        // Update existing data
                        DB::table('user_educations')->where('id', $education['id'])->updateTs($education);
                    } else if (isset($education)) {
                        // Insert new item
                        $education['user_id'] = $this->request->id;
                        array_push($educations, $education);
                    }
                }
                if (count($educations) > 0) {
                    DB::table('user_educations')->insertTs($educations);
                }
            } else {
                DB::table('user_educations')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->positions)) {
                // Get existing data
                $userPositions = DB::table('position_history_users')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userPositions = $userPositions->get();

                // Delete data
                $array1 = Arr::pluck($userPositions, 'id');
                $array2 = Arr::pluck($this->request->positions, 'id');
                $result = array_diff($array1, $array2);
                DB::table('position_history_users')->whereIn('id', $result)->delete();

                foreach ($this->request->positions as $position) {
                    if (isset($position['id'])) {
                        if (isset($position['decree_document']) && is_file($position['decree_document'])) {
                            $position['decree_document'] = $this->uploadDocument($position['decree_document'], 'decree_document');
                        } else if ($position['delete_decree_document'] == true) {
                            $position['decree_document'] = null;
                        } else {
                            unset($position['decree_document']);
                        }
                        unset($position['delete_decree_document']);

                        // Update existing data
                        DB::table('position_history_users')->where('id', $position['id'])->updateTs($position);
                    }
                }
            } else {
                DB::table('position_history_users')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->grades)) {
                // Get existing data
                $userGrades = DB::table('grade_history_users')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userGrades = $userGrades->get();

                // Delete data
                $array1 = Arr::pluck($userGrades, 'id');
                $array2 = Arr::pluck($this->request->grades, 'id');
                $result = array_diff($array1, $array2);
                DB::table('grade_history_users')->whereIn('id', $result)->delete();

                foreach ($this->request->grades as $grade) {
                    if (isset($grade['id'])) {
                        if (isset($grade['decree_document']) && is_file($grade['decree_document'])) {
                            $grade['decree_document'] = $this->uploadDocument($grade['decree_document'], 'decree_document');
                        } else if ($grade['delete_decree_document'] == true) {
                            $grade['decree_document'] = null;
                        } else {
                            unset($grade['decree_document']);
                        }
                        unset($grade['delete_decree_document']);

                        // Update existing data
                        DB::table('grade_history_users')->where('id', $grade['id'])->updateTs($grade);
                    }
                }
            } else {
                DB::table('grade_history_users')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->families)) {
                $families = array();
                // Get existing data
                $userFamilies = DB::table('user_families')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userFamilies = $userFamilies->get();

                // Delete data
                $array1 = Arr::pluck($userFamilies, 'id');
                $array2 = Arr::pluck($this->request->families, 'id');
                $result = array_diff($array1, $array2);
                DB::table('user_families')->whereIn('id', $result)->delete();

                foreach ($this->request->families as $family) {
                    if (isset($family['id'])) {
                        // Update existing data
                        DB::table('user_families')->where('id', $family['id'])->updateTs($family);
                    } else if (isset($family)) {
                        // Insert new item
                        $family['user_id'] = $this->request->id;
                        array_push($families, $family);
                    }
                }
                if (count($families) > 0) {
                    DB::table('user_families')->insertTs($families);
                }
            } else {
                DB::table('user_families')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->leaves)) {
                $leaves = array();
                // Get existing data
                $userLeaves = DB::table('user_leaves')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userLeaves = $userLeaves->get();

                // Delete data
                $array1 = Arr::pluck($userLeaves, 'id');
                $array2 = Arr::pluck($this->request->leaves, 'id');
                $result = array_diff($array1, $array2);
                DB::table('user_leaves')->whereIn('id', $result)->delete();

                foreach ($this->request->leaves as $leave) {
                    if (isset($leave['letter']) && is_file($leave['letter'])) {
                        $leave['letter'] = $this->uploadDocument($leave['letter'], 'letter');
                    } else if ($leave['delete_letter'] == true) {
                        $leave['letter'] = null;
                    } else {
                        unset($leave['letter']);
                    }
                    unset($leave['delete_letter']);

                    if (isset($leave['id'])) {
                        // Update existing data
                        DB::table('user_leaves')->where('id', $leave['id'])->updateTs($leave);
                    } else if (isset($leave)) {
                        // Insert new item
                        $leave['user_id'] = $this->request->id;
                        array_push($leaves, $leave);
                    }
                }
                if (count($leaves) > 0) {
                    DB::table('user_leaves')->insertTs($leaves);
                }
            } else {
                DB::table('user_leaves')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->notes)) {
                $notes = array();
                // Get existing data
                $userNotes = DB::table('user_notes')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userNotes = $userNotes->get();

                // Delete data
                $array1 = Arr::pluck($userNotes, 'id');
                $array2 = Arr::pluck($this->request->notes, 'id');
                $result = array_diff($array1, $array2);
                DB::table('user_notes')->whereIn('id', $result)->delete();

                foreach ($this->request->notes as $note) {
                    if (isset($note['id'])) {
                        // Update existing data
                        DB::table('user_notes')->where('id', $note['id'])->updateTs($note);
                    } else if (isset($note)) {
                        // Insert new item
                        $note['user_id'] = $this->request->id;
                        $note['giver_id'] = $this->request->user()->id;
                        array_push($notes, $note);
                    }
                }
                if (count($notes) > 0) {
                    DB::table('user_notes')->insertTs($notes);
                }
            } else {
                DB::table('user_notes')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->credits)) {
                $credits = array();
                // Get existing data
                $userCredits = DB::table('user_credits')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userCredits = $userCredits->get();

                // Delete data
                $array1 = Arr::pluck($userCredits, 'id');
                $array2 = Arr::pluck($this->request->credits, 'id');
                $result = array_diff($array1, $array2);
                DB::table('user_credits')->whereIn('id', $result)->delete();

                foreach ($this->request->credits as $credit) {
                    if (isset($credit['id'])) {
                        // Update existing data
                        DB::table('user_credits')->where('id', $credit['id'])->updateTs($credit);
                    } else if (isset($credit)) {
                        // Insert new item
                        $credit['user_id'] = $this->request->id;
                        array_push($credits, $credit);
                    }
                }
                if (count($credits) > 0) {
                    DB::table('user_credits')->insertTs($credits);
                }
            } else {
                DB::table('user_credits')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->assessments)) {
                $assessments = array();
                // Get existing data
                $userAssessments = DB::table('user_assessments')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userAssessments = $userAssessments->get();

                // Delete data
                $array1 = Arr::pluck($userAssessments, 'id');
                $array2 = Arr::pluck($this->request->assessments, 'id');
                $result = array_diff($array1, $array2);
                DB::table('user_assessments')->whereIn('id', $result)->delete();

                foreach ($this->request->assessments as $assessment) {
                    if (isset($assessment['assessment_document']) && is_file($assessment['assessment_document'])) {
                        $assessment['assessment_document'] = $this->uploadDocument($assessment['assessment_document'], 'assessment_document');
                    } else if ($assessment['delete_assessment_document'] == true) {
                        $assessment['assessment_document'] = null;
                    } else {
                        unset($assessment['assessment_document']);
                    }
                    unset($assessment['delete_assessment_document']);

                    if (isset($assessment['id'])) {
                        // Update existing data
                        DB::table('user_assessments')->where('id', $assessment['id'])->updateTs($assessment);
                    } else if (isset($assessment)) {
                        // Insert new item
                        $assessment['user_id'] = $this->request->id;
                        array_push($assessments, $assessment);
                    }
                }
                if (count($assessments) > 0) {
                    DB::table('user_assessments')->insertTs($assessments);
                }
            } else {
                DB::table('user_assessments')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->competencies)) {
                $competencies = array();
                // Get existing data
                $userCompetencies = DB::table('user_competencies')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userCompetencies = $userCompetencies->get();

                // Delete data
                $array1 = Arr::pluck($userCompetencies, 'id');
                $array2 = Arr::pluck($this->request->competencies, 'id');
                $result = array_diff($array1, $array2);
                DB::table('user_competencies')->whereIn('id', $result)->delete();

                foreach ($this->request->competencies as $competency) {
                    if (isset($competency['competency_document']) && is_file($competency['competency_document'])) {
                        $competency['competency_document'] = $this->uploadDocument($competency['competency_document'], 'competency_document');
                    } else if ($competency['delete_competency_document'] == true) {
                        $competency['competency_document'] = null;
                    } else {
                        unset($competency['competency_document']);
                    }
                    unset($competency['delete_competency_document']);

                    if (isset($competency['id'])) {
                        // Update existing data
                        DB::table('user_competencies')->where('id', $competency['id'])->updateTs($competency);
                    } else if (isset($competency)) {
                        // Insert new item
                        $competency['user_id'] = $this->request->id;
                        array_push($competencies, $competency);
                    }
                }
                if (count($competencies) > 0) {
                    DB::table('user_competencies')->insertTs($competencies);
                }
            } else {
                DB::table('user_competencies')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->talents)) {
                $talents = array();
                // Get existing data
                $userTalents = DB::table('user_talents')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userTalents = $userTalents->get();

                // Delete data
                $array1 = Arr::pluck($userTalents, 'id');
                $array2 = Arr::pluck($this->request->talents, 'id');
                $result = array_diff($array1, $array2);
                DB::table('user_talents')->whereIn('id', $result)->delete();

                foreach ($this->request->talents as $talent) {
                    if (isset($talent['talent_document']) && is_file($talent['talent_document'])) {
                        $talent['talent_document'] = $this->uploadDocument($talent['talent_document'], 'talent_document');
                    } else if ($talent['delete_talent_document'] == true) {
                        $talent['talent_document'] = null;
                    } else {
                        unset($talent['talent_document']);
                    }
                    unset($talent['delete_talent_document']);

                    if (isset($talent['id'])) {
                        // Update existing data
                        DB::table('user_talents')->where('id', $talent['id'])->updateTs($talent);
                    } else if (isset($talent)) {
                        // Insert new item
                        $talent['user_id'] = $this->request->id;
                        array_push($talents, $talent);
                    }
                }
                if (count($talents) > 0) {
                    DB::table('user_talents')->insertTs($talents);
                }
            } else {
                DB::table('user_talents')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->structurals)) {
                // Get existing data
                $userTrainings = DB::table('training_history_users')
                    ->leftJoin('training_histories', 'training_histories.id', '=', 'training_history_users.training_history_id')
                    ->where('training_history_users.user_id', $this->request->id)
                    ->where('training_histories.type', 1)
                    ->select('training_history_users.id');
                $userTrainings = $userTrainings->get();

                // Delete data
                $array1 = Arr::pluck($userTrainings, 'id');
                $array2 = Arr::pluck($this->request->structurals, 'id');
                $result = array_diff($array1, $array2);
                DB::table('training_history_users')->whereIn('id', $result)->delete();

                foreach ($this->request->structurals as $training) {
                    if (isset($training['id'])) {
                        if (isset($training['certificate']) && is_file($training['certificate'])) {
                            $training['certificate'] = $this->uploadDocument($training['certificate'], 'certificate');
                        } else if ($training['delete_certificate'] == true) {
                            $training['certificate'] = null;
                        } else {
                            unset($training['certificate']);
                        }
                        unset($training['delete_certificate']);

                        // Update existing data
                        DB::table('training_history_users')->where('id', $training['id'])->updateTs($training);
                    }
                }
            } else {
                DB::table('training_history_users')
                    ->leftJoin('training_histories', 'training_histories.id', '=', 'training_history_users.training_history_id')
                    ->where('training_history_users.user_id', $this->request->id)
                    ->where('training_histories.type', 1)
                    ->delete();
            }

            if (isset($this->request->functionals)) {
                // Get existing data
                $userTrainings = DB::table('training_history_users')
                    ->leftJoin('training_histories', 'training_histories.id', '=', 'training_history_users.training_history_id')
                    ->where('training_history_users.user_id', $this->request->id)
                    ->where('training_histories.type', 2)
                    ->select('training_history_users.id');
                $userTrainings = $userTrainings->get();

                // Delete data
                $array1 = Arr::pluck($userTrainings, 'id');
                $array2 = Arr::pluck($this->request->functionals, 'id');
                $result = array_diff($array1, $array2);
                DB::table('training_history_users')->whereIn('id', $result)->delete();

                foreach ($this->request->functionals as $training) {
                    if (isset($training['id'])) {
                        if (isset($training['certificate']) && is_file($training['certificate'])) {
                            $training['certificate'] = $this->uploadDocument($training['certificate'], 'certificate');
                        } else if ($training['delete_certificate'] == true) {
                            $training['certificate'] = null;
                        } else {
                            unset($training['certificate']);
                        }
                        unset($training['delete_certificate']);

                        // Update existing data
                        DB::table('training_history_users')->where('id', $training['id'])->updateTs($training);
                    }
                }
            } else {
                DB::table('training_history_users')
                    ->leftJoin('training_histories', 'training_histories.id', '=', 'training_history_users.training_history_id')
                    ->where('training_history_users.user_id', $this->request->id)
                    ->where('training_histories.type', 2)
                    ->delete();
            }

            if (isset($this->request->technicals)) {
                // Get existing data
                $userTrainings = DB::table('training_history_users')
                    ->leftJoin('training_histories', 'training_histories.id', '=', 'training_history_users.training_history_id')
                    ->where('training_history_users.user_id', $this->request->id)
                    ->where('training_histories.type', 3)
                    ->select('training_history_users.id');
                $userTrainings = $userTrainings->get();

                // Delete data
                $array1 = Arr::pluck($userTrainings, 'id');
                $array2 = Arr::pluck($this->request->technicals, 'id');
                $result = array_diff($array1, $array2);
                DB::table('training_history_users')->whereIn('id', $result)->delete();

                foreach ($this->request->technicals as $training) {
                    if (isset($training['id'])) {
                        if (isset($training['certificate']) && is_file($training['certificate'])) {
                            $training['certificate'] = $this->uploadDocument($training['certificate'], 'certificate');
                        } else if ($training['delete_certificate'] == true) {
                            $training['certificate'] = null;
                        } else {
                            unset($training['certificate']);
                        }
                        unset($training['delete_certificate']);

                        // Update existing data
                        DB::table('training_history_users')->where('id', $training['id'])->updateTs($training);
                    }
                }
            } else {
                DB::table('training_history_users')
                    ->leftJoin('training_histories', 'training_histories.id', '=', 'training_history_users.training_history_id')
                    ->where('training_history_users.user_id', $this->request->id)
                    ->where('training_histories.type', 3)
                    ->delete();
            }

            if (isset($this->request->recognitions)) {
                // Get existing data
                $userRecognitions = DB::table('recognition_history_users')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userRecognitions = $userRecognitions->get();

                // Delete data
                $array1 = Arr::pluck($userRecognitions, 'id');
                $array2 = Arr::pluck($this->request->recognitions, 'id');
                $result = array_diff($array1, $array2);
                DB::table('recognition_history_users')->whereIn('id', $result)->delete();
            } else {
                DB::table('recognition_history_users')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->targets)) {
                // Get existing data
                $userTargets = DB::table('target_history_users')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userTargets = $userTargets->get();

                // Delete data
                $array1 = Arr::pluck($userTargets, 'id');
                $array2 = Arr::pluck($this->request->targets, 'id');
                $result = array_diff($array1, $array2);
                DB::table('target_history_users')->whereIn('id', $result)->delete();

                foreach ($this->request->targets as $target) {
                    if (isset($target['id'])) {
                        // Update existing data
                        DB::table('target_history_users')->where('id', $target['id'])->updateTs($target);
                    }
                }
            } else {
                DB::table('target_history_users')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->performances)) {
                // Get existing data
                $userPerformances = DB::table('performance_history_users')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userPerformances = $userPerformances->get();

                // Delete data
                $array1 = Arr::pluck($userPerformances, 'id');
                $array2 = Arr::pluck($this->request->performances, 'id');
                $result = array_diff($array1, $array2);
                DB::table('performance_history_users')->whereIn('id', $result)->delete();

                foreach ($this->request->performances as $performance) {
                    if (isset($performance['id'])) {
                        // Update existing data
                        DB::table('performance_history_users')->where('id', $performance['id'])->updateTs($performance);
                    }
                }
            } else {
                DB::table('performance_history_users')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            if (isset($this->request->disciplinaries)) {
                // Get existing data
                $userDisciplinaries = DB::table('disciplinary_history_users')
                    ->where('user_id', $this->request->id)
                    ->select('id');
                $userDisciplinaries = $userDisciplinaries->get();

                // Delete data
                $array1 = Arr::pluck($userDisciplinaries, 'id');
                $array2 = Arr::pluck($this->request->disciplinaries, 'id');
                $result = array_diff($array1, $array2);
                DB::table('disciplinary_history_users')->whereIn('id', $result)->delete();

                foreach ($this->request->disciplinaries as $disciplinary) {
                    if (isset($disciplinary['id'])) {
                        // Update existing data
                        DB::table('disciplinary_history_users')->where('id', $disciplinary['id'])->updateTs($disciplinary);
                    }
                }
            } else {
                DB::table('disciplinary_history_users')
                    ->where('user_id', $this->request->id)
                    ->delete();
            }

            DB::commit();
            return $this->response(200, 'Pegawai berhasil diubah.');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::warning($th);
            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function status(UpdateStatusRequest $request)
    {
        $user = DB::table('users');
        $user->where('id', $this->request->id);
        $user = $user->first();
        if (!$user) {
            return $this->response(404, 'Mohon maaf, pegawai tidak ditemukan.');
        }

        // Set position_id null if employment status is not 1 or 6 or 10
        if ($this->posted['employment_status'] != 1 && $this->posted['employment_status'] != 6 && $this->posted['employment_status'] != 10) {
            $this->posted['position_id'] = null;
        }

        $query = DB::table('users');
        $query->where('id', $this->request->id);
        $query->updateTs($this->posted);
        return $this->response(200, 'Pegawai berhasil diupdate.');
    }

    public function delete()
    {
        $employee = DB::table('users')->select('id')->where('id', $this->request->id)->first();
        if (!$employee) {
            return $this->response(404, 'Pegawai tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            // Delete Employee's Families if exist
            DB::table('user_families')->where('user_id', $employee->id)->delete();

            // Delete Employee's Educations if exist
            DB::table('user_educations')->where('user_id', $employee->id)->delete();

            // Delete Employee's Assessments if exist
            DB::table('user_assessments')->where('user_id', $employee->id)->delete();

            // Delete Employee's Competencies if exist
            DB::table('user_competencies')->where('user_id', $employee->id)->delete();

            // Delete Employee's Credits if exist
            DB::table('user_credits')->where('user_id', $employee->id)->delete();

            // Delete Employee's Leaves if exist
            DB::table('user_leaves')->where('user_id', $employee->id)->delete();

            // Delete Employee's Notes if exist
            DB::table('user_notes')->where('user_id', $employee->id)->delete();

            // Delete Employee's Talents if exist
            DB::table('user_talents')->where('user_id', $employee->id)->delete();

            // Delete Employee Her/His self
            DB::table('users')->where('id', $employee->id)->delete();

            DB::commit();
            return $this->response(200, 'Pegawai berhasil dihapus.');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::warning($th);
            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function image($path = null)
    {
        abort_if(! $path || ! Storage::disk('s3')->exists($path), 404);

        $stream = Storage::disk('s3')->readStream($path);
        abort_if(! $stream, 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => Storage::disk('s3')->mimeType($path),
        ]);
    }
}
