<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
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
    ) {}

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
}
