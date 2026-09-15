<?php

namespace App\Http\Controllers;

use App\Repositories\PositionRepository;
use App\Repositories\PromotionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * @group Promotion
 * Below is the endpoint to get promotion data
 */
class PromotionController extends Controller
{

    protected $promotionRepository;
    protected $positionRepository;

    protected $request;
    protected $posted;

    public function __construct(
        Request $request,
        PromotionRepository $promotionRepository,
        PositionRepository $positionRepository,
    ) {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
        $this->promotionRepository = $promotionRepository;
        $this->positionRepository = $positionRepository;
    }

    /**
     * Get List of Promotions
     *
     * Below is the list of all data Promotions.
     * @authenticated
     * @response 200 {"code":200,"message":"success","data":[{"id":null,"name":"Jabatan Pimpinan Tinggi","cards":[{"id":1,"name":"Jabatan Pimpinan Tinggi Madya (Eselon I)","unoccupied":0},{"id":2,"name":"Jabatan Pimpinan Tinggi Pratama (Eselon II)","unoccupied":0}],"total":0},{"id":null,"name":"Jabatan Administrasi","cards":[{"id":3,"name":"Administrator (Eselon III)","unoccupied":0},{"id":4,"name":"Pengawas (Eselon IV)","unoccupied":0},{"id":9,"name":"Pelaksana","unoccupied":13}],"total":13},{"id":"41,45,49,53,58,62,66,69,74,79,84,89,93","name":"Jabatan Fungsional Analis Kebijakan","cards":[{"id":5,"name":"Ahli Utama","unoccupied":3},{"id":6,"name":"Ahli Madya","unoccupied":9},{"id":7,"name":"Ahli Muda","unoccupied":10},{"id":8,"name":"Ahli Pertama","unoccupied":16}],"total":38},{"id":"47,51,55,64,71,82,87,91,95,102,150,158,193,194,195,196,197,198,199,201,204,207","name":"Jabatan Fungsional Arsiparis","cards":[{"id":6,"name":"Ahli Madya","unoccupied":2},{"id":7,"name":"Ahli Muda","unoccupied":13},{"id":8,"name":"Ahli Pertama","unoccupied":12},{"id":10,"name":"Penyelia","unoccupied":11},{"id":11,"name":"Mahir","unoccupied":14},{"id":12,"name":"Terampil","unoccupied":13}],"total":65},{"id":"103,136","name":"Jabatan Fungsional Pranata Humas","cards":[{"id":6,"name":"Ahli Madya","unoccupied":2},{"id":7,"name":"Ahli Muda","unoccupied":0},{"id":8,"name":"Ahli Pertama","unoccupied":5},{"id":10,"name":"Penyelia","unoccupied":3},{"id":11,"name":"Mahir","unoccupied":4},{"id":12,"name":"Terampil","unoccupied":2}],"total":16},{"id":"202","name":"Jabatan Fungsional Penerjemah","cards":[{"id":6,"name":"Ahli Madya","unoccupied":2},{"id":7,"name":"Ahli Muda","unoccupied":4},{"id":8,"name":"Ahli Pertama","unoccupied":4}],"total":10},{"id":"142","name":"Jabatan Fungsional Analis Pengelolaan Keuangan APBN","cards":[{"id":6,"name":"Ahli Madya","unoccupied":0},{"id":7,"name":"Ahli Muda","unoccupied":0},{"id":8,"name":"Ahli Pertama","unoccupied":4}],"total":4},{"id":"205","name":"Jabatan Fungsional Pranata Keuangan APBN","cards":[{"id":10,"name":"Penyelia","unoccupied":2},{"id":11,"name":"Mahir","unoccupied":7},{"id":12,"name":"Terampil","unoccupied":4}],"total":13},{"id":"203","name":"Jabatan Fungsional Analis Anggaran","cards":[{"id":6,"name":"Ahli Madya","unoccupied":0},{"id":7,"name":"Ahli Muda","unoccupied":2},{"id":8,"name":"Ahli Pertama","unoccupied":2}],"total":4},{"id":"147","name":"Jabatan Fungsional Analis SDM Aparatur","cards":[{"id":6,"name":"Ahli Madya","unoccupied":0},{"id":7,"name":"Ahli Muda","unoccupied":0},{"id":8,"name":"Ahli Pertama","unoccupied":3}],"total":3},{"id":"208","name":"Jabatan Fungsional Pranata SDM Aparatur","cards":[{"id":10,"name":"Penyelia","unoccupied":3},{"id":11,"name":"Mahir","unoccupied":3},{"id":12,"name":"Terampil","unoccupied":1}],"total":7},{"id":"200,206","name":"Jabatan Fungsional Pranata Komputer","cards":[{"id":6,"name":"Ahli Madya","unoccupied":0},{"id":7,"name":"Ahli Muda","unoccupied":2},{"id":8,"name":"Ahli Pertama","unoccupied":6}],"total":8},{"id":"162","name":"Jabatan Fungsional Pengelola Pengadaan Barang / Jasa","cards":[{"id":6,"name":"Ahli Madya","unoccupied":1},{"id":7,"name":"Ahli Muda","unoccupied":0},{"id":8,"name":"Ahli Pertama","unoccupied":1}],"total":2}]}
     */
    public function index()
    {
        $structural = $this->promotionRepository->getAvailablePosition(1, [1, 2, 3, 4, 9], []);
        $functional = $this->promotionRepository->getAvailablePosition(2, [], [
            'analis kebijakan',
            'arsiparis',
            'pranata humas',
            'penerjemah',
            'analis pengelolaan keuangan apbn',
            'pranata keuangan apbn',
            'analis anggaran',
            'pranata sdm aparatur',
            'analis sdm aparatur',
            'pranata komputer',
            'pengelola pengadaan barang / jasa',
        ]);

        $response = [];

        $response[] = $this->addSection(
            1,
            $structural,
            [
                [
                    "echelon_id" => 1,
                    "name" => "Jabatan Pimpinan Tinggi Madya (Eselon I)",
                ],
                [
                    "echelon_id" => 2,
                    "name" => "Jabatan Pimpinan Tinggi Pratama (Eselon II)",
                ],
            ],
            "Jabatan Pimpinan Tinggi"
        );

        // Jabatan Administrasi
        $response[] = $this->addSection(
            1,
            $structural,
            [
                [
                    "echelon_id" => 3,
                    "name" => "Administrator (Eselon III)",
                ],
                [
                    "echelon_id" => 4,
                    "name" => "Pengawas (Eselon IV)",
                ],
                [
                    "echelon_id" => 9,
                    "name" => "Pelaksana",
                ],
            ],
            "Jabatan Administrasi",
        );

        // Jabatan Fungsional Analis Kebijakan
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Analis Kebijakan",
        );

        // Jabatan Fungsional Arsiparis
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Arsiparis",
        );

        // Jabatan Fungsional Pranata Humas
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Pranata Humas",
        );

        // Jabatan Fungsional Penerjemah
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Penerjemah",
        );

        // Jabatan Fungsional Analis Pengelolaan Keuangan APBN
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Analis Pengelolaan Keuangan APBN",
        );

        // Jabatan Fungsional Pranata Keuangan APBN
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Pranata Keuangan APBN",
        );

        // Jabatan Fungsional Analis Anggaran
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Analis Anggaran",
        );

        // Jabatan Fungsional Analis SDM Aparatur
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Analis SDM Aparatur",
        );

        // Jabatan Fungsional Pranata SDM Aparatur
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Pranata SDM Aparatur",
        );

        // Jabatan Fungsional Pranata Komputer
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Pranata Komputer",
        );

        // Jabatan Fungsional Pengelola Pengadaan Barang / Jasa
        $response[] = $this->addSection(
            2,
            $functional,
            [],
            "Pengelola Pengadaan Barang / Jasa",
        );

        return $this->response(200, 'success', $response);
    }

    /**
     * Get Detail of Promotions
     *
     * Below is the detail of all data Promotions.
     * @authenticated
     * @queryParam position_id string Refers to the position_id of promotions. Example: 41,45,49,53,58,62,66,69,74,79,84,89,93
     * @queryParam echelon_id integer Refers to the echelon_id of promotions. Example: 8
     * @response 200 {"code":200,"message":"success","data":[{"id":4,"position_id":45,"name":"Ahli Pertama","unoccupied":2,"work_unit":"Asisten Deputi Ekonomi dan Keuangan"},{"id":13,"position_id":49,"name":"Ahli Pertama","unoccupied":2,"work_unit":"Asisten Deputi Industri, Perdagangan, Pariwisata, dan Ekonomi Kreatif"},{"id":21,"position_id":53,"name":"Ahli Pertama","unoccupied":2,"work_unit":"Asisten Deputi Infrastruktur, Ketahanan Energi, dan Sumber Daya Alam"},{"id":30,"position_id":62,"name":"Ahli Pertama","unoccupied":1,"work_unit":"Asisten Deputi Penanggulangan Kemiskinan"},{"id":38,"position_id":66,"name":"Ahli Pertama","unoccupied":1,"work_unit":"Asisten Deputi Pembangunan Sumber Daya Manusia"},{"id":42,"position_id":69,"name":"Ahli Pertama","unoccupied":2,"work_unit":"Asisten Deputi Pemberdayaan Masyarakat dan Penanggulangan Bencana"},{"id":51,"position_id":79,"name":"Ahli Pertama","unoccupied":1,"work_unit":"Asisten Deputi Hubungan Luar Negeri"},{"id":59,"position_id":84,"name":"Ahli Pertama","unoccupied":2,"work_unit":"Asisten Deputi Politik, Hukum, dan Otonomi Daerah"},{"id":67,"position_id":89,"name":"Ahli Pertama","unoccupied":2,"work_unit":"Asisten Deputi Wawasan Kebangsaan, Pertahanan, dan Keamanan"},{"id":75,"position_id":93,"name":"Ahli Pertama","unoccupied":1,"work_unit":"Asisten Deputi Tata Kelola Pemerintahan"}]}
     */
    public function show()
    {
        $messages = [
            'position_id.regex' => 'Format ID jabatan yang dikirim tidak sesuai.',
            'echelon_id.required' => 'ID eselon harus dikirim',
            'echelon_id.numeric' => 'ID eselon harus berupa angka.',
        ];

        $this->request->validate([
            'position_id' => 'nullable|regex:/^\d+(,\d+)*$/',
            'echelon_id' => 'required|numeric',
        ], $messages);

        $response = $this->promotionRepository->getPromotionByEchelonId($this->request->echelon_id, isset($this->request->position_id) ? explode(",", $this->request->position_id) : []);
        foreach ($response as $data) {
            $shownHierarcy = '';
            //get last 3 parent
            $last3Parent = $this->positionRepository->getRecursivePosition($data->position_id, 4);
            $last3Parent = collect($last3Parent)->filter(function ($item) use ($data) {
                return $item->id != $data->position_id;
            })->reverse()->values()->all();

            if (sizeof($last3Parent)) {
                foreach ($last3Parent as $key => $value) {
                    if ($key > 0) {
                        $shownHierarcy .= " > ";
                    }
                    $shownHierarcy .= $value->name;
                }
            } else {
                $shownHierarcy = 'Sekretariat Wakil Presiden';
            }
            $data->hierarchy = $shownHierarcy;
        }

        return $this->response(200, 'success', $response);
    }

    private function addSection($type, $data, $cards, $name)
    {
        // Jabatan Pimpinan Tinggi
        list($resultCards, $total) = $this->getCards(
            $data,
            $cards,
            $type == 1 ? null : $name,
        );

        return [
            "id" => $type == 1 ? "" : $this->promotionRepository->getPositionIdByName($name),
            "name" => $type == 1 ? $name : "Jabatan Fungsional " . $name,
            "cards" => $resultCards,
            "total" => $total,
        ];
    }

    // $data: structural/functional data
    // $cards: given hardcoded id and card name
    private function getCards($data, $cards, $positionName = null)
    {
        $total = 0;
        $filteredData = array_values(array_filter($data, function ($item) use ($cards, $positionName) {
            if (!isset($positionName)) {
                return in_array($item->echelon_id, Arr::pluck($cards, "echelon_id"));
            } else {
                return $item->position_name == $positionName;
            }
        }));

        $resultCards = array_map(function ($item, $index) use ($cards, &$total, $positionName) {
            $result = [
                "id" => (int) $item->echelon_id,
                "name" => isset($positionName)
                    ? $item->echelon_name
                    : $cards[$index]["name"],
                "unoccupied" => (int) $item->unoccupied,
            ];
            $total += $item->unoccupied;
            return $result;
        }, $filteredData, array_keys($filteredData));

        return [$resultCards, $total];
    }
}
