<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\CreateRoleRequest;
use App\Http\Requests\Role\DeleteRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RoleController extends Controller
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

        $roles = DB::table('roles');
        $roles->select('id', 'name');
        $roles->where('name', 'like', '%'.$this->request->search.'%');
        $roles->orderBy('created_at', 'desc');

        if (is_null($this->request->limit)) {
            $roles = $roles->get();
            $message = count($roles) < 1 ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $roles);
        }

        $roles = $roles->paginate($this->request->limit);
        $message = $roles->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $roles);
    }

    public function create(CreateRoleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $roleId = DB::table('roles')->insertGetIdTs([
                'name' => $this->request->name,
            ]);

            $data = [];

            foreach ($this->request->permissions as $item) {
                $data[] = [
                    'role_id' => $roleId,
                    'permission_id' => $item['id'],
                    'create' => str_contains($item['permitted_actions'], 'c'),
                    'read' => str_contains($item['permitted_actions'], 'r'),
                    'update' => str_contains($item['permitted_actions'], 'u'),
                    'delete' => str_contains($item['permitted_actions'], 'd'),
                ];
            }

            DB::table('role_permissions')->insertTs($data);

            DB::commit();

            return $this->response(200, 'Role berhasil ditambah.');
        } catch (Throwable $throwable) {
            Log::error($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $role = DB::table('roles')
            ->where('id', $this->request->id)
            ->select('id', 'name')
            ->first();

        if (! $role) {
            return $this->response(404, 'Role tidak ditemukan.');
        }

        $role->permissions = DB::table('permissions as p')
            ->leftJoin('role_permissions as rp', 'rp.permission_id', '=', 'p.id')
            ->select('p.id', 'p.name', 'p.permitted_actions', 'rp.create', 'rp.read', 'rp.update', 'rp.delete')
            ->where('rp.role_id', $role->id)
            ->orderBy('p.id', 'asc')
            ->get();

        return $this->response(200, 'success', $role);
    }

    public function update(UpdateRoleRequest $request): JsonResponse
    {
        $role = DB::table('roles')
            ->where('id', $this->request->id)
            ->first();

        if (! $role) {
            return $this->response(404, 'Role tidak ditemukan.');
        }

        try {
            DB::beginTransaction();

            DB::table('roles')
                ->where('id', $this->request->id)
                ->updateTs(['name' => $this->request->name]);

            DB::table('role_permissions')
                ->where('role_id', $this->request->id)
                ->delete();

            $data = [];

            foreach ($this->request->permissions as $item) {
                $data[] = [
                    'role_id' => $this->request->id,
                    'permission_id' => $item['id'],
                    'create' => str_contains($item['permitted_actions'], 'c'),
                    'read' => str_contains($item['permitted_actions'], 'r'),
                    'update' => str_contains($item['permitted_actions'], 'u'),
                    'delete' => str_contains($item['permitted_actions'], 'd'),
                ];
            }

            DB::table('role_permissions')->insertTs($data);

            DB::commit();

            return $this->response(200, 'Role telah berhasil diupdate.');
        } catch (Throwable $throwable) {
            Log::error($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function delete(DeleteRoleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            DB::table('users')
                ->where('role_id', $this->request->id)
                ->updateTs(['role_id' => $this->request->role_id]);

            $deleted = DB::table('roles')
                ->where('id', $this->request->id)
                ->delete();

            if (! $deleted) {
                return $this->response(404, 'Role tidak ditemukan.');
            }

            DB::commit();

            return $this->response(200, 'Role berhasil dihapus.');
        } catch (Throwable $throwable) {
            Log::error($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }
}
