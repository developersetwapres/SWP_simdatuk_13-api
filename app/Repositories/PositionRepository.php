<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PositionRepository
{
    use Document;

    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        $positions = DB::table('position_history_users as phu');
        $positions->join('users as u', 'u.id', '=', 'phu.user_id');
        $positions->leftJoin('position_histories as ph', 'phu.position_history_id', '=', 'ph.id');
        $positions->leftJoin('groups as g', 'phu.group_id', '=', 'g.id');
        $positions->leftJoin('position_history_echelons as phe', 'phu.echelon', '=', 'phe.id');
        $positions->leftJoin('decrees as tod', 'phu.type_of_decree', '=', 'tod.id');
        $positions->leftJoin('decrees as totd', 'phu.type_of_termination_decree', '=', 'totd.id');
        $positions->where('phu.user_id', $userId);
        $positions->select(
            'phu.id',
            'ph.period_month',
            'ph.period_year',
            'phu.position',
            'g.id as group_id',
            'g.name as group_name',
            'phu.echelon',
            'phe.name as echelon_name',
            'phu.position_status',
            DB::raw("DATE_FORMAT(phu.effective_date, '%d-%m-%Y') as effective_date"),
            'phu.decree',
            'phu.decree_document',
            'phu.decree_number',
            'tod.id as type_decree_id',
            'tod.name as type_decree_name',
            'totd.id as type_termination_decree_id',
            'totd.name as type_termination_decree_name',
            DB::raw("DATE_FORMAT(phu.decree_date, '%d-%m-%Y') as decree_date"),
            DB::raw("DATE_FORMAT(phu.termination_date, '%d-%m-%Y') as termination_date"),
            'phu.termination_decree',
            'phu.termination_decree_number',
            DB::raw("DATE_FORMAT(phu.termination_decree_date, '%d-%m-%Y') as termination_decree_date"),
            'phu.status',
        );
        $positions->orderBy('phu.effective_date', 'desc');
        $positions = $positions->get();

        foreach ($positions as $position) {
            $position->decree_document = $this->getDocument($position->decree_document);
        }

        return $positions;
    }

    /** @return array<int, object> */
    public function getRecursivePosition(int|string|null $positionId, ?int $limit = null): array
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
        )
        SELECT
            *
        FROM
            hierarchy";

        if (isset($limit)) {
            $sql .= " LIMIT $limit";
        }

        return DB::select($sql);
    }
}
