<?php

namespace App\Http\Controllers;

use App\Repositories\RecapitulationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Summary
 * Below is endpoint to get list of employee for recapitulation.
 */
class RecapitulationEmployeeController extends Controller
{
    protected $recapitulationRepository;
    protected Request $request;
    protected array $posted;

    public function __construct(
        Request $request,
        RecapitulationRepository $recapitulationRepository
    ) {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
        $this->recapitulationRepository = $recapitulationRepository;
    }

    /**
     * Get List Employee
     *
     * Below is endpoint to get list of employee for recapitulation.
     * @authenticated
     * @queryParam page string Refers to page of recapitulation: recapitulation, asn, nonasn and outsource. Example: recapitulation
     * @queryParam section_id string Refers to section of recapitulation. Example: 1
     * @queryParam card_id string Refers to card_id of recapitulation. Example: 1
     * @queryParam category_id string Refers to category_id of recapitulation. Example: 1
     * @response 200 {"code": 200,"message": "success","data": {"total": 34,"items": [{"id": 2591,"position_name": "Staff Khusus Wakil Presiden","photo_profile": "https://content.ekuator.id/simdatuk/photo_profile/10015.jpg","name": "Dr Padmi Riyanti S.sos","echelon_name": "Eselon I","echelon_effective_date": "2013-07-23","grade_name": "Pembina Utama (IV/e)","grade_effective_date": "2010-10-21","employee_id_number": "10015","employee_registration_number": "10015"}]}}
     */
    public function index()
    {
        if ($this->request->page == 'recapitulation') {
            if ($this->request->category_id == 1) {
                if ($this->request->section_id == 1) {
                    $data = $this->getUsers(1, 'echelon', $this->request->card_id);
                    $data2 = $this->getUsers(1, 'echelon', $this->request->card_id, true);
                    $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                    $data = ['total' => count($data), 'items' => $data];
                } elseif ($this->request->section_id == 2) {
                    $data = $this->getUsersByGolongan($this->request->card_id);
                } elseif ($this->request->section_id == 3) {
                    $data = $this->getUsers(1, 'echelon', $this->request->card_id);
                    $data2 = $this->getUsers(1, 'echelon', $this->request->card_id, true);
                    $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                    $data = ['total' => count($data), 'items' => $data];
                } elseif ($this->request->section_id == 4) {
                    $data = $this->getUsers(1, 'echelon', $this->request->card_id);
                    $data2 = $this->getUsers(1, 'echelon', $this->request->card_id, true);
                    $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                    $data = ['total' => count($data), 'items' => $data];
                } elseif ($this->request->section_id == 5) {
                    $data = $this->getUsersByPejabatDiperbantukan($this->request->card_id);
                }
            } elseif ($this->request->category_id == 2) {
                if ($this->request->section_id == 1) {
                    $data = $this->getUsers(1, 'status', $this->request->card_id);
                }
            } elseif ($this->request->category_id == 3) {
                if ($this->request->section_id == 1) {
                    $data = $this->getUsers(2, 'position', $this->request->card_id);
                } elseif ($this->request->section_id == 2) {
                    $data = $this->getUsers(2, 'employment-type', $this->request->card_id);
                }
            } elseif ($this->request->category_id == 4) {
                if ($this->request->section_id == 1) {
                    $data = $this->getUsers(3, 'position', $this->request->card_id);
                } elseif ($this->request->section_id == 2) {
                    $data = $this->getUsers(3, 'position', $this->request->card_id);
                }
            }
        } elseif ($this->request->page == 'asn') {
            if ($this->request->section_id == 1) {
                $data = $this->getUsersByUnitKerja($this->request->card_id);
            } elseif ($this->request->section_id == 3 || $this->request->section_id == 4) {
                $data = $this->getUsers(1, 'grade', $this->request->card_id);
                $data2 = $this->getUsers(1, 'grade', $this->request->card_id, true);
                $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                $data = ['total' => count($data), 'items' => $data];
            } elseif ($this->request->section_id == 5) {
                $data = $this->getUsers(1, 'status', $this->request->card_id);
                $data2 = $this->getUsers(1, 'status', $this->request->card_id, true);
                $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                $data = ['total' => count($data), 'items' => $data];
            } elseif ($this->request->section_id == 6) {
                $data = $this->getUsers(1, 'education', $this->request->card_id);
                $data2 = $this->getUsers(1, 'education', $this->request->card_id, true);
                $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                $data = ['total' => count($data), 'items' => $data];
            } elseif ($this->request->section_id == 7) {
                $data = $this->getUsers(1, 'gender', $this->request->card_id);
                $data2 = $this->getUsers(1, 'gender', $this->request->card_id, true);
                $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                $data = ['total' => count($data), 'items' => $data];
            }

            if ($this->request->category_id == 1) {
                $data = $this->getUsers(1, 'echelon', $this->request->card_id);
                $data2 = $this->getUsers(1, 'echelon', $this->request->card_id, true);
                $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                $data = ['total' => count($data), 'items' => $data];
            } elseif ($this->request->category_id == 2) {
                $data = $this->getUsers(1, 'echelon', $this->request->card_id);
                $data2 = $this->getUsers(1, 'echelon', $this->request->card_id, true);
                $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                $data = ['total' => count($data), 'items' => $data];
            } elseif ($this->request->category_id == 3) {
                $data = $this->getUsersByJabatanFungsional($this->request->section_id, $this->request->card_id);
                $data2 = $this->getUsersByJabatanFungsional($this->request->section_id, $this->request->card_id, true);
                $data = array_merge($data['items']->toArray(), $data2['items']->toArray());
                $data = ['total' => count($data), 'items' => $data];
            }
        } elseif ($this->request->page == 'nonasn') {
            if ($this->request->section_id == 1) {
                $data = $this->getUsers(2, 'position', $this->request->card_id);
            } elseif ($this->request->section_id == 2) {
                $data = $this->getUsers(2, 'employment-type', $this->request->card_id);
            } elseif ($this->request->section_id == 3) {
                $data = $this->getUsers(2, 'education', $this->request->card_id);
            } elseif ($this->request->section_id == 4) {
                $data = $this->getUsers(2, 'gender', $this->request->card_id);
            }
        } elseif ($this->request->page == 'outsource') {
            if ($this->request->section_id == 1 || $this->request->section_id == 2) {
                $data = $this->getUsers(3, 'position', $this->request->card_id);
            } elseif ($this->request->section_id == 3) {
                $data = $this->getUsers(3, 'education', $this->request->card_id);
            } elseif ($this->request->section_id == 4) {
                $data = $this->getUsers(3, 'gender', $this->request->card_id);
            }
        }
        return $this->response(200, 'success', ["total" => $data['total'], "items" => $data['items']]);
    }

    public function getUsers($type, $filter, $cardId, $pppk = false)
    {
        $users = DB::table('users as u');
        $users->leftJoin('positions as p', 'u.position_id', '=', 'p.id');
        $users->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id');
        $users->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
        $users->select(
            'u.id',
            'p.name as position_name',
            'u.photo_profile',
            'u.name',
            'u.title_prefix',
            'u.title_suffix',
            DB::raw("DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date"),
            'e.name as echelon_name',
            DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
            'g.name as grade_name',
            'g.code as grade_code',
            DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
            'u.employee_id_number',
            'u.employee_registration_number',
            'u.type'
        );
        $users->where('u.type', $type);
        if ($filter == 'position') {
            $cardId = explode(',', $cardId);
            $cardId = array_map('intval', $cardId);
            $users->whereIn('u.position_id', $cardId);
        }
        if ($filter == 'grade') {
            $users->where('u.grade_id', $cardId);
        }
        if ($filter == 'education') {
            $users->where('u.education_level', $cardId);
            if ($type == 2) {
                $users->where(function ($query) {
                    $query->where('u.employment_type_id', '!=', 16);
                });
            } elseif ($type == 3) {
                $users->where('u.employment_type_id', 19);
            }
        }
        if ($filter == 'gender') {
            $users->where('u.gender', $cardId);
            if ($type == 2) {
                $users->where(function ($query) {
                    $query->where('u.employment_type_id', '!=', 16);
                });
            } elseif ($type == 3) {
                $users->where('u.employment_type_id', 19);
            }
        }
        if ($filter == 'employment-type') {
            $users->where('u.employment_type_id', $cardId);
        }
        if ($filter == 'status') {
            $users->where('u.employment_status', $cardId);
        } else {
            $users->whereIn('u.employment_status', [1, 6, 10]);
        }
        if ($filter == 'echelon') {
            $cardId = explode(',', $cardId);
            $cardId = array_map('intval', $cardId);
            $users->whereIn('u.echelon_id', $cardId);
        }
        if ($type == 1 && $pppk == true) {
            $users->whereIn('u.employment_type_id', [4]);
        } elseif ($type == 1 && $pppk == false) {
            $users->whereIn('u.employment_type_id', [1, 2, 3]);
        }

        $users->orderBy('e.sequence_number', 'asc');
        $users->orderBy('u.grade_id', 'asc');
        $users->orderBy('u.employment_type_id', 'desc');
        $users->orderBy('u.name', 'asc');
        $users = $users->get();
        foreach ($users as $item) {
            $item->photo_profile = $this->getDocument($item->photo_profile, true);
        }
        return ['total' => $users->count(), 'items' => $users];
    }

    public function getUsersByUnitKerja($parentId)
    {
        $sql = "
            WITH RECURSIVE hierarchy AS (
                -- Anchor member: Select the initial parent row
                SELECT
                    po.id,
                    po.name,
                    po.parent_id
                FROM
                    positions po
                WHERE
                    po.id = '$parentId' -- Replace ? with the specific parent id

                UNION DISTINCT

                -- Recursive member: Select the child row
                SELECT
                    p.id,
                    p.name,
                    p.parent_id
                FROM
                    positions p
                INNER JOIN
                    hierarchy h ON p.parent_id = h.id
            )
            SELECT
                u.id,
                p.name as position_name,
                u.photo_profile,
                u.name,
                u.title_prefix,
                u.title_suffix,
                DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date,
                e.name as echelon_name,
                DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date,
                g.name as grade_name,
                g.code as grade_code,
                DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date,
                u.employee_id_number,
                u.employee_registration_number,
                u.type
            FROM
                hierarchy
            JOIN users u ON hierarchy.id=u.position_id
            LEFT JOIN positions p ON u.position_id=p.id
            LEFT JOIN echelons e ON u.echelon_id=e.id
            LEFT JOIN grades g ON u.grade_id=g.id
            LEFT JOIN employment_types et ON u.employment_type_id=et.id
            WHERE
                u.employment_status
            IN
                (1,6,10)
            AND
                u.employment_type_id
            IN
                (1,2,3)
            ORDER BY
                e.sequence_number ASC,
                u.grade_id ASC,
                u.name ASC;
        ";

        $sql3 = "
            WITH RECURSIVE hierarchy AS (
                -- Anchor member: Select the initial parent row
                SELECT
                    po.id,
                    po.name,
                    po.parent_id
                FROM
                    positions po
                WHERE
                    po.id = '$parentId' -- Replace ? with the specific parent id

                UNION DISTINCT

                -- Recursive member: Select the child row
                SELECT
                    p.id,
                    p.name,
                    p.parent_id
                FROM
                    positions p
                INNER JOIN
                    hierarchy h ON p.parent_id = h.id
            )
            SELECT
                u.id,
                p.name as position_name,
                u.photo_profile,
                u.name,
                u.title_prefix,
                u.title_suffix,
                DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date,
                e.name as echelon_name,
                DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date,
                g.name as grade_name,
                g.code as grade_code,
                DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date,
                u.employee_id_number,
                u.employee_registration_number,
                u.type
            FROM
                hierarchy
            JOIN users u ON hierarchy.id=u.position_id
            LEFT JOIN positions p ON u.position_id=p.id
            LEFT JOIN echelons e ON u.echelon_id=e.id
            LEFT JOIN grades g ON u.grade_id=g.id
            LEFT JOIN employment_types et ON u.employment_type_id=et.id
            WHERE
                u.employment_status
            IN
                (1,6,10)
            AND
                u.employment_type_id
            IN
                (4)
            ORDER BY
                e.sequence_number ASC,
                u.grade_id ASC,
                u.name ASC;
        ";

        $sql2 = "
            SELECT
                u.id,
                p.name as position_name,
                u.photo_profile,
                u.name,
                u.title_prefix,
                u.title_suffix,
                e.name as echelon_name,
                DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date,
                DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date,
                g.name as grade_name,
                g.code as grade_code,
                DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date,
                u.employee_id_number,
                u.employee_registration_number,
                u.type
            FROM
                users as u
            LEFT JOIN positions p ON u.position_id=p.id
            LEFT JOIN echelons e ON u.echelon_id=e.id
            LEFT JOIN grades g ON u.grade_id=g.id
            LEFT JOIN employment_types et ON u.employment_type_id=et.id
            WHERE
                u.employment_status
            IN
                (1,6,10)
            AND
                position_id = 2
            ORDER BY
                e.sequence_number ASC,
                u.grade_id ASC,
                u.name ASC;
        ";

        if ($parentId == 2) {
            $users = DB::select($sql2);
        } else {
            $users = DB::select($sql);
            $users2 = DB::select($sql3);
            $users = array_merge($users, $users2);
        }

        foreach ($users as $item) {
            $item->photo_profile = $this->getDocument($item->photo_profile, true);
        }
        return ['total' => count($users), 'items' => $users];
    }

    public function getUsersByGolongan($cardId)
    {
        $users = DB::table('users as u');
        $users->leftJoin('positions as p', 'u.position_id', '=', 'p.id');
        $users->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id');
        $users->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
        $users->select(
            'u.id',
            'p.name as position_name',
            'u.photo_profile',
            'u.name',
            'u.title_prefix',
            'u.title_suffix',
            DB::raw("DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date"),
            'e.name as echelon_name',
            DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
            'g.name as grade_name',
            'g.code as grade_code',
            DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
            'u.employee_id_number',
            'u.employee_registration_number',
            'u.type'
        );
        $users->where('u.type', 1);
        $users->where('u.echelon_id', 9);
        $users->whereIn('u.employment_status', [1, 6, 10]);
        if ($cardId != 0) {
            $cardId = explode(',', $cardId);
            $cardId = array_map('intval', $cardId);
            $users->where('u.employment_type_id', '!=', 1);
            $users->whereIn('u.grade_id', $cardId);
        } else {
            $users->where('u.employment_type_id', '=', 1);
        }
        $users->orderBy('u.echelon_id', 'asc');
        $users->orderBy('u.grade_id', 'asc');
        $users->orderBy('u.employment_type_id', 'desc');
        $users->orderBy('u.name', 'asc');
        $users = $users->get();
        foreach ($users as $item) {
            $item->photo_profile = $this->getDocument($item->photo_profile, true);
        }
        return ['total' => $users->count(), 'items' => $users];
    }

    public function getUsersByPejabatDiperbantukan($cardId)
    {
        if ($cardId == 0) {
            $query = "WHERE e.id NOT IN (1,2,3,4,9)";
        } else {
            $query = "WHERE e.id IN ($cardId)";
        }
        $sql = "
            WITH RECURSIVE hierarchy AS (
                -- Anchor member: Select the initial parent row
                SELECT
                    po.id,
                    po.name,
                    po.parent_id
                FROM
                    positions po
                WHERE
                    po.id = 4 -- Replace ? with the specific parent id

                UNION DISTINCT

                -- Recursive member: Select the child row
                SELECT
                    p.id,
                    p.name,
                    p.parent_id
                FROM
                    positions p
                INNER JOIN
                    hierarchy h ON p.parent_id = h.id
            )
            SELECT
                u.id,
                p.name as position_name,
                u.photo_profile,
                u.name,
                u.title_prefix,
                u.title_suffix,
                DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date,
                e.name as echelon_name,
                DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date,
                g.name as grade_name,
                g.code as grade_code,
                DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date,
                u.employee_id_number,
                u.employee_registration_number,
                u.type
            FROM
              hierarchy
            JOIN users u ON hierarchy.id=u.position_id
            LEFT JOIN positions p ON u.position_id=p.id
            LEFT JOIN echelons e ON u.echelon_id=e.id
            LEFT JOIN grades g ON u.grade_id=g.id
            $query
            AND
              u.employment_status
            IN
              (1,6,10)
            ORDER BY
                u.echelon_id ASC,
                u.grade_id ASC,
                u.employment_type_id DESC,
                u.name ASC;
        ";
        $users = DB::select($sql);
        foreach ($users as $item) {
            $item->photo_profile = $this->getDocument($item->photo_profile, true);
        }
        return ['total' => count($users), 'items' => $users];
    }

    public function getUsersByJabatanFungsional($positions, $echelon, $pppk = false)
    {
        $positions = explode(',', $positions);
        $positions = array_map('intval', $positions);

        $users = DB::table('users as u');
        $users->leftJoin('positions as p', 'u.position_id', '=', 'p.id');
        $users->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id');
        $users->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
        $users->select(
            'u.id',
            'p.name as position_name',
            'u.photo_profile',
            'u.name',
            'u.title_prefix',
            'u.title_suffix',
            DB::raw("DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date"),
            'e.name as echelon_name',
            DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
            'g.name as grade_name',
            'g.code as grade_code',
            DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
            'u.employee_id_number',
            'u.employee_registration_number',
            'u.type'
        );
        $users->where('u.type', 1);
        $users->whereIn('u.position_id', $positions);
        $users->where('u.echelon_id', $echelon);
        $users->whereIn('u.employment_status', [1, 6, 10]);
        if ($pppk == true) {
            $users->whereIn('u.employment_type_id', [4]);
        } elseif ($pppk == false) {
            $users->whereIn('u.employment_type_id', [1, 2, 3]);
        }
        $users->orderBy('u.echelon_id', 'asc');
        $users->orderBy('u.grade_id', 'asc');
        $users->orderBy('u.employment_type_id', 'desc');
        $users->orderBy('u.name', 'asc');
        $users = $users->get();
        foreach ($users as $item) {
            $item->photo_profile = $this->getDocument($item->photo_profile, true);
        }
        return ['total' => $users->count(), 'items' => $users];
    }
}
