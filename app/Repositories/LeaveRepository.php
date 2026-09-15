<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaveRepository
{
    use Document;

    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        $leaves = DB::table('user_leaves')
            ->where('user_id', $userId)
            ->leftJoin('users as u', 'user_leaves.user_id', '=', 'u.id')
            ->leftJoin('grades as g', 'u.grade_id', '=', 'g.id')
            ->select(
                'user_leaves.id',
                DB::raw("CONCAT(g.name,' ',g.code) as grade"),
                'u.position_id',
                DB::raw("DATE_FORMAT(start_date, '%d-%m-%Y') as start_date"),
                DB::raw("DATE_FORMAT(end_date, '%d-%m-%Y') as end_date"),
                'user_leaves.type',
                'user_leaves.number',
                'user_leaves.description',
                'user_leaves.letter',
            )
            ->orderBy('start_date', 'desc')
            ->get();

        foreach ($leaves as $key => $leave) {
            $leave->letter = $this->getDocument($leave->letter);
            if (isset($leave->position_id)) {
                $leaves[$key]->position_merged = $this->getRecursivePosition($leave->position_id);
            }
        }

        return $leaves;
    }

    private function getRecursivePosition(int|string $positionId): string
    {
        $sql = "WITH RECURSIVE hierarchy AS (
            SELECT id, name, parent_id
            FROM positions
            WHERE id = '$positionId'

            UNION DISTINCT

            SELECT p.id, p.name, p.parent_id
            FROM positions p
            INNER JOIN hierarchy h ON p.id = h.parent_id
            WHERE p.entity = 1
        )
        SELECT * FROM hierarchy;";
        $position = DB::select($sql);

        return implode(', ', array_column($position, 'name'));
    }
    public function getDetailBulkUser($usersID)
    {
        $leaves = DB::table('user_leaves');
        $leaves->whereIn('user_id', $usersID);
        $leaves->leftJoin('users as u', 'user_leaves.user_id', '=', 'u.id');
        $leaves->leftJoin('grades as g', 'u.grade_id', '=', 'g.id');
        $leaves->select(
            'user_leaves.id',
            'user_leaves.user_id',
            DB::raw("CONCAT(g.name,' ',g.code) as grade"),
            'u.position_id',
            DB::raw("DATE_FORMAT(start_date, '%d-%m-%Y') as start_date"),
            DB::raw("DATE_FORMAT(end_date, '%d-%m-%Y') as end_date"),
            'user_leaves.type',
            'user_leaves.number',
            'user_leaves.description',
            'user_leaves.letter',
        );
        $leaves->orderBy('start_date', 'desc');
        $leaves = $leaves->get();

        $newLeaves = [];
        foreach ($leaves as $key => $leave) {
            $leave->letter = $this->getDocument($leave->letter);
            if (isset($leave->position_id)) {
                $leave->position_merged = $this->getRecursivePosition($leave->position_id);
            }
            $newLeaves[$leave->user_id][] = $leave;
        }

        return $newLeaves;
    }
}
