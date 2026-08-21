@extends('admin.layouts.app')

@section('title', 'Sổ đặt bàn')

@section('content')
    @php
        $reservationToday = business_today()->toDateString();
        $reservationTomorrow = business_today()->addDay()->toDateString();
        $reservationWeekEnd = business_today()->addDays(7)->toDateString();
    @endphp

    <div class="reservation-shift-head">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Bảng trực ca</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">Theo dõi đặt bàn</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                Mặc định hiển thị 7 ngày tới. Khi tìm theo tên hoặc SĐT, hệ thống sẽ tìm toàn bộ lịch sử nếu không chọn ngày cụ thể.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reservations.create') }}" class="admin-btn-primary">Tạo đặt bàn</a>
            <a href="{{ route('admin.reservations.export', request()->query()) }}" class="admin-btn-secondary">Xuất Excel</a>
            <a href="{{ route('admin.reservations.index', ['from' => $reservationToday, 'to' => $reservationWeekEnd, 'status' => 'active']) }}" class="admin-btn-secondary">7 ngày tới</a>
        </div>
    </div>

    <div class="reservation-shift-stats">
        @foreach ([
            ['label' => 'Tổng bàn hôm nay', 'value' => $todayStats['total'], 'tone' => 'slate'],
            ['label' => 'Chờ gọi', 'value' => $todayStats['pending'], 'tone' => 'amber'],
            ['label' => 'Đã giữ bàn', 'value' => $todayStats['confirmed'], 'tone' => 'emerald'],
            ['label' => 'Khách đã ngồi', 'value' => $todayStats['seated'], 'tone' => 'sky'],
            ['label' => 'Cần gọi ngay', 'value' => $todayStats['urgent'], 'tone' => 'red'],
            ['label' => 'Qua giờ chưa chốt', 'value' => $todayStats['past'], 'tone' => 'rose'],
        ] as $stat)
            <div class="reservation-shift-stat reservation-shift-stat-{{ $stat['tone'] }}">
                <span>{{ $stat['label'] }}</span>
                <strong>{{ $stat['value'] }}</strong>
            </div>
        @endforeach
    </div>

    <form class="admin-filter reservation-filter" method="GET">
        <input name="q" value="{{ request('q') }}" placeholder="Tìm tên hoặc SĐT..." class="admin-input">
        <input type="date" name="date" value="{{ $selectedDate }}" class="admin-input" title="Một ngày cụ thể">
        <input type="date" name="from" value="{{ request('from', $dateFilter['mode'] === 'upcoming' ? $fromDate : null) }}" class="admin-input" title="Từ ngày">
        <input type="date" name="to" value="{{ request('to', $dateFilter['mode'] === 'upcoming' ? $toDate : null) }}" class="admin-input" title="Đến ngày">
        <select name="branch_id" class="admin-input">
            <option value="">Tất cả cơ sở</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        <select name="table_id" class="admin-input">
            <option value="">Tất cả bàn</option>
            @foreach ($tables as $table)
                <option value="{{ $table->id }}" @selected((string) request('table_id') === (string) $table->id)>
                    {{ $table->name }} · {{ $table->seats }} ghế{{ $table->zone ? ' · '.$table->zone : '' }}
                </option>
            @endforeach
        </select>
        <select name="status" class="admin-input">
            <option value="active" @selected($status === 'active' || blank($status))>Đang xử lý</option>
            <option value="pending" @selected($status === 'pending')>Chờ gọi xác nhận</option>
            <option value="confirmed" @selected($status === 'confirmed')>Đã giữ bàn</option>
            <option value="seated" @selected($status === 'seated')>Khách đã ngồi</option>
            <option value="completed" @selected($status === 'completed')>Hoàn tất</option>
            <option value="no_show" @selected($status === 'no_show')>Không đến</option>
            <option value="cancelled" @selected($status === 'cancelled')>Đã hủy</option>
        </select>
        <button class="admin-btn-secondary">Lọc</button>
        <a href="{{ route('admin.reservations.index', ['from' => $reservationToday, 'to' => $reservationWeekEnd, 'status' => 'active']) }}" class="admin-btn-secondary">7 ngày tới</a>
        <a href="{{ route('admin.reservations.index', ['date' => $reservationToday, 'status' => 'active']) }}" class="admin-btn-secondary">Cả hôm nay</a>
        <a href="{{ route('admin.reservations.index', ['date' => $reservationTomorrow, 'status' => 'active']) }}" class="admin-btn-secondary">Ngày mai</a>
        <a href="{{ route('admin.reservations.export', request()->query()) }}" class="admin-btn-secondary">Xuất Excel</a>
    </form>

    <div class="reservation-board">
        @foreach ($sections as $section)
            @continue($section['items']->isEmpty() && $section['key'] === 'closed' && ! in_array($status, ['completed', 'cancelled', 'no_show'], true))
            <section class="reservation-lane reservation-lane-{{ $section['tone'] }}">
                <div class="reservation-lane-head">
                    <div>
                        <h3>{{ $section['title'] }}</h3>
                        <p>{{ $section['hint'] }}</p>
                    </div>
                    <strong>{{ $section['items']->count() }}</strong>
                </div>

                <div class="reservation-card-list">
                    @forelse ($section['items'] as $reservation)
                        @include('admin.reservations.partials.card', ['reservation' => $reservation])
                    @empty
                        <p class="reservation-empty">Không có đơn trong nhóm này.</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    @if ($reservations->count() >= 200)
        <p class="mt-4 rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-900">
            Đang hiển thị 200 đơn đầu tiên theo bộ lọc. Hãy lọc theo ngày, cơ sở hoặc trạng thái để xem chính xác hơn.
        </p>
    @endif
@endsection
