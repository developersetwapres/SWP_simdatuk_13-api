<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DecreeController extends Controller
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

        $decrees = DB::table('decrees');
        $decrees->select('id', 'name', 'acronym', 'created_at');
        $decrees->where('name', 'like', '%'.$this->request->search.'%');
        $decrees->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $decrees = $decrees->get();
            $message = (count($decrees) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $decrees);
        }

        $decrees = $decrees->paginate($this->request->limit);
        $message = ($decrees->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $decrees);
    }
}
