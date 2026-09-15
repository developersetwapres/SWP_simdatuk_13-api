<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Facades\DB;

class EmployeeRepository
{
    use Document;

    public function getDetail(int|string $userId, bool $export = false): ?object
    {
        $user = DB::table('users as u');
        $user->leftJoin('positions as p', 'u.position_id', '=', 'p.id');
        $user->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
        $user->leftJoin('echelons as e', 'u.echelon_id', '=', 'e.id');
        $user->leftJoin('residences as r', 'u.residence_id', '=', 'r.id');
        $user->leftJoin('institutions as i', 'u.institution_id', '=', 'i.id');
        $user->where('u.id', $userId);
        $user->select(
            'u.id',
            'u.email',
            'u.office_email',
            'u.title_prefix',
            'u.name',
            'u.title_suffix',
            'u.photo_profile',
            'u.employee_id_number',
            'u.employee_registration_number',
            'u.place_of_birth',
            DB::raw("DATE_FORMAT(u.date_of_birth, '%d-%m-%Y') as date_of_birth"),
            'u.religion',
            'u.gender',
            'u.marital_status',
            DB::raw("DATE_FORMAT(u.marriage_date, '%d-%m-%Y') as marriage_date"),
            'u.marriage_description',
            'u.employment_type_id',
            'u.grade_id',
            'g.name as grade_name',
            'g.code as grade_code',
            DB::raw("DATE_FORMAT(u.grade_effective_date, '%d-%m-%Y') as grade_effective_date"),
            'u.position_id',
            'p.name as position_name',
            'p.type as position_type',
            DB::raw("DATE_FORMAT(u.position_effective_date, '%d-%m-%Y') as position_effective_date"),
            'u.echelon_id',
            'e.name as echelon_name',
            DB::raw("DATE_FORMAT(u.echelon_effective_date, '%d-%m-%Y') as echelon_effective_date"),
            'u.institution_id',
            'i.name as institution_name',
            'u.education_level',
            'u.education_name',
            'u.education_year',
            'u.employee_id_card_number',
            'u.employee_id_card',
            'u.karisu_number',
            'u.id_tax',
            'u.employment_status',
            DB::raw("DATE_FORMAT(u.quit_date, '%d-%m-%Y') as quit_date"),
            'u.id_number',
            'u.family_registration_number',
            'u.residence_id',
            'r.name as residence_name',
            'u.residence_description',
            'u.current_address',
            'u.home_phone_number',
            'u.mobile_phone',
            'u.office_address',
            'u.office_phone_number',
            'u.emergency_contact',
            'u.description',
            'u.type',
            DB::raw("DATE_FORMAT(u.cpns_effective_date, '%d-%m-%Y') as cpns_effective_date"),
            DB::raw("
                IF(
                    u.quit_date IS NULL,
                    CONCAT(
                        TIMESTAMPDIFF(YEAR, u.cpns_effective_date, NOW()), ' Tahun, ',
                        TIMESTAMPDIFF(MONTH, u.cpns_effective_date, NOW()) % 12, ' Bulan, ',
                        DATEDIFF(
                            NOW(),
                            DATE_ADD(
                                u.cpns_effective_date,
                                INTERVAL TIMESTAMPDIFF(YEAR, u.cpns_effective_date, NOW()) YEAR
                            ) + INTERVAL TIMESTAMPDIFF(MONTH, u.cpns_effective_date, NOW()) % 12 MONTH
                        ), ' Hari'
                    ),
                    CONCAT(
                        TIMESTAMPDIFF(YEAR, u.cpns_effective_date, u.quit_date), ' Tahun, ',
                        TIMESTAMPDIFF(MONTH, u.cpns_effective_date, u.quit_date) % 12, ' Bulan, ',
                        DATEDIFF(
                            u.quit_date,
                            DATE_ADD(
                                u.cpns_effective_date,
                                INTERVAL TIMESTAMPDIFF(YEAR, u.cpns_effective_date, quit_date) YEAR
                            ) + INTERVAL TIMESTAMPDIFF(MONTH, u.cpns_effective_date, quit_date) % 12 MONTH
                        ), ' Hari'
                    )
                ) as cpns_years_of_service
            "),
            'years_of_service_total',
            'month_of_service_total',
            DB::raw("DATE_FORMAT(u.pns_effective_date, '%d-%m-%Y') as pns_effective_date"),
            DB::raw("
                IF(
                    u.quit_date IS NULL,
                    CONCAT(
                        TIMESTAMPDIFF(YEAR, u.pns_effective_date, NOW()), ' Tahun, ',
                        TIMESTAMPDIFF(MONTH, u.pns_effective_date, NOW()) % 12, ' Bulan, ',
                        DATEDIFF(
                            NOW(),
                            DATE_ADD(
                                u.pns_effective_date,
                                INTERVAL TIMESTAMPDIFF(YEAR, u.pns_effective_date, NOW()) YEAR
                            ) + INTERVAL TIMESTAMPDIFF(MONTH, u.pns_effective_date, NOW()) % 12 MONTH
                        ), ' Hari'
                    ),
                    CONCAT(
                        TIMESTAMPDIFF(YEAR, u.pns_effective_date, u.quit_date), ' Tahun, ',
                        TIMESTAMPDIFF(MONTH, u.pns_effective_date, u.quit_date) % 12, ' Bulan, ',
                        DATEDIFF(
                            u.quit_date,
                            DATE_ADD(
                                u.pns_effective_date,
                                INTERVAL TIMESTAMPDIFF(YEAR, u.pns_effective_date, quit_date) YEAR
                            ) + INTERVAL TIMESTAMPDIFF(MONTH, u.pns_effective_date, quit_date) % 12 MONTH
                        ), ' Hari'
                    )
                ) as pns_years_of_service
            "),
            'years_of_service_rank',
            'month_of_service_rank',
            DB::raw("
                CASE
                    WHEN u.type = 1 && u.echelon_id IS NOT NULL && u.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(u.date_of_birth, INTERVAL e.retirement_age YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                    WHEN u.type = 2 && u.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(u.date_of_birth, INTERVAL 58 YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                    WHEN u.type = 3 && u.date_of_birth IS NOT NULL THEN DATE_FORMAT(DATE_ADD(DATE_ADD(u.date_of_birth, INTERVAL 58 YEAR), INTERVAL 1 MONTH),'%d-%m-%Y')
                    ELSE NULL
                END AS retirement_age
            "),
            DB::raw('
                CASE
                    WHEN u.type = 1 THEN e.retirement_age
                    WHEN u.type = 2 THEN 58
                    WHEN u.type = 3 THEN 58
                    ELSE NULL
                END AS retirement_age_years
            '),
            'u.created_at',
        );
        $user = $user->first();

        if (isset($user->photo_profile)) {
            $user->photo_profile = $this->getDocument($user->photo_profile, true, $export);
        }

        if (isset($user->employee_id_card)) {
            $user->employee_id_card = $this->getDocument($user->employee_id_card, true, $export);
        }

        if (isset($user->position_id)) {
            $user->position_merged = $this->getRecursivePosition(
                $user->position_id,
                $user->position_type,
                $user->echelon_name,
            );
        }

        return $user;
    }

    private function getRecursivePosition(int|string $positionId, mixed $type = null, ?string $echelonName = ''): string
    {
        $sql = "WITH RECURSIVE hierarchy AS (
            SELECT
                id,
                name,
                parent_id
            FROM
                positions
            WHERE
                id = '$positionId'

            UNION DISTINCT

            SELECT
                p.id,
                p.name,
                p.parent_id
            FROM
                positions p
            INNER JOIN
                hierarchy h ON p.id = h.parent_id
            WHERE
                p.entity = 1
        )
        SELECT
            *
        FROM
            hierarchy;";

        $positions = DB::select($sql);
        $names = array_column($positions, 'name');

        if ($type == 2) {
            $names[0] = $names[0].' '.$echelonName;
        }

        foreach ($names as $index => $name) {
            if ($index != 0) {
                $names[$index] = str_replace('Kepala ', '', $name);
            }
        }

        if (count($names) > 1) {
            $names[array_key_last($names)] = 'Sekretariat Wakil Presiden';
        }

        return implode(', ', $names);
    }
}
