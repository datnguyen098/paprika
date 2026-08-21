@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- SECTION 1: Time Filter --}}
    <div class="mb-6">
        <div class="admin-filter-tabs">
            @foreach ([
                ['period' => 'today', 'label' => 'Hôm nay'],
                ['period' => '7days', 'label' => '7 ngày'],
                ['period' => '30days', 'label' => '30 ngày'],
                ['period' => 'this_month', 'label' => 'Tháng này'],
                ['period' => 'custom', 'label' => 'Tùy chọn'],
            ] as $tab)
                <a href="?period={{ $tab['period'] }}" class="admin-filter-tab {{ $period === $tab['period'] ? 'is-active' : '' }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>

        @if ($period === 'custom')
            <form method="GET" class="mt-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <input type="hidden" name="period" value="custom">
                <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                    <div>
                        <label for="start_date" class="admin-label">Từ ngày</label>
                        <input id="start_date" type="date" name="start_date" value="{{ $dateRange['start']->toDateString() }}" class="admin-input">
                    </div>
                    <div>
                        <label for="end_date" class="admin-label">Đến ngày</label>
                        <input id="end_date" type="date" name="end_date" value="{{ $dateRange['end']->toDateString() }}" class="admin-input">
                    </div>
                    <button class="admin-btn-primary">Lọc dữ liệu</button>
                </div>
            </form>
        @endif
    </div>

    {{-- SECTION 2: Revenue + Order KPI Cards --}}
    <div class="grid gap-5 grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['label' => 'Doanh thu', 'value' => format_money($revenuePaid), 'tone' => 'emerald'],
            ['label' => 'Đơn hàng', 'value' => $orderCount, 'tone' => 'sky'],
            ['label' => 'Giá trị TB', 'value' => format_money($avgOrderValue), 'tone' => 'amber'],
            ['label' => 'Đơn hoàn thành', 'value' => $completedOrderCount, 'tone' => 'emerald'],
            ['label' => 'Đơn đang xử lý', 'value' => $activeOrderCount, 'tone' => 'amber'],
            ['label' => 'Tổng ĐH hệ thống', 'value' => $allTimeOrderCount, 'tone' => 'violet'],
        ] as $card)
            <div class="admin-stat-card admin-stat-{{ $card['tone'] }}">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ $card['value'] }}</p>
                @if (!in_array($card['label'], ['Đơn đang xử lý', 'Tổng ĐH hệ thống']))
                    <p class="mt-2 text-xs font-semibold text-slate-500">{{ $dateRange['start']->format('d/m') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- SECTION 3: Payment KPI Cards --}}
    <div class="mt-6 grid gap-5 grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'GD thành công', 'value' => $paymentStats['success_count'], 'tone' => 'emerald'],
            ['label' => 'GD thất bại', 'value' => $paymentStats['failed_count'], 'tone' => 'rose'],
            ['label' => 'Tổng tiền online', 'value' => format_money($paymentStats['online_total']), 'tone' => 'violet'],
            ['label' => 'Tổng tiền offline', 'value' => format_money($paymentStats['offline_total']), 'tone' => 'slate'],
        ] as $card)
            <div class="admin-stat-card admin-stat-{{ $card['tone'] }}">
                <p class="text-sm font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">{{ $dateRange['start']->format('d/m') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
            </div>
        @endforeach
    </div>

    @if ($revenueRefunded > 0)
        <div class="mt-6 grid gap-5 grid-cols-2 xl:grid-cols-4">
            <div class="admin-stat-card admin-stat-rose">
                <p class="text-sm font-semibold text-slate-500">Hoàn tiền</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ format_money($revenueRefunded) }}</p>
                <p class="mt-2 text-xs font-semibold text-slate-500">{{ $dateRange['start']->format('d/m') }} - {{ $dateRange['end']->format('d/m/Y') }}</p>
            </div>
        </div>
    @endif

    {{-- SECTION 4: Charts Row 1 --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="admin-chart-box">
            <h3 class="mb-4 font-bold text-slate-950">Doanh thu theo {{ $dateRange['start']->diffInDays($dateRange['end']) > 90 ? 'tháng' : 'ngày' }}</h3>
            <canvas id="revenueChart"
                data-labels='@json($revenueChart['labels'])'
                data-data='@json($revenueChart['data'])'>
            </canvas>
        </div>
        <div class="admin-chart-box">
            <h3 class="mb-4 font-bold text-slate-950">Trạng thái đơn hàng</h3>
            <canvas id="statusChart"
                data-labels='@json($statusChart['labels'])'
                data-data='@json($statusChart['data'])'
                data-colors='@json($statusChart['colors'])'>
            </canvas>
        </div>
    </div>

    {{-- SECTION 5: Charts Row 2 --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="admin-chart-box">
            <h3 class="mb-4 font-bold text-slate-950">Đơn hàng theo {{ $dateRange['start']->diffInDays($dateRange['end']) > 90 ? 'tháng' : 'ngày' }}</h3>
            <canvas id="orderChart"
                data-labels='@json($orderChart['labels'])'
                data-data='@json($orderChart['data'])'>
            </canvas>
        </div>
        <div class="admin-chart-box">
            <h3 class="mb-4 font-bold text-slate-950">Phương thức thanh toán</h3>
            <canvas id="paymentMethodChart"
                data-labels='@json($paymentMethodChart['labels'])'
                data-data='@json($paymentMethodChart['data'])'
                data-colors='@json($paymentMethodChart['colors'])'>
            </canvas>
        </div>
    </div>

    {{-- SECTION 6: Tables --}}
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        {{-- Top 10 Dishes --}}
        <div class="admin-table-wrap">
            <div class="p-5">
                <h2 class="text-lg font-bold text-slate-950">Top 10 món bán chạy</h2>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tên món</th>
                        <th>Danh mục</th>
                        <th>SL bán</th>
                        <th>Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topDishes as $i => $dish)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="font-semibold">{{ $dish->dish_name }}</td>
                            <td class="text-slate-500">{{ $dish->category_name ?: '—' }}</td>
                            <td>{{ $dish->total_quantity }}</td>
                            <td class="font-semibold text-emerald-800">{{ format_money($dish->total_revenue) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-500">Chưa có dữ liệu bán hàng.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Recent Orders --}}
        <div class="admin-table-wrap">
            <div class="p-5">
                <h2 class="text-lg font-bold text-slate-950">Đơn hàng gần đây</h2>
            </div>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Khách</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th>Ngày</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        @php
                            $paidPayment = $order->payments->firstWhere('status', 'paid');
                            $paymentStatus = $order->payment_status;
                            $paymentBadge = match ($paymentStatus) {
                                'paid' => 'status-completed',
                                'unpaid' => 'status-pending',
                                default => 'status-cancelled',
                            };
                        @endphp
                        <tr>
                            <td class="font-semibold">{{ $order->code }}</td>
                            <td>
                                <span class="block truncate max-w-[120px]">{{ $order->customer_name }}</span>
                                <span class="text-xs text-slate-400">{{ $order->customer_phone }}</span>
                            </td>
                            <td class="font-semibold">{{ format_money($order->total) }}</td>
                            <td>
                                <span class="status-badge status-{{ $order->statusTone() }}">{{ $order->statusLabel('vi') }}</span>
                            </td>
                            <td>
                                <span class="status-badge {{ $paymentBadge }}">
                                    {{ $paymentStatus === 'paid' ? 'Đã TT' : ($paymentStatus === 'unpaid' ? 'Chưa TT' : $paymentStatus) }}
                                </span>
                            </td>
                            <td class="text-sm text-slate-500">{{ business_time($order->created_at, $order->branch)?->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('admin.orders.show', $order) }}" class="admin-btn-mini">Xem</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-slate-500">Chưa có đơn hàng.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- SECTION 7: Alert Cards --}}
    <div class="mt-6 grid gap-5 md:grid-cols-3">
        <a href="{{ route('admin.orders.index') }}" class="admin-alert-card">
            <span class="text-sm font-bold text-slate-500">Đơn hàng cần xử lý</span>
            <strong>{{ $activeOrderCount }}</strong>
        </a>
        <a href="{{ route('admin.reservations.index', ['status' => 'pending']) }}" class="admin-alert-card">
            <span class="text-sm font-bold text-slate-500">Đặt bàn chờ xử lý</span>
            <strong>{{ $pendingReservationCount }}</strong>
        </a>
        <a href="{{ route('admin.contacts.index', ['status' => 'new']) }}" class="admin-alert-card">
            <span class="text-sm font-bold text-slate-500">Liên hệ mới</span>
            <strong>{{ $newContactCount }}</strong>
        </a>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/admin-charts.js'])
@endpush
