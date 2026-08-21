@extends('admin.layouts.app')

@section('title', 'Tạo đặt bàn')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Paprika Patras</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">Tạo đặt bàn mới</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Nhập thông tin khách, số khách và bàn phục vụ. Nếu không chọn bàn, hệ thống sẽ tự xếp bàn trống phù hợp.
            </p>
        </div>
        <a href="{{ route('admin.reservations.index', ['status' => 'active']) }}" class="admin-btn-secondary">Về bảng trực</a>
    </div>

    <form method="POST" action="{{ route('admin.reservations.store') }}" class="admin-form-card">
        @csrf

        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label for="name" class="admin-label">Tên khách</label>
                <input id="name" name="name" value="{{ old('name', $reservation->name) }}" class="admin-input" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone" class="admin-label">Số điện thoại</label>
                <input id="phone" name="phone" value="{{ old('phone', $reservation->phone) }}" class="admin-input" required>
                @error('phone') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="admin-label">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $reservation->email) }}" class="admin-input">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="branch_id" class="admin-label">Cơ sở</label>
                @if ($branches->count() > 1)
                    <select id="branch_id" name="branch_id" class="admin-input" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $reservation->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="branch_id" value="{{ old('branch_id', $reservation->branch_id ?: $branches->first()?->id) }}">
                    <div class="admin-input bg-slate-50">{{ $branches->first()?->name ?: 'Paprika Patras' }}</div>
                @endif
                @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="reservation_date" class="admin-label">Ngày đặt</label>
                <input id="reservation_date" type="date" name="reservation_date" value="{{ old('reservation_date', $reservation->reservation_date ? \Illuminate\Support\Carbon::parse($reservation->reservation_date)->format('Y-m-d') : now()->toDateString()) }}" class="admin-input" required>
                @error('reservation_date') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="reservation_time" class="admin-label">Giờ đặt</label>
                <input id="reservation_time" type="time" name="reservation_time" value="{{ old('reservation_time', substr((string) $reservation->reservation_time, 0, 5)) }}" class="admin-input" required>
                @error('reservation_time') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="guests" class="admin-label">Số khách</label>
                <input id="guests" type="number" min="1" max="40" name="guests" value="{{ old('guests', $reservation->guests ?? 2) }}" class="admin-input" required>
                @error('guests') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="duration_minutes" class="admin-label">Thời lượng giữ bàn</label>
                <input id="duration_minutes" type="number" min="15" max="240" step="15" name="duration_minutes" value="{{ old('duration_minutes', $reservation->duration_minutes ?? 90) }}" class="admin-input" required>
                <p class="mt-1 text-xs font-semibold text-slate-500">Bàn được giữ cho khách 15 phút sau giờ đặt, thời lượng dùng để chống trùng lịch.</p>
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
                <label for="status" class="admin-label">Trạng thái ban đầu</label>
                <select id="status" name="status" class="admin-input" required>
                    @foreach (\App\Models\Reservation::STATUSES as $status)
                        <option value="{{ $status }}" @selected(old('status', $reservation->status ?? 'confirmed') === $status)>{{ \App\Models\Reservation::STATUS_LABELS[$status] ?? $status }}</option>
                    @endforeach
                </select>
                @error('status') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="lg:col-span-2">
                <label for="note" class="admin-label">Ghi chú của khách</label>
                <textarea id="note" name="note" rows="3" class="admin-input">{{ old('note', $reservation->note) }}</textarea>
                @error('note') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="lg:col-span-2">
                <label for="admin_note" class="admin-label">Ghi chú nội bộ</label>
                <textarea id="admin_note" name="admin_note" rows="3" class="admin-input">{{ old('admin_note', $reservation->admin_note) }}</textarea>
                @error('admin_note') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button class="admin-btn-primary">Tạo đặt bàn</button>
            <a href="{{ route('admin.reservations.index', ['status' => 'active']) }}" class="admin-btn-secondary">Hủy</a>
        </div>
    </form>
@endsection
