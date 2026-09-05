<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\UserExport;
use App\Http\Controllers\Controller;
use App\Imports\UserImport;
use App\Models\Officer;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    /**
     * @return list<string>
     */
    private function catalogRoles(): array
    {
        /** @var array<string, string> $roles */
        $roles = config('simapan_roles.roles', []);

        return array_keys($roles);
    }

    private function superRole(): string
    {
        return (string) config('simapan_roles.super_role', 'super_admin');
    }

    /**
     * Role yang dapat ditugaskan melalui form: katalog sistem + role custom
     * di database (guard web). Role super_admin hanya untuk Super Admin.
     *
     * @return list<string>
     */
    private function assignableRoles(): array
    {
        $dbRoles = Role::query()->where('guard_name', 'web')->pluck('name')->all();

        return array_values(array_unique(array_merge($this->catalogRoles(), $dbRoles)));
    }

    /**
     * Katalog untuk tampilan form: role katalog + role custom, tanpa
     * super_role bila aktor bukan Super Admin (anti-eskalasi UI).
     *
     * @return array<string, string>
     */
    private function displayableCatalog(): array
    {
        /** @var array<string, string> $labels */
        $labels = config('simapan_roles.roles', []);

        $actor = auth()->user();

        if ($actor === null || ! $actor->hasRole($this->superRole())) {
            unset($labels[$this->superRole()]);
        }

        $custom = Role::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', array_keys($labels))
            ->pluck('name', 'name')
            ->all();

        /** @var array<string, string> $merged */
        $merged = array_merge($labels, $custom);

        return $merged;
    }

    private function activeAdministratorCount(): int
    {
        return User::where('is_active', true)->role('administrator')->count();
    }

    private function isLastActiveAdministrator(User $user): bool
    {
        return $user->hasRole('administrator')
            && (bool) $user->is_active
            && $this->activeAdministratorCount() === 1;
    }

    /**
     * Daftar petugas aktif yang belum memiliki akun user (untuk penautan akun).
     */
    private function unlinkedOfficers(?int $currentOfficerId = null): array
    {
        return Officer::query()
            ->whereNull('user_id')
            ->when($currentOfficerId !== null, fn ($query) => $query->orWhere('id', $currentOfficerId))
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->all();
    }

    /**
     * Validasi bahwa petugas yang dipilih belum tertaut ke akun lain.
     */
    private function validateOfficerLink(Request $request, ?User $user = null): void
    {
        $request->validate([
            'officer_id' => ['nullable', 'integer', 'exists:officers,id'],
        ]);

        $officerId = $request->input('officer_id');

        if ($officerId === null) {
            return;
        }

        $officer = Officer::query()->findOrFail((int) $officerId);

        $conflict = $officer->user_id !== null
            && ($user === null || (int) $officer->user_id !== (int) $user->getKey());

        if ($conflict) {
            throw ValidationException::withMessages([
                'officer_id' => 'Petugas tersebut sudah tertaut dengan akun pengguna lain.',
            ]);
        }
    }

    /**
     * Terapkan penautan akun user ke data petugas.
     */
    private function applyOfficerLink(User $user, ?int $officerId): void
    {
        // Lepas penautan lama bila diganti.
        Officer::query()
            ->where('user_id', $user->getKey())
            ->whereKeyNot($officerId ?? 0)
            ->update(['user_id' => null]);

        if ($officerId !== null) {
            Officer::query()
                ->whereKey($officerId)
                ->update(['user_id' => $user->getKey()]);
        }
    }

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    private function auditRoleChanges(User $user, array $before, array $after, string $eventUuid): void
    {
        foreach (array_diff($after, $before) as $role) {
            AuditLogger::log('role_assigned', $user, [], [], ['role_slug' => $role], null, $eventUuid);
        }

        foreach (array_diff($before, $after) as $role) {
            AuditLogger::log('role_removed', $user, [], [], ['role_slug' => $role], null, $eventUuid);
        }
    }

    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('admin.users.index');
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'role' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ]);

        return Excel::download(new UserExport($filters), 'users.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new UserImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.users.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', [
            'catalog' => $this->displayableCatalog(),
            'officers' => $this->unlinkedOfficers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($this->assignableRoles())],
        ]);

        $this->validateOfficerLink($request);

        $assignedRoles = array_values(array_unique($validated['roles']));

        // Anti-eskalasi: hanya Super Admin boleh memberi role Super Admin.
        if (in_array($this->superRole(), $assignedRoles, true)
            && ! $request->user()->hasRole($this->superRole())) {
            abort(403, 'Hanya Super Admin yang dapat menetapkan role Super Admin.');
        }

        $eventUuid = (string) Str::uuid();

        DB::transaction(function () use ($validated, $assignedRoles, $eventUuid, $request): void {
            $created = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $created->syncRoles($assignedRoles);

            $this->auditRoleChanges($created, [], $assignedRoles, $eventUuid);

            $this->applyOfficerLink($created, $request->input('officer_id') !== null ? (int) $request->input('officer_id') : null);
        });

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        $user->load('roles', 'officer');

        return view('admin.users.edit', [
            'user' => $user,
            'catalog' => $this->displayableCatalog(),
            'officers' => $this->unlinkedOfficers($user->officer?->getKey()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in($this->catalogRoles())],
        ]);

        $isSelf = $user->is($request->user());
        $newRoles = array_values(array_unique($validated['roles']));
        $currentRoles = $user->getRoleNames()->all();
        sort($currentRoles);
        $sortedNew = $newRoles;
        sort($sortedNew);
        $rolesChanged = $sortedNew !== $currentRoles;
        $deactivating = array_key_exists('is_active', $validated) && ! (bool) $validated['is_active'];
        $revokingAdministrator = $user->hasRole('administrator') && ! in_array('administrator', $newRoles, true);

        if ($isSelf && ($rolesChanged || $deactivating)) {
            abort(403, 'Perubahan role atau status akun sendiri tidak diizinkan.');
        }

        // Anti-eskalasi: hanya Super Admin boleh mengubah peran Super Admin
        // (memberi role super_admin pada target) meski policy lolos.
        if (! $isSelf
            && in_array($this->superRole(), $newRoles, true)
            && ! $request->user()->hasRole($this->superRole())) {
            abort(403, 'Hanya Super Admin yang dapat mengubah role Super Admin.');
        }

        if (! $isSelf && $this->isLastActiveAdministrator($user) && ($revokingAdministrator || $deactivating)) {
            abort(403, 'Administrator aktif terakhir tidak boleh dicabut atau dinonaktifkan.');
        }

        $eventUuid = (string) Str::uuid();

        $this->validateOfficerLink($request, $user);

        DB::transaction(function () use ($user, $validated, $newRoles, $isSelf, $eventUuid, $request): void {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $isSelf ? $user->is_active : ($validated['is_active'] ?? $user->is_active),
            ]);

            if (! $isSelf) {
                $before = $user->getRoleNames()->all();
                $user->syncRoles($newRoles);
                $this->auditRoleChanges($user, $before, $newRoles, $eventUuid);
            }

            $this->applyOfficerLink($user, $request->input('officer_id') !== null ? (int) $request->input('officer_id') : null);
        });

        return redirect()->route('admin.users.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        if ($user->is($request->user())) {
            abort(403, 'Akun sendiri tidak boleh dihapus.');
        }

        if ($this->isLastActiveAdministrator($user)) {
            abort(403, 'Administrator aktif terakhir tidak boleh dihapus.');
        }

        $user->delete();

        return redirect()->route('admin.users.index');
    }
}
