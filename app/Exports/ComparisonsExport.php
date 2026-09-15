<?php

namespace App\Exports;

use App\Support\ExportLogo;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ComparisonsExport implements FromArray, WithDrawings, WithCustomStartCell, ShouldAutoSize
{
    use Exportable;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $name = ['Nama'];
        foreach ($this->data['users'] as $item) {
            array_push($name, $item->name);
        }

        $position = ['Jabatan'];
        foreach ($this->data['users'] as $item) {
            array_push($position, $item->position_name);
        }

        $echelon = ['Eselon'];
        foreach ($this->data['users'] as $item) {
            array_push($echelon, $item->echelon_name . ', ' . $item->echelon_effective_date);
        }

        $grade = ['Golongan'];
        foreach ($this->data['users'] as $item) {
            array_push($grade, $item->grade_name . ', ' . $item->grade_effective_date);
        }

        $education = ['Pendidikan Terakhir'];
        foreach ($this->data['users'] as $item) {
            array_push($education, $item->education_level . ', ' . $item->education_name);
        }

        $positionHistory = ['Riwayat Jabatan'];
        foreach ($this->data['positions'] as $item) {
            $result = $this->createListOnColumn($item, 'position');
            array_push($positionHistory, $result);
        }

        $strukturalHistory = ['Riwayat Pelatihan Struktural'];
        foreach ($this->data['strukturals'] as $item) {
            $result = $this->createListOnColumn($item, 'name');
            array_push($strukturalHistory, $result);
        }

        $fungsionalHistory = ['Riwayat Pelatihan Fungsional'];
        foreach ($this->data['fungsionals'] as $item) {
            $result = $this->createListOnColumn($item, 'name');
            array_push($fungsionalHistory, $result);
        }

        $teknisHistory = ['Riwayat Pelatihan Teknis'];
        foreach ($this->data['tekniss'] as $item) {
            $result = $this->createListOnColumn($item, 'name');
            array_push($teknisHistory, $result);
        }

        $targetHistory = ['Penilaian SKP (2 Tahun terakhir)'];
        foreach ($this->data['targets'] as $item) {
            $result = $this->createListOnColumn($item, 'name', 'target');
            array_push($targetHistory, $result);
        }

        $disciplinaryHistory = ['Riwayat Hukuman Disiplin'];
        foreach ($this->data['disciplinaries'] as $item) {
            $result = $this->createListOnColumn($item, 'name', 'disciplinary');
            array_push($disciplinaryHistory, $result);
        }

        $noteHistory = ['Catatan'];
        foreach ($this->data['notes'] as $item) {
            $result = $this->createListOnColumn($item, 'description');
            array_push($noteHistory, $result);
        }

        $assessmentHistory = ['Hasil Assessment'];
        foreach ($this->data['assessments'] as $item) {
            $result = $this->createListOnColumn($item, 'description', 'assessment');
            array_push($assessmentHistory, $result);
        }

        $competencyHistory = ['Hasil Uji Kompetensi'];
        foreach ($this->data['competencies'] as $item) {
            $result = $this->createListOnColumn($item, 'description', 'assessment');
            array_push($competencyHistory, $result);
        }

        $talentHistory = ['Hasil Talent Pool'];
        foreach ($this->data['talents'] as $item) {
            $result = $this->createListOnColumn($item, 'description', 'assessment');
            array_push($talentHistory, $result);
        }

        return [
            $name,
            $position,
            $echelon,
            $grade,
            $education,
            $positionHistory,
            $strukturalHistory,
            $fungsionalHistory,
            $teknisHistory,
            $targetHistory,
            $disciplinaryHistory,
            $noteHistory,
            $assessmentHistory,
            $competencyHistory,
            $talentHistory,
        ];
    }

    public function drawings(): \PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing|array
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Sekretariat Wakil Presiden');
        $drawing->setPath(ExportLogo::path());
        $drawing->setHeight(75);
        $drawing->setCoordinates('A1');
        return $drawing;
    }

    public function startCell(): string
    {
        return 'A6';
    }

    private function createListOnColumn($data, $object, $type = null)
    {
        $string = "";
        if ($type == 'target') {
            foreach ($data as $index => $item) {
                $string .= ($index + 1) . ". Rating Perilaku Kerja: " . $item['work_behavior_rating'] . ', Predikat Kinerja Pegawai: ' . $item['employee_performance_predicate'] . "\n";
            }
        } elseif ($type == 'disciplinary') {
            foreach ($data as $index => $item) {
                $string .= ($index + 1) . ". " . $item['description'] . ', ' . $item['start_date'] . "\n";
            }
        } elseif ($type == 'assessment') {
            foreach ($data as $index => $item) {
                $string .= ($index + 1) . ". " . $item['event_date'] . ', ' . $item['point'] . "\n";
            }
        } else {
            $mapped = Arr::pluck($data, $object);
            foreach ($mapped as $index => $item) {
                $string .= ($index + 1) . ". " . $item . "\n";
            }
        }
        return $string;
    }
}
