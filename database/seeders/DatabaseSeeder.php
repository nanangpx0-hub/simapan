<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\AuditLogger;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        AuditLogger::withoutAudit(function (): void {
            $this->call([
                PermissionSeeder::class,
                RoleSeeder::class,
                SurveyTypeSeeder::class,
                WorkUnitSeeder::class,
                RegionSeeder::class,
                OfficerSeeder::class,
                OfficerAliasSeeder::class,
            ]);

            if (app()->environment(['local', 'testing'])) {
                $this->call(DevelopmentAdminSeeder::class);
                $this->call(SurveyPeriodSeeder::class);
                $this->call(AllocationSeeder::class);
                $this->call(DsrtSampleSeeder::class);
                $this->call(DocumentMasterSeeder::class);
                $this->call(DocumentSeeder::class);
            }
        });
    }
}
