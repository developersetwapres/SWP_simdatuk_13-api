<?php

namespace App\Http\Controllers;

use App\Repositories\ComparisonRepository;
use Illuminate\Http\Request;

/**
 * @group Comparison
 * These endpoints allow you to perform compare data between employee.
 */
class ComparisonController extends Controller
{
    protected $comparisonRepository;
    protected $request;
    protected $posted;

    public function __construct(
        Request $request,
        ComparisonRepository $comparisonRepository,
    ) {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
        $this->comparisonRepository = $comparisonRepository;
    }

    /**
     * Get List of Employee Comparison
     *
     * Retrieve employees by parameters.
     * @authenticated
     * @queryParam group_id integer Refers to the id of groups. Example: 1
     * @queryParam echelon_id integer Refers to the id of echelons. Example: 1
     * @queryParam grade_id integer Refers to the id of grades. Example: 1
     * @queryParam education_level integer Refers to the level of education level employee. Example: 1
     * @queryParam max_age integer Refers to the max age of employee. Example: 1
     * @queryParam disciplinary_id integer Refers to the id of disciplinary_history. Example: 1
     * @queryParam target_predicate_id integer Refers to the employee_performance_predicate of target_history_users. Example: 1
     * @queryParam cpns_year integer Refers to the cpns_year of employee. Example: 1
     * @queryParam grade_year integer Refers to the date grade_effective_date of users. Example: 1
     * @queryParam credit_score integer Refers to the score of user_credits. Example: 1
     * @queryParam competency_point integer Refers to the point of user_competencies. Example: 1
     * @response 200
     */
    public function index()
    {
        $messages = [
            'page.numeric' => 'Page harus berupa angka.',
            'page.min' => 'Page minimal harus 1 atau lebih.',
            'limit.numeric' => 'Limit harus berupa angka.',
            'limit.min' => 'Limit minimal harus 1 atau lebih.',
            'group_id.numeric' => 'ID grade harus berupa angka.',
            'echelon_id.numeric' => 'ID eselon harus berupa angka.',
            'grade_id.numeric' => 'ID eselon harus berupa angka.',
            'education_level.numeric' => 'ID eselon harus berupa angka.',
            'max_age.numeric' => 'ID eselon harus berupa angka.',
            'disciplinary_id.numeric' => 'ID eselon harus berupa angka.',
            'target_predicate_id.numeric' => 'ID eselon harus berupa angka.',
            'cpns_year.numeric' => 'ID eselon harus berupa angka.',
            'grade_year.numeric' => 'ID eselon harus berupa angka.',
            'credit_score.numeric' => 'ID eselon harus berupa angka.',
            'competency_point.numeric' => 'ID eselon harus berupa angka.',
        ];

        $this->request->validate([
            'page' => 'nullable|numeric|min:1',
            'limit' => 'nullable|numeric|min:1',
            'group_id' => 'nullable|numeric',
            'echelon_id' => 'nullable|numeric',
            'grade_id' => 'nullable|numeric',
            'education_level' => 'nullable|numeric',
            'max_age' => 'nullable|numeric',
            'disciplinary_id' => 'nullable|numeric',
            'target_predicate_id' => 'nullable|numeric',
            'cpns_year' => 'nullable|numeric',
            'grade_year' => 'nullable|numeric',
            'credit_score' => 'nullable|numeric',
            'competency_point' => 'nullable|numeric',
        ], $messages);

        $users = $this->comparisonRepository->getUserByFilter(
            $this->request->page,
            $this->request->limit,
            $this->request->search,
            $this->request->group_id,
            $this->request->echelon_id,
            $this->request->grade_id,
            $this->request->education_level,
            $this->request->max_age,
            $this->request->disciplinary_id,
            $this->request->target_predicate_id,
            $this->request->cpns_year,
            $this->request->grade_year,
            $this->request->credit_score,
            $this->request->competency_point,
        );

        if (isset($this->request->limit)) {
            return $this->paginateResponse(200, 'success', $users);
        } else {
            return $this->response(200, 'success', $users);
        }
    }

    /**
     * Get Detail Employee of Comparison
     *
     * Below is for compare user by user id.
     * @authenticated
     * @bodyParam user_id int[] list of user_id' id. Example: [1,2]
     * @response 200
     */
    public function comparison()
    {
        $messages = [
            'user_id.required' => 'User ID tidak boleh kosong.',
            'user_id.array' => 'User ID harus berupa array.',
            'user_id.min' => 'User ID minimal 2 buah.',
            'user_id.max' => 'User ID maksimal 5 buah.',
            'user_id.*.required' => 'User ID tidak boleh kosong.',
            'user_id.*.numeric' => 'User ID harus berupa angka.',
        ];

        $this->request->validate([
            'user_id' => 'required|array|min:2|max:5',
            'user_id.*' => 'required|numeric',
        ], $messages);
        $data = $this->comparisonRepository->getDetailUsers($this->request->user_id);

        $groupedData = [];
        $array = array();

        foreach ($data['users'] as $user) {
            $userId = $user->id;
            $user = json_decode(json_encode($user), true);
            $groupedData[] = array_merge($user, [
                'positions' => $data['positions'][$userId] ?? [],
                'structurals' => $data['strukturals'][$userId] ?? [],
                'functionals' => $data['fungsionals'][$userId] ?? [],
                'technicals' => $data['tekniss'][$userId] ?? [],
                'targets' => $data['targets'][$userId] ?? [],
                'disciplinaries' => $data['disciplinaries'][$userId] ?? [],
                'notes' => $data['notes'][$userId] ?? [],
                'assessments' => $data['assessments'][$userId] ?? [],
                'competencies' => $data['competencies'][$userId] ?? [],
                'talents' => $data['talents'][$userId] ?? [],
            ]);
        }

        return $this->response(200, 'success', $groupedData);
    }

    /**
     * Get Detail Employee of Comparison Promotion
     *
     * Below is for compare user based for promotion by user id.
     * @authenticated
     * @bodyParam user_id int[] list of user_id' id. Example: [1,2]
     * @response 200
     */
    public function comparisonPromotion()
    {
        $messages = [
            'user_id.required' => 'User ID tidak boleh kosong.',
            'user_id.array' => 'User ID harus berupa array.',
            'user_id.min' => 'User ID minimal 2 buah.',
            'user_id.max' => 'User ID maksimal 5 buah.',
            'user_id.*.required' => 'User ID tidak boleh kosong.',
            'user_id.*.numeric' => 'User ID harus berupa angka.',
        ];

        $this->request->validate([
            'user_id' => 'required|array|min:2|max:5',
            'user_id.*' => 'required|numeric',
        ], $messages);

        $users = $this->comparisonRepository->getUserByIds($this->request->user_id);
        return $this->response(200, 'success', $users);
    }
}
