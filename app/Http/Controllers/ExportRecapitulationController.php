<?php

namespace App\Http\Controllers;

use App\Repositories\RecapitulationRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @group Export
 */
class ExportRecapitulationController extends Controller
{
    protected Request $request;
    protected array $posted;
    protected RecapitulationRepository $recapitulationRepository;

    public function __construct(
        Request $request,
        RecapitulationRepository $recapitulationRepository
    ) {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
        $this->recapitulationRepository = $recapitulationRepository;
    }

    /**
     * Export Recapitulation
     *
     * Export all recapitulation data, asn, non asn and outsource to .pdf
     * @group Export
     * @authenticated
     * @urlParam type Refers to the Type of recapitulation 1=All Recapitulation, 2=ASN, 3=Non ASN, 4=Outsource. Example: 1
     * @response 404 {"code": 404,"message": "Tipe tidak ditemukan. harap gunakan 1, 2, 3 atau 4.","data": null}
     */
    public function recapitulation()
    {
        if ($this->request->type == 1) {
            return $this->allRecapitulation();
        } else if ($this->request->type == 2) {
            return $this->asn();
        } else if ($this->request->type == 3) {
            return $this->nonAsn();
        } else if ($this->request->type == 4) {
            return $this->outsource();
        } else {
            return $this->response(404, 'Tipe tidak ditemukan. harap gunakan 1, 2, 3 atau 4.');
        }
    }

    private function allRecapitulation()
    {
        $pejabat = $this->recapitulationRepository->getPejabatPimpinanAndFungsional();
        $pelaksana = $this->recapitulationRepository->getPejabatPelaksana();
        $pppk = $this->recapitulationRepository->getPejabatPppk();
        $pejabatDiperbantukan = $this->recapitulationRepository->getPejabatDiperbantukan(4);
        $nonActive = $this->recapitulationRepository->getNonActiveAsn();
        $jabatanNonAsn = $this->recapitulationRepository->getJabatanNonAsn();
        $tim = $this->recapitulationRepository->getTim(15);

        /**
         * Begin total ASN active + non active
         */
        $arrayAsnActiveNonActive = [
            [
                'title' => 'Pejabat Pimpinan',
                'body' => 'Total : ' . $pejabat->total_pejabat_pimpinan,
                'type' => 1,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pimpinan Tinggi Madya (Eselon I)',
                'body' => $pejabat->echelon1,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pimpinan Tinggi Pratama (Eselon II)',
                'body' => $pejabat->echelon2,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Administrasi (Eselon III)',
                'body' => $pejabat->echelon3,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pengawas (Eselon IV)',
                'body' => $pejabat->echelon4,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pelaksana',
                'body' => 'Total : ' . $pelaksana->total,
                'type' => 1,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pelaksana Golongan IV',
                'body' => $pelaksana->golongan4,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pelaksana Golongan III',
                'body' => $pelaksana->golongan3,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pelaksana Golongan II',
                'body' => $pelaksana->golongan2,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pelaksana PPPK',
                'body' => $pppk->pelaksana,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pelaksana Perbantuan TNI dan POLRI',
                'body' => $pelaksana->tnipolri,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Keahlian',
                'body' => 'Total : ' . $pejabat->total_pejabat_fungsional_keahlian,
                'type' => 1,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Ahli Utama',
                'body' => $pejabat->ahli_utama,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Ahli Madya',
                'body' => $pejabat->ahli_madya,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Ahli Muda',
                'body' => $pejabat->ahli_muda,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Ahli Pertama',
                'body' => $pejabat->ahli_pertama,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Keahlian PPPK',
                'body' => $pppk->fungsional_keahlian,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Keterampilan',
                'body' => 'Total : ' . $pejabat->total_pejabat_fungsional_keterampilan,
                'type' => 1,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Penyelia',
                'body' => $pejabat->penyelia,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Mahir',
                'body' => $pejabat->mahir,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Terampil',
                'body' => $pejabat->terampil,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Pemula',
                'body' => $pejabat->pemula,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional Keterampilan PPPK',
                'body' => $pppk->fungsional_keterampilan,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Kemensetneg Yang Diperbantukan di Setwapres',
                'body' => 'Total : ' . $pejabatDiperbantukan->total,
                'type' => 1,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Struktural',
                'body' => $pejabatDiperbantukan->struktural,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Pelaksana',
                'body' => $pejabatDiperbantukan->pelaksana,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Pejabat Fungsional',
                'body' => $pejabatDiperbantukan->fungsional,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Aparatur Sipil Negara (ASN) Aktif + Perbantuan TNI/POLRI Pelaksana',
                'body' => 'Total : ' . $pejabat->total_pejabat_pimpinan + $pelaksana->total + $pejabat->total_pejabat_fungsional_keahlian + $pejabat->total_pejabat_fungsional_keterampilan,
                'type' => 3,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Tugas Belajar Luar Negeri (TBLN)',
                'body' => $nonActive->tbln,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Cuti di Luar Tanggungan Negara (CLTN)',
                'body' => $nonActive->cltn,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Tidak Aktif (Non Jabatan)',
                'body' => $nonActive->nonactive,
                'type' => 2,
                'kelompokan' => 1,
            ],
            [
                'title' => 'Aparatur Sipil Negara (ASN) Non Aktif',
                'body' => 'Total : ' . $nonActive->total,
                'type' => 3,
                'kelompokan' => 1,
            ],
        ];

        /**
         * Begin total non asn
         */
        $array = array();
        foreach ($jabatanNonAsn[1] as $item) {
            array_push($array, ['title' => $item['name'], 'body' => $item['total'], 'type' => 2, 'kelompokan' => 2]);
        }
        $arrayNonAsn = array_merge($array, [['title' => 'Non Aparatur Sipil Negara (Non ASN)', 'body' => 'Total: ' . $jabatanNonAsn[0], 'type' => 3, 'kelompokan' => 2]]);

        /**
         * Begin total non asn + tim
         */
        $arrayNonAsnTim = [
            [
                'title' => 'Tim',
                'body' => 'Total : ' . $tim,
                'type' => 1,
                'kelompokan' => 3,
            ],
            [
                'title' => 'Tim Nasional Percepatan Stunting (TPPS)',
                'body' => $tim,
                'type' => 2,
                'kelompokan' => 3,
            ],
            [
                'title' => 'Non Aparatur Sipil Negara (Non ASN) + Tim',
                'body' => 'Total : ' . $jabatanNonAsn[0] + $tim,
                'type' => 3,
                'kelompokan' => 3,
            ],
        ];

        /**
         * Begin total outsourcing
         */
        $outsource = $this->recapitulationRepository->getOutsource(19);
        $array = array();
        foreach ($outsource[1] as $item) {
            array_push($array, ['title' => $item->name, 'body' => $item->total, 'type' => 2, 'kelompokan' => 4]);
        }
        $arrayOutsource = array_merge($array, [['title' => 'Tenaga Outsourcing', 'body' => 'Total: ' . $outsource[0], 'type' => 3, 'kelompokan' => 4]]);

        /**
         * Grand total
         */
        $grandTotal = [[
            'title' => 'Grand Total',
            'body' => 'Total : ' . $pejabat->total_pejabat_pimpinan + $pelaksana->total + $pejabat->total_pejabat_fungsional_keahlian + $pejabat->total_pejabat_fungsional_keterampilan + $nonActive->total + $jabatanNonAsn[0] + $tim + $outsource[0],
            'type' => 3,
            'kelompokan' => 'Utama',
        ]];

        $title = 'Rekapitulasi Pegawai';
        $date = Carbon::now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y');
        $tmp = sys_get_temp_dir();
        $pdf = Pdf::loadview('exports/recapitulation', [
            'title' => $title . ' Sekretariat Wakil Presiden RI',
            'date' => $date,
            'data' => array_merge($grandTotal, $arrayAsnActiveNonActive, $arrayNonAsn, $arrayNonAsnTim, $arrayOutsource),
        ]);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_paper("A4", "portrait");
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('fontDir', $tmp);
        $pdf->set_option('fontCache', $tmp);
        $pdf->set_option('tempDir', $tmp);
        return $pdf->download($title . ' - ' . $date . '.pdf');
    }

    private function asn()
    {
        $unitKerja = $this->recapitulationRepository->getTotalUnitKerja();
        $pejabat = $this->recapitulationRepository->getPimpinanTinggi();
        $administrasi = $this->recapitulationRepository->getAdministrasi();
        $fungsional = $this->recapitulationRepository->getPejabatPimpinanAndFungsional();
        $grade = $this->recapitulationRepository->getGrade(1);
        $gradeTotalByGroup = $this->recapitulationRepository->getGradeTotalByGroup();
        $pelaksana = $this->recapitulationRepository->getPejabatPelaksana();
        $gradePPPK = $this->recapitulationRepository->getGrade(2);
        $nonActive = $this->recapitulationRepository->getNonActiveAsn();
        $educationAndGender = $this->recapitulationRepository->getEducationAndGender(1);

        $array = array();
        foreach ($unitKerja['data'] as $item) {
            array_push($array, ['title' => $item['name'], 'body' => $item['total'], 'type' => 2]);
        }
        $unitKerjaArray = array_merge($array, [['title' => 'Unit Kerja', 'body' => 'Total: ' . $unitKerja['total'], 'type' => 3]]);

        $pejabatArray = [
            [
                'title' => 'Jabatan Pimpinan Tinggi',
                'body' => 'Total : ' . $pejabat->total_jabatan_pimpinan_tinggi,
                'type' => 1,
            ],
            [
                'title' => 'Jabatan Pimpinan Madya',
                'body' => $pejabat->jabatan_tinggi_madya,
                'type' => 2,
            ],
            [
                'title' => 'Jabatan Pimpinan Pratama',
                'body' => $pejabat->jabatan_tinggi_pratama,
                'type' => 2,
            ],
        ];

        $administrasiArray = [
            [
                'title' => 'Jabatan Administrasi',
                'body' => 'Total : ' . $administrasi->total_jabatan_administrasi,
                'type' => 1,
            ],
            [
                'title' => 'Administrator',
                'body' => $administrasi->jabatan_administrasi,
                'type' => 2,
            ],
            [
                'title' => 'Pengawas',
                'body' => $administrasi->jabatan_pengawas,
                'type' => 2,
            ],
            [
                'title' => 'Pelaksana',
                'body' => $administrasi->jabatan_pelaksana,
                'type' => 2,
            ],
        ];

        $fungsionalArray = [
            [
                'title' => 'Jabatan Fungsional',
                'body' => 'Total : ' . $fungsional->total_pejabat_fungsional_keahlian + $fungsional->total_pejabat_fungsional_keterampilan,
                'type' => 1,
            ],
            [
                'title' => 'Keahlian',
                'body' => $fungsional->total_pejabat_fungsional_keahlian,
                'type' => 1,
            ],
            [
                'title' => 'Ahli Utama',
                'body' => $fungsional->ahli_utama,
                'type' => 2,
            ],
            [
                'title' => 'Ahli Madya',
                'body' => $fungsional->ahli_madya,
                'type' => 2,
            ],
            [
                'title' => 'Ahli Muda',
                'body' => $fungsional->ahli_muda,
                'type' => 2,
            ],
            [
                'title' => 'Ahli Pertama',
                'body' => $fungsional->ahli_pertama,
                'type' => 2,
            ],
            [
                'title' => 'Keterampilan',
                'body' => $fungsional->total_pejabat_fungsional_keterampilan,
                'type' => 1,
            ],
            [
                'title' => 'Penyelia',
                'body' => $fungsional->penyelia,
                'type' => 2,
            ],
            [
                'title' => 'Mahir',
                'body' => $fungsional->mahir,
                'type' => 2,
            ],
            [
                'title' => 'Terampil',
                'body' => $fungsional->terampil,
                'type' => 2,
            ],
            [
                'title' => 'Pemula',
                'body' => $fungsional->pemula,
                'type' => 2,
            ],
            [
                'title' => 'Aparatur Sipil Negara (ASN) + Perbantuan TNI/POLRI Pelaksana',
                'body' => 'Total : ' . $pejabat->total_jabatan_pimpinan_tinggi + $administrasi->total_jabatan_administrasi + $fungsional->total_pejabat_fungsional_keahlian + $fungsional->total_pejabat_fungsional_keterampilan,
                'type' => 3,
            ],
        ];

        $array = array();
        foreach ($grade[1] as $item) {
            array_push($array, ['title' => $item->name, 'body' => $item->total, 'type' => 2]);
        }
        $grade = array_merge($array, [['title' => 'Golongan/Pangkat', 'body' => 'Total: ' . $grade[0], 'type' => 3]]);

        $totalGolongan = [
            [
                'title' => 'Golongan IV (Pembina)',
                'body' => $gradeTotalByGroup->pembina,
                'type' => 2,
            ],
            [
                'title' => 'Golongan III (Penata)',
                'body' => $gradeTotalByGroup->penata,
                'type' => 2,
            ],
            [
                'title' => 'Golongan II (Pengatur)',
                'body' => $gradeTotalByGroup->pengatur,
                'type' => 2,
            ],
            [
                'title' => 'Total Golongan/Pangkat',
                'body' => 'Total : ' . $gradeTotalByGroup->total,
                'type' => 3,
            ],
        ];

        $array = array();
        foreach ($gradePPPK[1] as $item) {
            array_push($array, ['title' => $item->name, 'body' => $item->total, 'type' => 2]);
        }
        $arrayGradePPPK = array_merge($array, [['title' => 'Golongan PPPK', 'body' => 'Total: ' . $gradePPPK[0], 'type' => 3]]);

        $totalPPK = [
            [
                'title' => 'Golongan IV (Pembina)',
                'body' => $pelaksana->golongan4,
                'type' => 2,
            ],
            [
                'title' => 'Golongan III (Penata)',
                'body' => $pelaksana->golongan3,
                'type' => 2,
            ],
            [
                'title' => 'Golongan II (Pengatur)',
                'body' => $pelaksana->golongan2,
                'type' => 2,
            ],
            [
                'title' => 'Total Golongan/Pangkat',
                'body' => 'Total : ' . $pelaksana->golongan4 + $pelaksana->golongan3 + $pelaksana->golongan2,
                'type' => 3,
            ],
        ];

        $arrayNonActive = [
            [
                'title' => 'Tugas Belajar Luar Negeri (TBLN)',
                'body' => $nonActive->tbln,
                'type' => 2,
            ],
            [
                'title' => 'Cuti di Luar Tanggungan Negara (CLTN)',
                'body' => $nonActive->cltn,
                'type' => 2,
            ],
            [
                'title' => 'Pegawai Non Aktif',
                'body' => 'Total : ' . $nonActive->tbln + $nonActive->cltn,
                'type' => 3,
            ],
        ];

        $educationAndGender = [
            [
                'title' => 'Strata III',
                'body' => $educationAndGender->s3,
                'type' => 2,
            ],
            [
                'title' => 'Strata II',
                'body' => $educationAndGender->s2,
                'type' => 2,
            ],
            [
                'title' => 'Diploma IV/Strata I',
                'body' => $educationAndGender->s1,
                'type' => 2,
            ],
            [
                'title' => 'Akademi/Diploma III/Sarjana Muda',
                'body' => $educationAndGender->d3,
                'type' => 2,
            ],
            [
                'title' => 'Diploma I/II',
                'body' => $educationAndGender->d1,
                'type' => 2,
            ],
            [
                'title' => 'SLTA/Sederajat',
                'body' => $educationAndGender->sma,
                'type' => 2,
            ],
            [
                'title' => 'SLTP/Sederajat',
                'body' => $educationAndGender->smp,
                'type' => 2,
            ],
            [
                'title' => 'SD/Sederajat',
                'body' => $educationAndGender->sd,
                'type' => 2,
            ],
            [
                'title' => 'Pendidikan',
                'body' => 'Total : ' . $educationAndGender->total_education,
                'type' => 3,
            ],
            [
                'title' => 'Laki-laki',
                'body' => $educationAndGender->male,
                'type' => 2,
            ],
            [
                'title' => 'Perempuan',
                'body' => $educationAndGender->female,
                'type' => 2,
            ],
            [
                'title' => 'Jenis Kelamin',
                'body' => 'Total : ' . $educationAndGender->total_gender,
                'type' => 3,
            ],
        ];

        $grandTotal = [[
            'title' => 'Grand Total',
            'body' => 'Total : ' . $pejabat->total_jabatan_pimpinan_tinggi + $administrasi->total_jabatan_administrasi + $fungsional->total_pejabat_fungsional_keahlian + $fungsional->total_pejabat_fungsional_keterampilan,
            'type' => 3,
        ]];

        $title = 'Rekapitulasi Pegawai ASN';
        $date = Carbon::now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y');
        $tmp = sys_get_temp_dir();
        $pdf = Pdf::loadview('exports/recapitulation', [
            'title' => $title . ' Sekretariat Wakil Presiden RI',
            'date' => $date,
            'data' => array_merge($grandTotal, $unitKerjaArray, $pejabatArray, $administrasiArray, $fungsionalArray, $grade, $totalGolongan, $arrayGradePPPK, $educationAndGender, $arrayNonActive),
        ]);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_paper("A4", "portrait");
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('fontDir', $tmp);
        $pdf->set_option('fontCache', $tmp);
        $pdf->set_option('tempDir', $tmp);
        return $pdf->download($title . ' - ' . $date . '.pdf');
    }

    private function nonAsn()
    {
        $jabatanNonAsn = $this->recapitulationRepository->getJabatanNonAsn();
        $educationAndGender = $this->recapitulationRepository->getEducationAndGender(2);
        $tim = $this->recapitulationRepository->getTim(15);

        $array = array();
        foreach ($jabatanNonAsn[1] as $item) {
            array_push($array, ['title' => $item['name'], 'body' => $item['total'], 'type' => 2]);
        }
        $arrayJabatanNonAsn = array_merge($array, [['title' => 'Jabatan', 'body' => 'Total: ' . $jabatanNonAsn[0], 'type' => 3]]);
        $arrayData = [
            [
                'title' => 'Tim Nasional Percepatan Penurunan Stunting (TPPS)',
                'body' => $tim,
                'type' => 2,
            ],
            [
                'title' => 'Tim',
                'body' => 'Total : ' . $tim,
                'type' => 3,
            ],
            [
                'title' => 'Strata III',
                'body' => $educationAndGender->s3,
                'type' => 2,
            ],
            [
                'title' => 'Strata II',
                'body' => $educationAndGender->s2,
                'type' => 2,
            ],
            [
                'title' => 'Diploma IV/Strata I',
                'body' => $educationAndGender->s1,
                'type' => 2,
            ],
            [
                'title' => 'Akademi/Diploma III/Sarjana Muda',
                'body' => $educationAndGender->d3,
                'type' => 2,
            ],
            [
                'title' => 'Diploma I/II',
                'body' => $educationAndGender->d1,
                'type' => 2,
            ],
            [
                'title' => 'SLTA/Sederajat',
                'body' => $educationAndGender->sma,
                'type' => 2,
            ],
            [
                'title' => 'SLTP/Sederajat',
                'body' => $educationAndGender->smp,
                'type' => 2,
            ],
            [
                'title' => 'SD/Sederajat',
                'body' => $educationAndGender->sd,
                'type' => 2,
            ],
            [
                'title' => 'Pendidikan',
                'body' => 'Total : ' . $educationAndGender->total_education,
                'type' => 3,
            ],
            [
                'title' => 'Laki-laki',
                'body' => $educationAndGender->male,
                'type' => 2,
            ],
            [
                'title' => 'Perempuan',
                'body' => $educationAndGender->female,
                'type' => 2,
            ],
            [
                'title' => 'Jenis Kelamin',
                'body' => 'Total : ' . $educationAndGender->total_gender,
                'type' => 3,
            ],
        ];

        $grandTotal = [[
            'title' => 'Grand Total',
            'body' => 'Total : ' . $jabatanNonAsn[0] + $tim,
            'type' => 3,
        ]];

        $title = 'Rekapitulasi Pegawai Non ASN';
        $date = Carbon::now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y');
        $tmp = sys_get_temp_dir();
        $pdf = Pdf::loadview('exports/recapitulation', [
            'title' => $title . ' Sekretariat Wakil Presiden RI',
            'date' => $date,
            'data' => array_merge($grandTotal, $arrayJabatanNonAsn, $arrayData),
        ]);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_paper("A4", "portrait");
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('fontDir', $tmp);
        $pdf->set_option('fontCache', $tmp);
        $pdf->set_option('tempDir', $tmp);
        return $pdf->download($title . ' - ' . $date . '.pdf');
    }

    private function outsource()
    {
        $outsource = $this->recapitulationRepository->getOutsource(19);
        $educationAndGender = $this->recapitulationRepository->getEducationAndGender(3);

        $array = array();
        foreach ($outsource[1] as $item) {
            array_push($array, ['title' => $item->name, 'body' => $item->total, 'type' => 2]);
        }
        $outsource = array_merge($array, [['title' => 'Tenaga Outsourcing', 'body' => 'Total: ' . $outsource[0], 'type' => 3]]);

        $educationAndGender = [
            [
                'title' => 'Strata III',
                'body' => $educationAndGender->s3,
                'type' => 2,
            ],
            [
                'title' => 'Strata II',
                'body' => $educationAndGender->s2,
                'type' => 2,
            ],
            [
                'title' => 'Diploma IV/Strata I',
                'body' => $educationAndGender->s1,
                'type' => 2,
            ],
            [
                'title' => 'Akademi/Diploma III/Sarjana Muda',
                'body' => $educationAndGender->d3,
                'type' => 2,
            ],
            [
                'title' => 'Diploma I/II',
                'body' => $educationAndGender->d1,
                'type' => 2,
            ],
            [
                'title' => 'SLTA/Sederajat',
                'body' => $educationAndGender->sma,
                'type' => 2,
            ],
            [
                'title' => 'SLTP/Sederajat',
                'body' => $educationAndGender->smp,
                'type' => 2,
            ],
            [
                'title' => 'SD/Sederajat',
                'body' => $educationAndGender->sd,
                'type' => 2,
            ],
            [
                'title' => 'Pendidikan',
                'body' => 'Total : ' . $educationAndGender->total_education,
                'type' => 3,
            ],
            [
                'title' => 'Laki-laki',
                'body' => $educationAndGender->male,
                'type' => 2,
            ],
            [
                'title' => 'Perempuan',
                'body' => $educationAndGender->female,
                'type' => 2,
            ],
            [
                'title' => 'Jenis Kelamin',
                'body' => 'Total : ' . $educationAndGender->total_gender,
                'type' => 3,
            ],
        ];

        $title = 'Rekapitulasi Pegawai Outsourcing';
        $date = Carbon::now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y');
        $tmp = sys_get_temp_dir();
        $pdf = Pdf::loadview('exports/recapitulation', [
            'title' => $title . ' Sekretariat Wakil Presiden RI',
            'date' => $date,
            'data' => array_merge($outsource, $educationAndGender),
        ]);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_paper("A4", "portrait");
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('fontDir', $tmp);
        $pdf->set_option('fontCache', $tmp);
        $pdf->set_option('tempDir', $tmp);
        return $pdf->download($title . ' - ' . $date . '.pdf');
    }
}
