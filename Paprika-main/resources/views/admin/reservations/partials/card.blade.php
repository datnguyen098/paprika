@php
    $scheduledAt = $reservation->scheduledAt();
    $phoneHref = preg_replace('/\D+/', '', $reservation->phone);
@endphp

<article @class(['reservation-card', 'is-urgent' => $reservation->needsUrgentCall(), 'is-past' => $reservation->isPastServiceTime()])>
    <div class="reservation-card-top">
        <div>
            <h4>{{ $reservation->name }}</h4>
            <p>{{ $reservation->branch?->name ?: 'Chưa chọn cơ sở' }} · {{ $reservation->tableLabel() }}</p>
        </div>
        <span class="status-badge status-{{ $reservation->statusTone() }}">{{ $reservation->statusLabel() }}</span>
    </div>

    <dl class="reservation-card-meta">
        <div>
            <dt>Ngày giờ</dt>
            <dd>{{ $scheduledAt->format('d/m/Y H:i') }}</dd>
        </div>
        <div>
            <dt>Số khách</dt>
            <dd>{{ $reservation->guests }} khách</dd>
        </div>
        <div>
            <dt>Bàn</dt>
            <dd>{{ $reservation->tableLabel() }}</dd>
        </div>
        <div>
            <dt>Điện thoại</dt>
            <dd><a href="tel:{{ $phoneHref }}">{{ $reservation->phone }}</a></dd>
        </div>
        <div>
            <dt>Đã gọi</dt>
            <dd>{{ $reservation->contact_attempts }} lần{{ $reservation->last_contacted_at ? ' - '.business_time($reservation->last_contacted_at, $reservation->branch)?->format('H:i d/m') : '' }}</dd>
        </div>
    </dl>

    @if ($reservation->note)
        <p class="reservation-card-note">{{ $reservation->note }}</p>
    @endif

    <div class="reservation-card-alerts">
        @if ($reservation->needsUrgentCall())
            <span>Cần gọi ngay, đã chờ {{ $reservation->waitingMinutes() }} phút</span>
        @endif
        @if ($reservation->isDueSoon())
            <span>Sắp đến giờ trong {{ max(0, (int) now()->diffInMinutes($scheduledAt)) }} phút</span>
        @endif
        @if ($reservation->isPastServiceTime())
            <span>Đã qua giờ đặt, cần chốt trạng thái</span>
        @endif
    </div>

    <div class="reservation-card-actions">
        <a href="{{ route('admin.reservations.show', $reservation) }}" class="admin-btn-mini">Chi tiết</a>
        <a href="tel:{{ $phoneHref }}" class="admin-btn-mini">Gọi</a>

        @if ($reservation->status === 'pending')
            <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="workflow_action" value="contact_attempt">
                <button class="admin-btn-mini">Đã gọi</button>
            </form>
            <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="workflow_action" value="confirmed">
                <button class="admin-btn-primary admin-btn-mini">Giữ bàn</button>
            </form>
        @endif

        @if (in_array($reservation->status, ['confirmed', 'seated'], true))
            <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="workflow_action" value="completed">
                <button class="admin-btn-primary admin-btn-mini">Hoàn tất</button>
            </form>
        @endif

        @if (in_array($reservation->status, ['pending', 'confirmed', 'seated'], true))
            <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}" data-confirm="Đánh dấu hủy đặt bàn này?">
                @csrf
                @method('PUT')
                <input type="hidden" name="workflow_action" value="cancelled">
                <button class="admin-btn-danger">Hủy</button>
            </form>
        @endif
    </div>
</article>
