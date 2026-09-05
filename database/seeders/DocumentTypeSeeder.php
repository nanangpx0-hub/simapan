<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

/**
 * Tipe dokumen kanonik pelaporan SUSENAS-SERUTI (5 laporan, 3 tipe dokumen).
 * Idempotent; append-only terhadap katalog document_types.
 */
class DocumentTypeSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private array $reportingTypes = [
        'P_SUSENAS' => 'Dokumen Pemutakhiran SUSENAS',
        'VSEN_SUSENAS' => 'Dokumen Sampel Utama SUSENAS Kor & Konsumsi',
        'VSERUTI' => 'Dokumen Sampel SERUTI (Sub-sampel)',
    ];

    public function run(): void
    {
        foreach ($this->reportingTypes as $code => $name) {
            DocumentType::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => 'Tipe dokumen pelaporan SUSENAS-SERUTI (standarisasi 5 laporan).',
                    'is_active' => true,
                ]
            );
        }
    }
}
