<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EchelonController extends Controller
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

        $echelons = DB::table('echelons');
        $echelons->select('echelons.id', 'echelons.name');
        $echelons->where('echelons.name', 'like', '%'.$this->request->search.'%');
        $echelons->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $echelons = $echelons->get();
            $message = (count($echelons) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $echelons);
        }

        $echelons = $echelons->paginate($this->request->limit);
        $message = ($echelons->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $echelons);
    }
}
