<?php

namespace App\Http\Controllers;

use App\Http\Requests\TargetHistory\CreateTargetHistoryRequest;
use App\Http\Requests\TargetHistory\UpdateTargetHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TargetHistoryController extends Controller
{
    public function __construct(protected Request $request) {}

    public function index(): JsonResponse
    {
        $this->validateList();
        $this->request->limit = $this->request->limit ? $this->request->limit : 10;

        $histories = DB::table('target_histories as th');
        $histories->leftJoin('target_history_users as thu', 'th.id', '=', 'thu.target_history_id');
        $histories->select(
            'th.id',
            DB::raw("DATE_FORMAT(th.created_at, '%d-%m-%Y %H:%i:%s') as created_at"),
            'th.name',
            'th.period_month',
            'th.period_year',
            'th.appraisal_period',
            DB::raw('COUNT(thu.id) AS total'),
        );
        $histories->where('th.name', 'like', '%'.$this->request->search.'%');
        $histories->orderBy('th.updated_at', 'desc');
        $histories->orderBy('th.created_at', 'desc');
        $histories->groupBy('th.id');
        $histories = $histories->paginate($this->request->limit);

        return $this->paginateResponse(200, $histories->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success', $histories);
    }

    public function create(CreateTargetHistoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $historyId = DB::table('target_histories')->insertGetIdTs($this->request->except('users'));

            if (isset($this->request->users)) {
                $users = [];
                foreach ($this->request->users as $user) {
                    $user['target_history_id'] = $historyId;
                    $users[] = $user;
                }
                DB::table('target_history_users')->insertTs($users);
            }

            DB::commit();

            return $this->response(200, 'SKP berhasil ditambah.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $history = DB::table('target_histories')
            ->where('id', $this->request->id)
            ->select('id', 'period_month', 'period_year', 'name', 'appraisal_period', 'year')
            ->first();

        if (! $history) {
            return $this->response(404, 'SKP tidak ditemukan.');
        }

        $history->users = DB::table('target_history_users as thu')
            ->join('users as u', 'u.id', '=', 'thu.user_id')
            ->where('target_history_id', $history->id)
            ->select(
                'thu.id',
                'thu.employee_performance_predicate',
                'thu.organizational_performance_achievement',
                'thu.work_behavior_rating',
                'u.id as user_id',
                'u.name',
                'u.employee_id_number',
                'thu.created_at',
            )->get();

        return $this->response(200, 'success', $history);
    }

    public function update(UpdateTargetHistoryRequest $request): JsonResponse
    {
        $history = DB::table('target_histories')->where('id', $this->request->id)->select('id')->first();

        if (! $history) {
            return $this->response(404, 'SKP tidak ditemukan.');
        }

        DB::table('target_histories')->where('id', $this->request->id)->updateTs($this->request->except('users'));
        $users = [];

        if (isset($this->request->users)) {
            $existing = DB::table('target_history_users')->where('target_history_id', $this->request->id)->select('id')->get();
            DB::table('target_history_users')->whereIn('id', array_diff(Arr::pluck($existing, 'id'), Arr::pluck($this->request->users, 'id')))->delete();

            foreach ($this->request->users as $user) {
                if (! is_null($user['id'])) {
                    DB::table('target_history_users')->where('id', $user['id'])->updateTs($user);
                } else {
                    $user['target_history_id'] = $this->request->id;
                    $users[] = $user;
                }
            }

            if (count($users) > 0) {
                DB::table('target_history_users')->insertTs($users);
            }
        }

        return $this->response(200, 'SKP berhasil diupdate.');
    }

    public function delete(): JsonResponse
    {
        $history = DB::table('target_histories')->select('id')->where('id', $this->request->id)->first();

        if (! $history) {
            return $this->response(404, 'Riwayat SKP tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            DB::table('target_histories')->where('id', $history->id)->delete();
            DB::commit();

            return $this->response(200, 'Riwayat SKP berhasil dihapus.');
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    private function validateList(): void
    {
        $this->request->validate([
            'page' => 'nullable|numeric|min:1',
            'limit' => 'nullable|numeric|min:1',
        ], [
            'page.numeric' => 'Page harus berupa angka.',
            'page.min' => 'Page minimal harus 1 atau lebih.',
            'limit.numeric' => 'Limit harus berupa angka.',
            'limit.min' => 'Limit minimal harus 1 atau lebih.',
        ]);
    }
}
