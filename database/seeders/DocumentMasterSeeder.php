<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DocumentLocation;
use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentMasterSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private array $types = [
        'KUESIONER' => 'Kuesioner Survei',
        'DAFTAR_SAMPEL' => 'Daftar Sampel',
        'BERITA_ACARA' => 'Berita Acara',
    ];

    /**
     * @var array<string, string>
     */
    private array $locations = [
        'LEMARI-CONTOH-A1' => 'Lemari Contoh A1',
        'RUANG-ARSIP-CONTOH' => 'Ruang Arsip Contoh',
    ];

    public function run(): void
    {
        foreach ($this->types as $code => $name) {
            DocumentType::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }

        foreach ($this->locations as $code => $name) {
            DocumentLocation::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
