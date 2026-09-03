<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuditAuthentication
{
    public function handleLogin(Login $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        AuditLogger::log('login_succeeded', $user ?? self::placeholder(), [], [], [
            'authentication_result' => 'succeeded',
        ], $user);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        AuditLogger::log('logout', $user ?? self::placeholder(), [], [], [], $user);
    }

    public function handleFailed(Failed $event): void
    {
        AuditLogger::log('login_failed', self::placeholder(), [], [], [
            'authentication_result' => 'failed',
            'error_category' => 'invalid_credentials',
        ]);
    }

    private static function placeholder(): User
    {
        $user = new User;
        $user->id = 0;
        $user->exists = true;

        return $user;
    }
}
