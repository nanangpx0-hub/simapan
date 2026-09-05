<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportingReconciliationExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    use Exportable;

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        private array $rows,
        private string $periodName,
    ) {}

    /**
     * @return list<array<int, string>>
     */
    public function array(): array
    {
        return array_map(
            fn (array $row): array => [
                (string) $row['nks'],
                (string) $row['lap1'],
                (string) $row['lap3'],
                (string) $row['lap2'],
                (string) $row['lap4'],
                (string) $row['lap5'],
                (string) $row['recon'],
                (string) $row['sla'],
            ],
            $this->rows
        );
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['NKS', 'Status Lap 1', 'Status Lap 3', 'Status Lap 2', 'Status Lap 4', 'Status Lap 5', 'Status Rekonsiliasi', 'SLA Status'];
    }

    public function title(): string
    {
        return 'Rekonsiliasi 5 Dokumen';
    }
}
