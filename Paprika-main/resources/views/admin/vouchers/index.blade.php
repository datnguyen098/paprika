@extends('admin.layouts.app')

@section('title', 'Voucher')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm mã hoặc tên voucher..." class="admin-input">
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Đang bật</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Đang tắt</option>
            </select>
            <select name="public" class="admin-input">
                <option value="">Public/Private</option>
                <option value="1" @selected(request('public') === '1')>Public</option>
                <option value="0" @selected(request('public') === '0')>Private</option>
            </select>
            <select name="default" class="admin-input">
                <option value="">Mặc định</option>
                <option value="1" @selected(request('default') === '1')>Đang mặc định</option>
                <option value="0" @selected(request('default') === '0')>Không mặc định</option>
            </select>
            <select name="type" class="admin-input">
                <option value="">Tất cả loại giảm</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('site.voucher.types.'.$type) }}</option>
                @endforeach
            </select>
            <select name="branch_id" class="admin-input">
                <option value="">Tất cả cơ sở</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
        <a href="{{ route('admin.vouchers.create') }}" class="admin-btn-primary">Thêm voucher</a>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th>Loại giảm</th>
                    <th>Điều kiện</th>
                    <th>Cơ sở</th>
                    <th>Đã dùng</th>
                    <th>Hiển thị</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vouchers as $voucher)
                    @php
                        if (! $voucher->is_active) {
                            $status = ['label' => 'đang tắt', 'class' => 'status-inactive'];
                        } elseif ($voucher->starts_at && $voucher->starts_at->isFuture()) {
                            $status = ['label' => 'chưa tới ngày', 'class' => 'status-upcoming'];
                        } elseif ($voucher->ends_at && $voucher->ends_at->isPast()) {
                            $status = ['label' => 'hết hạn', 'class' => 'status-expired'];
                        } else {
                            $status = ['label' => 'đang chạy', 'class' => 'status-active'];
                        }
                    @endphp
                    <tr>
                        <td>
                            <p class="font-mono text-sm font-black text-emerald-950">{{ $voucher->code }}</p>
                            @if ($voucher->is_default)
                                <span class="mt-1 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black uppercase text-amber-800">Mặc định</span>
                            @endif
                        </td>
                        <td>
                            <p class="font-semibold">{{ $voucher->name }}</p>
                            <p class="text-xs text-slate-500">{{ $voucher->description }}</p>
                        </td>
                        <td>
                            <p class="font-semibold">{{ __('site.voucher.types.'.$voucher->discount_type) }}</p>
                            <p class="text-xs text-slate-500">{{ $voucher->displayValue() }}</p>
                        </td>
                        <td class="text-sm text-slate-600">
                            <p>Đơn tối thiểu: {{ format_money($voucher->min_order_amount) }}</p>
                            @if ($voucher->max_discount_amount)
                                <p>Trần giảm: {{ format_money($voucher->max_discount_amount) }}</p>
                            @endif
                            <p>{{ optional($voucher->starts_at)->format('d/m/Y H:i') ?: 'Ngay' }} - {{ optional($voucher->ends_at)->format('d/m/Y H:i') ?: 'Không giới hạn' }}</p>
                        </td>
                        <td>{{ $voucher->branch?->name ?: 'Tất cả' }}</td>
                        <td>{{ $voucher->used_count }}{{ $voucher->usage_limit_total ? '/'.$voucher->usage_limit_total : '' }}</td>
                        <td>
                            <span class="status-badge {{ $status['class'] }}">{{ $status['label'] }}</span>
                            <p class="mt-1 text-xs text-slate-500">{{ $voucher->is_public ? 'Public' : 'Private' }}</p>
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}" data-confirm="Xóa voucher này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-slate-500">Chưa có voucher.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $vouchers->links() }}</div>
@endsection
