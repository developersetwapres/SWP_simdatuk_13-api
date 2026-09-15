<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateStatusRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Mail\RegisterVerification;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class UserController extends Controller
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

        $users = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->select(
                'users.id',
                'users.username',
                'users.employee_id_number',
                'users.employee_registration_number',
                'roles.name as role_name',
                'users.status',
            )
            ->where('users.username', 'like', '%'.$this->request->search.'%');

        if (is_null($this->request->limit)) {
            $users = $users->get();
            $message = count($users) < 1 ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            return $this->response(200, $message, $users);
        }

        $users = $users->paginate($this->request->limit);
        $message = $users->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        return $this->paginateResponse(200, $message, $users);
    }

    public function create(CreateUserRequest $request): JsonResponse
    {
        $user = DB::table('users')
            ->select('name')
            ->where('id', $this->request->user_id)
            ->first();

        if (! $user) {
            return $this->response(422, 'Pengguna tidak ditemukan.');
        }

        $role = DB::table('roles')
            ->where('id', $this->request->role_id)
            ->first();

        if (! $role) {
            return $this->response(422, 'Role tidak ditemukan.');
        }

        $this->request->name = $user->name;
        $this->request->password = Str::password(8);

        try {
            DB::beginTransaction();

            DB::table('users')
                ->where('id', $this->request->user_id)
                ->updateTs([
                    'username' => $this->request->username,
                    'email' => $this->request->email,
                    'password' => Hash::make($this->request->password),
                    'role_id' => $this->request->role_id,
                    'status' => true,
                ]);

            DB::commit();

            try {
                Mail::to($this->request->email)->send(new RegisterVerification($this->request));
            } catch (Exception) {
                return $this->response(404, 'Gagal mengirimkan email, silakan hubungi admin.');
            }

            return $this->response(200, 'Pengguna berhasil ditambah.');
        } catch (Throwable) {
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function show(): JsonResponse
    {
        $user = DB::table('users')
            ->where('id', $this->request->id)
            ->where('role_id', '!=', null)
            ->select('role_id', 'id', 'username', 'email', 'name', 'employee_id_number', 'status')
            ->first();

        if (! $user) {
            return $this->response(404, 'Pengguna tidak ditemukan.');
        }

        $role = DB::table('roles')
            ->where('id', $user->role_id)
            ->select('id', 'name')
            ->first();

        unset($user->role_id);
        $user->role = $role;

        return $this->response(200, 'success', $user);
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $user = DB::table('users')
            ->where('id', $this->request->id)
            ->where('role_id', '!=', null)
            ->select('role_id', 'id', 'username', 'email', 'name', 'employee_id_number')
            ->first();

        if (! $user) {
            return $this->response(404, 'Pengguna tidak ditemukan.');
        }

        $role = DB::table('roles')
            ->where('id', $this->request->role_id)
            ->first();

        if (! $role) {
            return $this->response(422, 'Role tidak ditemukan.');
        }

        try {
            DB::beginTransaction();
            $this->request->name = $user->name;

            if ($user->email !== $this->request->email) {
                $this->request->password = Str::password(8);
                DB::table('users')
                    ->where('id', $this->request->id)
                    ->updateTs([
                        'email' => $this->request->email,
                        'password' => Hash::make($this->request->password),
                        'status' => true,
                    ]);
            }

            DB::table('users')
                ->where('id', $this->request->id)
                ->updateTs([
                    'username' => $this->request->username,
                    'role_id' => $this->request->role_id,
                ]);

            DB::commit();

            if ($user->email !== $this->request->email) {
                try {
                    Mail::to($this->request->email)->send(new RegisterVerification($this->request));
                } catch (Exception) {
                    return $this->response(404, 'Gagal mengirimkan email, silakan hubungi admin.');
                }
            }

            return $this->response(200, 'Pengguna berhasil diupdate.');
        } catch (Throwable $throwable) {
            Log::warning($throwable);
            DB::rollBack();

            return $this->response(400, 'Mohon maaf, fitur dalam kendala harap hubungi Tim IT!');
        }
    }

    public function status(UpdateStatusRequest $request): JsonResponse
    {
        $user = DB::table('users')
            ->where('id', $this->request->id)
            ->where('role_id', '!=', null)
            ->first();

        if (! $user) {
            return $this->response(404, 'Mohon maaf, pengguna tidak ditemukan.');
        }

        DB::table('users')
            ->where('id', $this->request->id)
            ->updateTs(['status' => $this->request->status]);

        $message = $this->request->status == true ? 'diaktifkan' : 'dinonaktifkan';

        return $this->response(200, 'Pengguna berhasil '.$message.'.');
    }
}
