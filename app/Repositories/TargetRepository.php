<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TargetRepository
{
    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        return DB::table('target_histories as th')
            ->join('target_history_users as thu', 'th.id', '=', 'thu.target_history_id')
            ->where('thu.user_id', $userId)
            ->select(
                'thu.id',
                'th.period_month',
                'th.period_year',
                'th.name',
                'th.appraisal_period',
                'th.year',
                'thu.work_behavior_rating',
                'thu.employee_performance_predicate',
                'thu.organizational_performance_achievement',
            )
            ->orderBy('th.period_year', 'desc')
            ->orderBy('th.period_month', 'desc')
            ->get();
    }
}
