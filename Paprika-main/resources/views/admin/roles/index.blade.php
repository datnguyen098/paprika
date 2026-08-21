@extends('admin.layouts.app')

@section('title', 'Phân quyền')

@section('content')
    <div class="admin-page-head">
        <div>
            <p class="text-sm text-slate-500">Quản lý nhóm quyền cho từng tài khoản admin.</p>
        </div>
        @permission('roles.create')
            <a href="{{ route('admin.roles.create') }}" class="admin-btn-primary">Thêm vai trò</a>
        @endpermission
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Vai trò</th>
                    <th>Mô tả</th>
                    <th>Số quyền</th>
                    <th>Tài khoản</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td>
                            <div class="font-semibold text-slate-900">{{ $role->name }}</div>
                            <div class="text-xs text-slate-500">{{ $role->slug }}</div>
                        </td>
                        <td class="max-w-md text-slate-600">{{ $role->description }}</td>
                        <td>{{ $role->permissions_count }}</td>
                        <td>{{ $role->users_count }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                @permission('roles.update')
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="admin-btn-mini">Sửa</a>
                                @endpermission
                                @permission('roles.delete')
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm="Xóa vai trò này?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="admin-btn-danger" @disabled($role->is_system || $role->users_count > 0)>Xóa</button>
                                    </form>
                                @endpermission
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500">Chưa có vai trò.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $roles->links() }}</div>
@endsection
