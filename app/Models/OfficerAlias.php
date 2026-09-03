<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\NameNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficerAlias extends Model
{
    use Auditable;

    protected $fillable = [
        'officer_id',
        'alias_name',
        'created_by',
    ];

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return ['alias_name', 'officer_id'];
    }

    protected static function booted(): void
    {
        static::saving(function (OfficerAlias $alias): void {
            $alias->normalized_alias = NameNormalizer::normalize((string) $alias->alias_name);
        });
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
