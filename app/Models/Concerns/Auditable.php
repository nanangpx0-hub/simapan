<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Observers\AuditObserver;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::observe(AuditObserver::class);
    }

    /**
     * Field yang boleh masuk diff audit (selain itu diabaikan).
     *
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [];
    }
}
