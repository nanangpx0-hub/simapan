<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use Auditable;

    public const LEVELS = [
        'PROVINSI',
        'KAB_KOTA',
        'KECAMATAN',
        'DESA_KELURAHAN_NAGARI',
    ];

    public const PARENT_LEVELS = [
        'KAB_KOTA' => 'PROVINSI',
        'KECAMATAN' => 'KAB_KOTA',
        'DESA_KELURAHAN_NAGARI' => 'KECAMATAN',
    ];

    protected $fillable = [
        'parent_id',
        'level',
        'code',
        'name',
        'is_active',
    ];

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return ['code', 'name', 'level', 'parent_id', 'is_active'];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Region $region): void {
            if (! empty($region->full_code)) {
                return;
            }

            $parent = $region->parent_id !== null
                ? static::query()->find($region->parent_id)
                : null;

            $region->full_code = $parent instanceof Region
                ? $parent->full_code.$region->code
                : $region->code;
        });
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    /**
     * @return HasMany<Region, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    /**
     * @return HasMany<Region, $this>
     */
    public function activeChildren(): HasMany
    {
        return $this->hasMany(Region::class, 'parent_id')->where('is_active', true);
    }

    /**
     * @return HasMany<Allocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class, 'village_region_id');
    }

    /**
     * @param  Builder<Region>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
