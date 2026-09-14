<?php

namespace App\Http\Controllers;

use App\Http\Requests\Institution\CreateInstitutionRequest;
use App\Http\Requests\Institution\UpdateInstitutionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitutionController extends Controller
{
    /** @var array<string, mixed> */
    protected array $posted;

    public function __construct(protected Request $request)
    {
        $this->posted = $request->except('_token', '_method');
    }

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

        $institutions = DB::table('institutions');
        $institutions->select('id', 'name');
        $institutions->where('name', 'like', '%'.$this->request->search.'%');
        $institutions->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $institutions = $institutions->get();
            $message = (count($institutions) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $institutions);
        }

        $institutions = $institutions->paginate($this->request->limit);
        $message = ($institutions->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $institutions);
    }

    public function create(CreateInstitutionRequest $request): JsonResponse
    {
        DB::table('institutions')->insertTs($this->posted);

        return $this->response(200, 'Institusi berhasil ditambah.');
    }

    public function show(): JsonResponse
    {
        $institution = DB::table('institutions')
            ->select('id', 'name')
            ->where('id', $this->request->id)
            ->first();

        if (! $institution) {
            return $this->response(404, 'Institusi tidak ditemukan.');
        }

        return $this->response(200, 'success', $institution);
    }

    public function update(UpdateInstitutionRequest $request): JsonResponse
    {
        $institution = DB::table('institutions')
            ->where('id', $this->request->id)
            ->select('id')
            ->first();

        if (! $institution) {
            return $this->response(404, 'Institusi tidak ditemukan.');
        }

        DB::table('institutions')
            ->where('id', $this->request->id)
            ->updateTs($this->posted);

        return $this->response(200, 'Institusi berhasil diupdate.');
    }

    public function delete(): JsonResponse
    {
        $institution = DB::table('institutions')
            ->where('id', $this->request->id)
            ->select('id')
            ->first();

        if (! $institution) {
            return $this->response(404, 'Institusi tidak ditemukan.');
        }

        DB::table('institutions')
            ->where('id', $this->request->id)
            ->delete();

        return $this->response(200, 'Institusi berhasil dihapus.');
    }
}
