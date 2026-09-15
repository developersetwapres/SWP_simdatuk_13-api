<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeHistory\CreateGradeHistoryRequest;
use App\Http\Requests\GradeHistory\UpdateGradeHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GradeHistoryController extends Controller
{
    public function __construct(protected Request $request) {}

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
        $this->request->limit = $this->request->limit ? $this->request->limit : 10;

        $gradeHistories = DB::table('grade_histories as gh');
        $gradeHistories->leftJoin('grade_history_users as ghu', 'gh.id', '=', 'ghu.grade_history_id');
        $gradeHistories->select(
            'gh.id',
            DB::raw("DATE_FORMAT(gh.created_at, '%d-%m-%Y %H:%i:%s') as created_at"),
            'gh.name',
            'gh.period_month',
            'gh.period_year',
            DB::raw('COUNT(ghu.id) AS total'),
        );
        $gradeHistories->where('gh.name', 'like', '%'.$this->request->search.'%');
        $gradeHistories->orderBy('gh.updated_at', 'desc');
        $gradeHistories->orderBy('gh.created_at', 'desc');
        $gradeHistories->groupBy('gh.id');
        $gradeHistories = $gradeHistories->paginate($this->request->limit);

        if ($gradeHistories->isEmpty()) {
            return $this->paginateResponse(200, 'Mohon maaf, data tidak ditemukan.', $gradeHistories);
        }

        return $this->paginateResponse(200, 'success', $gradeHistories);
    }

    public function create(CreateGradeHistoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $gradeHistoryId = DB::table('grade_histories')->insertGetIdTs($this->request->except('users'));

            if (isset($this->request->users)) {
                $users = [];

                foreach ($this->request->users as $user) {
                    $user['status'] = 1;
                    $user['grade_history_id'] = $gradeHistoryId;
                    $users[] = $user;

                    if (isset($user['grade_id']) && isset($user['user_id'])) {
                        DB::table('users')
                            ->where('id', $user['user_id'])
                            ->updateTs([
                                'grade_id' => $user['grade_id'],
                                'grade_effective_date' => $user['effective_date'],
                            ]);
                    }
                }

                DB::table('grade_history_users')->insertTs($users);
            }

            DB::commit();

            return $this->response(200, 'Riwayat golongan berhasil ditambah.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $gradeHistory = DB::table('grade_histories')
            ->where('id', $this->request->id)
            ->select('id', 'period_month', 'period_year', 'name', 'created_at')
            ->first();

        if (! $gradeHistory) {
            return $this->response(404, 'Riwayat golongan tidak ditemukan.');
        }

        $users = DB::table('grade_history_users as ghu');
        $users->join('users as u', 'u.id', '=', 'ghu.user_id');
        $users->join('grades as g', 'ghu.grade_id', '=', 'g.id');
        $users->where('ghu.grade_history_id', $gradeHistory->id);
        $users->select(
            'ghu.id',
            'ghu.user_id',
            'u.name',
            'u.employee_id_number',
            'g.id as grade_id',
            'g.name as grade_name',
            'g.code as grade_code',
            DB::raw("DATE_FORMAT(ghu.effective_date, '%d-%m-%Y') as effective_date"),
            'ghu.decree_number',
            'ghu.status',
            'ghu.created_at',
        );
        $gradeHistory->users = $users->get();

        return $this->response(200, 'success', $gradeHistory);
    }

    public function update(UpdateGradeHistoryRequest $request): JsonResponse
    {
        $gradeHistory = DB::table('grade_histories')
            ->where('id', $this->request->id)
            ->select('id')
            ->first();

        if (! $gradeHistory) {
            return $this->response(404, 'Riwayat golongan tidak ditemukan.');
        }

        DB::table('grade_histories')
            ->where('id', $this->request->id)
            ->updateTs($this->request->except('users'));

        $users = [];

        if (isset($this->request->users)) {
            $gradeHistoryUsers = DB::table('grade_history_users')
                ->where('grade_history_id', $this->request->id)
                ->select('id')
                ->get();
            $existingIds = Arr::pluck($gradeHistoryUsers, 'id');
            $submittedIds = Arr::pluck($this->request->users, 'id');
            $deletedIds = array_diff($existingIds, $submittedIds);
            DB::table('grade_history_users')->whereIn('id', $deletedIds)->delete();

            foreach ($this->request->users as $user) {
                if (! is_null($user['id'])) {
                    DB::table('grade_history_users')->where('id', $user['id'])->updateTs($user);

                    if (isset($user['grade_id']) && isset($user['user_id'])) {
                        DB::table('users')
                            ->where('id', $user['user_id'])
                            ->updateTs([
                                'grade_id' => $user['grade_id'],
                                'grade_effective_date' => $user['effective_date'],
                            ]);
                    }
                } else {
                    $user['grade_history_id'] = $this->request->id;
                    $users[] = $user;

                    if (isset($user['grade_id']) && isset($user['user_id'])) {
                        DB::table('users')
                            ->where('id', $user['user_id'])
                            ->updateTs([
                                'grade_id' => $user['grade_id'],
                                'grade_effective_date' => $user['effective_date'],
                            ]);
                    }
                }
            }

            if (count($users) > 0) {
                DB::table('grade_history_users')->insertTs($users);
            }
        }

        return $this->response(200, 'Riwayat golongan berhasil diupdate.');
    }

    public function delete(): JsonResponse
    {
        $history = DB::table('grade_histories')
            ->select('id')
            ->where('id', $this->request->id)
            ->first();

        if (! $history) {
            return $this->response(404, 'Riwayat golongan tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            DB::table('grade_histories')->where('id', $history->id)->delete();
            DB::commit();

            return $this->response(200, 'Riwayat golongan berhasil dihapus.');
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }
}
