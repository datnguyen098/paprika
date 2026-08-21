@extends('admin.layouts.app')

@section('title', $title)

@php
$statusOrder = ['pending' => 1, 'confirmed' => 2, 'preparing' => 3, 'ready' => 4];
$statusLabels = [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'preparing' => 'Đang chế biến',
    'ready' => 'Sẵn sàng giao/nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy',
];
$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'confirmed' => 'bg-blue-100 text-blue-800 border-blue-200',
    'preparing' => 'bg-orange-100 text-orange-800 border-orange-200',
    'ready' => 'bg-green-100 text-green-800 border-green-200',
    'shipping' => 'bg-purple-100 text-purple-800 border-purple-200',
    'completed' => 'bg-gray-100 text-gray-600 border-gray-200',
    'cancelled' => 'bg-red-100 text-red-700 border-red-200',
];
@endphp

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-slate-800">{{ $title }}</h1>
    <span class="text-sm text-slate-500" id="kitchen-refresh-label">Tự động làm mới 10s</span>
</div>

@if($orders->isEmpty())
    <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-white py-24 text-center">
        <svg class="mb-4 h-16 w-16 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p class="text-lg font-semibold text-slate-400">Không có đơn hàng nào trong bếp</p>
        <p class="mt-1 text-sm text-slate-300">Danh sách sẽ tự động cập nhật khi có đơn mới</p>
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach($orders as $order)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm" data-order-card data-order-id="{{ $order->id }}" data-order-status="{{ $order->status }}">
            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-xs font-bold text-slate-500">{{ $order->code }}</p>
                    <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $order->customer_name }}</p>
                    <p class="text-xs text-slate-400">{{ $order->customer_phone }}</p>
                </div>
                <span class="rounded-full border px-2 py-0.5 text-xs font-semibold {{ $statusColors[$order->status] ?? '' }}">
                    {{ $statusLabels[$order->status] ?? $order->status }}
                </span>
            </div>

            {{-- Info --}}
            <div class="border-b border-slate-100 px-4 py-2.5">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    @if($order->fulfillment_method === 'delivery')
                        <svg class="h-3.5 w-3.5 shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        <span>Giao hàng
                            @if($order->branch)
                                — {{ $order->branch->name }}
                            @endif
                        </span>
                    @else
                        <svg class="h-3.5 w-3.5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Tự nhận
                            @if($order->branch)
                                — {{ $order->branch->name }}
                            @endif
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-400">
                    {{ business_time($order->created_at, $order->branch)?->format('H:i') }} · {{ business_time($order->created_at, $order->branch)?->locale('vi')->diffForHumans(business_now($order->branch)) }}
                </p>
            </div>

            {{-- Items --}}
            <div class="max-h-48 overflow-y-auto px-4 py-2.5">
                @foreach($order->items as $item)
                <div class="flex items-start gap-2 py-1 text-xs">
                    <span class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-slate-600">x{{ $item->quantity }}</span>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-700">{{ $item->dish?->name ?? 'Món đã xóa' }}</p>
                        @if($item->options_snapshot)
                            @foreach($item->options_snapshot as $opt)
                                <p class="text-slate-500">{{ $opt['group_name'] ?? '' }}: {{ $opt['name'] ?? $opt['value'] ?? $opt }}</p>
                            @endforeach
                        @endif
                        @if($item->customization_note)
                            <p class="mt-0.5 text-orange-600">Ghi chú: {{ $item->customization_note }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
                @if($order->note)
                <div class="mt-2 rounded bg-amber-50 px-2 py-1.5 text-xs text-amber-700">
                    <strong>Ghi chú:</strong> {{ $order->note }}
                </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex flex-wrap gap-2 border-t border-slate-100 px-4 py-3">
                @if($order->status === 'pending')
                    <button type="button"
                        data-action-btn
                        data-action="confirmed"
                        data-order-id="{{ $order->id }}"
                        class="flex-1 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100">
                        Xác nhận
                    </button>
                @endif

                @if(in_array($order->status, ['pending', 'confirmed']))
                    <button type="button"
                        data-action-btn
                        data-action="preparing"
                        data-order-id="{{ $order->id }}"
                        class="flex-1 rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 transition hover:bg-orange-100">
                        Bắt đầu chế biến
                    </button>
                @endif

                @if(in_array($order->status, ['confirmed', 'preparing']))
                    <button type="button"
                        data-action-btn
                        data-action="ready"
                        data-order-id="{{ $order->id }}"
                        class="flex-1 rounded-xl border border-green-200 bg-green-50 px-3 py-2 text-xs font-bold text-green-700 transition hover:bg-green-100">
                        Sẵn sàng
                    </button>
                @endif

                @if(in_array($order->status, ['preparing', 'ready']))
                    <button type="button"
                        data-action-btn
                        data-action="shipping"
                        data-order-id="{{ $order->id }}"
                        class="flex-1 rounded-xl border border-purple-200 bg-purple-50 px-3 py-2 text-xs font-bold text-purple-700 transition hover:bg-purple-100">
                        Đang giao
                    </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var refreshTimer;
    var label = document.getElementById('kitchen-refresh-label');

    function scheduleRefresh(seconds) {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(function () { location.reload(); }, seconds * 1000);
    }

    function scheduleCountdown(seconds) {
        var remaining = seconds;
        var tick = setInterval(function () {
            remaining--;
            if (remaining <= 0) { clearInterval(tick); return; }
            if (label) label.textContent = 'Làm mới sau ' + remaining + 's';
        }, 1000);
    }

    scheduleRefresh(10);
    scheduleCountdown(10);

    // Workflow actions
    document.querySelectorAll('[data-action-btn]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var btn = this;
            var orderId = btn.dataset.orderId;
            var action = btn.dataset.action;
            var card = document.querySelector('[data-order-id="' + orderId + '"]');

            btn.disabled = true;
            btn.textContent = '...';

            fetch('/admin/kitchen/' + orderId + '/action', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ action: action }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    if (card) {
                        card.setAttribute('data-order-status', data.status);
                        var statusBadge = card.querySelector('span[class*="rounded-full"]');
                        if (statusBadge) {
                            var colors = {
                                'pending': 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                'confirmed': 'bg-blue-100 text-blue-800 border-blue-200',
                                'preparing': 'bg-orange-100 text-orange-800 border-orange-200',
                                'ready': 'bg-green-100 text-green-800 border-green-200',
                                'shipping': 'bg-purple-100 text-purple-800 border-purple-200',
                            };
                            var labels = {
                                'pending': 'Chờ xác nhận',
                                'confirmed': 'Đã xác nhận',
                                'preparing': 'Đang chế biến',
                                'ready': 'Sẵn sàng giao/nhận',
                                'shipping': 'Đang giao',
                            };
                            statusBadge.className = 'rounded-full border px-2 py-0.5 text-xs font-semibold ' + (colors[data.status] || '');
                            statusBadge.textContent = labels[data.status] || data.status;
                        }
                    }
                    // Reload to show updated action buttons
                    scheduleRefresh(1);
                }
            })
            .catch(function () {
                btn.disabled = false;
                btn.textContent = btn.dataset.originalText || action;
                alert('Có lỗi xảy ra, thử lại.');
            });
        });

        // Store original text for error recovery
        btn.dataset.originalText = btn.textContent;
    });
})();
</script>
@endpush
