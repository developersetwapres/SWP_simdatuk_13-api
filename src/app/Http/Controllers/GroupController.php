<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
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

        $groups = DB::table('groups');
        $groups->select('id', 'name', 'type', 'created_at');
        $groups->where('name', 'like', '%'.$this->request->search.'%');
        $groups->where('type', '=', 1);
        $groups->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $groups = $groups->get();
            $message = (count($groups) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            foreach ($groups as $item) {
                $item->type = ($item->type == 1) ? 'Rumpun Riwayat Pegawai' : 'Rumpun Pelatihan Teknis';
            }

            return $this->response(200, $message, $groups);
        }

        $groups = $groups->paginate($this->request->limit);
        $message = ($groups->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        foreach ($groups as $item) {
            $item->type = ($item->type == 1) ? 'Rumpun Riwayat Pegawai' : 'Rumpun Pelatihan Teknis';
        }

        return $this->paginateResponse(200, $message, $groups);
    }
}
