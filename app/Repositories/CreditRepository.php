<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreditRepository
{
    public function getDetail(mixed $userId): Collection
    {
        return DB::table('user_credits as uc')
            ->join('users as u', 'uc.user_id', '=', 'u.id')
            ->where('uc.user_id', $userId)
            ->select(
                'uc.id',
                'uc.position',
                'uc.period',
                'uc.year',
                'uc.score',
                'uc.start_month',
                'uc.end_month',
            )
            ->orderBy('year', 'desc')
            ->orderBy('period', 'desc')
            ->get();
    }

    /**
     * @param  array<int, mixed>  $usersId
     * @return array<int|string, array<int, object>>
     */
    public function getDetailBulkUser(array $usersId): array
    {
        $credits = DB::table('user_credits as uc')
            ->join('users as u', 'uc.user_id', '=', 'u.id')
            ->whereIn('uc.user_id', $usersId)
            ->select(
                'uc.id',
                'uc.user_id',
                'uc.position',
                'uc.period',
                'uc.year',
                'uc.score',
                'uc.start_month',
                'uc.end_month',
            )
            ->orderBy('year', 'desc')
            ->orderBy('period', 'desc')
            ->get();

        $groupedCredits = [];

        foreach ($credits as $credit) {
            $groupedCredits[$credit->user_id][] = $credit;
        }

        return $groupedCredits;
    }
}
