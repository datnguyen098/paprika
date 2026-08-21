@extends('admin.layouts.app')

@section('title', 'Bàn ăn')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm mã bàn, tên bàn, khu vực..." class="admin-input">
            @if ($branches->count() > 1)
                <select name="branch_id" class="admin-input">
                    <option value="">Tất cả cơ sở</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
            @endif
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                @foreach (\App\Models\RestaurantTable::STATUS_LABELS as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
        <a href="{{ route('admin.restaurant-tables.create') }}" class="admin-btn-primary">Thêm bàn</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã bàn</th>
                    <th>Bàn</th>
                    <th>Cơ sở</th>
                    <th>Số ghế</th>
                    <th>Khu vực</th>
                    <th>Trạng thái</th>
                    <th>Thứ tự</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tables as $table)
                    <tr>
                        <td><span class="font-black text-emerald-800">{{ $table->code }}</span></td>
                        <td>
                            <span class="block font-semibold">{{ $table->name }}</span>
                            @if ($table->note)<span class="text-xs text-slate-500">{{ $table->note }}</span>@endif
                        </td>
                        <td>{{ $table->branch?->name }}</td>
                        <td>{{ $table->seats }} khách</td>
                        <td>{{ $table->zone ?: 'Sảnh chính' }}</td>
                        <td><span class="status-badge status-{{ $table->status === 'active' ? 'active' : 'inactive' }}">{{ $table->statusLabel() }}</span></td>
                        <td>{{ $table->sort_order }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.restaurant-tables.edit', $table) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.restaurant-tables.destroy', $table) }}" data-confirm="Xóa bàn này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-500">Chưa có bàn ăn.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $tables->links() }}</div>
@endsection
