<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
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

        $users = DB::table('users as u');
        $users->leftJoin('positions as p', 'u.position_id', '=', 'p.id');
        $users->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
        $users->leftJoin('employment_types as et', 'u.employment_type_id', '=', 'et.id');
        $users->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id');
        $users->select(
            'u.id',
            'u.photo_profile',
            DB::raw("
                CASE
                    WHEN u.title_prefix IS NULL && u.title_suffix IS NULL THEN u.name
                    WHEN u.title_prefix IS NOT NULL && u.title_suffix IS NULL THEN CONCAT(u.title_prefix, ' ', u.name)
                    WHEN u.title_prefix IS NULL && u.title_suffix IS NOT NULL THEN CONCAT(u.name, ' ', u.title_suffix)
                    ELSE CONCAT(u.title_prefix, ' ',u.name, ' ',u.title_suffix)
                END AS name
            "),
            'u.employee_id_number',
            'u.employee_registration_number',
            'p.name as position_name',
            'e.name as echelon_name',
            DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
            DB::raw("CONCAT(g.name, ' ', g.code) as grade_name"),
            DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
            'et.name as employment_type',
            'u.description',
        );

        $users->where(function ($query): void {
            $query->where('u.name', 'like', '%'.$this->request->search.'%')
                ->orWhere('u.employee_id_number', 'like', '%'.$this->request->search.'%');
        });

        if (! is_null($this->request->type)) {
            $users->where('u.type', $this->request->type);
        }

        if (! is_null($this->request->position_id)) {
            $users->where('u.position_id', $this->request->position_id);
        }

        if (! is_null($this->request->grade_id)) {
            $users->where('u.grade_id', $this->request->grade_id);
        }

        if (! is_null($this->request->echelon_id)) {
            $users->where('u.echelon_id', $this->request->echelon_id);
        }

        if (! is_null($this->request->employment_type_id)) {
            $users->where('u.employment_type_id', $this->request->employment_type_id);
        }

        if (! is_null($this->request->religion)) {
            $users->where('u.religion', $this->request->religion);
        }

        if (! is_null($this->request->employment_status)) {
            $users->where('u.employment_status', $this->request->employment_status);
        }

        if (! is_null($this->request->month_of_birth)) {
            $users->whereMonth('u.date_of_birth', $this->request->month_of_birth);
        }

        if (! is_null($this->request->gender)) {
            $users->where('u.gender', $this->request->gender);
        }

        if (isset($this->request->min_age)) {
            $maxDate = now()->subYears($this->request->min_age)->format('Y-m-d');
            $users->whereDate('u.date_of_birth', '<=', $maxDate);
        }

        if (isset($this->request->max_age)) {
            $minDate = now()->subYears($this->request->max_age)->format('Y-m-d');
            $users->whereDate('u.date_of_birth', '>=', $minDate);
        }

        if (! is_null($this->request->education_level)) {
            $users->where('u.education_level', $this->request->education_level);
        }

        $users->orderBy('u.employment_status', 'asc');
        $users->orderBy('u.echelon_id', 'asc');
        $users->orderBy('u.position_id', 'asc');

        if (is_null($this->request->limit)) {
            $users = $users->get();
            $message = count($users) < 1 ? 'Mohon maaf, data tidak ditemukan.' : 'success';

            foreach ($users as $item) {
                $item->photo_profile = $this->getDocument($item->photo_profile, true);
            }

            return $this->response(200, $message, $users);
        }

        $users = $users->paginate($this->request->limit);
        $message = $users->isEmpty() ? 'Mohon maaf, data tidak ditemukan.' : 'success';

        foreach ($users->items() as $item) {
            $item->photo_profile = $this->getDocument($item->photo_profile, true);
        }

        return $this->paginateResponse(200, $message, $users);
    }
}
