<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable;

    /**
     * Pemetaan resmi Spatie role ke Assignment::ROLES (Fase 2).
     *
     * @var array<string, string>
     */
    public const ROLE_ASSIGNMENT_MAP = [
        'field_officer' => 'FIELD_OFFICER',
        'field_supervisor' => 'FIELD_SUPERVISOR',
        'processing_officer' => 'PROCESSING_OFFICER',
        'processing_supervisor' => 'PROCESSING_SUPERVISOR',
    ];

    /**
     * Role dengan akses data lintas wilayah (tanpa scoping petugas).
     *
     * @var list<string>
     */
    public const FULL_SCOPE_ROLES = [
        'super_admin',
        'administrator',
        'viewer',
        'social_operator',
        'ipds_operator',
    ];

    /**
     * Role teknis lapangan/pengolahan yang datanya di-scope ke penugasan.
     *
     * @var list<string>
     */
    public const SCOPED_ROLES = [
        'field_officer',
        'field_supervisor',
        'processing_officer',
        'processing_supervisor',
    ];

    /**
     * Kode jenis survei kategori sosial (cakupan Tim Statistik Sosial).
     *
     * @var list<string>
     */
    public const SOCIAL_SURVEY_CODES = [
        'SUSENAS',
        'SERUTI',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return ['name', 'is_active'];
    }

    /**
     * @return HasOne<Officer, $this>
     */
    public function officer(): HasOne
    {
        return $this->hasOne(Officer::class);
    }

    /**
     * Akun user ini tertaut ke data petugas aktif?
     */
    public function isOfficer(): bool
    {
        return $this->officerId() !== null;
    }

    /**
     * ID data petugas yang tertaut dengan akun ini, bila ada.
     */
    public function officerId(): ?int
    {
        if (! $this->relationLoaded('officer')) {
            $this->load('officer');
        }

        $id = $this->officer?->getKey();

        return $id === null ? null : (int) $id;
    }

    /**
     * Apakah user ini memiliki akses data lintas wilayah
     * (Administrator/Pimpinan/Operator) tanpa scoping penugasan?
     */
    public function hasFullDataScope(): bool
    {
        foreach (self::FULL_SCOPE_ROLES as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Daftar assignment_role aktif milik petugas penanggung jawab akun ini.
     *
     * @return list<string>
     */
    public function activeAssignmentRoles(): array
    {
        $officerId = $this->officerId();

        if ($officerId === null) {
            return [];
        }

        return Assignment::query()
            ->active()
            ->where('officer_id', $officerId)
            ->pluck('assignment_role')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Apakah petugas penanggung jawab akun ini punya penugasan aktif
     * dengan assignment_role tertentu?
     */
    public function hasActiveAssignmentRole(string $assignmentRole): bool
    {
        return in_array($assignmentRole, $this->activeAssignmentRoles(), true);
    }

    /**
     * Apakah user ini termasuk role teknis yang datanya di-scope
     * ke penugasan petugas (PPL/PML/Pengolahan)?
     */
    public function hasScopedDataAccess(): bool
    {
        if ($this->hasFullDataScope()) {
            return false;
        }

        foreach (self::SCOPED_ROLES as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Konversi Spatie role slug ke Assignment::ROLES yang setara.
     */
    public static function assignmentRoleFor(string $spatieRole): ?string
    {
        return self::ROLE_ASSIGNMENT_MAP[$spatieRole] ?? null;
    }

    /**
     * Konversi Assignment::ROLES ke Spatie role slug yang setara.
     */
    public static function spatieRoleFor(string $assignmentRole): ?string
    {
        $mapped = array_search($assignmentRole, self::ROLE_ASSIGNMENT_MAP, true);

        return $mapped === false ? null : (string) $mapped;
    }
}
