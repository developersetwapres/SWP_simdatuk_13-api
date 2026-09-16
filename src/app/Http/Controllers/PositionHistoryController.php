<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionHistory\CreatePositionHistoryRequest;
use App\Http\Requests\PositionHistory\UpdatePositionHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PositionHistoryController extends Controller
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

        $positionHistories = DB::table('position_histories as ph');
        $positionHistories->leftJoin('position_history_users as phu', 'ph.id', '=', 'phu.position_history_id');
        $positionHistories->select(
            'ph.id',
            'ph.name',
            'ph.period_month',
            'ph.period_year',
            DB::raw("DATE_FORMAT(ph.created_at, '%d-%m-%Y %H:%i:%s') as created_at"),
            DB::raw('COUNT(phu.id) AS total'),
        );
        $positionHistories->where('ph.name', 'like', '%'.$this->request->search.'%');
        $positionHistories->orderBy('ph.updated_at', 'desc');
        $positionHistories->orderBy('ph.created_at', 'desc');
        $positionHistories->groupBy('ph.id');
        $positionHistories = $positionHistories->paginate($this->request->limit);

        if ($positionHistories->isEmpty()) {
            return $this->paginateResponse(200, 'Mohon maaf, data tidak ditemukan.', $positionHistories);
        }

        return $this->paginateResponse(200, 'success', $positionHistories);
    }

    public function create(CreatePositionHistoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $positionHistoryId = DB::table('position_histories')->insertGetIdTs($this->request->except('users'));

            if (isset($this->request->users)) {
                $users = [];

                foreach ($this->request->users as $user) {
                    if (isset($user['decree_document']) && is_file($user['decree_document'])) {
                        $user['decree_document'] = $this->uploadDocument($user['decree_document'], 'decree_document');
                    } else {
                        $user['decree_document'] = null;
                    }

                    $user['position_history_id'] = $positionHistoryId;
                    $users[] = $user;
                }

                DB::table('position_history_users')->insertTs($users);
            }

            DB::commit();

            return $this->response(200, 'Riwayat jabatan berhasil ditambah.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $positionHistory = DB::table('position_histories')
            ->where('id', $this->request->id)
            ->select('id', 'period_month', 'period_year', 'name', 'created_at')
            ->first();

        if (! $positionHistory) {
            return $this->response(404, 'Riwayat golongan tidak ditemukan.');
        }

        $users = DB::table('position_history_users as phu');
        $users->join('users as u', 'u.id', '=', 'phu.user_id');
        $users->leftJoin('groups as g', 'phu.group_id', '=', 'g.id');
        $users->leftJoin('decrees as tod', 'phu.type_of_decree', '=', 'tod.id');
        $users->leftJoin('decrees as totd', 'phu.type_of_termination_decree', '=', 'totd.id');
        $users->where('phu.position_history_id', $positionHistory->id);
        $users->select(
            'phu.id',
            'phu.user_id',
            'u.name',
            'u.employee_id_number',
            'phu.position',
            'g.id as group_id',
            'g.name as group_name',
            'phu.echelon',
            'phu.position_status',
            DB::raw("DATE_FORMAT(phu.effective_date, '%d-%m-%Y') as effective_date"),
            'phu.decree',
            'phu.decree_document',
            'phu.decree_number',
            'tod.id as type_decree_id',
            'tod.name as type_decree_name',
            'totd.id as type_termination_decree_id',
            'totd.name as type_termination_decree_name',
            DB::raw("DATE_FORMAT(phu.decree_date, '%d-%m-%Y') as decree_date"),
            DB::raw("DATE_FORMAT(phu.termination_date, '%d-%m-%Y') as termination_date"),
            'phu.termination_decree',
            'phu.termination_decree_number',
            DB::raw("DATE_FORMAT(phu.termination_decree_date, '%d-%m-%Y') as termination_decree_date"),
            'phu.status',
        );
        $users = $users->get();

        foreach ($users as $user) {
            $user->decree_document = $this->getDocument($user->decree_document);
        }

        $positionHistory->users = $users;

        return $this->response(200, 'success', $positionHistory);
    }

    public function update(UpdatePositionHistoryRequest $request): JsonResponse
    {
        $positionHistory = DB::table('position_histories')
            ->where('id', $this->request->id)
            ->select('id')
            ->first();

        if (! $positionHistory) {
            return $this->response(404, 'Riwayat jabatan tidak ditemukan.');
        }

        DB::table('position_histories')
            ->where('id', $this->request->id)
            ->updateTs($this->request->except('users'));

        $users = [];

        if (isset($this->request->users)) {
            $positionHistoryUsers = DB::table('position_history_users')
                ->where('position_history_id', $this->request->id)
                ->select('id')
                ->get();
            $existingIds = Arr::pluck($positionHistoryUsers, 'id');
            $submittedIds = Arr::pluck($this->request->users, 'id');
            $deletedIds = array_diff($existingIds, $submittedIds);
            DB::table('position_history_users')->whereIn('id', $deletedIds)->delete();

            foreach ($this->request->users as $user) {
                if (! is_null($user['id'])) {
                    DB::table('position_history_users')->where('id', $user['id'])->updateTs($user);
                } else {
                    $user['position_history_id'] = $this->request->id;
                    $users[] = $user;
                }
            }

            if (count($users) > 0) {
                DB::table('position_history_users')->insertTs($users);
            }
        }

        return $this->response(200, 'Riwayat jabatan berhasil diupdate.');
    }

    public function delete(): JsonResponse
    {
        $history = DB::table('position_histories')
            ->select('id')
            ->where('id', $this->request->id)
            ->first();

        if (! $history) {
            return $this->response(404, 'Riwayat jabatan tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            DB::table('position_histories')->where('id', $history->id)->delete();
            DB::commit();

            return $this->response(200, 'Riwayat jabatan berhasil dihapus.');
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }
}
