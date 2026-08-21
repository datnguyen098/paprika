@extends('admin.layouts.app')

@section('title', 'Không gian quán')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm tên ảnh..." class="admin-input">
            <select name="branch_id" class="admin-input">
                <option value="">Tất cả cơ sở</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Đang hiển thị</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Đang ẩn</option>
            </select>
            <button class="admin-btn-secondary">Tìm</button>
        </form>
        <a href="{{ route('admin.gallery.create') }}" class="admin-btn-primary">Thêm ảnh</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Cơ sở</th>
                    <th>Vị trí</th>
                    <th>Thứ tự</th>
                    <th>Nổi bật</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($galleryImages as $image)
                    <tr>
                        <td>
                            <img src="{{ media_variant_url($image->image, 'thumb') }}" alt="{{ $image->alt_text ?: $image->title }}" class="h-16 w-24 rounded-xl object-cover">
                        </td>
                        <td>
                            <span class="block font-semibold">{{ $image->title }}</span>
                            <span class="text-xs text-slate-500">{{ $image->slug }}</span>
                        </td>
                        <td>{{ $image->branch?->name ?: 'Dùng chung' }}</td>
                        <td>{{ $image->location }}</td>
                        <td>{{ $image->sort_order }}</td>
                        <td><span class="status-badge {{ $image->is_featured ? 'status-active' : 'status-inactive' }}">{{ $image->is_featured ? 'có' : 'không' }}</span></td>
                        <td><span class="status-badge {{ $image->is_active ? 'status-active' : 'status-inactive' }}">{{ $image->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ localized_route('gallery.index') }}" target="_blank" class="admin-btn-mini">Xem</a>
                                <a href="{{ route('admin.gallery.edit', $image) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.gallery.destroy', $image) }}" data-confirm="Xóa ảnh không gian này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-500">Chưa có ảnh không gian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $galleryImages->links() }}</div>
@endsection
