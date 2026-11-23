<?php

use App\Imports\EmployeesImport;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function sourceDeltaWorkbook(int $rootDataRows): string
{
    $spreadsheet = new Spreadsheet;
    $spreadsheet->removeSheetByIndex(0);

    foreach ([
        ['Data Pegawai', 27],
        ['Riwayat Pendidikan', 10],
        ['Riwayat Jabatan', 19],
        ['Riwayat Pelatihan Teknis', 11],
        ['Riwayat Keluarga', 19],
        ['Riwayat Catatan', 3],
    ] as [$name, $width]) {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($name);
        for ($column = 1; $column <= $width; $column++) {
            $sheet->setCellValue([$column, 1], "Header {$column}");
        }
    }

    $root = $spreadsheet->getSheet(0);
    for ($row = 1; $row <= $rootDataRows; $row++) {
        $excelRow = $row + 1;
        $root->setCellValue([1, $excelRow], 'Employee '.$row);
        $root->setCellValue([18, $excelRow], 'NIK-'.$row);
        $root->setCellValue([27, $excelRow], 'Emergency '.$row);
    }

    $nik = 'NIK-'.$rootDataRows;
    $spreadsheet->getSheet(1)->setCellValue([1, 2], $nik);
    $spreadsheet->getSheet(1)->setCellValue([10, 2], 'Education');
    $spreadsheet->getSheet(2)->setCellValue([4, 2], $nik);
    $spreadsheet->getSheet(2)->setCellValue([19, 2], 'Position');
    $spreadsheet->getSheet(3)->setCellValue([10, 2], $nik);
    $spreadsheet->getSheet(3)->setCellValue([11, 2], 'Training');
    $spreadsheet->getSheet(4)->setCellValue([1, 2], $nik);
    $spreadsheet->getSheet(4)->setCellValue([19, 2], 'Family');
    $spreadsheet->getSheet(5)->setCellValue([1, 2], $nik);
    $spreadsheet->getSheet(5)->setCellValue([3, 2], 'Note');

    $path = tempnam(sys_get_temp_dir(), 'simdatuk-delta-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

it('keeps every import sheet stable across Laravel Excel chunk boundaries', function (int $rootDataRows) {
    $path = sourceDeltaWorkbook($rootDataRows);

    try {
        $import = new EmployeesImport(6);
        Excel::import($import, $path, null, ExcelFormat::XLSX);

        $sheets = $import->data();
        expect($import->sheetNames())->toBe([
            'Data Pegawai',
            'Riwayat Pendidikan',
            'Riwayat Jabatan',
            'Riwayat Pelatihan Teknis',
            'Riwayat Keluarga',
            'Riwayat Catatan',
        ])->and($sheets[0])->toHaveCount($rootDataRows + 1)
            ->and($sheets[0][$rootDataRows][17])->toBe('NIK-'.$rootDataRows);

        foreach ([1, 2, 3, 4, 5] as $sheetIndex) {
            $nikColumn = $sheetIndex === 2 ? 3 : ($sheetIndex === 3 ? 9 : 0);
            expect($sheets[$sheetIndex])->toHaveCount(2)
                ->and($sheets[$sheetIndex][1][$nikColumn])->toBe('NIK-'.$rootDataRows);
        }
    } finally {
        @unlink($path);
    }
})->with([
    'before first boundary' => 999,
    'at first boundary' => 1000,
    'after first boundary' => 1001,
    'after second boundary' => 2001,
]);

it('names only the Employee update route for update-permission selection', function () {
    $update = Route::getRoutes()->getByName('employees.update');

    expect($update)->not->toBeNull()
        ->and($update->methods()[0])->toBe('POST')
        ->and($update->uri())->toBe('api/employees/{id}')
        ->and($update->getActionName())->toBe('App\\Http\\Controllers\\EmployeeController@update');
});
