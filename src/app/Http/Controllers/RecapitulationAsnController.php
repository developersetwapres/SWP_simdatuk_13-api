<?php

namespace App\Http\Controllers;

use App\Repositories\RecapitulationRepository;
use Illuminate\Http\Request;

/**
 * @group Summary
 */
class RecapitulationAsnController extends Controller
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
     * Get List of Recap ASN
     *
     * This endpoint is used to retrieve summary data.
     * @subgroup Recapitulation ASN
     * @authenticated
     * @response 200
     */
    public function index()
    {
        $unitKerja = $this->recapitulationRepository->getTotalUnitKerja();
        $pejabat = $this->recapitulationRepository->getKeteranganJabatan();
        $nonActive = $this->recapitulationRepository->getNonActiveAsn();
        $grade = $this->recapitulationRepository->getGrade(1);
        $gradePPPK = $this->recapitulationRepository->getGrade(2);
        $educationAndGender = $this->recapitulationRepository->getEducationAndGender(1);

        $data = [
            "name" => "Rekapitulasi Pegawai ASN",
            "total" => $pejabat->total_pejabat_pimpinan,
            "cards" => [
                [
                    "id" => 1,
                    "name" => "Unit Kerja",
                    "total" => $unitKerja['total'],
                    "cards" => $unitKerja['data'],
                ],
                [
                    "id" => 2,
                    "name" => "Keterangan Jabatan",
                    "total" => $pejabat->total_pejabat_pimpinan,
                    "cards" => [
                        [
                            "id" => 1,
                            "name" => "Jabatan Pimpinan Tinggi",
                            "total" => $pejabat->jabatan_pimpinan_tinggi,
                        ],
                        [
                            "id" => 2,
                            "name" => "Jabatan Administrasi",
                            "total" => $pejabat->jabatan_administrasi,
                        ],
                        [
                            "id" => 3,
                            "name" => "Jabatan Fungsional",
                            "total" => $pejabat->jabatan_fungsional,
                        ],
                    ],
                ],
                [
                    "id" => 3,
                    "name" => "Pangkat/Golongan ASN",
                    "total" => $grade[0],
                    "cards" => $grade[1],
                ],
                [
                    "id" => 4,
                    "name" => "Golongan PPPK",
                    "total" => $gradePPPK[0],
                    "cards" => $gradePPPK[1],
                ],
                [
                    "id" => 5,
                    "name" => "Pegawai Non Aktif",
                    "total" => $nonActive->tbln + $nonActive->cltn,
                    "cards" => [
                        [
                            "id" => 8,
                            "name" => "TBLN",
                            "total" => $nonActive->tbln,
                        ],
                        [
                            "id" => 7,
                            "name" => "CLTN",
                            "total" => $nonActive->cltn,
                        ],
                    ],
                ],
                [
                    "id" => 6,
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
                    "id" => 7,
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

    /**
     * Get List of Recap ASN by Category
     *
     * This endpoint is used to retrieve summary data based on the category parameter.
     * @subgroup Recapitulation ASN
     * @authenticated
     * @urlParam category integer Refers to the category of results being displayed. Example: 1
     * @response 200
     */
    public function show()
    {
        if ($this->request->category == 1) {
            return $this->getCategory1();
        } else if ($this->request->category == 2) {
            return $this->getCategory2();
        } else {
            return $this->getCategory3();
        }
    }

    private function getCategory1()
    {
        $pejabat = $this->recapitulationRepository->getPimpinanTinggi();
        $data = [
            "id" => 1,
            "name" => "Jabatan Pimpinan Tinggi",
            "total" => $pejabat->total_jabatan_pimpinan_tinggi,
            "cards" => [
                [
                    "id" => 1,
                    "name" => "Jabatan Pimpinan Tinggi",
                    "total" => $pejabat->total_jabatan_pimpinan_tinggi,
                    "cards" => [
                        [
                            "id" => 1,
                            "name" => "Jabatan Pimpinan Tinggi Madya",
                            "total" => $pejabat->jabatan_tinggi_madya,
                        ],
                        [
                            "id" => 2,
                            "name" => "Jabatan Pimpinan Tinggi Pratama",
                            "total" => $pejabat->jabatan_tinggi_pratama,
                        ],
                    ],
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }

    private function getCategory2()
    {
        $pejabat = $this->recapitulationRepository->getAdministrasi();
        $data = [
            "id" => 2,
            "name" => "Jabatan Administrasi",
            "total" => $pejabat->total_jabatan_administrasi,
            "cards" => [
                [
                    "id" => 1,
                    "name" => "Jabatan Administrasi",
                    "total" => $pejabat->total_jabatan_administrasi,
                    "cards" => [
                        [
                            "id" => 3,
                            "name" => "Jabatan Administrasi",
                            "total" => $pejabat->jabatan_administrasi,
                        ],
                        [
                            "id" => 4,
                            "name" => "Jabatan Pengawas",
                            "total" => $pejabat->jabatan_pengawas,
                        ],
                        [
                            "id" => 9,
                            "name" => "Jabatan Pelaksana",
                            "total" => $pejabat->jabatan_pelaksana,
                        ],
                    ],
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }

    private function getCategory3()
    {
        $jabatanFungsional = $this->recapitulationRepository->getJabatanFungsional();
        $data = [
            "id" => 3,
            "name" => "Jabatan Fungsional",
            "total" => $jabatanFungsional[0],
            "cards" => $jabatanFungsional[1],
        ];
        return $this->response(200, 'success', $data);
    }
}
