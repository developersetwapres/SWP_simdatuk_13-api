<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PerformanceRepository
{
    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        return DB::table('performance_histories as ph')
            ->join('performance_history_users as phu', 'ph.id', '=', 'phu.performance_history_id')
            ->where('phu.user_id', $userId)
            ->select(
                'phu.id',
                'ph.period_month',
                'ph.period_year',
                'ph.name',
                'ph.performance_period',
                'phu.work_performance_score',
                'phu.description',
            )
            ->orderBy('ph.period_year', 'desc')
            ->orderBy('ph.period_month', 'desc')
            ->get();
    }
    public function getDetailBulkUser($usersID)
    {
        $performances = DB::table('performance_histories as ph');
        $performances->join('performance_history_users as phu', 'ph.id', '=', 'phu.performance_history_id');
        $performances->whereIn('phu.user_id', $usersID);
        $performances->select(
            'phu.id',
            'phu.user_id',
            'ph.period_month',
            'ph.period_year',
            'ph.name',
            'ph.performance_period',
            'phu.work_performance_score',
            'phu.description',
        );
        $performances->orderBy('ph.period_year', 'desc');
        $performances->orderBy('ph.period_month', 'desc');
        $performances = $performances->get();

        $newPerformances = [];
        foreach ($performances as $performance) {
            $newPerformances[$performance->user_id][] = $performance;
        }

        return $newPerformances;
    }
}
