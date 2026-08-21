<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->withCount('users')
            ->withCount('permissions')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->paginate(12);

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'role' => new Role(),
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::create($request->safe()->except('permissions') + ['is_system' => false]);
        $this->syncPermissions($role, $request->input('permissions', []));

        return redirect()->route('admin.roles.index')->with('success', 'Đã thêm vai trò.');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => $role->permissions->pluck('slug')->all(),
        ]);
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->slug === 'super-admin') {
            $role->update($request->safe()->only(['name', 'description']));
        } else {
            $role->update($request->safe()->except('permissions'));
            $this->syncPermissions($role, $request->input('permissions', []));
        }

        return redirect()->route('admin.roles.index')->with('success', 'Đã cập nhật vai trò.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system || $role->users()->exists()) {
            return back()->with('error', 'Không thể xóa vai trò hệ thống hoặc vai trò đang có tài khoản sử dụng.');
        }

        $role->delete();

        return back()->with('success', 'Đã xóa vai trò.');
    }

    private function syncPermissions(Role $role, array $permissions): void
    {
        $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->all();
        $role->permissions()->sync($permissionIds);
    }

    private function permissionGroups(): array
    {
        $labels = config('permissions.groups', []);

        return Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group')
            ->mapWithKeys(fn ($permissions, $group) => [$group => [
                'label' => $labels[$group] ?? $group,
                'permissions' => $permissions,
            ]])
            ->all();
    }
}
