<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssessmentRepository
{
    use Document;

    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        $assessments = DB::table('user_assessments')
            ->where('user_id', $userId)
            ->select(
                'id',
                DB::raw("DATE_FORMAT(event_date, '%d-%m-%Y') as event_date"),
                'point',
                'organizer',
                'assessment_document',
            )
            ->orderBy('event_date', 'desc')
            ->get();

        foreach ($assessments as $assessment) {
            $assessment->assessment_document = $this->getDocument($assessment->assessment_document);
        }

        return $assessments;
    }
}
