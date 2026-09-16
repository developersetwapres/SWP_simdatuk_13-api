<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecognitionRepository
{
    /** @return Collection<int, object> */
    public function getDetail(int $userId): Collection
    {
        $recognitions = DB::table('recognition_histories as rh');
        $recognitions->join('recognition_history_users as rhu', 'rh.id', '=', 'rhu.recognition_history_id');
        $recognitions->leftJoin('recognitions as r', 'rh.recognition_id', '=', 'r.id');
        $recognitions->leftJoin('decrees as d', 'rh.type_of_decree', '=', 'd.id');
        $recognitions->where('rhu.user_id', $userId);
        $recognitions->select(
            'rhu.id',
            'rh.period_month',
            'rh.period_year',
            'r.id as recognition_id',
            'r.name as recognition_name',
            'rh.description',
            'rh.type_of_decree',
            'd.name as type_of_decree_name',
            DB::raw("DATE_FORMAT(rh.decree_date, '%d-%m-%Y') as decree_date"),
            'rh.decree_number',
            'rh.decree_year',
            'rh.awarding_institution',
        );
        $recognitions->orderBy('rh.decree_date', 'desc');

        return $recognitions->get();
    }
    public function getDetailBulkUser($usersID)
    {
        $recognitions = DB::table('recognition_histories as rh');
        $recognitions->join('recognition_history_users as rhu', 'rh.id', '=', 'rhu.recognition_history_id');
        $recognitions->leftJoin('recognitions as r', 'rh.recognition_id', '=', 'r.id');
        $recognitions->leftJoin('decrees as d', 'rh.type_of_decree', '=', 'd.id');
        $recognitions->whereIn('rhu.user_id', $usersID);
        $recognitions->select(
            'rhu.id',
            'rhu.user_id',
            'rh.period_month',
            'rh.period_year',
            'r.id as recognition_id',
            'r.name as recognition_name',
            'rh.description',
            'rh.type_of_decree',
            'd.name as type_of_decree_name',
            DB::raw("DATE_FORMAT(rh.decree_date, '%d-%m-%Y') as decree_date"),
            'rh.decree_number',
            'rh.decree_year',
            'rh.awarding_institution',
        );
        $recognitions->orderBy('rh.decree_date', 'desc');
        $recognitions = $recognitions->get();

        $newRecognitions = [];
        foreach ($recognitions as $recognition) {
            $newRecognitions[$recognition->user_id][] = $recognition;
        }

        return $newRecognitions;
    }
}
