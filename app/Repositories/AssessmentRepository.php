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
    public function getDetailBulkUser($usersID)
    {
        $assessements = DB::table('user_assessments');
        $assessements->whereIn('user_id', $usersID);
        $assessements->select(
            'id',
            'user_id',
            DB::raw("DATE_FORMAT(event_date, '%d-%m-%Y') as event_date"),
            'point',
            'organizer',
            'assessment_document'
        );
        $assessements->orderBy('event_date', 'desc');
        $assessements = $assessements->get();

        $newAssessment = [];
        foreach ($assessements as $assessment) {
            $assessment->assessment_document = $this->getDocument($assessment->assessment_document);
            $newAssessment[$assessment->user_id][] = $assessment;
        }

        return $newAssessment;
    }
}
