<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Officer;
use App\Support\NameNormalizer;
use Illuminate\Database\Seeder;

class OfficerAliasSeeder extends Seeder
{
    /**
     * @var array<string, list<string>>
     */
    private array $aliases = [
        'OFF-001' => ['P. Contoh Satu'],
        'OFF-002' => ['Petugas Contoh II'],
    ];

    public function run(): void
    {
        $this->call(OfficerSeeder::class);

        foreach ($this->aliases as $code => $names) {
            $officer = Officer::where('code', $code)->firstOrFail();

            foreach ($names as $aliasName) {
                $officer->aliases()->firstOrCreate(
                    ['normalized_alias' => NameNormalizer::normalize($aliasName)],
                    ['alias_name' => $aliasName]
                );
            }
        }
    }
}
