<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

final class AuditLogger
{
    private static bool $auditingEnabled = true;

    public static function isAuditing(): bool
    {
        return self::$auditingEnabled;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutAudit(callable $callback): mixed
    {
        $previous = self::$auditingEnabled;
        self::$auditingEnabled = false;

        try {
            return $callback();
        } finally {
            self::$auditingEnabled = $previous;
        }
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @param  array<string, mixed>  $metadata
     */
    public static function log(
        string $action,
        Model $auditable,
        array $old = [],
        array $new = [],
        array $metadata = [],
        ?User $actor = null,
        ?string $eventUuid = null
    ): ?AuditLog {
        if (! self::$auditingEnabled) {
            return null;
        }

        $actor ??= auth()->user();

        $metadata['change_source'] ??= app()->runningInConsole() ? 'console' : 'ui';

        return AuditLog::create([
            'event_uuid' => $eventUuid ?? (string) Str::uuid(),
            'user_id' => $actor instanceof User ? $actor->getKey() : null,
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'old_values' => AuditSanitizer::sanitize($old) ?: null,
            'new_values' => AuditSanitizer::sanitize($new) ?: null,
            'metadata' => AuditSanitizer::sanitize($metadata) ?: null,
            'route_name' => Route::currentRouteName(),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 1024),
        ]);
    }
}
