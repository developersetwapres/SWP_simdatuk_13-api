<?php

namespace App\Http\Controllers;

use App\Http\Requests\Position\CreatePositionRequest;
use App\Http\Requests\Position\UpdatePositionRequest;
use App\Repositories\PositionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PositionController extends Controller
{
    /** @var array<string, mixed> */
    protected array $posted;

    public function __construct(
        protected Request $request,
        protected PositionRepository $positionRepository,
    ) {
        $this->posted = $request->except('_token', '_method');
    }

    public function index(): JsonResponse
    {
        $messages = [
            'page.numeric' => 'Page harus berupa angka.',
            'page.min' => 'Page minimal harus 1 atau lebih.',
            'limit.numeric' => 'Limit harus berupa angka.',
            'limit.min' => 'Limit minimal harus 1 atau lebih.',
            'type.regex' => 'Format type tidak sesuai.',
        ];

        $this->request->validate([
            'page' => 'nullable|numeric|min:1',
            'limit' => 'nullable|numeric|min:1',
            'type' => 'nullable|regex:/^\d+(,\d+)*$/',
        ], $messages);

        try {
            $positions = DB::table('positions')
                ->select(
                    'positions.id',
                    'positions.name',
                    'positions.type',
                    'positions.parent_id',
                )
                ->orderBy('positions.id', 'ASC');

            if (isset($this->request->type)) {
                $positions->whereIn('positions.type', explode(',', $this->request->type));
            }

            if (! is_null($this->request->search)) {
                $positions->where('positions.name', 'LIKE', '%'.$this->request->search.'%');
            }

            if ($this->request->filter_parent === true || $this->request->filter_parent === 'true') {
                $positions->where('parent_id', $this->request->parent_id);
            }

            $positions = $positions->get();

            $dupePositions = [];
            $originalPositions = unserialize(serialize($positions));

            foreach ($positions as $position) {
                $position->type = [
                    'id' => $position->type,
                    'name' => $position->type == 1 ? 'Struktural' : ($position->type == 2 ? 'Fungsional' : 'Outsource'),
                ];

                $shownHierarchy = '';
                $lastThreeParents = $this->positionRepository->getRecursivePosition($position->id, 4);
                $lastThreeParents = collect($lastThreeParents)->filter(function ($item) use ($position) {
                    return $item->id != $position->id;
                })->reverse()->values()->all();

                if (count($lastThreeParents)) {
                    foreach ($lastThreeParents as $key => $value) {
                        if ($key > 0) {
                            $shownHierarchy .= ' > ';
                        }

                        $shownHierarchy .= $value->name;
                    }
                } else {
                    $shownHierarchy = '-';
                }

                $position->hierarchies = $shownHierarchy;

                $duplicatePositions = collect($originalPositions)->filter(function ($item) use ($position) {
                    return $item->name == $position->name;
                })->values()->all();

                if (count($duplicatePositions) > 1) {
                    $user = DB::table('users')
                        ->select('name')
                        ->where('position_id', $position->id)
                        ->first();

                    if ($user) {
                        $position->name = $position->name.' ('.$user->name.')';
                    } else {
                        $dupePositions[] = $position->name;
                        $position->name = $position->name.' '.count($dupePositions);
                    }
                }

                unset($position->parent_id);
            }

            if (is_null($this->request->limit)) {
                $message = count($positions) < 1 ? 'Mohon maaf, data tidak ditemukan.' : 'success';

                return $this->response(200, $message, $positions);
            }

            $page = $this->request->get('page', 1);
            $paginatedPositions = new LengthAwarePaginator(
                $positions->forPage($page, $this->request->limit)->values(),
                $positions->count(),
                $this->request->limit,
                $page,
                ['path' => $this->request->url(), 'query' => $this->request->query()],
            );
            $message = $paginatedPositions->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->paginateResponse(200, $message, $paginatedPositions);
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function create(CreatePositionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            if (count($this->request->position_echelons)) {
                $this->request->merge(['available' => 0]);
            }

            if (is_null($this->request->parent_id)) {
                $this->request->merge(['vertical_order' => $this->request->order]);
                $this->request->merge(['horizontal_order' => 1]);
                $this->request->request->remove('order');
            } else {
                $this->request->merge(['vertical_order' => 1]);
                $this->request->merge(['horizontal_order' => $this->request->order]);
                $this->request->request->remove('order');
            }

            $positionId = DB::table('positions')
                ->insertGetIdTs($this->request->except('position_echelons'));

            if (count($this->request->position_echelons)) {
                $positionEchelons = [];

                foreach ($this->request->position_echelons as $key => $positionEchelon) {
                    $positionEchelon['position_id'] = $positionId;
                    $positionEchelon['horizontal_order'] = $key + 1;
                    $positionEchelons[] = $positionEchelon;
                }

                DB::table('position_echelons')->insertTs($positionEchelons);
            }

            DB::commit();

            return $this->response(200, 'Jabatan berhasil ditambah.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        try {
            $position = DB::table('positions')
                ->select(
                    'id',
                    'name',
                    'available',
                    'type',
                    'entity',
                    'vertical_order',
                    'horizontal_order',
                    'parent_id',
                    'status',
                )
                ->where('id', $this->request->id)
                ->first();

            if (! $position) {
                return $this->response(404, 'Jabatan tidak ditemukan.');
            }

            $position->filled = DB::table('users')
                ->where('position_id', $position->id)
                ->whereIn('employment_status', [1, 6, 10])
                ->whereNot('employment_type_id', 16)
                ->count();

            $position->type = [
                'id' => $position->type,
                'name' => $position->type == 1 ? 'Struktural' : ($position->type == 2 ? 'Fungsional' : 'Outsource'),
            ];
            $position->entity = [
                'id' => $position->entity,
                'name' => $position->entity == 1 ? 'Orang' : 'Kelompok',
            ];
            $position->order = isset($position->parent_id)
                ? $position->horizontal_order
                : $position->vertical_order;

            $positionEchelons = DB::table('position_echelons')
                ->select(
                    'position_echelons.id',
                    'echelons.id as echelon_id',
                    'echelons.name',
                    'position_echelons.available',
                    'position_echelons.position_id',
                )
                ->join('echelons', 'position_echelons.echelon_id', '=', 'echelons.id')
                ->where('position_echelons.position_id', $position->id)
                ->get();

            foreach ($positionEchelons as $positionEchelon) {
                $users = DB::select(
                    'SELECT COUNT(1) as count FROM users WHERE echelon_id = ? AND position_id = ? AND employment_status IN (1, 6, 10) AND employment_type_id != 16',
                    [$positionEchelon->echelon_id, $positionEchelon->position_id],
                );
                $positionEchelon->filled = $users[0]->count;
                unset($positionEchelon->position_id);
            }

            $position->echelons = $positionEchelons;
            $hierarchies = $this->positionRepository->getRecursivePosition($position->id);
            $position->hierarchies = collect($hierarchies)->filter(function ($item) use ($position) {
                return $item->id != $position->id;
            })->reverse()->values()->all();

            unset($position->parent_id, $position->horizontal_order, $position->vertical_order);

            return $this->response(200, 'success', $position);
        } catch (Throwable $throwable) {
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function update(UpdatePositionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $position = DB::table('positions')
                ->select('id')
                ->where('id', $this->request->id)
                ->first();

            if (! $position) {
                return $this->response(404, 'Jabatan tidak ditemukan.');
            }

            if (count($this->request->position_echelons)) {
                foreach ($this->request->position_echelons as $value) {
                    $countAvailable = DB::table('users as u')
                        ->where('u.position_id', $this->request->id)
                        ->where('u.echelon_id', $value['echelon_id'])
                        ->whereIn('u.employment_status', [1, 6, 10])
                        ->whereNot('employment_type_id', 16)
                        ->count();

                    if ($countAvailable > $value['available']) {
                        $echelon = DB::table('echelons as e')
                            ->select('e.name')
                            ->where('e.id', $value['echelon_id'])
                            ->first();

                        return $this->response(404, 'Eselon '.$echelon->name.' sudah terisi '.$countAvailable.' orang.');
                    }
                }

                $this->request->merge(['available' => 0]);
            } else {
                $countAvailable = DB::table('users as u')
                    ->where('u.position_id', $this->request->id)
                    ->whereIn('u.employment_status', [1, 6, 10])
                    ->whereNot('employment_type_id', 16)
                    ->count();

                if ($countAvailable > $this->request->available) {
                    $countAvailable = DB::table('users as u')
                        ->where('u.position_id', $this->request->id)
                        ->whereIn('u.employment_status', [1, 6, 10])
                        ->whereNot('employment_type_id', 16)
                        ->count();

                    return $this->response(404, 'Posisi sudah terisi '.$countAvailable.' orang.');
                }
            }

            if (is_null($this->request->parent_id)) {
                $this->request->merge(['vertical_order' => $this->request->order]);
                $this->request->request->remove('order');
            } else {
                $this->request->merge(['horizontal_order' => $this->request->order]);
                $this->request->request->remove('order');
            }

            DB::table('positions')
                ->where('id', $this->request->id)
                ->updateTs($this->request->except('position_echelons', 'deleted_echelon_id'));

            if (isset($this->request->deleted_echelon_id)) {
                DB::table('position_echelons')
                    ->whereIn('id', $this->request->deleted_echelon_id)
                    ->where('position_id', $position->id)
                    ->delete();
            }

            $positionEchelonsInsert = [];

            foreach ($this->request->position_echelons as $key => $value) {
                if (isset($value['id'])) {
                    $value['horizontal_order'] = $key + 1;
                    DB::table('position_echelons')
                        ->where('id', $value['id'])
                        ->updateTs($value);
                } else {
                    $value['position_id'] = $position->id;
                    $value['horizontal_order'] = $key + 1;
                    $positionEchelonsInsert[] = $value;
                }
            }

            if (count($positionEchelonsInsert)) {
                DB::table('position_echelons')->insertTs($positionEchelonsInsert);
            }

            DB::commit();

            return $this->response(200, 'Jabatan berhasil diubah.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function delete(): JsonResponse
    {
        try {
            $position = DB::table('positions')
                ->select('id')
                ->where('id', $this->request->id)
                ->first();

            if (! $position) {
                return $this->response(404, 'Jabatan tidak ditemukan.');
            }

            $existingUsers = DB::table('users')
                ->where('position_id', $position->id)
                ->count();

            if ($existingUsers > 0) {
                return $this->response(404, 'Jabatan ini masih digunakan oleh beberapa pegawai.');
            }

            DB::table('positions')
                ->where('id', $position->id)
                ->delete();

            return $this->response(200, 'Jabatan berhasil dihapus.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function availableOrder(): JsonResponse
    {
        try {
            $positions = DB::table('positions')
                ->select('id', 'horizontal_order', 'vertical_order')
                ->where('positions.type', '!=', 3)
                ->where('parent_id', $this->request->id)
                ->get();

            $existingOrder = isset($this->request->id)
                ? $positions->pluck('horizontal_order')->toArray()
                : $positions->pluck('vertical_order')->toArray();

            $allowedOrder = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
            $availableOrder = array_filter($allowedOrder, function ($item) use ($existingOrder) {
                return ! in_array($item, $existingOrder);
            });

            return $this->response(200, 'success', array_values($availableOrder));
        } catch (Throwable $throwable) {
            Log::warning($throwable);

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }
}
