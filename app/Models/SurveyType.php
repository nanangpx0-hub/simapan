<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SurveyType extends Model
{
    use Auditable;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return ['code', 'name', 'description', 'is_active'];
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<SurveyPeriod, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(SurveyPeriod::class);
    }
}
