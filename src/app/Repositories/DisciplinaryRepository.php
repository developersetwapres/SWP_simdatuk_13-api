<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DisciplinaryRepository
{
    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        return DB::table('disciplinary_history_users as dhu')
            ->join('disciplinary_histories as dh', 'dhu.disciplinary_history_id', '=', 'dh.id')
            ->leftJoin('disciplinaries as d', 'dhu.disciplinary_id', '=', 'd.id')
            ->where('dhu.user_id', $userId)
            ->select(
                'dhu.id',
                'dh.period_month',
                'dh.period_year',
                'dhu.grade',
                'dhu.position',
                'd.id as disciplinary_id',
                'd.name as disciplinary_name',
                'd.description as disciplinary_description',
                'd.performance_allowance_deduction',
                'd.performance_allowance_duration',
                'dhu.decree_number',
                'dhu.date_of_decree',
                'dhu.start_date',
                'dhu.end_date',
                'dhu.authorizing_officer',
                'dhu.name_of_authorizing_officer',
                'dhu.description',
                DB::raw('CASE WHEN dhu.end_date > NOW() THEN true ELSE false END as status'),
                DB::raw('datediff(dhu.end_date, dhu.start_date) as validity_period'),
            )
            ->orderBy('dhu.date_of_decree', 'desc')
            ->get();
    }
    public function getDetailBulkUser($usersID)
    {
        $disciplinaries = DB::table('disciplinary_history_users as dhu')
            ->join('disciplinary_histories as dh', 'dhu.disciplinary_history_id', '=', 'dh.id')
            ->join('disciplinaries as d', 'dhu.disciplinary_id', '=', 'd.id')
            ->whereIn('dhu.user_id', $usersID)
            ->select(
                'dhu.id',
                'dhu.user_id',
                'dh.period_month',
                'dh.period_year',
                'dhu.grade',
                'dhu.position',
                'd.id as disciplinary_id',
                'd.name as disciplinary_name',
                'd.description as disciplinary_description',
                'd.performance_allowance_deduction',
                'd.performance_allowance_duration',
                'dhu.decree_number',
                'dhu.date_of_decree',
                'dhu.start_date',
                'dhu.end_date',
                'dhu.authorizing_officer',
                'dhu.name_of_authorizing_officer',
                'dhu.description',
                DB::raw('CASE WHEN dhu.end_date > NOW() THEN true ELSE false END as status'),
                DB::raw('datediff(dhu.end_date, dhu.start_date) as validity_period'),
            )
            ->orderBy('dhu.date_of_decree', 'desc')
            ->get();

        $newDisciplinaries = [];
        foreach ($disciplinaries as $disciplinary) {
            $newDisciplinaries[$disciplinary->user_id][] = $disciplinary;
        }

        return $newDisciplinaries;
    }
}
