@extends('admin.layouts.app')

@section('title', 'Cơ sở')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm cơ sở..." class="admin-input">
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Đang hiển thị</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Đang ẩn</option>
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
        <a href="{{ route('admin.branches.create') }}" class="admin-btn-primary">Thêm cơ sở</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Cơ sở</th>
                    <th>Liên hệ</th>
                    <th>Giờ nhận đặt</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branches as $branch)
                    <tr>
                        <td>
                            @if ($branch->image)
                                <img src="{{ media_variant_url($branch->image, 'thumb') }}" alt="{{ $branch->name }}" class="h-14 w-20 rounded-xl object-cover">
                            @endif
                        </td>
                        <td>
                            <span class="block font-semibold">{{ $branch->name }}</span>
                            <span class="text-xs text-slate-500">{{ $branch->city }} · {{ $branch->slug }}</span>
                            <p class="mt-1 max-w-sm text-xs text-slate-500">{{ $branch->address }}</p>
                        </td>
                        <td>
                            <p>{{ $branch->hotline ?: $branch->phone }}</p>
                            <p class="text-xs text-slate-500">{{ $branch->email }}</p>
                        </td>
                        <td>
                            <p>{{ $branch->reservation_time_slots ?: 'Theo setting chung' }}</p>
                            <p class="text-xs text-slate-500">Nhận đến {{ $branch->reservation_last_booking_time ?: setting('reservation_last_booking_time', '20:30') }}</p>
                        </td>
                        <td>{{ $branch->sort_order }}</td>
                        <td><span class="status-badge {{ $branch->is_active ? 'status-active' : 'status-inactive' }}">{{ $branch->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.branches.edit', $branch) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.branches.destroy', $branch) }}" data-confirm="Xóa cơ sở này? Dữ liệu đặt bàn/liên hệ cũ sẽ chỉ mất liên kết cơ sở.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500">Chưa có cơ sở.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $branches->links() }}</div>
@endsection
