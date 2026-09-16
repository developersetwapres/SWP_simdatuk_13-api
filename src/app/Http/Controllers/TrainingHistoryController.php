<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrainingHistory\CreateTrainingHistoryRequest;
use App\Http\Requests\TrainingHistory\UpdateTrainingHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TrainingHistoryController extends Controller
{
    public function __construct(protected Request $request) {}

    public function index(): JsonResponse
    {
        $messages = [
            'page.numeric' => 'Page harus berupa angka.',
            'page.min' => 'Page minimal harus 1 atau lebih.',
            'limit.numeric' => 'Limit harus berupa angka.',
            'limit.min' => 'Limit minimal harus 1 atau lebih.',
            'type.required' => 'Tipe tidak boleh kosong.',
            'type.in' => 'Tipe harus diantara 1, 2 atau 3.',
        ];

        $this->request->validate([
            'page' => 'nullable|numeric|min:1',
            'limit' => 'nullable|numeric|min:1',
            'type' => 'required|in:1,2,3',
        ], $messages);
        $this->request->limit = $this->request->limit ? $this->request->limit : 10;

        $trainingHistories = DB::table('training_histories as th');
        $trainingHistories->leftJoin('training_history_users as thu', 'th.id', '=', 'thu.training_history_id');
        $trainingHistories->select(
            'th.id',
            DB::raw("DATE_FORMAT(th.created_at, '%d-%m-%Y %H:%i:%s') as created_at"),
            'th.name',
            'th.period_month',
            'th.period_year',
            'th.start_date',
            'th.end_date',
            DB::raw('COUNT(thu.id) AS total'),
        );
        $trainingHistories->where('th.name', 'like', '%'.$this->request->search.'%');
        $trainingHistories->where('th.type', $this->request->type);
        $trainingHistories->orderBy('th.updated_at', 'desc');
        $trainingHistories->orderBy('th.created_at', 'desc');
        $trainingHistories->groupBy('th.id');
        $trainingHistories = $trainingHistories->paginate($this->request->limit);

        if ($trainingHistories->isEmpty()) {
            return $this->paginateResponse(200, 'Mohon maaf, data tidak ditemukan.', $trainingHistories);
        }

        return $this->paginateResponse(200, 'success', $trainingHistories);
    }

    public function create(CreateTrainingHistoryRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $trainingHistoryId = DB::table('training_histories')->insertGetIdTs($this->request->except('users'));

            if (isset($this->request->users)) {
                $users = [];

                foreach ($this->request->users as $user) {
                    if (isset($user['certificate']) && is_file($user['certificate'])) {
                        $user['certificate'] = $this->uploadDocument($user['certificate'], 'certificate');
                    } else {
                        $user['certificate'] = null;
                    }

                    $user['training_history_id'] = $trainingHistoryId;
                    $users[] = $user;
                }

                DB::table('training_history_users')->insertTs($users);
            }

            DB::commit();

            return $this->response(200, 'Pelatihan berhasil ditambah.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $checkTraining = DB::table('training_histories')->find($this->request->id);

        if (! $checkTraining) {
            return $this->response(404, 'Pelatihan tidak ditemukan.');
        }

        $trainingHistory = DB::table('training_histories as th')->where('th.id', $this->request->id);
        $trainingHistory->select(
            'th.id',
            'th.period_month',
            'th.period_year',
            'th.name',
            'th.reference_number',
            'th.type',
            'th.start_date',
            'th.end_date',
            'th.duration',
            'th.organizer',
            'th.link',
            'th.description',
            'th.level',
            'th.group_id',
        );

        if ($trainingHistory->first()->type == 3 && ! is_null($trainingHistory->first()->group_id)) {
            $trainingHistory->join('groups as rumpun', 'rumpun.id', '=', 'th.group_id');
            $trainingHistory->selectRaw('rumpun.name as group_name, NULL as `level_name`');
        } elseif ($trainingHistory->first()->type == (1 || 2) && ! is_null($trainingHistory->first()->level)) {
            $trainingHistory->join('training_levels as jenjang', 'th.level', '=', 'jenjang.id');
            $trainingHistory->selectRaw('jenjang.level_name, NULL as `group_name`');
        } else {
            $trainingHistory->selectRaw('NULL as `level_name`, NULL as `group_name`');
        }

        $trainingHistory = $trainingHistory->first();
        $trainingHistory->type = $trainingHistory->type == 1
            ? 'Pelatihan Struktural'
            : ($trainingHistory->type == 2
                ? 'Pelatihan Fungsional'
                : ($trainingHistory->type == 3 ? 'Pelatihan Teknis' : null));

        $users = DB::table('training_history_users as thu');
        $users->join('users as u', 'u.id', '=', 'thu.user_id');
        $users->where('thu.training_history_id', $trainingHistory->id);
        $users->select(
            'thu.id',
            'thu.user_id',
            DB::raw("CONCAT(COALESCE(u.title_prefix,''),' ',u.name,' ',COALESCE(u.title_suffix,'')) as name"),
            'u.employee_id_number',
            'thu.certificate',
            'thu.created_at',
        );
        $users = $users->get();

        foreach ($users as $user) {
            $user->certificate = $this->getDocument($user->certificate);
        }

        $trainingHistory->users = $users;

        return $this->response(200, 'success', $trainingHistory);
    }

    public function update(UpdateTrainingHistoryRequest $request): JsonResponse
    {
        try {
            $trainingHistory = DB::table('training_histories')
                ->where('id', $this->request->id)
                ->select('id')
                ->first();

            if (! $trainingHistory) {
                return $this->response(404, 'Pelatihan tidak ditemukan.');
            }

            DB::table('training_histories')
                ->where('id', $this->request->id)
                ->updateTs($this->request->except('users'));

            $users = [];

            if (isset($this->request->users)) {
                $trainingHistoryUsers = DB::table('training_history_users')
                    ->where('training_history_id', $this->request->id)
                    ->select('id')
                    ->get();
                $existingIds = Arr::pluck($trainingHistoryUsers, 'id');
                $submittedIds = Arr::pluck($this->request->users, 'id');
                $deletedIds = array_diff($existingIds, $submittedIds);
                DB::table('training_history_users')->whereIn('id', $deletedIds)->delete();

                foreach ($this->request->users as $user) {
                    if (isset($user['certificate']) && is_file($user['certificate'])) {
                        $user['certificate'] = $this->uploadDocument($user['certificate'], 'certificate');
                    } elseif ($user['delete_certificate'] == true) {
                        $user['certificate'] = null;
                    } else {
                        unset($user['certificate']);
                    }

                    unset($user['delete_certificate']);

                    if (! is_null($user['id'])) {
                        DB::table('training_history_users')->where('id', $user['id'])->updateTs($user);
                    } else {
                        $user['training_history_id'] = $this->request->id;
                        $users[] = $user;
                    }
                }

                if (count($users) > 0) {
                    DB::table('training_history_users')->insertTs($users);
                }
            }

            return $this->response(200, 'Pelatihan berhasil diupdate.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function delete(): JsonResponse
    {
        $history = DB::table('training_histories')
            ->select('id')
            ->where('id', $this->request->id)
            ->first();

        if (! $history) {
            return $this->response(404, 'Riwayat pelatihan tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            DB::table('training_histories')->where('id', $history->id)->delete();
            DB::commit();

            return $this->response(200, 'Riwayat pelatihan berhasil dihapus.');
        } catch (Throwable $throwable) {
            DB::rollBack();
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function structuralLevels(): JsonResponse
    {
        return $this->levels(1);
    }

    public function functionalLevels(): JsonResponse
    {
        return $this->levels(2);
    }

    public function technicalGroups(): JsonResponse
    {
        $this->validateListRequest();

        $groups = DB::table('groups');
        $groups->select('id', 'name', 'type', 'created_at');
        $groups->where('name', 'like', '%'.$this->request->search.'%');
        $groups->where('type', 2);
        $groups->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $groups = $groups->get();
            $message = count($groups) < 1 ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            foreach ($groups as $item) {
                $item->type = $item->type == 1 ? 'Rumpun Riwayat Pegawai' : 'Rumpun Pelatihan Teknis';
            }

            return $this->response(200, $message, $groups);
        }

        $groups = $groups->paginate($this->request->limit);
        $message = $groups->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        foreach ($groups as $item) {
            $item->type = $item->type == 1 ? 'Rumpun Riwayat Pegawai' : 'Rumpun Pelatihan Teknis';
        }

        return $this->paginateResponse(200, $message, $groups);
    }

    private function levels(int $type): JsonResponse
    {
        $this->validateListRequest();

        $levels = DB::table('training_levels');
        $levels->select('training_levels.id', 'training_levels.level_name', 'training_levels.level_type', 'training_levels.description');
        $levels->where('training_levels.level_name', 'like', '%'.$this->request->search.'%');
        $levels->where('training_levels.level_type', $type);
        $levels->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $levels = $levels->get();
            $message = count($levels) < 1 ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            foreach ($levels as $item) {
                $item->level_type = $item->level_type == 1 ? 'Jenjang Struktural' : 'Jenjang Fungsional';
            }

            return $this->response(200, $message, $levels);
        }

        $levels = $levels->paginate($this->request->limit);
        $message = $levels->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        foreach ($levels as $item) {
            $item->level_type = $item->level_type == 1 ? 'Jenjang Struktural' : 'Jenjang Fungsional';
        }

        return $this->paginateResponse(200, $message, $levels);
    }

    private function validateListRequest(): void
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
