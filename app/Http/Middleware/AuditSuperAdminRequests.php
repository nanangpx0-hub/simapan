<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat seluruh aktivitas mutasi (POST/PUT/PATCH/DELETE) yang dilakukan
 * akun Super Admin ke audit trail (append-only). Request read-only (GET)
 * tidak dicatat di sini karena tidak mengubah data; aksi bisnis tetap
 * ter-audit via model events dan AuditLogger di controller.
 */
final class AuditSuperAdminRequests
{
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $isSuperAdmin = $user instanceof User
            && $user->hasRole((string) config('simapan_roles.super_role'))
            && in_array($request->getMethod(), self::MUTATING_METHODS, true);

        $response = $next($request);

        if ($isSuperAdmin && ($response->isSuccessful() || $response->isRedirection())) {
            AuditLogger::log(
                'super_admin_request',
                $user,
                [],
                [],
                [
                    'method' => $request->getMethod(),
                    'path' => '/'.$request->path(),
                    'route' => $request->route()?->getName(),
                ],
                $user
            );
        }

        return $response;
    }
}
