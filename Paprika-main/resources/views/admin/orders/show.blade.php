@extends('admin.layouts.app')

@section('title', 'Chi tiết đơn hàng')

@section('content')
    @php
        $phoneHref = preg_replace('/\D+/', '', $order->customer_phone);
        $timeline = [
            ['label' => 'Tạo đơn', 'value' => $order->created_at],
            ['label' => 'Xác nhận', 'value' => $order->confirmed_at],
            ['label' => 'Chế biến', 'value' => $order->preparing_at],
            ['label' => 'Sẵn sàng', 'value' => $order->ready_at],
            ['label' => 'Đang giao', 'value' => $order->shipping_at],
            ['label' => 'Hoàn tất', 'value' => $order->completed_at],
            ['label' => 'Đã hủy', 'value' => $order->cancelled_at],
        ];
        $workflowSteps = [
            'confirmed' => 'Xác nhận',
            'preparing' => 'Chế biến',
            'ready' => 'Sẵn sàng',
            'shipping' => 'Đang giao',
            'completed' => 'Hoàn tất',
        ];
        $workflowOrder = array_keys($workflowSteps);
        $currentWorkflowIndex = array_search($order->status, $workflowOrder, true);
        $isTerminalStatus = in_array($order->status, ['completed', 'cancelled'], true);
        $paymentStatusLabels = ['unpaid' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán'];
        $paymentTone = $order->payment_status === 'paid'
            ? 'bg-emerald-100 text-emerald-800'
            : 'bg-amber-100 text-amber-800';
        $latestPayment = $order->payments->first();
    @endphp

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Đơn hàng Paprika</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950">{{ $order->code }}</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.orders.index') }}" class="admin-btn-secondary">Về danh sách</a>
            <a href="tel:{{ $phoneHref }}" class="admin-btn-primary">Gọi khách</a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(28rem,1.05fr)]">
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <span class="status-badge status-{{ $order->statusTone() }}">{{ ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'preparing' => 'Đang chế biến', 'ready' => 'Sẵn sàng', 'shipping' => 'Đang giao', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'][$order->status] ?? $order->status }}</span>
                    <h3 class="mt-4 text-3xl font-black text-slate-950">{{ format_money($order->total) }}</h3>
                    <p class="mt-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500">
                        <span>{{ $order->fulfillmentLabel() }}</span>
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black {{ $paymentTone }}">{{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}</span>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600">{{ \App\Models\Payment::METHOD_LABELS[$order->payment_method] ?? $order->payment_method }}</span>
                    </p>
                </div>
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-900">
                    {{ $order->branch?->name ?: 'Chưa chọn cơ sở' }}
                </div>
            </div>

            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="font-bold text-slate-500">Khách hàng</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ $order->customer_name }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="font-bold text-slate-500">Điện thoại</dt>
                    <dd class="mt-1 font-bold text-slate-950"><a href="tel:{{ $phoneHref }}">{{ $order->customer_phone }}</a></dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="font-bold text-slate-500">Ngày/giờ mong muốn</dt>
                    <dd class="mt-1 font-bold text-slate-950">
                        {{ $order->requested_date?->format('d/m/Y') ?: 'Chưa chọn' }}
                        {{ $order->requested_time ? substr((string) $order->requested_time, 0, 5) : '' }}
                    </dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="font-bold text-slate-500">Email</dt>
                    <dd class="mt-1 font-bold text-slate-950">{{ $order->customer_email ?: 'Không có' }}</dd>
                </div>
            </dl>

            @if ($order->delivery_address)
                <div class="mt-6">
                    <h3 class="font-bold text-slate-950">Địa chỉ giao hàng</h3>
                    <p class="mt-2 whitespace-pre-line rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">{{ $order->delivery_address }}</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl bg-emerald-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-widest text-emerald-700">Khoảng cách</dt>
                            <dd class="mt-1 font-black text-emerald-950">{{ $order->delivery_distance_km !== null ? number_format((float) $order->delivery_distance_km, 1, ',', '.').' km' : 'Chưa có' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-widest text-emerald-700">Mốc ship</dt>
                            <dd class="mt-1 font-black text-emerald-950">{{ $order->delivery_zone_label ?: 'Chưa có' }}</dd>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-4">
                            <dt class="text-xs font-bold uppercase tracking-widest text-emerald-700">Phí ship</dt>
                            <dd class="mt-1 font-black text-emerald-950">{{ format_money($order->shipping_fee) }} @if ($order->delivery_fee_overridden) <span class="text-xs text-rose-700">(đã sửa tay)</span> @endif</dd>
                        </div>
                    </div>
                </div>
            @endif

            @if ($order->discount_total > 0 || $order->voucher_code)
                <div class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                    <h3 class="font-bold text-emerald-950">Voucher</h3>
                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-widest text-emerald-700">Ma</dt>
                            <dd class="mt-1 font-mono font-black text-emerald-950">{{ $order->voucher_code ?: 'Khong co' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-widest text-emerald-700">Ten</dt>
                            <dd class="mt-1 font-bold text-emerald-950">{{ data_get($order->voucher_snapshot, 'localized_name') ?: data_get($order->voucher_snapshot, 'name', 'Khong co') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-widest text-emerald-700">Giam</dt>
                            <dd class="mt-1 font-black text-emerald-950">-{{ format_money($order->discount_total) }}</dd>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <h3 class="font-bold text-slate-950">Món trong đơn</h3>
                <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Món</th>
                                <th>SL</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="font-semibold">
                                        <span>{{ $item->dish_name }}</span>
                                        @if ($item->options_snapshot || $item->customization_note)
                                            <div class="mt-2 space-y-1 text-xs font-medium text-slate-500">
                                                @foreach (($item->options_snapshot ?? []) as $option)
                                                    <p>{{ $option['group_name'] ?? 'Tùy chọn' }}: {{ $option['name'] ?? '' }} @if (($option['price_delta'] ?? 0) != 0) ({{ format_money((int) $option['price_delta']) }}) @endif</p>
                                                @endforeach
                                                @if ($item->customization_note)
                                                    <p>Ghi chú: {{ $item->customization_note }}</p>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ format_money($item->unit_price) }}</td>
                                    <td class="font-semibold">{{ format_money($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="font-black text-slate-950">Chuyển trạng thái nhanh</h3>
                        <p class="mt-1 text-xs font-semibold text-slate-500">Trạng thái hiện tại được đánh dấu nổi bật trong quy trình xử lý đơn.</p>
                    </div>
                    <span class="status-badge status-{{ $order->statusTone() }}">{{ $order->statusLabel('vi') }}</span>
                </div>

                <div class="grid gap-2.5 sm:grid-cols-2 2xl:grid-cols-3">
                    @foreach ($workflowSteps as $action => $label)
                        @php
                            $stepIndex = array_search($action, $workflowOrder, true);
                            $isCurrent = $order->status === $action;
                            $isPast = $currentWorkflowIndex !== false && $stepIndex < $currentWorkflowIndex;
                            $isDisabled = $isTerminalStatus || $isCurrent;
                            $stepClasses = $isCurrent
                                ? 'border-emerald-900 bg-emerald-950 text-white shadow-lg shadow-emerald-950/18'
                                : ($isPast
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-900');
                        @endphp
                        <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="workflow_action" value="{{ $action }}">
                            <button
                                type="submit"
                                @disabled($isDisabled)
                                class="group flex min-h-16 w-full items-center gap-2.5 rounded-2xl border px-3 py-3 text-left transition {{ $stepClasses }} {{ $isDisabled ? 'cursor-default' : '' }}"
                            >
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-sm font-black {{ $isCurrent ? 'bg-white text-emerald-950' : ($isPast ? 'bg-emerald-700 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-emerald-700 group-hover:text-white') }}">
                                    @if ($isPast)
                                        ✓
                                    @elseif ($isCurrent)
                                        ●
                                    @else
                                        {{ $stepIndex + 1 }}
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block break-words text-sm font-black leading-snug">{{ $label }}</span>
                                    <span class="mt-0.5 block text-[9px] font-black uppercase tracking-[0.1em] opacity-70">
                                        @if ($isCurrent)
                                            Hiện tại
                                        @elseif ($isPast)
                                            Đã qua
                                        @else
                                            Chuyển tới
                                        @endif
                                    </span>
                                </span>
                            </button>
                        </form>
                    @endforeach
                </div>

                @unless ($isTerminalStatus)
                    <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="mt-3" data-confirm="Hủy đơn hàng này?">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="workflow_action" value="cancelled">
                        <button class="admin-btn-danger w-full justify-center sm:w-auto">Hủy đơn hàng</button>
                    </form>
                @endunless
            </div>

            <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-bold text-slate-950">Mốc xử lý</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach ($timeline as $item)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ $item['label'] }}</p>
                            <p class="mt-2 font-bold text-slate-950">{{ $item['value'] ? business_time($item['value'], $order->branch)?->format('H:i d/m/Y') : 'Chưa có' }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mt-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-bold text-slate-950">Lịch sử thao tác</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($order->activities as $activity)
                        <article class="rounded-2xl border border-slate-200 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-bold text-slate-950">{{ $activity->actionLabel() }}</p>
                                <time class="text-xs font-bold text-slate-400">{{ business_time($activity->created_at, $order->branch)?->format('H:i d/m/Y') }}</time>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $activity->user?->name ?: 'Hệ thống' }}
                                @if ($activity->from_status !== $activity->to_status)
                                    - {{ \App\Models\Order::STATUS_LABELS[$activity->from_status] ?? $activity->from_status }} -> {{ \App\Models\Order::STATUS_LABELS[$activity->to_status] ?? $activity->to_status }}
                                @endif
                            </p>
                            @if ($activity->note)
                                <p class="mt-3 whitespace-pre-line rounded-xl bg-slate-50 p-3 text-sm text-slate-700">{{ $activity->note }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Chưa có thao tác nào.</p>
                    @endforelse
                </div>
            </section>
        </section>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                @csrf
                @method('PUT')

                <div class="border-b border-slate-200 bg-gradient-to-br from-emerald-950 to-emerald-800 p-5 text-white">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-100">Điều phối đơn</p>
                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="text-xl font-black">Cập nhật thủ công</h3>
                            <p class="mt-1 text-sm font-semibold text-emerald-50/80">Sửa trạng thái, thanh toán, phí ship và ghi chú nội bộ.</p>
                        </div>
                        <div class="rounded-2xl bg-white/12 px-4 py-3 text-right ring-1 ring-white/15">
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-emerald-50/70">Tổng đơn</p>
                            <p class="mt-1 font-mono text-2xl font-black">{{ format_money($order->total) }}</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="status" class="admin-label">Trạng thái đơn</label>
                                <select id="status" name="status" class="admin-input" required>
                                    @foreach (\App\Models\Order::STATUSES as $status)
                                        <option value="{{ $status }}" @selected(old('status', $order->status) === $status)>{{ \App\Models\Order::STATUS_LABELS[$status] }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">Dùng cho quy trình bếp, giao hàng và hoàn tất đơn.</p>
                            </div>
                            <div>
                                <label for="payment_status" class="admin-label">Trạng thái thanh toán</label>
                                <select id="payment_status" name="payment_status" class="admin-input" required>
                                    <option value="unpaid" @selected(old('payment_status', $order->payment_status) === 'unpaid')>Chưa thanh toán</option>
                                    <option value="paid" @selected(old('payment_status', $order->payment_status) === 'paid')>Đã thanh toán</option>
                                </select>
                                <p class="mt-2 text-xs font-semibold leading-5 text-slate-500">
                                    Phương thức: <span class="font-black text-slate-700">{{ \App\Models\Payment::METHOD_LABELS[$order->payment_method] ?? $order->payment_method }}</span>.
                                    @if ($order->payment_method === 'viva')
                                        Khi đánh dấu đã thanh toán, bản ghi Viva sẽ được đồng bộ theo đơn này.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h4 class="font-black text-emerald-950">Giao hàng & hóa đơn</h4>
                                <p class="mt-1 text-xs font-semibold text-emerald-900/70">Các giá trị tiền nhập theo EUR, hệ thống tự lưu dạng cent.</p>
                            </div>
                            @if ($order->delivery_fee_overridden)
                                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-black text-rose-700">Phí ship đã sửa tay</span>
                            @endif
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="shipping_fee" class="admin-label">Phí ship (€)</label>
                                <input id="shipping_fee" name="shipping_fee" value="{{ old('shipping_fee', number_format($order->shipping_fee / 100, 2, '.', '')) }}" type="number" min="0" step="0.01" class="admin-input">
                            </div>
                            <div>
                                <label for="delivery_distance_km" class="admin-label">Khoảng cách ship (km)</label>
                                <input id="delivery_distance_km" name="delivery_distance_km" value="{{ old('delivery_distance_km', $order->delivery_distance_km) }}" type="number" min="0" step="0.01" class="admin-input">
                            </div>
                            <div class="md:col-span-2">
                                <label for="delivery_zone_label" class="admin-label">Mốc phí ship</label>
                                <input id="delivery_zone_label" name="delivery_zone_label" value="{{ old('delivery_zone_label', $order->delivery_zone_label) }}" class="admin-input" placeholder="VD: 1-3km">
                            </div>
                            <div>
                                <label for="invoice_status" class="admin-label">Hóa đơn</label>
                                <select id="invoice_status" name="invoice_status" class="admin-input">
                                    @foreach (\App\Models\Invoice::STATUSES as $status)
                                        <option value="{{ $status }}" @selected(old('invoice_status', $order->invoice?->status) === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if ($order->shipment)
                                <div>
                                    <label for="shipment_status" class="admin-label">Vận chuyển nội bộ</label>
                                    <select id="shipment_status" name="shipment_status" class="admin-input">
                                        @foreach (\App\Models\Shipment::STATUSES as $status)
                                            <option value="{{ $status }}" @selected(old('shipment_status', $order->shipment->status) === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section>
                        <label for="admin_note" class="admin-label">Ghi chú nội bộ</label>
                        <textarea id="admin_note" name="admin_note" rows="4" class="admin-input" placeholder="Ví dụ: khách yêu cầu gọi trước 10 phút, shipper đã thu tiền mặt...">{{ old('admin_note', $order->admin_note) }}</textarea>
                    </section>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs font-semibold leading-5 text-slate-500">
                            Payment hiện tại:
                            <span class="font-black text-slate-700">{{ $latestPayment?->methodLabel() ?? 'Chưa có' }}</span>
                            @if ($latestPayment)
                                - {{ $latestPayment->statusLabel() }}
                            @endif
                        </p>
                        <button class="admin-btn-primary min-w-40">Lưu cập nhật</button>
                    </div>
                </div>
            </form>

            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="text-lg font-bold text-slate-950">Hóa đơn & ship</h3>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="font-bold text-slate-500">Số hóa đơn</dt>
                        <dd class="mt-1 font-bold text-slate-950">{{ $order->invoice?->invoice_number }} - {{ $order->invoice?->status }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="font-bold text-slate-500">Vận chuyển</dt>
                        <dd class="mt-1 font-bold text-slate-950">{{ $order->shipment ? $order->shipment->carrier.' - '.$order->shipment->status : 'Không cần giao hàng' }}</dd>
                    </div>
                    @if ($order->shipment)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <dt class="font-bold text-slate-500">Khoảng cách / mốc phí</dt>
                            <dd class="mt-1 font-bold text-slate-950">
                                {{ $order->shipment->distance_km !== null ? number_format((float) $order->shipment->distance_km, 1, ',', '.').' km' : 'Chưa có km' }}
                                - {{ $order->shipment->zone_label ?: 'Chưa có mốc' }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </section>

            <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-slate-950">Thanh toán</h3>
                            <p class="mt-1 text-sm font-semibold text-slate-500">Theo dõi offline/Viva, mã giao dịch và thời điểm thu tiền.</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $paymentTone }}">{{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}</span>
                    </div>
                </div>
                <div class="space-y-3 p-5">
                    @forelse ($order->payments as $payment)
                        @php
                            $paymentBadge = match ($payment->status) {
                                'paid' => 'bg-emerald-100 text-emerald-800',
                                'failed' => 'bg-rose-100 text-rose-800',
                                'refunded' => 'bg-slate-200 text-slate-700',
                                default => 'bg-amber-100 text-amber-800',
                            };
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-white p-4 text-sm shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-slate-950">{{ $payment->methodLabel() }}</p>
                                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ $payment->provider ?: 'Nội bộ' }}</p>
                                </div>
                                <p class="rounded-full px-2.5 py-1 text-xs font-black {{ $paymentBadge }}">{{ $payment->statusLabel() }}</p>
                            </div>
                            <p class="mt-3 font-mono text-2xl font-black text-slate-950">{{ format_money($payment->amount, $payment->currency) }}</p>
                            <dl class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <dt class="font-black uppercase tracking-[0.12em] text-slate-400">Mã Viva/đơn</dt>
                                    <dd class="mt-1 break-all font-bold text-slate-700">{{ $payment->reference ?: 'Chưa có' }}</dd>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <dt class="font-black uppercase tracking-[0.12em] text-slate-400">Transaction</dt>
                                    <dd class="mt-1 break-all font-bold text-slate-700">{{ $payment->transaction_code ?: 'Chưa có' }}</dd>
                                </div>
                            </dl>
                            @if ($payment->paid_at || $payment->failed_at || $payment->refunded_at)
                                <p class="mt-3 text-xs font-semibold text-slate-500">
                                    @if ($payment->paid_at) Thu tiền: {{ $payment->paid_at->format('H:i d/m/Y') }} @endif
                                    @if ($payment->failed_at) Thất bại: {{ $payment->failed_at->format('H:i d/m/Y') }} @endif
                                    @if ($payment->refunded_at) Hoàn tiền: {{ $payment->refunded_at->format('H:i d/m/Y') }} @endif
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">Chưa có bản ghi thanh toán.</p>
                    @endforelse
                </div>
            </section>

        </div>
    </div>
@endsection
