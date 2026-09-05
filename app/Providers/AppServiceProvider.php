<?php

namespace App\Providers;

use App\Listeners\AuditAuthentication;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);

        /*
        | Super Admin: tingkat tertinggi — bypass seluruh pemeriksaan
        | ability/policy/permission sehingga mempunyai akses penuh ke semua
        | modul. Return null untuk user lain agar evaluasi normal berlanjut.
        */
        Gate::before(function ($user, string $ability): ?bool {
            return $user instanceof User && $user->hasRole((string) config('simapan_roles.super_role'))
                ? true
                : null;
        });

        // Ability eksplisit untuk manajemen role (create/update/delete role,
        // sinkronisasi permission) — hanya pemegang role super_admin.
        Gate::define('super-admin', function ($user): bool {
            return $user instanceof User && $user->hasRole((string) config('simapan_roles.super_role'));
        });

        Event::listen(Login::class, [AuditAuthentication::class, 'handleLogin']);
        Event::listen(Logout::class, [AuditAuthentication::class, 'handleLogout']);
        Event::listen(Failed::class, [AuditAuthentication::class, 'handleFailed']);
    }
}
