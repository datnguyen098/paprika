@extends('admin.layouts.app')

@section('title', 'Khung giờ món')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <select name="branch_id" class="admin-input">
                <option value="">Tất cả cơ sở</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
        <a href="{{ route('admin.dish-time-slots.create') }}" class="admin-btn-primary">Thêm khung giờ</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cơ sở</th>
                    <th>Tên</th>
                    <th>Ngày áp dụng</th>
                    <th>Giờ</th>
                    <th>Trạng thái</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($slots as $slot)
                    <tr>
                        <td>{{ $slot->id }}</td>
                        <td>{{ $slot->branch?->name }}</td>
                        <td>
                            <p class="font-semibold">{{ $slot->name }}</p>
                            <p class="text-xs text-slate-500">EN: {{ $slot->translation('en')?->name ?? '-' }} | EL: {{ $slot->translation('el')?->name ?? '-' }}</p>
                        </td>
                        <td>
                            <span class="text-sm">
                                {{ $slot->start_date?->toDateString() ?? '—' }}
                                →
                                {{ $slot->end_date?->toDateString() ?? '—' }}
                            </span>
                        </td>
                        <td>{{ substr((string) $slot->start_time, 0, 5) }} - {{ substr((string) $slot->end_time, 0, 5) }}</td>
                        <td><span class="status-badge {{ $slot->is_active ? 'status-active' : 'status-inactive' }}">{{ $slot->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.dish-time-slots.edit', $slot) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.dish-time-slots.destroy', $slot) }}" data-confirm="Xóa khung giờ này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500">Chưa có khung giờ.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $slots->links() }}</div>
@endsection
