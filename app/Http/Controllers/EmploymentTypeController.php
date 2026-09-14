<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentType\CreateEmploymentTypeRequest;
use App\Http\Requests\EmploymentType\UpdateEmploymentTypeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmploymentTypeController extends Controller
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
            'status.boolean' => 'Status harus berupa boolean. ',
            'type.in' => 'Type harus diantara 1, 2 atau 3. ',
        ];

        $this->request->validate([
            'page' => 'nullable|numeric|min:1',
            'limit' => 'nullable|numeric|min:1',
            'status' => 'nullable|boolean',
            'type' => 'nullable|in:1,2,3',
        ], $messages);

        $employmentTypes = DB::table('employment_types');
        $employmentTypes->select('id', 'name', 'status', 'type');
        $employmentTypes->where('name', 'like', '%'.$this->request->search.'%');

        if ($this->request->status) {
            $employmentTypes->where('status', $this->request->status);
        }

        if ($this->request->type) {
            $employmentTypes->where('type', $this->request->type);
        }

        $employmentTypes->orderBy('id', 'asc');

        if (is_null($this->request->limit)) {
            $employmentTypes = $employmentTypes->get();
            $message = (count($employmentTypes) < 1) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $employmentTypes);
        }

        $employmentTypes = $employmentTypes->paginate($this->request->limit);
        $message = ($employmentTypes->isEmpty()) ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $employmentTypes);
    }

    public function create(CreateEmploymentTypeRequest $request): JsonResponse
    {
        DB::table('employment_types')->insertTs($this->posted);

        return $this->response(200, 'Jenis pegawai berhasil ditambah.');
    }

    public function show(): JsonResponse
    {
        $employmentType = DB::table('employment_types')
            ->select('id', 'name', 'status', 'type')
            ->where('id', $this->request->id)
            ->first();

        if (! $employmentType) {
            return $this->response(404, 'Jenis pegawai tidak ditemukan.');
        }

        return $this->response(200, 'success', $employmentType);
    }

    public function update(UpdateEmploymentTypeRequest $request): JsonResponse
    {
        $employmentType = DB::table('employment_types')
            ->where('id', $this->request->id)
            ->select('id')
            ->first();

        if (! $employmentType) {
            return $this->response(404, 'Jenis pegawai tidak ditemukan.');
        }

        DB::table('employment_types')
            ->where('id', $this->request->id)
            ->updateTs($this->posted);

        return $this->response(200, 'Jenis pegawai berhasil diupdate.');
    }

    public function delete(): JsonResponse
    {
        $employmentType = DB::table('employment_types')
            ->where('id', $this->request->id)
            ->select('id')
            ->first();

        if (! $employmentType) {
            return $this->response(404, 'Jenis pegawai tidak ditemukan.');
        }

        DB::table('employment_types')
            ->where('id', $this->request->id)
            ->delete();

        return $this->response(200, 'Jenis pegawai berhasil dihapus.');
    }
}
