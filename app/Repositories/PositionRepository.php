<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class PositionRepository
{
    /** @return array<int, object> */
    public function getRecursivePosition(int|string $positionId, ?int $limit = null): array
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
