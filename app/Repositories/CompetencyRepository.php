<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompetencyRepository
{
    use Document;

    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        $competencies = DB::table('user_competencies')
            ->where('user_id', $userId)
            ->select(
                'id',
                DB::raw("DATE_FORMAT(event_date, '%d-%m-%Y') as event_date"),
                'point',
                'organizer',
                'competency_document',
            )
            ->orderBy('event_date', 'desc')
            ->get();

        foreach ($competencies as $competency) {
            $competency->competency_document = $this->getDocument($competency->competency_document);
        }

        return $competencies;
    }
}
