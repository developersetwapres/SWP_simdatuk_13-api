<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResidenceController extends Controller
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

        $residences = DB::table('residences');
        $residences->select('id', 'name', 'created_at');
        $residences->where('name', 'like', '%'.$this->request->search.'%');
        $residences->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $residences = $residences->get();
            $message = (count($residences) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $residences);
        }

        $residences = $residences->paginate($this->request->limit);
        $message = ($residences->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $residences);
    }
}
