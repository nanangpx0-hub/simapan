<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SurveyPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SurveyTypeSeeder::class);

        $susenas = SurveyType::where('code', 'SUSENAS')->firstOrFail();
        $seruti = SurveyType::where('code', 'SERUTI')->firstOrFail();

        $adminId = $this->seedAuthorId();

        SurveyPeriod::firstOrCreate(
            ['code' => 'SUSENAS-S1-2099'],
            [
                'survey_type_id' => $susenas->getKey(),
                'name' => 'Periode Dummy Susenas 2099',
                'period_type' => 'SEMESTER',
                'period_number' => 1,
                'year' => 2099,
                'start_date' => '2099-01-01',
                'end_date' => '2099-06-30',
                'status' => 'DRAFT',
                'created_by' => $adminId,
            ]
        );

        SurveyPeriod::firstOrCreate(
            ['code' => 'SERUTI-T1-2099'],
            [
                'survey_type_id' => $seruti->getKey(),
                'name' => 'Periode Dummy Seruti 2099',
                'period_type' => 'TRIWULAN',
                'period_number' => 1,
                'year' => 2099,
                'start_date' => '2099-01-01',
                'end_date' => '2099-03-31',
                'status' => 'DRAFT',
                'created_by' => $adminId,
            ]
        );
    }

    private function seedAuthorId(): int
    {
        $existing = User::query()
            ->where('email', 'seeder-periode-uji@simapan.test')
            ->first();

        if ($existing instanceof User) {
            return (int) $existing->getKey();
        }

        $hasAdminRole = Role::query()
            ->where('name', 'administrator')
            ->where('guard_name', 'web')
            ->exists();

        if ($hasAdminRole) {
            $admin = User::query()->role('administrator')->orderBy('id')->first();

            if ($admin instanceof User) {
                return (int) $admin->getKey();
            }
        }

        return (int) User::factory()->create([
            'name' => 'Seeder Periode Uji',
            'email' => 'seeder-periode-uji@simapan.test',
        ])->getKey();
    }
}
