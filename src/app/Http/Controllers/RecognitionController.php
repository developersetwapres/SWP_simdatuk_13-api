<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecognitionController extends Controller
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

        $recognitions = DB::table('recognitions');
        $recognitions->select('id', 'name', 'description', 'created_at');
        $recognitions->where('name', 'like', '%'.$this->request->search.'%');
        $recognitions->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $recognitions = $recognitions->get();
            $message = (count($recognitions) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $recognitions);
        }

        $recognitions = $recognitions->paginate($this->request->limit);
        $message = ($recognitions->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $recognitions);
    }
}
