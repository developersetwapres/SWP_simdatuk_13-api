<?php

namespace App\Http\Controllers;

use App\Http\Requests\PerformanceHistory\CreatePerformanceHistoryRequest;
use App\Http\Requests\PerformanceHistory\UpdatePerformanceHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PerformanceHistoryController extends Controller
{
    public function __construct(protected Request $request) {}

    public function index(): JsonResponse
    {
        $this->validateList();
        $this->request->limit = $this->request->limit ? $this->request->limit : 10;

        $histories = DB::table('performance_histories as ph');
        $histories->leftJoin('performance_history_users as phu', 'ph.id', '=', 'phu.performance_history_id');
        $histories->select(
            'ph.id',
            DB::raw("DATE_FORMAT(ph.created_at, '%d-%m-%Y %H:%i:%s') as created_at"),
            'ph.name',
            'ph.period_month',
            'ph.period_year',
            'ph.performance_period',
            DB::raw('COUNT(phu.id) AS total'),
        );
        $histories->where('ph.name', 'like', '%'.$this->request->search.'%');
        $histories->orderBy('ph.updated_at', 'desc');
        $histories->orderBy('ph.created_at', 'desc');
        $histories->groupBy('ph.id');
        $histories = $histories->paginate($this->request->limit);

        return $this->paginateResponse(200, $histories->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success', $histories);
    }

    public function create(CreatePerformanceHistoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $historyId = DB::table('performance_histories')->insertGetIdTs($this->request->except('users'));

            if (isset($this->request->users)) {
                $users = [];
                foreach ($this->request->users as $user) {
                    $user['performance_history_id'] = $historyId;
                    $users[] = $user;
                }
                DB::table('performance_history_users')->insertTs($users);
            }

            DB::commit();

            return $this->response(200, 'PPK berhasil ditambahkan.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $history = DB::table('performance_histories')
            ->where('id', $this->request->id)
            ->select('id', 'name', 'performance_period', 'period_year', 'period_month')
            ->first();

        if (! $history) {
            return $this->response(404, 'PPK tidak ditemukan.');
        }

        $history->users = DB::table('performance_history_users as phu')
            ->join('users as u', 'u.id', '=', 'phu.user_id')
            ->where('performance_history_id', $history->id)
            ->select('phu.id', 'phu.user_id', 'u.name', 'u.employee_id_number', 'phu.work_performance_score', 'phu.description', 'phu.created_at')
            ->get();

        return $this->response(200, 'success', $history);
    }

    public function update(UpdatePerformanceHistoryRequest $request): JsonResponse
    {
        $history = DB::table('performance_histories')->where('id', $this->request->id)->select('id')->first();

        if (! $history) {
            return $this->response(404, 'PPK tidak ditemukan.');
        }

        DB::table('performance_histories')->where('id', $this->request->id)->updateTs($this->request->except('users'));
        $users = [];

        if (isset($this->request->users)) {
            $existing = DB::table('performance_history_users')->where('performance_history_id', $this->request->id)->select('id')->get();
            DB::table('performance_history_users')->whereIn('id', array_diff(Arr::pluck($existing, 'id'), Arr::pluck($this->request->users, 'id')))->delete();

            foreach ($this->request->users as $user) {
                if (! is_null($user['id'])) {
                    DB::table('performance_history_users')->where('id', $user['id'])->updateTs($user);
                } else {
                    $user['performance_history_id'] = $this->request->id;
                    $users[] = $user;
                }
            }

            if (count($users) > 0) {
                DB::table('performance_history_users')->insertTs($users);
            }
        }

        return $this->response(200, 'PPK berhasil diupdate.');
    }

    public function delete(): JsonResponse
    {
        $history = DB::table('performance_histories')->select('id')->where('id', $this->request->id)->first();

        if (! $history) {
            return $this->response(404, 'Riwayat PPK tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            DB::table('performance_histories')->where('id', $history->id)->delete();
            DB::commit();

            return $this->response(200, 'Riwayat PPK berhasil dihapus.');
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
