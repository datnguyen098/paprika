@php
    $selected = old('permissions', $selectedPermissions ?? []);
    $isSuperAdmin = $role->slug === 'super-admin';
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="admin-label" for="name">Tên vai trò</label>
        <input id="name" name="name" value="{{ old('name', $role->name) }}" class="admin-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="slug">Slug</label>
        <input id="slug" name="slug" value="{{ old('slug', $role->slug) }}" class="admin-input" required @readonly($isSuperAdmin)>
        @error('slug') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="lg:col-span-2">
        <label class="admin-label" for="description">Mô tả</label>
        <textarea id="description" name="description" rows="3" class="admin-input">{{ old('description', $role->description) }}</textarea>
        @error('description') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-8">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Danh sách quyền</h2>
            <p class="text-sm text-slate-500">Tick các thao tác mà vai trò này được phép sử dụng.</p>
        </div>
        @if ($isSuperAdmin)
            <span class="status-badge status-active">Super Admin luôn có tất cả quyền</span>
        @endif
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        @foreach ($permissionGroups as $group)
            <section class="rounded-2xl border border-slate-200 bg-white p-4">
                <h3 class="mb-3 font-bold text-emerald-950">{{ $group['label'] }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($group['permissions'] as $permission)
                        <label class="flex items-start gap-2 rounded-xl border border-slate-100 p-3 text-sm hover:bg-emerald-50">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permission->slug }}"
                                class="mt-1"
                                @checked($isSuperAdmin || in_array($permission->slug, $selected, true))
                                @disabled($isSuperAdmin)
                            >
                            <span>
                                <span class="block font-semibold text-slate-800">{{ $permission->name }}</span>
                                <span class="text-xs text-slate-500">{{ $permission->slug }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
    @error('permissions') <p class="form-error">{{ $message }}</p> @enderror
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $role->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.roles.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
