<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SurveyType;
use Illuminate\Database\Seeder;

class SurveyTypeSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, description: string}>
     */
    private array $types = [
        'SUSENAS' => [
            'name' => 'Survei Sosial Ekonomi Nasional',
            'description' => 'Master jenis survei Susenas',
        ],
        'SERUTI' => [
            'name' => 'Survei Rutin/Seruti',
            'description' => 'Master jenis survei Seruti',
        ],
    ];

    public function run(): void
    {
        foreach ($this->types as $code => $attributes) {
            SurveyType::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $attributes['name'],
                    'description' => $attributes['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
