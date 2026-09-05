<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\NameNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Officer extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    public const STATUSES = [
        'ACTIVE',
        'INACTIVE',
    ];

    protected $fillable = [
        'code',
        'name',
        'work_unit_id',
        'user_id',
        'phone',
        'email',
        'status',
        'active_from',
        'active_until',
    ];

    protected function casts(): array
    {
        return [
            'active_from' => 'date',
            'active_until' => 'date',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'code',
            'name',
            'work_unit_id',
            'user_id',
            'status',
            'active_from',
            'active_until',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Officer $officer): void {
            $officer->normalized_name = NameNormalizer::normalize((string) $officer->name);
        });
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OfficerAlias, $this>
     */
    public function aliases(): HasMany
    {
        return $this->hasMany(OfficerAlias::class);
    }

    /**
     * @param  Builder<Officer>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * @param  Builder<Officer>  $query
     */
    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        $normalized = NameNormalizer::normalize($keyword);
        $like = '%'.str_replace(['%', '_'], '', $keyword).'%';
        $normalizedLike = '%'.str_replace(['%', '_'], '', $normalized).'%';

        return $query->where(function ($query) use ($like, $normalizedLike): void {
            $query->where('code', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('normalized_name', 'like', $normalizedLike)
                ->orWhereHas('aliases', function ($query) use ($like, $normalizedLike): void {
                    $query->where('alias_name', 'like', $like)
                        ->orWhere('normalized_alias', 'like', $normalizedLike);
                });
        });
    }

    public function maskedPhone(): string
    {
        $phone = (string) ($this->phone ?? '');

        if ($phone === '') {
            return '—';
        }

        $visible = mb_substr($phone, -3);

        return '•••'.$visible;
    }

    public function maskedEmail(): string
    {
        $email = (string) ($this->email ?? '');

        if ($email === '' || ! str_contains($email, '@')) {
            return '—';
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'•••@'.$domain;
    }
}
