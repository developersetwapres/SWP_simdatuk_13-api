<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NoteRepository
{
    public function getDetail(mixed $userId): Collection
    {
        return DB::table('user_notes as un')
            ->leftJoin('users as u', 'un.giver_id', '=', 'u.id')
            ->where('un.user_id', $userId)
            ->select(
                'un.id',
                'un.description',
                'u.name as giver_name',
                DB::raw("DATE_FORMAT(un.created_at, '%d-%m-%Y') as created_at"),
            )
            ->orderBy('un.created_at', 'desc')
            ->get();
    }

    /**
     * @param  array<int, mixed>  $usersId
     * @return array<int|string, array<int, object>>
     */
    public function getDetailBulkUser(array $usersId): array
    {
        $notes = DB::table('user_notes as un')
            ->leftJoin('users as u', 'un.giver_id', '=', 'u.id')
            ->whereIn('un.user_id', $usersId)
            ->select(
                'un.id',
                'un.user_id',
                'un.description',
                'u.name as giver_name',
                DB::raw("DATE_FORMAT(un.created_at, '%d-%m-%Y') as created_at"),
            )
            ->orderBy('un.created_at', 'desc')
            ->get();

        $groupedNotes = [];

        foreach ($notes as $note) {
            $groupedNotes[$note->user_id][] = $note;
        }

        return $groupedNotes;
    }
}
