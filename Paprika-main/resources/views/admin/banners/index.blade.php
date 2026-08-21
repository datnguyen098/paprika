@extends('admin.layouts.app')

@section('title', 'Banner trang chủ')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm banner..." class="admin-input">
            <button class="admin-btn-secondary">Tìm</button>
        </form>
        <a href="{{ route('admin.banners.create') }}" class="admin-btn-primary">Thêm banner</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Vị trí</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($banners as $banner)
                    <tr>
                        <td><img src="{{ media_url($banner->image) }}" alt="{{ $banner->title }}" class="h-16 w-24 rounded-xl object-cover"></td>
                        <td class="font-semibold">{{ $banner->title }}</td>
                        <td>{{ $banner->position }}</td>
                        <td>{{ $banner->sort_order }}</td>
                        <td><span class="status-badge {{ $banner->is_active ? 'status-active' : 'status-inactive' }}">{{ $banner->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <form method="POST" action="{{ route('admin.banners.toggle', $banner) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="admin-btn-mini">Bật/tắt</button>
                                </form>
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" data-confirm="Xóa banner này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500">Chưa có banner.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $banners->links() }}</div>
@endsection
