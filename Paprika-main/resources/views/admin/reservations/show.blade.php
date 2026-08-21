@extends('admin.layouts.app')

@section('title', 'Chi tiết đặt bàn')

@section('content')
    @php
        $scheduledAt = $reservation->scheduledAt();
        $phoneHref = preg_replace('/\D+/', '', $reservation->phone);
        $timeline = [
            ['label' => 'Khách gửi đặt bàn', 'value' => $reservation->created_at],
            ['label' => 'Gọi gần nhất', 'value' => $reservation->last_contacted_at],
            ['label' => 'Xác nhận giữ bàn', 'value' => $reservation->confirmed_at],
            ['label' => 'Hết hạn giữ bàn', 'value' => $reservation->hold_expires_at],
            ['label' => 'Khách đã ngồi', 'value' => $reservation->seated_at],
            ['label' => 'Hoàn tất', 'value' => $reservation->completed_at],
            ['label' => 'Không đến', 'value' => $reservation->no_show_at],
            ['label' => 'Đã hủy', 'value' => $reservation->cancelled_at],
        ];
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Phiếu đặt bàn</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">{{ $reservation->name }}</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.reservations.index', ['date' => $reservation->reservation_date->toDateString(), 'status' => 'active']) }}" class="admin-btn-secondary">Về bảng trực</a>
            <a href="tel:{{ $phoneHref }}" class="admin-btn-primary">Gọi khách</a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <span class="status-badge status-{{ $reservation->statusTone() }}">{{ $reservation->statusLabel() }}</span>
                    <h3 class="mt-4 text-3xl font-black text-slate-950">{{ $scheduledAt->format('H:i') }}</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ $scheduledAt->format('d/m/Y') }} · {{ $reservation->guests }} khách · {{ $reservation->duration_minutes ?: 90 }} phút</p>
                </div>
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-900">
                    {{ $reservation->branch?->name ?: 'Chưa chọn cơ sở' }}<br>
                    <span class="text-emerald-700">{{ $reservation->tableLabel() }}</span>
                </div>
            </div>

            @if ($reservation->needsUrgentCall() || $reservation->isDueSoon() || $reservation->isPastServiceTime())
                <div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm font-bold text-amber-950">
                    @if ($reservation->needsUrgentCall())
                        Cần gọi ngay. Đơn đã chờ {{ $reservation->waitingMinutes() }} phút hoặc gần tới giờ dùng bữa.
                    @elseif ($reservation->isDueSoon())
                        Bàn sắp tới giờ trong {{ max(0, (int) now()->diffInMinutes($scheduledAt)) }} phút.
                    @else
                        Đã qua giờ đặt, cần chốt hoàn tất hoặc hủy.
                    @endif
                </div>
            @endif

            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="font-bold text-slate-500">Số điện thoại</dt>
                    <dd class="mt-1 font-bold text-slate-950"><a href="tel:{{ $phoneHref }}">{{ $reservation->phone }}</a></dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="font-bold text-slate-500">Bàn phục vụ</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ $reservation->tableLabel() }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="font-bold text-slate-500">Đã gọi</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ $reservation->contact_attempts }} lần</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="font-bold text-slate-500">Email</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ $reservation->email ?: 'Không có' }}</dd>
                </div>
            </dl>

            <div class="mt-6">
                <h3 class="font-bold text-slate-950">Ghi chú của khách</h3>
                <p class="mt-2 min-h-20 whitespace-pre-line rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $reservation->note ?: 'Không có ghi chú.' }}</p>
            </div>

            <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @if ($reservation->status === 'pending')
                    <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="workflow_action" value="contact_attempt">
                        <button class="admin-btn-secondary w-full justify-center">Đã gọi</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="workflow_action" value="confirmed">
                        <button class="admin-btn-primary w-full justify-center">Giữ bàn</button>
                    </form>
                @endif

                @if (in_array($reservation->status, ['confirmed', 'seated'], true))
                    <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="workflow_action" value="completed">
                        <button class="admin-btn-primary w-full justify-center">Hoàn tất</button>
                    </form>
                @endif

                @if (in_array($reservation->status, ['pending', 'confirmed', 'seated'], true))
                    <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}" data-confirm="Đánh dấu hủy đặt bàn này?">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="workflow_action" value="cancelled">
                        <button class="admin-btn-danger w-full justify-center">Hủy đặt bàn</button>
                    </form>
                @endif
            </div>
        </section>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                @csrf
                @method('PUT')
                <h3 class="text-lg font-bold text-slate-950">Cập nhật thủ công</h3>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="admin-label">Tên khách</label>
                        <input id="name" name="name" value="{{ old('name', $reservation->name) }}" class="admin-input">
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="admin-label">Số điện thoại</label>
                        <input id="phone" name="phone" value="{{ old('phone', $reservation->phone) }}" class="admin-input">
                        @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="reservation_date" class="admin-label">Ngày đặt</label>
                        <input id="reservation_date" type="date" name="reservation_date" value="{{ old('reservation_date', $reservation->reservation_date->format('Y-m-d')) }}" class="admin-input">
                        @error('reservation_date') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="reservation_time" class="admin-label">Giờ đặt</label>
                        <input id="reservation_time" type="time" name="reservation_time" value="{{ old('reservation_time', substr((string) $reservation->reservation_time, 0, 5)) }}" class="admin-input">
                        @error('reservation_time') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="guests" class="admin-label">Số khách</label>
                        <input id="guests" type="number" min="1" max="40" name="guests" value="{{ old('guests', $reservation->guests) }}" class="admin-input">
                        @error('guests') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="duration_minutes" class="admin-label">Thời lượng</label>
                        <input id="duration_minutes" type="number" min="15" max="240" step="15" name="duration_minutes" value="{{ old('duration_minutes', $reservation->duration_minutes ?: 90) }}" class="admin-input">
                        @error('duration_minutes') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="table_id" class="admin-label">Bàn</label>
                        <select id="table_id" name="table_id" class="admin-input">
                            <option value="">Tự xếp bàn phù hợp</option>
                            @foreach ($tables as $table)
                                <option value="{{ $table->id }}" @selected((string) old('table_id', $reservation->table_id) === (string) $table->id)>
                                    {{ $table->name }} · {{ $table->seats }} ghế{{ $table->zone ? ' · '.$table->zone : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('table_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="status" class="admin-label">Trạng thái</label>
                        <select id="status" name="status" class="admin-input" required>
                            @foreach (\App\Models\Reservation::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $reservation->status) === $status)>{{ \App\Models\Reservation::STATUS_LABELS[$status] ?? $status }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="note" class="admin-label">Ghi chú của khách</label>
                        <textarea id="note" name="note" rows="3" class="admin-input">{{ old('note', $reservation->note) }}</textarea>
                        @error('note') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="admin_note" class="admin-label">Ghi chú nội bộ</label>
                        <textarea id="admin_note" name="admin_note" rows="4" class="admin-input">{{ old('admin_note', $reservation->admin_note) }}</textarea>
                        @error('admin_note') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <button class="admin-btn-primary mt-5">Lưu cập nhật</button>
            </form>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-bold text-slate-950">Mốc xử lý</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($timeline as $item)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-2 font-bold text-slate-950">{{ $item['value'] ? business_time($item['value'], $reservation->branch)?->format('H:i d/m/Y') : 'Chưa có' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-bold text-slate-950">Lịch sử thao tác</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($reservation->activities as $activity)
                        <article class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-bold text-slate-950">{{ $activity->actionLabel() }}</p>
                                <time class="text-xs font-bold text-slate-400">{{ business_time($activity->created_at, $reservation->branch)?->format('H:i d/m/Y') }}</time>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $activity->user?->name ?: 'Hệ thống' }}
                                @if ($activity->from_status !== $activity->to_status)
                                    · {{ \App\Models\Reservation::STATUS_LABELS[$activity->from_status] ?? $activity->from_status }} → {{ \App\Models\Reservation::STATUS_LABELS[$activity->to_status] ?? $activity->to_status }}
                                @endif
                            </p>
                            @if ($activity->note)
                                <p class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-sm text-slate-700">{{ $activity->note }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Chưa có thao tác nội bộ nào.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
