<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisciplinaryHistory\CreateDisciplinaryHistoryRequest;
use App\Http\Requests\DisciplinaryHistory\UpdateDisciplinaryHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DisciplinaryHistoryController extends Controller
{
    public function __construct(protected Request $request) {}

    public function index(): JsonResponse
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
        $this->request->limit = $this->request->limit ? $this->request->limit : 10;

        $histories = DB::table('disciplinary_histories as d');
        $histories->leftJoin('disciplinary_history_users as ud', 'd.id', '=', 'ud.disciplinary_history_id');
        $histories->select(
            'd.id',
            DB::raw("DATE_FORMAT(d.created_at, '%d-%m-%Y %H:%i:%s') as created_at"),
            'd.name',
            'd.period_month',
            'd.period_year',
            DB::raw('COUNT(ud.id) AS total'),
        );
        $histories->where('d.name', 'like', '%'.$this->request->search.'%');
        $histories->orderBy('d.updated_at', 'desc');
        $histories->orderBy('d.created_at', 'desc');
        $histories->groupBy('d.id');
        $histories = $histories->paginate($this->request->limit);

        return $this->paginateResponse(200, $histories->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success', $histories);
    }

    public function create(CreateDisciplinaryHistoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $historyId = DB::table('disciplinary_histories')->insertGetIdTs($this->request->except('users'));

            if (isset($this->request->users)) {
                $users = [];
                foreach ($this->request->users as $user) {
                    $user['disciplinary_history_id'] = $historyId;
                    $users[] = $user;
                }
                DB::table('disciplinary_history_users')->insertTs($users);
            }

            DB::commit();

            return $this->response(200, 'Hukuman disiplin berhasil ditambah.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $history = DB::table('disciplinary_histories')
            ->where('id', $this->request->id)
            ->select('id', 'period_month', 'period_year', 'name', 'created_at')
            ->first();

        if (! $history) {
            return $this->response(404, 'Hukuman disiplin tidak ditemukan.');
        }

        $history->users = DB::table('disciplinary_history_users as ud')
            ->join('users as u', 'u.id', '=', 'ud.user_id')
            ->leftJoin('disciplinaries as dt', 'ud.disciplinary_id', '=', 'dt.id')
            ->where('ud.disciplinary_history_id', $history->id)
            ->select(
                'ud.id',
                'ud.user_id',
                'u.name',
                'u.employee_id_number',
                'ud.grade',
                'ud.position',
                'dt.id as disciplinary_type_id',
                'dt.name as disciplinary_type_name',
                'dt.description as disciplinary_type_description',
                'dt.performance_allowance_deduction',
                'dt.performance_allowance_duration',
                'ud.decree_number',
                'ud.date_of_decree',
                'ud.start_date',
                'ud.end_date',
                'ud.authorizing_officer',
                'ud.name_of_authorizing_officer',
                'ud.description',
                'ud.created_at',
            )->get();

        return $this->response(200, 'success', $history);
    }

    public function update(UpdateDisciplinaryHistoryRequest $request): JsonResponse
    {
        $history = DB::table('disciplinary_histories')->where('id', $this->request->id)->select('id')->first();

        if (! $history) {
            return $this->response(404, 'Hukuman disiplin tidak ditemukan.');
        }

        DB::table('disciplinary_histories')->where('id', $this->request->id)->updateTs($this->request->except('users'));
        $users = [];

        if (isset($this->request->users)) {
            $existing = DB::table('disciplinary_history_users')->where('disciplinary_history_id', $this->request->id)->select('id')->get();
            DB::table('disciplinary_history_users')->whereIn('id', array_diff(Arr::pluck($existing, 'id'), Arr::pluck($this->request->users, 'id')))->delete();

            foreach ($this->request->users as $user) {
                if (! is_null($user['id'])) {
                    DB::table('disciplinary_history_users')->where('id', $user['id'])->updateTs($user);
                } else {
                    $user['disciplinary_history_id'] = $this->request->id;
                    $users[] = $user;
                }
            }

            if (count($users) > 0) {
                DB::table('disciplinary_history_users')->insertTs($users);
            }
        }

        return $this->response(200, 'Hukuman disiplin berhasil diupdate.');
    }

    public function delete(): JsonResponse
    {
        $history = DB::table('disciplinary_histories')->select('id')->where('id', $this->request->id)->first();

        if (! $history) {
            return $this->response(404, 'Riwayat hukuman disiplin tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            DB::table('disciplinary_histories')->where('id', $history->id)->delete();
            DB::commit();

            return $this->response(200, 'Riwayat hukuman disiplin berhasil dihapus.');
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }
}
