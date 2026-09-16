<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmployeesImport implements ToArray, WithChunkReading
{
    /** @var array<int, array<int, array<int, mixed>>> */
    public array $data = [];

    public function array(array $rows): void
    {
        $this->data[] = $rows;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
