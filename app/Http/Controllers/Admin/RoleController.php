<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Role bawaan katalog (sistem-terkelola) — tidak dapat direname/dihapus.
     *
     * @return list<string>
     */
    private function systemRoles(): array
    {
        return array_keys(config('simapan_roles.roles', []));
    }

    /**
     * @return list<string>
     */
    private function catalogPermissions(): array
    {
        /** @var list<string> $permissions */
        $permissions = config('simapan_roles.permissions', []);

        return $permissions;
    }

    public function index(): View
    {
        Gate::authorize('viewAny', Role::class);

        return view('admin.roles.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Role::class);

        return view('admin.roles.create', [
            'permissions' => $this->catalogPermissions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Role::class);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in($this->catalogPermissions())],
        ]);

        $name = (string) $validated['name'];
        $granted = array_values(array_unique($validated['permissions'] ?? []));

        $role = DB::transaction(function () use ($name, $granted): Role {
            $role = Role::create(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($granted);

            return $role;
        });

        AuditLogger::log('role_created', $role, [], [
            'name' => $name,
            'permissions' => $granted,
        ]);

        return redirect()->route('admin.roles.index')
            ->with('status', __('Role berhasil dibuat.'));
    }

    public function edit(Role $role): View
    {
        Gate::authorize('update', $role);

        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $this->catalogPermissions(),
            'isSystemRole' => in_array($role->name, $this->systemRoles(), true),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('update', $role);

        $isSystemRole = in_array($role->name, $this->systemRoles(), true);

        $rules = [
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in($this->catalogPermissions())],
        ];

        if (! $isSystemRole) {
            $rules['name'] = [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->getKey()),
            ];
        }

        $validated = $request->validate($rules);

        $newName = $isSystemRole ? $role->name : (string) $validated['name'];
        $oldName = $role->name;
        $granted = array_values(array_unique($validated['permissions'] ?? []));

        $oldPermissions = $role->permissions->pluck('name')->sort()->values()->all();

        $role = DB::transaction(function () use ($role, $newName, $granted): Role {
            $role->update(['name' => $newName]);
            $role->syncPermissions($granted);

            return $role->refresh();
        });

        AuditLogger::log('role_updated', $role, [
            'name' => $oldName,
            'permissions' => $oldPermissions,
        ], [
            'name' => $newName,
            'permissions' => $granted,
        ]);

        return redirect()->route('admin.roles.index')
            ->with('status', __('Role berhasil diperbarui.'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        if (in_array($role->name, $this->systemRoles(), true)) {
            throw ValidationException::withMessages([
                'role' => 'Role sistem (termasuk Super Admin) tidak dapat dihapus.',
            ]);
        }

        if ($role->users()->count() > 0) {
            throw ValidationException::withMessages([
                'role' => 'Role masih digunakan oleh akun pengguna dan tidak dapat dihapus.',
            ]);
        }

        $oldPermissions = $role->permissions->pluck('name')->sort()->values()->all();

        DB::transaction(function () use ($role): void {
            $role->syncPermissions([]);
            $role->delete();
        });

        AuditLogger::log('role_deleted', $role, [
            'name' => $role->name,
            'permissions' => $oldPermissions,
        ]);

        return redirect()->route('admin.roles.index')
            ->with('status', __('Role berhasil dihapus.'));
    }
}
