<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class RecapitulationRepository
{

    /**
     * Get total of pejabat pimpinan
     *
     * @return void
     */
    public function getPejabatPimpinanAndFungsional()
    {
        $pejabat = DB::table('users as u');
        $pejabat->select(
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (1,2,3,4) THEN 1 END) AS total_pejabat_pimpinan'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (1) THEN 1 END) AS echelon1'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (2) THEN 1 END) AS echelon2'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (3) THEN 1 END) AS echelon3'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (4) THEN 1 END) AS echelon4'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (5,6,7,8) THEN 1 END) AS total_pejabat_fungsional_keahlian'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (5) THEN 1 END) AS ahli_utama'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (6) THEN 1 END) AS ahli_madya'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (7) THEN 1 END) AS ahli_muda'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (8) THEN 1 END) AS ahli_pertama'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (10,11,12,13) THEN 1 END) AS total_pejabat_fungsional_keterampilan'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (10) THEN 1 END) AS penyelia'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (11) THEN 1 END) AS mahir'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (12) THEN 1 END) AS terampil'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (13) THEN 1 END) AS pemula'),
        );
        return $pejabat = $pejabat->first();
    }

    /**
     * Get total pejabat pelaksana
     */
    public function getPejabatPelaksana()
    {
        $pelaksana = DB::table('users');
        $pelaksana->select(
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (9) THEN 1 END) AS total'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (9) AND employment_type_id != 1 AND grade_id IN (1,2,3,4,5) THEN 1 END) AS golongan4'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (9) AND employment_type_id != 1 AND grade_id IN (6,7,8,9) THEN 1 END) AS golongan3'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (9) AND employment_type_id != 1 AND grade_id IN (10,11,12,13) THEN 1 END) AS golongan2'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (1, 6, 10) AND echelon_id IN (9) AND employment_type_id = 1 THEN 1 END) AS tnipolri'),
        );
        return $pelaksana = $pelaksana->first();
    }

    /**
     * Get active PPPK totals by position category.
     */
    public function getPejabatPppk()
    {
        $pppk = DB::table('users');
        $pppk->select(
            DB::raw('COUNT(CASE WHEN echelon_id IN (9) THEN 1 END) AS pelaksana'),
            DB::raw('COUNT(CASE WHEN echelon_id IN (5,6,7,8) THEN 1 END) AS fungsional_keahlian'),
            DB::raw('COUNT(CASE WHEN echelon_id IN (10,11,12,13) THEN 1 END) AS fungsional_keterampilan'),
        );
        $pppk->where('type', 1);
        $pppk->where('employment_type_id', 4);
        $pppk->whereIn('employment_status', [1, 6, 10]);
        return $pppk->first();
    }

    /**
     * Get keterangan jabatan
     *
     * @return void
     */
    public function getKeteranganJabatan()
    {
        $pejabat = DB::table('users as u');
        $pejabat->select(
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IS NOT NULL THEN 1 END) AS total_pejabat_pimpinan'),
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (1,2) THEN 1 END) AS jabatan_pimpinan_tinggi'),
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (3,4,9) THEN 1 END) AS jabatan_administrasi'),
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (5,6,7,8,10,11,12,13) THEN 1 END) AS jabatan_fungsional'),
        );
        return $pejabat = $pejabat->first();
    }

    /**
     * Get total grade by type of grade
     *
     * @param $type
     * @return void
     */
    public function getGrade($type)
    {
        $grade = DB::table('grades as g');
        $grade->join('users as u', 'u.grade_id', '=', 'g.id');
        $grade->select(
            DB::raw("g.id"),
            DB::raw("CONCAT(g.name, ' ', g.code) as name"),
            DB::raw('COUNT(u.id) as total')
        );
        $grade->where('g.type', $type);

        // Make filters consistent with getUsers for ASN: only users with type=1
        // and employment_type in ASN/PPPK groups so counts match the listing endpoints.
        $grade->where('u.type', 1);
        $grade->whereIn('u.employment_type_id', [1, 2, 3, 4]);

        $grade->whereIn('u.employment_status', [1, 6, 10]);
        // Group by grade columns to avoid ONLY_FULL_GROUP_BY issues
        $grade->groupBy('g.id', 'g.name', 'g.code');
        $grade->orderBy('g.id', 'asc');
        $grade = $grade->get();
        $total = $grade->sum('total');
        return array($total, $grade);
    }

    public function getGradeTotalByGroup()
    {
        $grade = DB::table('grades as g');
        $grade->join('users as u', 'u.grade_id', '=', 'g.id');
        $grade->select(
            DB::raw('COUNT(CASE WHEN u.grade_id IN (1,2,3,4,5,6,7,8,9,10,11,12,13) THEN 1 END) as total'),
            DB::raw('COUNT(CASE WHEN u.grade_id IN (1,2,3,4,5) THEN 1 END) as pembina'),
            DB::raw('COUNT(CASE WHEN u.grade_id IN (6,7,8,9) THEN 1 END) as penata'),
            DB::raw('COUNT(CASE WHEN u.grade_id IN (10,11,12,13) THEN 1 END) as pengatur')
        );
        $grade->whereIn('u.employment_status', [1, 6, 10]);
        $grade->where('u.type', 1);
        return $grade = $grade->first();
    }

    /**
     * Get list of outsource position and total by type of employment
     *
     * @param int $type
     * @return void
     */
    public function getOutsource($type)
    {
        $positions = DB::table('positions as p');
        $positions->select(
            'p.id',
            'p.name',
            DB::raw('COUNT(u.id) as total'),
        );
        $positions->join('users as u', 'u.position_id', '=', 'p.id');
        $positions->where('p.type', 3);
        $positions->whereIn('u.employment_status', [1, 6, 10]);
        $positions->where('u.employment_type_id', $type);
        $positions->orderBy('p.id', 'asc');
        $positions->groupBy('p.id', 'p.name');
        $positions = $positions->get();
        $total = $positions->sum('total');
        return array($total, $positions);
    }

    /**
     * Get total of education and gender by type of employee
     *
     * @param int $type
     * @return void
     */
    public function getEducationAndGender($type)
    {
        $total = DB::table('users as u');
        $total->select(
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.gender IS NOT NULL THEN 1 END) as total_gender'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.gender = 0 THEN 1 END) as female'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.gender = 1 THEN 1 END) as male'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level IS NOT NULL THEN 1 END) as total_education'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level = 1 THEN 1 END) as sd'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level = 2 THEN 1 END) as smp'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level = 3 THEN 1 END) as sma'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level = 4 THEN 1 END) as d1'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level = 5 THEN 1 END) as d3'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level = 6 THEN 1 END) as s1'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level = 7 THEN 1 END) as s2'),
            DB::raw('COUNT(CASE WHEN u.employment_status IN (1, 6, 10) AND u.education_level = 8 THEN 1 END) as s3'),
        );
        $total->leftJoin('positions as p', 'u.position_id', '=', 'p.id');

        if ($type == 2) {
            $total->where(function ($query) {
                $query->where('u.employment_type_id', '!=', 16);
            });
        } elseif ($type == 3) {
            $total->where('u.employment_type_id', 19);
        }

        $total->where('u.type', $type);
        return $total = $total->first();
    }

    /**
     * Get Total TIM TPPS
     *
     * @param int $type
     * @return void
     */
    public function getTim($type)
    {
        $users = DB::table('users');
        $users->where('employment_type_id', $type);
        $users->where('employment_status', 1);
        return $users = $users->count();
    }

    /**
     * Get non active ASN
     *
     * @return void
     */
    public function getNonActiveAsn()
    {
        $users = DB::table('users');
        $users->select(
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status IN (7, 8, 9) THEN 1 END) as total'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status = 7 THEN 1 END) as cltn'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status = 8 THEN 1 END) as tbln'),
            DB::raw('COUNT(CASE WHEN type = 1 AND employment_status = 9 THEN 1 END) as nonactive'),
        );
        return $users = $users->first();
    }

    /**
     * Get total unit kerja
     *
     * @return void
     */
    public function getTotalUnitKerja()
    {
        $positions = DB::table('positions');
        $positions->select('id', 'name');
        $positions->where('parent_id', 2);
        $positions->orderBy('id', 'asc');
        $positions = $positions->get();
        $newItem = (object) ['id' => 4, 'name' => 'Kementerian Sekretariat Negara'];
        $positions->push($newItem);

        $data = array();
        foreach ($positions as $position) {
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
                        po.id = '$position->id' -- Replace ? with the specific parent id

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
                    COUNT(*) as total
                FROM
                    hierarchy
                JOIN users ON hierarchy.id=users.position_id
                WHERE
                    users.employment_status
                IN
                    (1,6,10);
            ";
            $total = DB::select($sql);
            $total = $total[0]->total;
            array_push($data, ['id' => $position->id, 'name' => $position->name, 'total' => $total]);
        }
        array_unshift($data, ['id' => 2, 'name' => 'Sekretaris Wakil Presiden', 'total' => 1]);
        $totalSum = array_reduce($data, function ($carry, $item) {
            return $carry + $item['total'];
        }, 0);
        $data = ["total" => $totalSum, 'data' => $data];
        return $data;
    }

    /**
     * Pimpinan tinggi
     *
     * @return void
     */
    public function getPimpinanTinggi()
    {
        $pejabat = DB::table('users as u');
        $pejabat->select(
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (1,2) THEN 1 END) AS total_jabatan_pimpinan_tinggi'),
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (1) THEN 1 END) AS jabatan_tinggi_madya'),
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (2) THEN 1 END) AS jabatan_tinggi_pratama'),
        );
        return $pejabat = $pejabat->first();
    }

    /**
     * Administrasi
     *
     * @return void
     */
    public function getAdministrasi()
    {
        $pejabat = DB::table('users as u');
        $pejabat->select(
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (3,4,9) THEN 1 END) AS total_jabatan_administrasi'),
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (3) THEN 1 END) AS jabatan_administrasi'),
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (4) THEN 1 END) AS jabatan_pengawas'),
            DB::raw('COUNT(CASE WHEN employment_status IN (1, 6, 10) AND type = 1 AND echelon_id IN (9) THEN 1 END) AS jabatan_pelaksana'),
        );
        return $pejabat = $pejabat->first();
    }

    /**
     * Jabatan fungsional
     *
     * @return void
     */
    public function getJabatanFungsional()
    {
        $positions = DB::table('positions as p');
        $positions->select('id', 'name');
        $positions->where('type', 2);
        $positions->where('status', true);
        $positions->orderBy('id', 'asc');
        $positions = $positions->get();

        $grouped = [];
        foreach (json_decode(json_encode($positions), true) as $item) {
            if (!isset($grouped[$item['name']])) {
                $grouped[$item['name']] = [];
            }
            $grouped[$item['name']][] = $item['id'];
        }

        $result = [];
        foreach ($grouped as $name => $ids) {
            $result[] = [
                'name' => $name,
                'ids' => implode(', ', $ids),
            ];
        }

        $data = array();
        foreach ($result as $position) {
            $numbers_array = explode(',', $position['ids']);
            $numbers_array = array_map('intval', $numbers_array);
            $positionEchelons = DB::table('users');
            $positionEchelons->join('positions', 'users.position_id', '=', 'positions.id');
            $positionEchelons->join('echelons', 'users.echelon_id', '=', 'echelons.id');
            $positionEchelons->whereIn('positions.id', $numbers_array);
            $positionEchelons->select('echelons.id', 'echelons.name', DB::raw('COUNT(*) as total'));
            $positionEchelons->groupBy('echelons.name');
            $positionEchelons->orderBy('echelons.id', 'asc');
            $positionEchelons = $positionEchelons->get();
            array_push($data, [
                "id" => $position['ids'],
                "name" => $position['name'],
                'total' => $positionEchelons->sum('total'),
                "cards" => $positionEchelons,
            ]);
        }

        $totalSum = 0;
        foreach ($data as $item) {
            $totalSum += $item["total"];
        }
        return array($totalSum, $data);
    }

    /**
     * Jabatan Non ASN
     *
     * @return void
     */
    public function getJabatanNonAsn()
    {
        $sql = "SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));";
        DB::select($sql);

        $positions = DB::table('users as u');
        $positions->join('positions as p', 'u.position_id', '=', 'p.id');
        $positions->select(DB::raw('GROUP_CONCAT(p.id) AS id'), 'p.name', DB::raw('COUNT(u.id) as total'));
        $positions->where('u.type', 2);
        $positions->where('u.employment_type_id', '!=', 16);
        $positions->where('u.employment_type_id', '!=', 15);
        $positions->wherein('u.employment_status', [1, 6, 10]);
        $positions->groupBy('p.name');
        $positions->orderBy('p.id', 'asc');
        $positions = $positions->get();

        foreach ($positions as $position) {
            // Step 1: Convert the comma-separated string into an array
            $array = explode(',', $position->id);

            // Step 2: Remove duplicates from the array
            $uniqueArray = array_unique($array);

            // Step 3: Convert the array back to a comma-separated string
            $uniqueCommaSeparatedString = implode(',', $uniqueArray);
            $position->id = $uniqueCommaSeparatedString;
        }
        $data = array();
        foreach ($positions as $index => $item) {
            $normalizedName = $this->normalizeNonAsnPositionName($item->name);
            $item->name = $normalizedName;

            $groupName = $this->getNonAsnPositionGroup($normalizedName);
            if ($groupName !== null) {
                $this->addOrUpdateGroupData($data, $item, $groupName);
            } elseif (str_contains($normalizedName, 'Pengemudi')) {
                $this->addOrUpdateGroupData($data, $item, 'Pengemudi VVIP');
            } elseif (str_contains($normalizedName, "Mudi Wapres RI KH. Ma'ruf Amin, Ba Mudi-2 VVIP Unit Mudi Tim Pampri Don 1 Grup B Paspamres")) {
                $this->addOrUpdateGroupData($data, $item, 'Pengemudi VVIP');
            } elseif (str_contains($normalizedName, "Sekretariat Staf Khusus Wakil Presiden")) {
                $this->addOrUpdateGroupData($data, $item, 'Sekretariat Staf Khusus Wakil Presiden');
            } else {
                array_push($data, $item);
            }
        }

        $groupOrder = [
            'Staf Khusus Wakil Presiden',
            'Sekretaris Pribadi Wakil Presiden',
            'Wakil Sekretaris Pribadi Wakil Presiden',
            'Asisten Staf Khusus Wakil Presiden',
            'Asisten Sekretaris Pribadi Wakil Presiden',
            'Ajudan Wakil Presiden',
            'Ajudan Istri Wakil Presiden',
            'Dokter Pribadi Wakil Presiden',
            'Asisten Ajudan',
            'Pembantu Asisten Staf Khusus Wakil Presiden',
            'Pembantu Asisten Sekretaris Pribadi Wakil Presiden',
        ];
        usort($data, function ($left, $right) use ($groupOrder) {
            $leftOrder = array_search($left['name'], $groupOrder, true);
            $rightOrder = array_search($right['name'], $groupOrder, true);

            return ($leftOrder === false ? PHP_INT_MAX : $leftOrder)
                <=> ($rightOrder === false ? PHP_INT_MAX : $rightOrder);
        });

        $data = json_decode(json_encode($data), true);

        $totalSum = 0;
        foreach ($data as $item) {
            $totalSum += $item['total'];
        }

        return array($totalSum, $data);
    }

    private function getNonAsnPositionGroup($name)
    {
        $groups = [
            'Pembantu Asisten Staf Khusus Wakil Presiden' => 'Pembantu Asisten Staf Khusus Wakil Presiden',
            'Pembantu Asisten Sekretaris Pribadi Wakil Presiden' => 'Pembantu Asisten Sekretaris Pribadi Wakil Presiden',
            'Asisten Staf Khusus Wakil Presiden' => 'Asisten Staf Khusus Wakil Presiden',
            'Asisten Sekretaris Pribadi Wakil Presiden' => 'Asisten Sekretaris Pribadi Wakil Presiden',
            'Wakil Sekretaris Pribadi Wakil Presiden' => 'Wakil Sekretaris Pribadi Wakil Presiden',
            'Ajudan Istri Wakil Presiden' => 'Ajudan Istri Wakil Presiden',
            'Dokter Pribadi Wakil Presiden' => 'Dokter Pribadi Wakil Presiden',
            'Asisten Ajudan' => 'Asisten Ajudan',
            'Ajudan Wakil Presiden' => 'Ajudan Wakil Presiden',
            'Sekretaris Pribadi Wakil Presiden' => 'Sekretaris Pribadi Wakil Presiden',
            'Staf Khusus Wakil Presiden' => 'Staf Khusus Wakil Presiden',
        ];

        foreach (array_keys($groups) as $group) {
            if (str_contains($name, $group)) {
                return $groups[$group];
            }
        }

        return null;
    }

    private function addOrUpdateGroupData(&$data, $item, $name)
    {
        $groupName = array_column($data, 'name');
        $newIndex = array_search($name, $groupName);

        if ($newIndex === false) {
            array_push($data, ['id' => $item->id, 'name' => $name, 'total' => $item->total]);
        } else {
            $data[$newIndex]['id'] = $data[$newIndex]['id'] . ',' . $item->id;
            $data[$newIndex]['total'] += $item->total;
        }
    }

    private function normalizeNonAsnPositionName($name)
    {
        // Remove trailing parenthetical suffixes like "(9A)", "(9B)", etc.
        $normalized = preg_replace('/\s*\([^)]*\)$/u', '', $name);

        // Specific normalization rules for known titles.
        $patterns = [
            '/^Staf pada Sespri(?: Wakil Presiden)?(?:\s*\(.*\))?$/iu' => 'Sekretaris Pribadi Wakil Presiden',
            '/^Sekretaris Pribadi Wakil Presiden\s*\(.*\)$/iu' => 'Sekretaris Pribadi Wakil Presiden',
            '/^Wakil Sekretaris Pribadi Wakil Presiden\s*\(.*\)$/iu' => 'Wakil Sekretaris Pribadi Wakil Presiden',
            '/^Asisten Ajudan(?: Wakil Presiden)?\s*\(.*\)$/iu' => 'Asisten Ajudan',
            '/^Asisten Sekretaris Pribadi Wakil Presiden\s*\(.*\)$/iu' => 'Asisten Sekretaris Pribadi Wakil Presiden',
            '/^Pembantu Asisten Sekretaris Pribadi Wakil Presiden\s*\(.*\)$/iu' => 'Pembantu Asisten Sekretaris Pribadi Wakil Presiden',
            '/^Pembantu Asisten Staf Khusus Wakil Presiden\s*\(.*\)$/iu' => 'Pembantu Asisten Staf Khusus Wakil Presiden',
            '/^Staf Khusus Wakil Presiden\s*\(.*\)$/iu' => 'Staf Khusus Wakil Presiden',
            '/^Asisten Staf Khusus Wakil Presiden\s*\(.*\)$/iu' => 'Asisten Staf Khusus Wakil Presiden',
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $name)) {
                return $replacement;
            }
        }

        return trim($normalized);
    }

    /**
     * Pejabat perbantuan
     *
     * @param int $parentId
     * @return void
     */
    public function getPejabatDiperbantukan($parentId)
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
                COUNT(*) AS total,
                COUNT(CASE WHEN echelons.id IN (1,2,3,4) THEN 1 END) AS struktural,
                COUNT(CASE WHEN echelons.id IN (9) THEN 1 END) AS pelaksana,
                COUNT(CASE WHEN echelons.id NOT IN (1,2,3,4,9) THEN 1 END) AS fungsional
            FROM
                hierarchy
            JOIN users ON hierarchy.id=users.position_id
            LEFT JOIN echelons ON users.echelon_id=echelons.id
            WHERE
                users.employment_status
            IN
                (1,6,10);
        ";
        $users = DB::select($sql);
        return $users[0];
    }
}
