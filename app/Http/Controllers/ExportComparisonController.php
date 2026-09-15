<?php

namespace App\Http\Controllers;

use App\Exports\ComparisonsExport;
use App\Repositories\ComparisonRepository;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @group Export
 */
class ExportComparisonController extends Controller
{
    protected $comparisonRepository;
    protected Request $request;
    protected array $posted;

    public function __construct(
        Request $request,
        ComparisonRepository $comparisonRepository
    ) {
        $this->request = $request;
        $this->posted = $request->except('_token', '_method');
        $this->comparisonRepository = $comparisonRepository;
    }

    /**
     * Export Comparison
     *
     * Export comparison data between employee
     * @authenticated
     * @bodyParam user_id int[] list of user_id' id. Example: [1,2]
     * @bodyParam output string Refers output of data, the list is .pdf, .xlsx, .csv. Example: .pdf
     */
    public function comparison()
    {
        $messages = [
            'user_id.required' => 'User ID tidak boleh kosong.',
            'user_id.array' => 'User ID harus berupa array.',
            'user_id.min' => 'User ID minimal 2 buah.',
            'user_id.max' => 'User ID maksimal 5 buah.',
            'user_id.*.required' => 'User ID tidak boleh kosong.',
            'user_id.*.numeric' => 'User ID harus berupa angka.',
            'output.required' => 'Output tidak boleh kosong.',
            'output.in' => 'Output harus diantara .pdf, .xlsx, .csv.',
        ];

        $validatedData = $this->request->validate([
            'user_id' => 'required|array|min:2|max:5',
            'user_id.*' => 'required|numeric',
            'output' => 'required|in:.pdf,.xlsx,.csv',
        ], $messages);

        $data = $this->comparisonRepository->getDetailUsers($this->request->user_id, true);

        $title = 'Bandingkan Pegawai';
        $date = Carbon::now()->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMMM Y');

        if ($this->request->output == '.pdf') {
            $tmp = sys_get_temp_dir();
            $pdf = Pdf::loadview('exports/comparison', [
                'title' => $title,
                'date' => $date,
                'data' => $data,
            ]);
            $pdf->set_option('isHtml5ParserEnabled', true);
            $pdf->set_paper("A2", "landscape");
            $pdf->set_option('isRemoteEnabled', true);
            $pdf->set_option('fontDir', $tmp);
            $pdf->set_option('fontCache', $tmp);
            $pdf->set_option('tempDir', $tmp);
            return $pdf->download($title . ' - ' . $date . '.pdf');
        } elseif ($this->request->output == '.xlsx') {
            return (new ComparisonsExport($data))->download($title . ' - ' . $date . '.xlsx');
        } else {
            return (new ComparisonsExport($data))->download($title . ' - ' . $date . '.csv');
        }
    }

    /**
     * Export Comparison Promotion
     *
     * Below is for export user based for promotion by user id.
     * @authenticated
     * @bodyParam user_id int[] list of user_id' id. Example: [1,2]
     */
    public function comparisonPromotion()
    {
        $messages = [
            'user_id.required' => 'User ID tidak boleh kosong.',
            'user_id.array' => 'User ID harus berupa array.',
            'user_id.min' => 'User ID minimal 2 buah.',
            'user_id.max' => 'User ID maksimal 5 buah.',
            'user_id.*.required' => 'User ID tidak boleh kosong.',
            'user_id.*.numeric' => 'User ID harus berupa angka.',
        ];

        $this->request->validate([
            'user_id' => 'required|array|min:2|max:5',
            'user_id.*' => 'required|numeric',
        ], $messages);

        $colors = [
            '#F16637',
            '#74B856',
            '#2D9DD1',
            '#F8A232',
            '#506CB2',
            '#C22551',
        ];

        $users = $this->comparisonRepository->getUserByIds($this->request->user_id, true);

        foreach ($users as $key => $user) {
            $user->color = $colors[$key];
        }

        $tmp = sys_get_temp_dir();

        $pdf = Pdf::loadview('exports/promotion', [
            'users' => $users,
        ]);

        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_paper("A4", "portrait");
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('fontDir', $tmp);
        $pdf->set_option('fontCache', $tmp);
        $pdf->set_option('tempDir', $tmp);
        return $pdf->download('promotion-user.pdf');
    }
}
