<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
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

        $grades = DB::table('grades');
        $grades->select('grades.id', 'grades.name', 'grades.code', 'grades.type');
        $grades->where('grades.name', 'like', '%'.$this->request->search.'%');
        $grades->orWhere('grades.code', 'like', '%'.$this->request->search.'%');
        $grades->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $grades = $grades->get();
            $message = (count($grades) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            foreach ($grades as $item) {
                $item->type = ($item->type == 1) ? 'PNS' : 'PPPK';
            }

            return $this->response(200, $message, $grades);
        }

        $grades = $grades->paginate($this->request->limit);
        $message = ($grades->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        foreach ($grades->items() as $item) {
            $item->type = ($item->type == 1) ? 'PNS' : 'PPPK';
        }

        return $this->paginateResponse(200, $message, $grades);
    }
}
