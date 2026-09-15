<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TalentRepository
{
    use Document;

    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        $talents = DB::table('user_talents')
            ->where('user_id', $userId)
            ->select(
                'id',
                DB::raw("DATE_FORMAT(event_date, '%d-%m-%Y') as event_date"),
                'point',
                'organizer',
                'talent_document',
            )
            ->orderBy('event_date', 'desc')
            ->get();

        foreach ($talents as $talent) {
            $talent->talent_document = $this->getDocument($talent->talent_document);
        }

        return $talents;
    }
}
