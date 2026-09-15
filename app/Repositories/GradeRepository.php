<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeRepository
{
    use Document;

    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        $grades = DB::table('grade_history_users as ghu');
        $grades->leftJoin('grades as g', 'ghu.grade_id', '=', 'g.id');
        $grades->leftJoin('grade_histories as gh', 'ghu.grade_history_id', '=', 'gh.id');
        $grades->leftJoin('decrees as d', 'ghu.type_of_decree', '=', 'd.id');
        $grades->where('ghu.user_id', $userId);
        $grades->select(
            'ghu.id',
            'gh.period_month',
            'gh.period_year',
            'g.id as grade_id',
            'g.name as grade_name',
            'g.code as grade_code',
            DB::raw("DATE_FORMAT(ghu.effective_date, '%d-%m-%Y') as effective_date"),
            'ghu.decree_name',
            'ghu.decree_document',
            'ghu.type_of_decree',
            'd.name as type_of_decree_name',
            'ghu.decree_number',
            DB::raw("DATE_FORMAT(ghu.decree_date, '%d-%m-%Y') as decree_date"),
            'ghu.description',
            'ghu.status',
        );
        $grades->orderBy('ghu.effective_date', 'desc');
        $grades = $grades->get();

        foreach ($grades as $grade) {
            $grade->decree_document = $this->getDocument($grade->decree_document);
        }

        return $grades;
    }
}
