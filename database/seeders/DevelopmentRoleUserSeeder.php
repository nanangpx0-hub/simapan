<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder user dummy per-peran untuk lingkungan local/testing.
 * Dilarang dipakai di produksi; kredensial hanya untuk dev/E2E.
 */
class DevelopmentRoleUserSeeder extends Seeder
{
    /** Password dev dummy yang sama untuk semua user peran. */
    public const DEV_PASSWORD = 'Simapan-Dev-2026';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command->error('DevelopmentRoleUserSeeder hanya untuk local/testing. Dilewati.');

            return;
        }

        $roles = (array) config('simapan_roles.roles');

        foreach ($roles as $slug => $label) {
            $email = $slug.'@simapan.test';

            $user = User::where('email', $email)->first();

            if ($user === null) {
                $user = User::create([
                    'name' => 'Dev '.Str::title(str_replace('_', ' ', (string) $label)),
                    'email' => $email,
                    'password' => Hash::make(self::DEV_PASSWORD),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
            }

            if (! $user->hasRole($slug)) {
                $user->assignRole($slug);
            }
        }

        // Izin level-user khusus dev/E2E (tidak mengubah mapping role kanonik
        // di RoleSeeder — RbacMatrixTest tetap lolos; lihat docs/e2e-testing.md).
        $devUserPermissions = [
            'field_supervisor' => ['allocation.view', 'dsrt.view', 'dsrt.manage', 'dsrt.verify'],
        ];

        foreach ($devUserPermissions as $slug => $permissions) {
            $user = User::where('email', $slug.'@simapan.test')->first();

            if ($user instanceof User) {
                $user->givePermissionTo($permissions);
            }
        }
    }
}
