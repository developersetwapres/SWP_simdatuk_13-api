<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisciplinaryController extends Controller
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

        $disciplinaries = DB::table('disciplinaries');
        $disciplinaries->select('id', 'name', 'description', 'performance_allowance_deduction', 'performance_allowance_duration');
        $disciplinaries->where('name', 'like', '%'.$this->request->search.'%');
        $disciplinaries->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $disciplinaries = $disciplinaries->get();
            $message = (count($disciplinaries) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $disciplinaries);
        }

        $disciplinaries = $disciplinaries->paginate($this->request->limit);
        $message = ($disciplinaries->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $disciplinaries);
    }
}
