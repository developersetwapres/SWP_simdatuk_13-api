<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Diagram
 * Below is the endpoint to get top level and child of diagram data
 */
class DiagramController extends Controller
{

    protected $request;
    protected $posted;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
    }

    /**
     * Get List of Diagrams
     *
     * Below is the list of all data diagrams.
     * @authenticated
     * @queryParam id integer Refers to the id of parent data. Example: 1
     * @response 200 {"code": 200,"message": "success","data": [{"id": 1,"name": "Staff Khusus Wakil Presiden","type": 1,"available": 0,"filled": 0,"children": 10,"entity": 2,"users": []},{"id": 2,"name": "Kepala Sekretariat Wakil Presiden","type": 1,"available": 1,"filled": 1,"children": 4,"entity": 1,"users": [{"id": 10578,"name": "Ahmad Erani Yustika","echelon_id": null,"echelon_effective_date": null,"grade_id": null,"grade_effective_date": "2022-10-01","employee_id_number": "197303221997021001","employee_registration_number": "197303221997021001"}]},{"id": 3,"name": "Pejabat Kemensetneg yang Diperbantukan di Sekretariat Wakil Presiden","type": 1,"available": 0,"filled": 0,"children": 4,"entity": 2,"users": []}]}
     */
    public function index()
    {
        if (is_null($this->request->id)) {
            return $this->getTopLevelPositions();
        } else {
            return $this->getPositionWithChildren($this->request->id);
        }
    }

    private function getTopLevelPositions()
    {
        $positions = $this->getPositions(null, 2, true, false, true, false);

        foreach ($positions as $position) {
            $position->users = [];
            if ($position->entity == 1) {
                $position->users = $this->getUsers($position->id);
                $position->filled = sizeof($position->users);
            }
        }

        return $this->response(200, 'success', $positions);
    }

    private function getPositionWithChildren($positionId)
    {
        // $this->getPositions($positionId, 2, true, false, true, false);
        // die;
        $positions = $this->getPositions($positionId, 1, false, false, true, false);




        if ($positions) {
            $positions->users = [];
            if ($positions->entity == 1) {
                $positions->users = $this->getUsers($positions->id);
                $positions->filled = sizeof($positions->users);
            }

            if ($positions->type == 1) {
                $positions->childs = $this->getPositions($positionId, 2, true, false, true, false);
            } else {
                $positions->childs = $this->getNestedJafung([], $positionId);
            }
            $positions->children = sizeof($positions->childs);

            foreach ($positions->childs as $childPosition) {
                if (isset($childPosition->entity)) {
                    if ($childPosition->entity == 1) {
                        if (str_starts_with(strtolower($childPosition->name), 'asisten staf khusus wakil presiden')) {
                            $childPosition->users = $this->getUsers($childPosition->id);
                            if (sizeof($childPosition->users) > 1) {
                                $ass = collect([]);
                                foreach ($childPosition->users as $user) {
                                    $uniqueChild = unserialize(serialize($childPosition));
                                    $uniqueChild->users = [$user];
                                    $uniqueChild->available = 1;
                                    $uniqueChild->filled = sizeof($uniqueChild->users);
                                    $ass->push($uniqueChild);
                                }
                                $positions->childs = $ass;
                            }
                        } else {
                            $childPosition->users = $this->getUsers($childPosition->id);
                        }
                    } else {
                        $childPosition->users = [];
                    }
                }

                $childPosition->filled = sizeof($childPosition->users);

                $childPosition->childs = [];
                //jabatan fungsional
                if (isset($childPosition->type) && $childPosition->type == 2) {
                    $childPosition->childs = $this->getNestedJafung([], $childPosition->id);

                    $childPosition->children = sizeof($childPosition->childs);
                    $childPosition->available = 0;
                    $childPosition->filled = 0;
                }

                //special case Pejabat Kemensetneg yang Diperbantukan di Sekretariat Wakil Presiden
                if (isset($positions->id) && $positions->id == 4) {
                    $grandchildPositions = $this->getPositions($childPosition->id, 2, true, false, true, false);

                    foreach ($grandchildPositions as $grandchildPosition) {
                        $grandchildUsers = $this->getUsers($grandchildPosition->id);
                        foreach ($grandchildUsers as $value) {
                            $childPosition->users[] = $value;
                        }
                    }

                    $childPosition->filled = sizeof($childPosition->users);
                }
            }
        }


        return $this->response(200, 'success', $positions);
    }

    private function getNestedJafung($list, $id)
    {
        foreach ($this->getPositionEchelons($id) as $value) {
            $list[] = $value;
        }

        foreach ($this->getPositions($id, 2, true, false, true, false) as $value) {
            if ($value->type == 2) {
                $value->childs = $this->getNestedJafung([], $value->id);
                $value->children = sizeof($value->childs);
            }
            $value->available = 0;
            $value->filled = 0;
            $list[] = $value;
        }

        return $list;
    }

    //idType : 1=id, 2=parent_id
    private function getPositions($id, $idType, $allData, $withUser, $hasChildrenStatus, $withEmptySlot)
    {
        $positions = DB::table('positions')
            ->select(
                'positions.id',
                'positions.name',
                'positions.type',
                DB::raw('CASE WHEN position_echelons.available IS NOT NULL THEN position_echelons.available ELSE positions.available END as available'),
                'positions.entity',
            )
            ->leftJoin('position_echelons', 'positions.id', '=', 'position_echelons.position_id')
            ->where('positions.type', '!=', 3)
            ->where('positions.status', true)
            ->orderBy('positions.vertical_order')
            ->orderBy('positions.horizontal_order')
            ->orderBy('position_echelons.vertical_order')
            ->orderBy('position_echelons.horizontal_order');

        if ($withUser === true) {
            $positions->addSelect(
                'u.name as user_name',
                'u.title_prefix',
                'u.title_suffix',
                'u.photo_profile as user_photo_profile',
                'u.type as user_type',
                DB::raw("DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date"),
                DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
                DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
                'e.id as echelon_id',
                'e.name as echelon_name',
                'g.name as grade_name',
                'g.code as grade_code',
                'u.employee_id_number',
                'u.employee_registration_number',
            );

            $positions->leftJoin('users as u', function ($join) {
                $join->on('positions.id', '=', 'u.position_id');
                $join->on(DB::raw('CASE WHEN position_echelons.echelon_id IS NOT NULL THEN position_echelons.echelon_id ELSE true END'), '=', DB::raw('CASE WHEN position_echelons.echelon_id IS NOT NULL THEN u.echelon_id ELSE true END'));
            });

            $positions->leftJoin('echelons as e', function ($join) {
                $join->on('position_echelons.echelon_id', '=', 'e.id');
            });
            $positions->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
            $positions->orderBy('u.type', 'ASC');
            $positions->orderBy('u.employment_type_id', 'DESC');
            $positions->orderBy('u.grade_id', 'ASC');
            $positions->orderBy('u.position_effective_date', 'ASC');
            $positions->orderBy('u.name', 'ASC');
            $positions->orderBy('u.grade_effective_date', 'ASC');
        }

        if ($idType == 1) {
            $positions->where('positions.id', $id);
        } else {
            $positions->where('positions.parent_id', $id)
                ->orWhere('positions.status', 2);
        }


        // dd($positions->get());

        if ($allData === true) {
            if ($withUser != true) {
                $positions = collect($positions->get()->unique('id')->values());
            } else {
                $positions = $positions->get();
            }
        } else {
            $positions = $positions->first();
        }

        if ($hasChildrenStatus === true) {
            if ($allData === true) {
                foreach ($positions as $position) {
                    $hasChild = DB::select('SELECT COUNT(1) as co FROM positions WHERE parent_id = ?', [$position->id]);
                    $position->has_child = $hasChild[0]->co > 0;
                }
            } else if ($positions) {
                $hasChild = DB::select('SELECT COUNT(1) as co FROM positions WHERE parent_id = ?', [$positions->id]);
                $positions->has_child = $hasChild[0]->co > 0;
            }
        }

        $positionId = '';
        $echelonName = '';
        $positionWithEmptySlot = collect([]);

        if ($withEmptySlot === true && $withUser === true) {
            foreach ($positions as $key => $position) {
                $positionWithEmptySlot->push($position);

                if ($position->available > 1) {
                    $placeholder = (object) [
                        "id" => $position->id,
                        "name" => $position->name,
                        "type" => $position->type,
                        "available" => $position->available,
                        "entity" => $position->entity,
                        "user_name" => null,
                        "title_prefix" => null,
                        "title_suffix" => null,
                        "user_photo_profile" => null,
                        "echelon_id" => null,
                        "echelon_name" => $position->type == 1 ? '-' : $position->echelon_name,
                        "grade_name" => null,
                        "employee_id_number" => null,
                        "employee_registration_number" => null,
                        "children" => [],
                    ];
                    if ($position->entity == 1 && $position->type == 1) {
                        if (($key < sizeof($positions) - 1 && $positions[$key + 1]->id != $position->id) || $key == sizeof($positions) - 1) {
                            $count = collect($positions)->where('id', $position->id);

                            for ($i = 0; $i < $position->available - $count->count(); $i++) {
                                $positionWithEmptySlot->push($placeholder);
                            }
                        }

                        if ($positionId != $position->id) {
                            $positionId = $position->id;
                        }
                    } else if ($position->type == 2) {
                        if (($key < sizeof($positions) - 1 && ($positions[$key + 1]->id != $position->id || $positions[$key + 1]->echelon_name != $position->echelon_name)) || $key == sizeof($positions) - 1) {
                            $count = collect($positions)->where('id', $position->id)->where('echelon_name', $position->echelon_name);

                            for ($i = 0; $i < $position->available - $count->count(); $i++) {
                                $positionWithEmptySlot->push($placeholder);
                            }
                        }

                        if ($echelonName != $position->echelon_name || $positionId != $position->id) {
                            $echelonName = $position->echelon_name;
                            $positionId = $position->id;
                        }
                    }
                }
            }
            $positions = $positionWithEmptySlot;
        }

        return $positions;
    }

    private function getUsers($positionId, $echelonId = null)
    {
        $users = DB::table('users')
            ->select(
                'users.id',
                'users.name',
                'users.title_prefix',
                'users.title_suffix',
                DB::raw("DATE_FORMAT(users.position_effective_date, '%d-%m-%Y') as position_effective_date"),
                'users.employee_id_number',
                'users.employee_registration_number',
                'users.type',
                'users.photo_profile',
                'positions.name as position_name',
                'echelons.name as echelon_name',
                DB::raw("DATE_FORMAT(users.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
                'grades.name as grade_name',
                DB::raw("DATE_FORMAT(users.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
                'grades.code as grade_code',
            )
            ->leftJoin('positions', 'positions.id', '=', 'users.position_id')
            ->leftJoin('echelons', 'echelons.id', '=', 'users.echelon_id')
            ->leftJoin('grades', 'grades.id', '=', 'users.grade_id')
            ->where('users.position_id', $positionId)
            ->orderBy('users.type', 'ASC')
            ->orderBy('users.employment_type_id', 'DESC')
            ->orderBy('users.grade_id', 'ASC')
            ->orderBy('users.position_effective_date', 'ASC')
            ->orderBy('users.name', 'ASC')
            ->orderBy('users.grade_effective_date', 'ASC');

        if (isset($echelonId)) {
            $users->where('users.echelon_id', '=', $echelonId);
        }

        $users = $users->get();

        foreach ($users as $user) {
            $user->photo_profile = $this->getDocument($user->photo_profile, true);
        }

        return $users;
    }

    private function getPositionEchelons($positionId)
    {
        $positionEchelons = DB::table('position_echelons')
            ->select(
                'echelons.name',
                'position_echelons.available',
                'position_echelons.echelon_id',
            )
            ->leftJoin('echelons', 'position_echelons.echelon_id', '=', 'echelons.id')
            ->where('position_id', $positionId)
            ->orderBy('echelons.sequence_number')
            ->orderBy('position_echelons.vertical_order')
            ->orderBy('position_echelons.horizontal_order')
            ->get();

        foreach ($positionEchelons as $positionEchelon) {
            $positionEchelon->users = $this->getUsers($positionId, $positionEchelon->echelon_id);
            $positionEchelon->filled = sizeof($positionEchelon->users);
            $positionEchelon->children = 0;
        }

        return $positionEchelons;
    }

    /**
     * Export Diagrams
     *
     * Below is function to export diagrams.
     * @authenticated
     */
    public function export()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(300);

        $hierarchies = $this->getHierarchy(null, 1);

        $html = '<ul><li class="li-last">
                    <div class="node-non-person">
                        <table style="width: 100%; table-layout: fixed;">
                            <tr>
                                <td class="node-position-name-non-person">
                                   SEKRETARIAT WAKIL PRESIDEN
                                </td>
                            </tr>
                        </table>
                    </div>';
        $html .= $this->generateHtml($hierarchies, null, false, false);
        $html .= '</li></ul>';

        $tmp = sys_get_temp_dir();

        $pdf = Pdf::loadview('exports/diagram', ['html' => $html]);

        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_paper("A0", "landscape");
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('fontDir', $tmp);
        $pdf->set_option('fontCache', $tmp);
        $pdf->set_option('tempDir', $tmp);
        return $pdf->download('peta-jabatan.pdf');
    }

    private function getHierarchy($parentId = null, $positionType = null)
    {
        $positions = [];
        if ($positionType != 2) {
            $positions = $this->getPositions($parentId, 2, true, true, false, true);
        }

        foreach ($positions as $position) {
            $children = $this->getHierarchy($position->id, $position->type);

            $grandChildParentId  = '';
            $childrenWithGrandchild = collect([]);
            foreach ($children as $key => $child) {
                $childrenWithGrandchild->push($child);
                if (
                    $child->type == 2 &&
                    $child->id != $grandChildParentId &&
                    (
                        (
                            $key < sizeof($children) - 1 &&
                            $children[$key + 1]->id != $child->id
                        ) ||
                        (
                            $key == sizeof($children) - 1
                        )
                    )
                ) {
                    $grandChild = $this->getHierarchy($child->id, null);

                    $childrenWithGrandchild = $childrenWithGrandchild->merge($grandChild);

                    $grandChildParentId = $child->id;
                }
            }

            $position->children = $childrenWithGrandchild;
        }

        return $positions;
    }

    private function generateHtml($hierarchies, $parent, $wrapChild, $pageBreak)
    {
        $html = '';
        if ($pageBreak === true) {
            $html .= '<div class="page_break"></div>';
        }

        if (isset($parent)) {
            $html .= '<ul>';
            $html .= $this->addCard($parent, false);
        }

        $html .= '<ul class="ul-child">';

        if ($wrapChild === false) {
            //show all child

            foreach ($hierarchies as $key => $hierarchy) {
                if (sizeof($hierarchies) == 1) {
                    $html .= '<li class="li-single">';
                } else if ($key == 0 && sizeof($hierarchies) > 1) {
                    $html .= '<li class="li-last">';
                } else {
                    $html .= '<li class="li-left li-last">';
                }

                $html .= $this->addCard($hierarchy, false);

                //special case id 4
                if (isset($parent) && $parent->entity == 2 && $hierarchy->entity == 2 && sizeof($hierarchy->children) > 0) {
                    $html .= '<ul class="ul-child"><li class="li-single">';
                    $html .= $this->addCard($hierarchy->children->first(), $hierarchy->children->count() > 1);
                    $html .= '</li></ul>';
                }
                $html .= '</li>';
            }
        } else {
            //show child grouped by echelon and position
            $position = '';
            foreach ($hierarchies as $key => $hierarchy) {
                if ($position != $hierarchy->name) {
                    if (sizeof($hierarchies) == 1) {
                        $html .= '<li class="li-single">';
                    } else if ($key == 0 && sizeof($hierarchies) > 1) {
                        $html .= '<li class="li-last">';
                    } else {
                        $html .= '<li class="li-left li-last">';
                    }

                    $sub = collect($hierarchies)->filter(function ($item) use ($hierarchy) {
                        return $item->name == $hierarchy->name;
                    });

                    $html .= $this->addCard($hierarchy, $sub->count() > 1);
                    $html .= '</li>';

                    $position = $hierarchy->name;
                }
            }

            //show grouped child on new page
            $position = '';
            $echelon = '';
            foreach ($hierarchies as $key => $hierarchy) {
                if ($position != $hierarchy->name) {
                    $sub = collect($hierarchies)->filter(function ($item) use ($hierarchy) {
                        $position = $item->name == $hierarchy->name;
                        return $position === true;
                    })->values();

                    if ($sub->count() > 1) {
                        if ($sub->count() > 18) {
                            $echelons = $sub->groupBy('echelon_name')->values();
                            foreach ($echelons as $key => $echelon) {
                                $wrapped = $echelon->count() > 18; //max child/node in 1 row is 18 items

                                $html .= '</ul>';
                                $html .= $this->generateHtml($echelon, $parent, $wrapped, true);
                            }
                        } else {
                            $wrapped = $sub->count() > 18; //max child/node in 1 row is 18 items

                            $html .= '</ul>';
                            $html .= $this->generateHtml($sub, $parent, $wrapped, true);
                        }
                    }

                    $position = $hierarchy->name;
                }
            }
        }

        $html .= '</ul>';

        //check if has childs
        foreach ($hierarchies as $key => $hierarchy) {
            if (sizeof($hierarchy->children)) {
                $wrapped = sizeof($hierarchy->children) > 18; //max child/node in 1 row is 18 items

                $html .= '</ul>';
                $html .= $this->generateHtml($hierarchy->children, $hierarchy, $wrapped, true);
            }
        }

        if (isset($parent)) {
            $html .= '</ul>';
        }

        return $html;
    }

    private function addCard($hierarchy, $stack)
    {
        if (($hierarchy->type == 1 && $hierarchy->entity == 1) || $hierarchy->type == 2) {
            //card person

            $userName = '-';

            if (isset($hierarchy->user_name)) {
                $userName = $hierarchy->user_name;
            }
            if (isset($hierarchy->title_prefix)) {
                $userName = $hierarchy->title_prefix . ' ' . $userName;
            }
            if (isset($hierarchy->title_suffix)) {
                $userName = $userName . ' ' . $hierarchy->title_suffix;
            }

            $card = '
            <div class="' . (($stack === true) ? 'node-person-stack' : 'node-person') . '">
                <table style="width: 100%; table-layout: fixed;">
                    <tr>
                        <td class="node-position-name-person">
                            ' . ($hierarchy->name ?? '-') . '
                        </td>
                    </tr>
                    <tr>
                        <td class="node-photo-container">
                            <img src="' . (isset($hierarchy->user_photo_profile) ? $this->getDocument($hierarchy->user_photo_profile, true, true) : 'img/profile.jpg') . '" class="node-photo"/>
                        </td>
                    </tr>
                    <tr>
                        <td class="node-user-name">
                            ' . $userName . '
                        </td>
                    </tr>
                    <tr style="margin-top:8px">
                        <td>
                            TMT
                        </td>
                    </tr>
                    <tr>
                        <td class="node-item-value">
                            ' . (isset($hierarchy->position_effective_date) ? Carbon::parse($hierarchy->position_effective_date)->format('d-m-Y') : '-') . '
                        </td>
                    </tr>
                    <tr>
                        <td class="node-item-title">
                            ' . (($hierarchy->echelon_id == 1 || $hierarchy->echelon_id == 2 || $hierarchy->echelon_id == 3 || $hierarchy->echelon_id == 4) ? 'Eselon' : 'Tingkat Jabatan') . '
                        </td>
                    </tr>
                    <tr>
                        <td class="node-item-value">
                            ' . ($hierarchy->echelon_name ?? '-') . (isset($hierarchy->echelon_effective_date) ? (', ' . Carbon::parse($hierarchy->echelon_effective_date)->format('d-m-Y')) : '') . '
                        </td>
                    </tr>
                    <tr>
                        <td class="node-item-title">
                            Golongan
                        </td>
                    </tr>
                    <tr>
                        <td class="node-item-value">
                            ' . ($hierarchy->grade_name ?? '-') . (isset($hierarchy->grade_code) ? (' ' . $hierarchy->grade_code) : '') . ((isset($hierarchy->grade_effective_date) ? (', ' . Carbon::parse($hierarchy->grade_effective_date)->format('d-m-Y')) : '')) . '
                        </td>
                    </tr>
                    <tr>
                        <td class="node-item-title">
                            NIP/NRP
                        </td>
                    </tr>
                    <tr>
                        <td class="node-item-value">
                            ' . ($hierarchy->employee_id_number ?? '-') . '/' . ($hierarchy->employee_registration_number ?? '-') . '
                        </td>
                    </tr>
                </table>
            </div>
            ';

            if ($stack === true) {
                return '<div class="node-person-stack-container">
                            <div class="node-person-stack"></div>
                            <div class="node-person-stack"></div>
                            ' . $card . '
                        </div>';
            }

            return $card;
        } else {
            //card non person
            return '<div class="node-non-person">
                        <table style="width: 100%; table-layout: fixed;">
                            <tr>
                                <td class="node-position-name-non-person">
                                    ' . ($hierarchy->name ?? '-') . '
                                </td>
                            </tr>
                        </table>
                    </div>';
        }
    }
}
