@extends('admin.layouts.app')

@section('title', 'Trang tĩnh')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm trang..." class="admin-input">
            <button class="admin-btn-secondary">Tìm</button>
        </form>
        <a href="{{ route('admin.pages.create') }}" class="admin-btn-primary">Thêm trang</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Slug</th>
                    <th>Template</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pages as $page)
                    <tr>
                        <td>
                            @if ($page->image)
                                <img src="{{ media_url($page->image) }}" alt="{{ $page->title }}" class="h-14 w-20 rounded-xl object-cover">
                            @endif
                        </td>
                        <td class="font-semibold">{{ $page->title }}</td>
                        <td>{{ $page->slug }}</td>
                        <td>{{ $page->template }}</td>
                        <td><span class="status-badge {{ $page->is_active ? 'status-active' : 'status-inactive' }}">{{ $page->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" data-confirm="Xóa trang này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500">Chưa có trang tĩnh.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $pages->links() }}</div>
@endsection
