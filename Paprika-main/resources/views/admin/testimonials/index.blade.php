@extends('admin.layouts.app')

@section('title', 'Review khách hàng')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm tên khách..." class="admin-input">
            <button class="admin-btn-secondary">Tìm</button>
        </form>
        <a href="{{ route('admin.testimonials.create') }}" class="admin-btn-primary">Thêm review</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Khách</th>
                    <th>Nội dung</th>
                    <th>Rating</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($testimonials as $testimonial)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($testimonial->avatar)
                                    <img src="{{ media_url($testimonial->avatar) }}" alt="{{ $testimonial->name }}" class="h-12 w-12 rounded-full object-cover">
                                @else
                                    <span class="grid h-12 w-12 place-items-center rounded-full bg-emerald-100 font-bold text-emerald-800">{{ mb_substr($testimonial->name, 0, 1) }}</span>
                                @endif
                                <span>
                                    <span class="block font-semibold">{{ $testimonial->name }}</span>
                                    <span class="text-xs text-slate-500">{{ $testimonial->role }}</span>
                                </span>
                            </div>
                        </td>
                        <td class="max-w-md">{{ str($testimonial->content)->limit(90) }}</td>
                        <td>{{ str_repeat('★', $testimonial->rating) }}</td>
                        <td>{{ $testimonial->sort_order }}</td>
                        <td><span class="status-badge {{ $testimonial->is_active ? 'status-active' : 'status-inactive' }}">{{ $testimonial->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" data-confirm="Xóa review này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-slate-500">Chưa có review.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $testimonials->links() }}</div>
@endsection
