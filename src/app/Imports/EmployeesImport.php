<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\BeforeSheet;

class EmployeesImport implements Import, WithChunkReading, WithMultipleSheets
{
    /** @var array<int, EmployeeSheetImport> */
    private array $sheetImports = [];

    public function __construct(int $sheetCount)
    {
        for ($index = 0; $index < $sheetCount; $index++) {
            $this->sheetImports[$index] = new EmployeeSheetImport;
        }
    }

    /** @return array<int, EmployeeSheetImport> */
    public function sheets(): array
    {
        return $this->sheetImports;
    }

    /** @return array<int, array<int, array<int, mixed>>> */
    public function data(): array
    {
        return array_map(
            static fn (EmployeeSheetImport $sheet): array => $sheet->rows(),
            $this->sheetImports,
        );
    }

    /** @return array<int, string|null> */
    public function sheetNames(): array
    {
        return array_map(
            static fn (EmployeeSheetImport $sheet): ?string => $sheet->name(),
            $this->sheetImports,
        );
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}

class EmployeeSheetImport implements ToArray, WithEvents
{
    /** @var array<int, array<int, mixed>> */
    private array $data = [];

    private ?string $sheetName = null;

    public function array(array $rows): void
    {
        $this->data = array_merge($this->data, $rows);
    }

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event): void {
                $this->sheetName = $event->getSheet()->getDelegate()->getTitle();
            },
        ];
    }

    /** @return array<int, array<int, mixed>> */
    public function rows(): array
    {
        return $this->data;
    }

    public function name(): ?string
    {
        return $this->sheetName;
    }
}
