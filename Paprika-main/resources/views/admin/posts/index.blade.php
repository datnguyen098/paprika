@extends('admin.layouts.app')

@section('title', 'Bài viết')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm bài viết..." class="admin-input">
            <select name="category_id" class="admin-input">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
        <a href="{{ route('admin.posts.create') }}" class="admin-btn-primary">Thêm bài viết</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Danh mục</th>
                    <th>Ngày đăng</th>
                    <th>Nổi bật</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    <tr>
                        <td>
                            @if ($post->thumbnail)
                                <img src="{{ media_url($post->thumbnail) }}" alt="{{ $post->title }}" class="h-16 w-20 rounded-xl object-cover">
                            @endif
                        </td>
                        <td>
                            <p class="font-semibold">{{ $post->title }}</p>
                            <p class="text-xs text-slate-500">{{ $post->slug }}</p>
                        </td>
                        <td>{{ $post->category?->name }}</td>
                        <td>{{ optional($post->published_at)->format('d/m/Y H:i') ?: 'Chưa đăng' }}</td>
                        <td>{{ $post->is_featured ? 'Có' : 'Không' }}</td>
                        <td><span class="status-badge {{ $post->is_active ? 'status-active' : 'status-inactive' }}">{{ $post->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ localized_route('blog.show', ['post' => $post->localizedSlug()]) }}" target="_blank" class="admin-btn-mini">Preview</a>
                                <a href="{{ route('admin.posts.edit', $post) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" data-confirm="Xóa bài viết này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500">Chưa có bài viết.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $posts->links() }}</div>
@endsection
