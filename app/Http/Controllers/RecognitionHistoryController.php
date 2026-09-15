<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecognitionHistory\CreateRecognitionHistoryRequest;
use App\Http\Requests\RecognitionHistory\UpdateRecognitionHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecognitionHistoryController extends Controller
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

        $recognitionHistories = DB::table('recognition_histories as rh');
        $recognitionHistories->leftJoin('recognition_history_users as rhu', 'rh.id', '=', 'rhu.recognition_history_id');
        $recognitionHistories->leftJoin('recognitions as r', 'rh.recognition_id', '=', 'r.id');
        $recognitionHistories->select(
            'rh.id',
            DB::raw("DATE_FORMAT(rh.created_at, '%d-%m-%Y %H:%i:%s') as created_at"),
            'r.name',
            'rh.period_month',
            'rh.period_year',
            'rh.awarding_institution',
            DB::raw('COUNT(rhu.id) AS total'),
        );
        $recognitionHistories->where('r.name', 'like', '%'.$this->request->search.'%');
        $recognitionHistories->orderBy('rh.updated_at', 'desc');
        $recognitionHistories->orderBy('rh.created_at', 'desc');
        $recognitionHistories->groupBy('rh.id');
        $recognitionHistories = $recognitionHistories->paginate($this->request->limit);

        if ($recognitionHistories->isEmpty()) {
            return $this->paginateResponse(200, 'Mohon maaf, data tidak ditemukan.', $recognitionHistories);
        }

        return $this->paginateResponse(200, 'success', $recognitionHistories);
    }

    public function create(CreateRecognitionHistoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $recognitionHistoryId = DB::table('recognition_histories')->insertGetIdTs($this->request->except('users'));

            if (isset($this->request->users)) {
                $users = [];

                foreach ($this->request->users as $user) {
                    $user['recognition_history_id'] = $recognitionHistoryId;
                    $users[] = $user;
                }

                DB::table('recognition_history_users')->insertTs($users);
            }

            DB::commit();

            return $this->response(200, 'Penghargaan berhasil ditambah.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $recognitionHistory = DB::table('recognition_histories as rh');
        $recognitionHistory->leftJoin('recognitions as r', 'rh.recognition_id', '=', 'r.id');
        $recognitionHistory->where('rh.id', $this->request->id);
        $recognitionHistory->select(
            'rh.id',
            'rh.period_month',
            'rh.period_year',
            'rh.recognition_id',
            'r.name as recognition_name',
            'rh.description',
            'rh.type_of_decree',
            'rh.decree_date',
            'rh.decree_number',
            'rh.decree_year',
            'rh.awarding_institution',
            'rh.created_at',
        );
        $recognitionHistory = $recognitionHistory->first();

        if (! $recognitionHistory) {
            return $this->response(404, 'Penghargaan tidak ditemukan.');
        }

        $users = DB::table('recognition_history_users as rhu');
        $users->join('users as u', 'u.id', '=', 'rhu.user_id');
        $users->where('rhu.recognition_history_id', $recognitionHistory->id);
        $users->select('rhu.id', 'rhu.user_id', 'u.name', 'u.employee_id_number', 'rhu.created_at');
        $recognitionHistory->users = $users->get();

        return $this->response(200, 'success', $recognitionHistory);
    }

    public function update(UpdateRecognitionHistoryRequest $request): JsonResponse
    {
        $recognitionHistory = DB::table('recognition_histories')
            ->where('id', $this->request->id)
            ->select('id')
            ->first();

        if (! $recognitionHistory) {
            return $this->response(404, 'Penghargaan tidak ditemukan.');
        }

        DB::table('recognition_histories')
            ->where('id', $this->request->id)
            ->updateTs($this->request->except('users'));

        $users = [];

        if (isset($this->request->users)) {
            $recognitionHistoryUsers = DB::table('recognition_history_users')
                ->where('recognition_history_id', $this->request->id)
                ->select('id')
                ->get();
            $existingIds = Arr::pluck($recognitionHistoryUsers, 'id');
            $submittedIds = Arr::pluck($this->request->users, 'id');
            $deletedIds = array_diff($existingIds, $submittedIds);
            DB::table('recognition_history_users')->whereIn('id', $deletedIds)->delete();

            foreach ($this->request->users as $user) {
                if (isset($user['id'])) {
                    DB::table('recognition_history_users')->where('id', $user['id'])->updateTs($user);
                } else {
                    $user['recognition_history_id'] = $this->request->id;
                    $users[] = $user;
                }
            }

            if (count($users) > 0) {
                DB::table('recognition_history_users')->insertTs($users);
            }
        }

        return $this->response(200, 'Penghargaan berhasil diupdate.');
    }

    public function delete(): JsonResponse
    {
        $history = DB::table('recognition_histories')
            ->select('id')
            ->where('id', $this->request->id)
            ->first();

        if (! $history) {
            return $this->response(404, 'Riwayat penghargaan tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            DB::table('recognition_histories')->where('id', $history->id)->delete();
            DB::commit();

            return $this->response(200, 'Riwayat penghargaan berhasil dihapus.');
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }
}
