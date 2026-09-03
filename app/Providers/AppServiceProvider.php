<?php

namespace App\Providers;

use App\Listeners\AuditAuthentication;
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

        Event::listen(Login::class, [AuditAuthentication::class, 'handleLogin']);
        Event::listen(Logout::class, [AuditAuthentication::class, 'handleLogout']);
        Event::listen(Failed::class, [AuditAuthentication::class, 'handleFailed']);
    }
}
