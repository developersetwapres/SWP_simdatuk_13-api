<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FamilyRepository
{
    /** @return Collection<int, object> */
    public function getDetail(int|string $userId): Collection
    {
        return DB::table('user_families')
            ->where('user_id', $userId)
            ->select(
                'id',
                'card_number',
                'name',
                'id_number',
                'gender',
                'religion',
                DB::raw("DATE_FORMAT(date_of_birth, '%d-%m-%Y') as date_of_birth"),
                'place_of_birth',
                'name_of_father',
                'name_of_mother',
                'relationship_status',
                'education',
                'occupation',
                'occupation_description',
                'marital_status',
                'marriage_other_notes',
                'mobile_phone',
                'sequence_number',
            )
            ->orderByRaw('FIELD(relationship_status, 2,3,4,7,8,1,5,6,9,10,11) ASC')
            ->get();
    }
    public function getDetailBulkuser($usersID)
    {
        $families = DB::table('user_families');
        $families->whereIn('user_id', $usersID);
        $families->select(
            'id',
            'user_id',
            'card_number',
            'name',
            'id_number',
            'gender',
            'religion',
            DB::raw("DATE_FORMAT(date_of_birth, '%d-%m-%Y') as date_of_birth"),
            'place_of_birth',
            'name_of_father',
            'name_of_mother',
            'relationship_status',
            'education',
            'occupation',
            'occupation_description',
            'marital_status',
            'marriage_other_notes',
            'mobile_phone',
            'sequence_number',
        );
        // note: '1=Kepala Keluarga, 2=Suami, 3=Istri, 4=Anak, 5=Menantu, 6=Cucu, 7=Orang Tua, 8=Mertua, 9=Famili Lainnya, 10=Pembantu, 11=Lainnya'
        $families->orderByRaw("FIELD(relationship_status, 2,3,4,7,8,1,5,6,9,10,11) ASC");
        $families = $families->get();

        $newFamilies = [];
        foreach ($families as $family) {
            $newFamilies[$family->user_id][] = $family;
        }
        return $newFamilies;
    }
}
