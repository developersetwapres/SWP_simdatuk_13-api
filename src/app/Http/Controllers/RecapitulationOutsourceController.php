<?php

namespace App\Http\Controllers;

use App\Repositories\RecapitulationRepository;
use Illuminate\Http\Request;

/**
 * @group Summary
 */
class RecapitulationOutsourceController extends Controller
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
     * Get List of Recap Outsource
     *
     * This endpoint is used to retrieve summary data.
     * @subgroup Recapitulation Outsource
     * @authenticated
     * @response 200
     */
    public function index()
    {
        $outsource = $this->recapitulationRepository->getOutsource(19);
        // $nonOutsource = $this->recapitulationRepository->getOutsource(20);
        $educationAndGender = $this->recapitulationRepository->getEducationAndGender(3);
        $data = [
            "name" => "Rekapitulasi Pegawai Outsourcing",
            "total" => $outsource[0],
            "cards" => [
                [
                    "id" => 1,
                    "name" => "Tenaga Outsourcing",
                    "total" => $outsource[0],
                    "cards" => $outsource[1],
                ],
                // [
                //     "id" => 2,
                //     "name" => "Tenaga Non Outsourcing",
                //     "total" => $nonOutsource[0],
                //     "cards" => $nonOutsource[1],
                // ],
                [
                    "id" => 3,
                    "name" => "Pendidikan",
                    "total" => $educationAndGender->total_education,
                    "cards" => [
                        [
                            "id" => 8,
                            "name" => "Strata III",
                            "total" => $educationAndGender->s3,
                        ],
                        [
                            "id" => 7,
                            "name" => "Strata II",
                            "total" => $educationAndGender->s2,
                        ],
                        [
                            "id" => 6,
                            "name" => "Diploma IV/Strata I",
                            "total" => $educationAndGender->s1,
                        ],
                        [
                            "id" => 5,
                            "name" => "Akademik/D3/S.Muda",
                            "total" => $educationAndGender->d3,
                        ],
                        [
                            "id" => 4,
                            "name" => "Diploma I/II",
                            "total" => $educationAndGender->d1,
                        ],
                        [
                            "id" => 3,
                            "name" => "SLTA/Sederajat",
                            "total" => $educationAndGender->sma,
                        ],
                        [
                            "id" => 2,
                            "name" => "SLTP/Sederajat",
                            "total" => $educationAndGender->smp,
                        ],
                        [
                            "id" => 1,
                            "name" => "SD/Sederajat",
                            "total" => $educationAndGender->sd,
                        ],
                    ],
                ],
                [
                    "id" => 4,
                    "name" => "Jenis Kelamin",
                    "total" => $educationAndGender->total_gender,
                    "cards" => [
                        [
                            "id" => 1,
                            "name" => "Laki-laki",
                            "total" => $educationAndGender->male,
                        ],
                        [
                            "id" => 0,
                            "name" => "Perempuan",
                            "total" => $educationAndGender->female,
                        ],
                    ],
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }
}
