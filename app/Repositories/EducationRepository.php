<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EducationRepository
{
    use Document;

    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        $educations = DB::table('user_educations')
            ->where('user_id', $userId)
            ->select(
                'id',
                'level',
                'name',
                'study_area',
                'accreditation',
                'faculty',
                'major',
                'year_of_graduation',
                'description',
                'degree_document',
                'study_assignment_letter',
                'academic_title_letter',
            )
            ->orderBy('level', 'desc')
            ->orderBy('year_of_graduation', 'desc')
            ->get();

        foreach ($educations as $education) {
            $education->degree_document = $this->getDocument($education->degree_document);
            $education->study_assignment_letter = $this->getDocument($education->study_assignment_letter);
            $education->academic_title_letter = $this->getDocument($education->academic_title_letter);
        }

        return $educations;
    }
}
