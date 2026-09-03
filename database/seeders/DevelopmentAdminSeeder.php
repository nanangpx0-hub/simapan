<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DevelopmentAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command->error('DevelopmentAdminSeeder hanya untuk local/testing. Dilewati.');

            return;
        }

        $name = (string) env('SIMAPAN_ADMIN_NAME', '');
        $email = (string) env('SIMAPAN_ADMIN_EMAIL', '');
        $password = (string) env('SIMAPAN_ADMIN_PASSWORD', '');

        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException(
                'SIMAPAN_ADMIN_NAME, SIMAPAN_ADMIN_EMAIL, dan SIMAPAN_ADMIN_PASSWORD wajib diisi di .env lokal sebelum seeding admin.'
            );
        }

        $admin = User::where('email', $email)->first();

        if ($admin === null) {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        }

        if (! $admin->hasRole('administrator')) {
            $admin->assignRole('administrator');
        }
    }
}
