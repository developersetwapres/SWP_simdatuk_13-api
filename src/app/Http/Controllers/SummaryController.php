<?php

namespace App\Http\Controllers;

use App\Repositories\RecapitulationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @group Summary
 * Below is the comprehensive list of all data entities managed by the application:
 */
class SummaryController extends Controller
{
    protected $recapitulationRepository;
    protected Request $request;
    protected array $posted;

    public function __construct(
        Request $request,
        RecapitulationRepository $recapitulationRepository
    ) {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
        $this->recapitulationRepository = $recapitulationRepository;
    }

    /**
     * Get List of Summaries
     *
     * Below is the list of all data entities managed by the application.
     * @authenticated
     * @queryParam month integer Refers to the month between 1 - 12 of results being displayed. Default is '1'. Example: 1
     * @response 200 {"code":200,"message":"success","data":{"users":[{"name":"Syahrul Udjud","photo_profile":"http://localhost/img/avatar.jpeg","date_of_birth":"1943-01-13"},{"name":"Stanislaus Widjanarto","photo_profile":"http://localhost/img/avatar.jpeg","date_of_birth":"1947-01-08"},{"name":"Hadi Sukesto","photo_profile":"http://localhost/img/avatar.jpeg","date_of_birth":"1950-01-17"},{"name":"Bambang Wurjanto","photo_profile":"http://localhost/storage/photo_profile/195101011982031001.jpg","date_of_birth":"1951-01-01"},{"name":"H. Maman Herman Soetardja","photo_profile":"http://localhost/storage/photo_profile/195201031984031001.jpg","date_of_birth":"1952-01-03"},{"name":"R. Widjajanto","photo_profile":"http://localhost/img/avatar.jpeg","date_of_birth":"1952-01-17"},{"name":"Siti Iswari","photo_profile":"http://localhost/storage/photo_profile/195201171985032001.jpg","date_of_birth":"1952-01-17"},{"name":"Baharudin","photo_profile":"http://localhost/storage/photo_profile/060045905.jpg","date_of_birth":"1953-01-21"}],"total_government_employees":{"all":1399,"active":680},"gender_employees":{"male":958,"female":390},"total_non_government_employees":{"assistance":393,"outsource":362},"work_unit":[{"name":"Kepala Sekretariat Wakil Presiden","quantity":1},{"name":"Deputi Bidang Dukungan Kebijakan Pembangunan Ekonomi dan Peningkatan Daya Saing","quantity":24},{"name":"Deputi Bidang Dukungan Kebijakan Pembangunan Manusia dan Pemerataan Pembangunan","quantity":26},{"name":"Deputi Bidang Dukungan Kebijakan Pemerintah dan Wawasan Kebangsaan","quantity":31},{"name":"Deputi Bidang Administrasi","quantity":186},{"name":"Kementerian Sekretariat Negara","quantity":15}],"education_employees":[{"name":"Strata III","quantity":8},{"name":"Strata II","quantity":96},{"name":"Diploma IV / Strata I","quantity":92},{"name":"Akademi / Diploma III / Sarjana Muda","quantity":18},{"name":"Diploma I / II","quantity":1},{"name":"SLTA / Sederajat","quantity":67},{"name":"SLTP / Sederajat","quantity":1}]}}
     */
    public function index()
    {
        // get users by month of birth
        $users = DB::table('users');
        $users->select(
            DB::raw("
                CASE
                    WHEN title_prefix IS NULL && title_suffix IS NULL THEN name
                    WHEN title_prefix IS NOT NULL && title_suffix IS NULL THEN CONCAT(title_prefix, ' ', name)
                    WHEN title_prefix IS NULL && title_suffix IS NOT NULL THEN CONCAT(name, ' ', title_suffix)
                    ELSE CONCAT(title_prefix, ' ',name, ' ',title_suffix)
                END AS name
            "),
            'photo_profile',
            DB::raw("DATE_FORMAT(date_of_birth, '%d-%m-%Y') as date_of_birth")
        );
        $users->whereMonth('date_of_birth', $this->request->month);
        $users->where('type', 1);
        $users->where('employment_status', 1);
        $users->orderBy('date_of_birth', 'asc');
        $users = $users->get();
        foreach ($users as $item) {
            $item->photo_profile = $this->getDocument($item->photo_profile, true);
        }

        $unitKerja = $this->recapitulationRepository->getTotalUnitKerja();

        $countable = DB::table('users as u')
            ->select(
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) THEN 1 END) as active'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 7, 8, 9, 10) THEN 1 END) as total'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.gender = 1 THEN 1 END) as male'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.gender = 0 THEN 1 END) as female'),
                DB::raw('COUNT(CASE WHEN u.type = 2 AND u.employment_status IN (1, 6, 10) AND u.employment_type_id != 16 THEN 1 END) as assistance'),
                DB::raw('COUNT(CASE WHEN u.type = 3 AND u.employment_status IN (1, 6, 10) AND u.employment_type_id = 19 THEN 1 END) as outsource'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level IS NOT NULL THEN 1 END) as total_education'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level = 1 THEN 1 END) as sd'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level = 2 THEN 1 END) as smp'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level = 3 THEN 1 END) as sma'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level = 4 THEN 1 END) as d1'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level = 5 THEN 1 END) as d3'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level = 6 THEN 1 END) as s1'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level = 7 THEN 1 END) as s2'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.education_level = 8 THEN 1 END) as s3'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.date_of_birth >= "2000-01-01" AND u.date_of_birth <= "2006-12-31" THEN 1 END) as genz'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.date_of_birth >= "1981-01-01" AND u.date_of_birth <= "1999-12-31" THEN 1 END) as geny'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.date_of_birth >= "1965-01-01" AND u.date_of_birth <= "1980-12-31" THEN 1 END) as genx'),
                DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) AND u.date_of_birth >= "1946-01-01" AND u.date_of_birth <= "1964-12-31" THEN 1 END) as babyboomer')
            )
            ->first();

        $data = [
            "users" => $users,
            "total_government_employees" => [
                "active" => $countable->active,
                "all" => $countable->total,
            ],
            "gender_employees" => [
                "male" => $countable->male,
                "female" => $countable->female,
            ],
            "total_non_government_employees" => [
                "assistance" => $countable->assistance,
                "outsource" => $countable->outsource,
            ],
            "work_unit" => $unitKerja['data'],
            "education_employees" => [
                [
                    "name" => 'Strata III',
                    "quantity" => $countable->s3,
                ],
                [
                    "name" => 'Strata II',
                    "quantity" => $countable->s2,
                ],
                [
                    "name" => 'Diploma IV / Strata I',
                    "quantity" => $countable->s1,
                ],
                [
                    "name" => 'Akademi / Diploma III / Sarjana Muda',
                    "quantity" => $countable->d3,
                ],
                [
                    "name" => 'Diploma I / II',
                    "quantity" => $countable->d1,
                ],
                [
                    "name" => 'SLTA / Sederajat',
                    "quantity" => $countable->sma,
                ],
                [
                    "name" => 'SLTP / Sederajat',
                    "quantity" => $countable->smp,
                ],
                [
                    "name" => 'SD / Sederajat',
                    "quantity" => $countable->sd,
                ],
            ],
            "generations" => [
                [
                    "name" => 'Gen Z (18-24) (2000-2006)',
                    "total" => $countable->genz,
                ],
                [
                    "name" => 'Gen Y (25-40) (1981-1999)',
                    "total" => $countable->geny,
                ],
                [
                    "name" => 'Gen X (41-58) (1965-1980)',
                    "total" => $countable->genx,
                ],
                [
                    "name" => 'Baby Boomer (59-75) (1946-1964)',
                    "total" => $countable->babyboomer,
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }
}
