@extends('admin.layouts.app')

@section('title', 'Danh mục')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm danh mục..." class="admin-input">
            <select name="type" class="admin-input">
                <option value="">Tất cả loại</option>
                <option value="dish" @selected(request('type') === 'dish')>Món ăn</option>
                <option value="post" @selected(request('type') === 'post')>Bài viết</option>
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
        <a href="{{ route('admin.categories.create') }}" class="admin-btn-primary">Thêm danh mục</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tên</th>
                    <th>Slug</th>
                    <th>Loại</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td>
                            @if ($category->image)
                                <img src="{{ media_url($category->image) }}" alt="{{ $category->name }}" class="h-12 w-16 rounded-lg object-cover">
                            @endif
                        </td>
                        <td class="font-semibold">{{ $category->name }}</td>
                        <td>{{ $category->slug }}</td>
                        <td>{{ $category->type === 'dish' ? 'Món ăn' : 'Bài viết' }}</td>
                        <td>{{ $category->sort_order }}</td>
                        <td><span class="status-badge {{ $category->is_active ? 'status-active' : 'status-inactive' }}">{{ $category->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" data-confirm="Xóa danh mục này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500">Chưa có danh mục.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $categories->links() }}</div>
@endsection
