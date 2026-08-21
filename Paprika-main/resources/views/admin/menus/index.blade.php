@extends('admin.layouts.app')

@section('title', 'Menu điều hướng')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <select name="location" class="admin-input">
                <option value="">Tất cả vị trí</option>
                <option value="header" @selected(request('location') === 'header')>Header</option>
                <option value="footer" @selected(request('location') === 'footer')>Footer</option>
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
        <a href="{{ route('admin.menus.create') }}" class="admin-btn-primary">Thêm menu</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>URL</th>
                    <th>Vị trí</th>
                    <th>Menu cha</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($menus as $menu)
                    <tr>
                        <td class="font-semibold">{{ $menu->title }}</td>
                        <td>{{ $menu->url }}</td>
                        <td>{{ $menu->location }}</td>
                        <td>{{ $menu->parent?->title }}</td>
                        <td>{{ $menu->sort_order }}</td>
                        <td><span class="status-badge {{ $menu->is_active ? 'status-active' : 'status-inactive' }}">{{ $menu->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.menus.edit', $menu) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" data-confirm="Xóa menu này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500">Chưa có menu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $menus->links() }}</div>
@endsection
