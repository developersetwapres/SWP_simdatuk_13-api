<?php

namespace App\Http\Controllers;

use App\Repositories\RecapitulationRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Summary
 */
class RecapitulationController extends Controller
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
     * Get List of Recap
     *
     * This endpoint is used to retrieve summary data.
     * @subgroup Recapitulation
     * @authenticated
     * @response 200
     */
    public function index()
    {
        $users = $this->getTotalRecapitulation();
        $data = [
            "name" => 'Komposisi Pegawai',
            "total" => $users->total,
            "cards" => [
                [
                    "name" => 'Komposisi Pegawai',
                    "total" => $users->total,
                    "cards" => [
                        [
                            "id" => 1,
                            "name" => 'Aparatur Sipil Negara (ASN) Aktif + Perbantuan TNI/POLRI Pelaksana',
                            "total" => $users->asn_active,
                        ],
                        [
                            "id" => 2,
                            "name" => 'Aparatur Sipil Negara (ASN) Non Aktif',
                            "total" => $users->asn_nonactive,
                        ],
                        [
                            "id" => 3,
                            "name" => 'Non Aparatur Sipil Negara (Non ASN) + Tim',
                            "total" => $users->nonasn,
                        ],
                        [
                            "id" => 4,
                            "name" => 'Tenaga Outsourcing',
                            "total" => $users->outsource,
                        ],
                    ],
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }

    /**
     * Get List of Recap by Category
     *
     * This endpoint is used to retrieve summary data based on the category parameter.
     * @subgroup Recapitulation
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
        } else if ($this->request->category == 3) {
            return $this->getCategory3();
        } else {
            return $this->getCategory4();
        }
    }

    private function getCategory1()
    {
        $pejabat = $this->recapitulationRepository->getPejabatPimpinanAndFungsional();
        $pelaksana = $this->recapitulationRepository->getPejabatPelaksana();
        $pejabatDiperbantukan = $this->recapitulationRepository->getPejabatDiperbantukan(4);
        $data = [
            "id" => 1,
            "name" => 'Apartur Sipil Negara (ASN) Aktif + Perbantuan TNI/POLRI Pelaksana',
            "total" => $pejabat->total_pejabat_pimpinan + $pelaksana->total + $pejabat->total_pejabat_fungsional_keahlian + $pejabat->total_pejabat_fungsional_keterampilan,
            "cards" => [
                [
                    "id" => 1,
                    "name" => 'Pejabat Pimpinan',
                    "total" => $pejabat->total_pejabat_pimpinan,
                    "cards" => [
                        [
                            "id" => 1,
                            "name" => "Pejabat Pimpinan Tinggi Madya (Eselon I)",
                            "total" => $pejabat->echelon1,
                        ],
                        [
                            "id" => 2,
                            "name" => "Pejabat Pimpinan Tinggi Pratama (Eselon II)",
                            "total" => $pejabat->echelon2,
                        ],
                        [
                            "id" => 3,
                            "name" => "Pejabat Administrator (Eselon III)",
                            "total" => $pejabat->echelon3,
                        ],
                        [
                            "id" => 4,
                            "name" => "Pejabat Pengawas (Eselon IV)",
                            "total" => $pejabat->echelon4,
                        ],
                    ],
                ],
                [
                    "id" => 2,
                    "name" => 'Pejabat Pelaksana',
                    "total" => $pelaksana->total,
                    "cards" => [
                        [
                            "id" => "1,2,3,4,5",
                            "name" => "Pejabat Pelaksana Golongan IV",
                            "total" => $pelaksana->golongan4,
                        ],
                        [
                            "id" => "6,7,8,9",
                            "name" => "Pejabat Pelaksana Golongan III",
                            "total" => $pelaksana->golongan3,
                        ],
                        [
                            "id" => "10,11,12,13",
                            "name" => "Pejabat Pelaksana Golongan II",
                            "total" => $pelaksana->golongan2,
                        ],
                        [
                            "id" => 0,
                            "name" => "Pejabat Pelaksana Perbantuan TNI dan POLRI",
                            "total" => $pelaksana->tnipolri,
                        ],
                    ],
                ],
                [
                    "id" => 3,
                    "name" => 'Pejabat Fungsional Keahlian',
                    "total" => $pejabat->total_pejabat_fungsional_keahlian,
                    "cards" => [
                        [
                            "id" => 5,
                            "name" => "Pejabat Fungsional Ahli Utama",
                            "total" => $pejabat->ahli_utama,
                        ],
                        [
                            "id" => 6,
                            "name" => "Pejabat Fungsional Ahli Madya",
                            "total" => $pejabat->ahli_madya,
                        ],
                        [
                            "id" => 7,
                            "name" => "Pejabat Fungsional Ahli Muda",
                            "total" => $pejabat->ahli_muda,
                        ],
                        [
                            "id" => 8,
                            "name" => "Pejabat Fungsional Ahli Pertama",
                            "total" => $pejabat->ahli_pertama,
                        ],
                    ],
                ],
                [
                    "id" => 4,
                    "name" => 'Pejabat Fungsional Keterampilan',
                    "total" => $pejabat->total_pejabat_fungsional_keterampilan,
                    "cards" => [
                        [
                            "id" => 10,
                            "name" => "Pejabat Fungsional Penyelia",
                            "total" => $pejabat->penyelia,
                        ],
                        [
                            "id" => 11,
                            "name" => "Pejabat Fungsional Mahir",
                            "total" => $pejabat->mahir,
                        ],
                        [
                            "id" => 12,
                            "name" => "Pejabat Fungsional Terampil",
                            "total" => $pejabat->terampil,
                        ],
                        [
                            "id" => 13,
                            "name" => "Pejabat Fungsional Pemula",
                            "total" => $pejabat->pemula,
                        ],
                    ],
                ],
                [
                    "id" => 5,
                    "name" => 'Pejabat Kemensetneg Yang Diperbantukan di Setwapres',
                    "total" => $pejabatDiperbantukan->total,
                    "cards" => [
                        [
                            "id" => "1,2,3,4",
                            "name" => "Pejabat Struktural",
                            "total" => $pejabatDiperbantukan->struktural,
                        ],
                        [
                            "id" => "9",
                            "name" => "Pejabat Pelaksana",
                            "total" => $pejabatDiperbantukan->pelaksana,
                        ],
                        [
                            "id" => "0",
                            "name" => "Pejabat Fungsional",
                            "total" => $pejabatDiperbantukan->fungsional,
                        ],
                    ],
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }

    private function getCategory2()
    {
        $nonActive = $this->recapitulationRepository->getNonActiveAsn();
        $data = [
            "id" => 2,
            "name" => 'Aparatur Sipil Negara (ASN) Non Aktif',
            "total" => $nonActive->total,
            "cards" => [
                [
                    "id" => 1,
                    "name" => "Aparatur Sipil Negara (ASN) Non Aktif",
                    "total" => $nonActive->total,
                    "cards" => [
                        [
                            "id" => 8,
                            "name" => "Tugas Belajar Luar Negeri (TBLN)",
                            "total" => $nonActive->tbln,
                        ],
                        [
                            "id" => 7,
                            "name" => "Cuti di Luar Tanggungan Negara (CLTN)",
                            "total" => $nonActive->cltn,
                        ],
                        [
                            "id" => 9,
                            "name" => "Tidak Aktif (Non Jabatan)",
                            "total" => $nonActive->nonactive,
                        ],
                    ],
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }

    private function getCategory3()
    {
        $tim = $this->recapitulationRepository->getTim(15);
        $jabatanNonAsn = $this->recapitulationRepository->getJabatanNonAsn();
        $data = [
            "id" => 3,
            "name" => 'Non Aparatur Sipil Negara (Non ASN) + Tim',
            "total" => $jabatanNonAsn[0] + $tim,
            "cards" => [
                [
                    "id" => 1,
                    "name" => "Non Aparatur Sipil Negara (Non ASN)",
                    "total" => $jabatanNonAsn[0],
                    "cards" => $jabatanNonAsn[1],
                ],
                [
                    "id" => 2,
                    "name" => "Tim",
                    "total" => $tim,
                    "cards" => [
                        [
                            "id" => 15,
                            "name" => "Tim Nasional Percepatan Penurunan Stunting (TPPS)",
                            "total" => $tim,
                        ],
                    ],
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }

    private function getCategory4()
    {
        $outsource = $this->recapitulationRepository->getOutsource(19);
        $data = [
            "id" => 4,
            "name" => "Tenaga Outsourcing",
            "total" => $outsource[0],
            "cards" => [
                [
                    "id" => 1,
                    "name" => 'Tenaga Outsourcing',
                    "total" => $outsource[0],
                    "cards" => $outsource[1],
                ],
            ],
        ];
        return $this->response(200, 'success', $data);
    }

    private function getTotalRecapitulation()
    {
        $users = DB::table('users as u');
        $users->select(
            DB::raw('
                COUNT(
                    CASE
                        WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) THEN 1
                        WHEN u.type = 1 AND u.employment_status IN (7, 8, 9) THEN 1
                        WHEN u.type = 2 AND u.employment_status IN (1, 6, 10) AND u.employment_type_id != 16 THEN 1
                        WHEN u.type = 3 AND u.employment_status IN (1, 6, 10) AND u.employment_type_id = 19 THEN 1
                    END
                ) as total
            '),
            DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (1, 6, 10) THEN 1 END) as asn_active'),
            DB::raw('COUNT(CASE WHEN u.type = 1 AND u.employment_status IN (7, 8, 9) THEN 1 END) as asn_nonactive'),
            DB::raw('COUNT(CASE WHEN u.type = 2 AND u.employment_status IN (1, 6, 10) AND u.employment_type_id != 16 THEN 1 END) as nonasn'),
            DB::raw('COUNT(CASE WHEN u.type = 3 AND u.employment_status IN (1, 6, 10) AND u.employment_type_id = 19 THEN 1 END) as outsource'),
        );
        return $users = $users->first();
    }
}
