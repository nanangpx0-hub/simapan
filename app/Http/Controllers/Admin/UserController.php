<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use Illuminate\View\View;

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

        $users = User::with('roles')->orderBy('name')->paginate(15);

        return view('admin.users.index', ['users' => $users]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', ['catalog' => config('simapan_roles.roles', [])]);
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
            'roles.*' => ['string', Rule::in($this->catalogRoles())],
        ]);

        $eventUuid = (string) Str::uuid();
        $assignedRoles = array_values(array_unique($validated['roles']));

        DB::transaction(function () use ($validated, $assignedRoles, $eventUuid): void {
            $created = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => $validated['is_active'] ?? true,
            ]);

            $created->syncRoles($assignedRoles);

            $this->auditRoleChanges($created, [], $assignedRoles, $eventUuid);
        });

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'catalog' => config('simapan_roles.roles', []),
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

        if (! $isSelf && $this->isLastActiveAdministrator($user) && ($revokingAdministrator || $deactivating)) {
            abort(403, 'Administrator aktif terakhir tidak boleh dicabut atau dinonaktifkan.');
        }

        $eventUuid = (string) Str::uuid();

        DB::transaction(function () use ($user, $validated, $newRoles, $isSelf, $eventUuid): void {
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
