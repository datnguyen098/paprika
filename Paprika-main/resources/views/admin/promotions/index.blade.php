@extends('admin.layouts.app')

@section('title', 'Khuyến mãi / Popup')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm khuyến mãi..." class="admin-input">
            <select name="placement" class="admin-input">
                <option value="">Tất cả vị trí</option>
                <option value="home" @selected(request('placement') === 'home')>Trang chủ</option>
                <option value="popup" @selected(request('placement') === 'popup')>Popup</option>
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
        <a href="{{ route('admin.promotions.create') }}" class="admin-btn-primary">Thêm khuyến mãi</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Vị trí</th>
                    <th>Thứ tự</th>
                    <th>Mẫu</th>
                    <th>Thời gian</th>
                    <th>Hiển thị</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($promotions as $promotion)
                    @php
                        if (! $promotion->is_active) {
                            $displayStatus = ['label' => 'inactive', 'class' => 'status-inactive'];
                        } elseif ($promotion->starts_at && $promotion->starts_at->isFuture()) {
                            $displayStatus = ['label' => 'chưa tới ngày', 'class' => 'status-upcoming'];
                        } elseif ($promotion->ends_at && $promotion->ends_at->isPast()) {
                            $displayStatus = ['label' => 'hết hạn', 'class' => 'status-expired'];
                        } else {
                            $displayStatus = ['label' => 'đang hiện', 'class' => 'status-active'];
                        }
                    @endphp
                    <tr>
                        <td>
                            @if ($promotion->image)
                                <img src="{{ media_url($promotion->image) }}" alt="{{ $promotion->title }}" class="h-14 w-20 rounded-xl object-cover">
                            @endif
                        </td>
                        <td>
                            <p class="font-semibold">{{ $promotion->title }}</p>
                            <p class="text-xs text-slate-500">{{ $promotion->badge }}</p>
                        </td>
                        <td>{{ $promotion->placement === 'popup' ? 'Popup' : 'Trang chủ' }}</td>
                        <td class="font-bold text-slate-700">{{ $promotion->sort_order }}</td>
                        <td>{{ $promotion->template }}</td>
                        <td class="text-sm text-slate-500">
                            {{ optional($promotion->starts_at)->format('d/m/Y H:i') ?: 'Ngay' }}
                            -
                            {{ optional($promotion->ends_at)->format('d/m/Y H:i') ?: 'Không giới hạn' }}
                        </td>
                        <td><span class="status-badge {{ $displayStatus['class'] }}">{{ $displayStatus['label'] }}</span></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.promotions.edit', $promotion) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" data-confirm="Xóa khuyến mãi này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-500">Chưa có khuyến mãi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $promotions->links() }}</div>
@endsection
