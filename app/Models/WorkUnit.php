<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkUnit extends Model
{
    use Auditable;

    protected $fillable = [
        'code',
        'name',
        'parent_id',
        'is_active',
    ];

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return ['code', 'name', 'parent_id', 'is_active'];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'parent_id');
    }

    /**
     * @return HasMany<WorkUnit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(WorkUnit::class, 'parent_id');
    }
}
