<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'event_uuid',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'metadata',
        'route_name',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): bool {
            throw new RuntimeException('Audit log bersifat append-only.');
        });

        static::deleting(function (): bool {
            throw new RuntimeException('Audit log bersifat append-only.');
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actorName(): string
    {
        return $this->user?->name ?? 'System/Unknown';
    }

    public function objectLabel(): string
    {
        return match ($this->auditable_type) {
            User::class => 'Pengguna',
            SurveyType::class => 'Jenis Survei',
            WorkUnit::class => 'Unit Kerja',
            Region::class => 'Wilayah',
            SurveyPeriod::class => 'Periode Survei',
            Officer::class => 'Petugas',
            OfficerAlias::class => 'Alias Petugas',
            default => class_basename($this->auditable_type),
        };
    }

    /**
     * @return list<string>
     */
    public function changedKeys(): array
    {
        $old = is_array($this->old_values) ? array_keys($this->old_values) : [];
        $new = is_array($this->new_values) ? array_keys($this->new_values) : [];

        return array_values(array_unique(array_merge($old, $new)));
    }
}
