<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function __construct(protected Request $request) {}

    public function index(): JsonResponse
    {
        $permissions = DB::table('permissions')
            ->select('id', 'name', 'permitted_actions')
            ->get();

        return $this->response(200, 'success', $permissions);
    }
}
