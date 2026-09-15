<?php

namespace App\Repositories;

use App\Helpers\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrainingRepository
{
    use Document;

    /** @return Collection<int, object> */
    public function getDetail(int $userId, int $type): Collection
    {
        $trainings = DB::table('training_histories as th');
        $trainings->join('training_history_users as thu', 'th.id', '=', 'thu.training_history_id');
        $trainings->leftJoin('training_levels as tl', 'th.level', '=', 'tl.id');
        $trainings->where('thu.user_id', $userId);
        $trainings->where('th.type', $type);
        $trainings->select(
            'thu.id',
            'th.period_month',
            'th.period_year',
            'th.name',
            DB::raw('tl.level_name as level'),
            DB::raw("DATE_FORMAT(th.start_date, '%d-%m-%Y') as start_date"),
            DB::raw("DATE_FORMAT(th.end_date, '%d-%m-%Y') as end_date"),
            'th.duration',
            'th.organizer',
            'th.reference_number',
            'th.link',
            'thu.certificate',
            'th.type',
            'th.description',
        );
        $trainings->orderBy('th.start_date', 'desc');
        $trainings = $trainings->get();

        foreach ($trainings as $training) {
            $training->certificate = $this->getDocument($training->certificate);
        }

        return $trainings;
    }
}
