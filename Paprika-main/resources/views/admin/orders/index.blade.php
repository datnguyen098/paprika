@extends('admin.layouts.app')

@section('title', 'Đơn hàng')

@prepend('styles')
<style>
    .bulk-bar {
        animation: slideDown .2s ease-out;
    }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endprepend

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter" method="GET">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm mã đơn, tên, SĐT..." class="admin-input">
            <select name="branch_id" class="admin-input">
                <option value="">Tất cả cơ sở</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                @foreach (\App\Models\Order::STATUSES as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ \App\Models\Order::STATUS_LABELS[$status] }}</option>
                @endforeach
            </select>
            <button class="admin-btn-secondary">Lọc</button>
        </form>
    </div>

    {{-- Bulk action bar --}}
    <div id="bulkBar" class="bulk-bar hidden mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-sm font-semibold text-amber-800">
                Đã chọn <span id="bulkCount">0</span> đơn hàng
            </span>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
                <form method="POST" action="{{ route('admin.orders.bulk-cancel') }}" id="bulkCancelForm" data-confirm="Hủy các đơn đã chọn?" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="admin-btn-warning w-full justify-center sm:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Hủy đơn
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.orders.bulk-destroy') }}" id="bulkDestroyForm" data-confirm="Xóa vĩnh viễn các đơn đã chọn?" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="admin-btn-danger w-full justify-center sm:w-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Xóa đơn
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th class="w-10">
                        <input type="checkbox" id="selectAll">
                    </th>
                    <th>Đơn</th>
                    <th>Khách</th>
                    <th>Cơ sở</th>
                    <th>Hình thức</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr class="cursor-pointer transition {{ $order->status === 'cancelled' ? 'opacity-50' : '' }}" data-bulk-select-row>
                        <td>
                            <input type="checkbox" name="ids[]" value="{{ $order->id }}" class="order-check rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </td>
                        <td>
                            <p class="font-semibold">{{ $order->code }}</p>
                            <p class="text-xs text-slate-500">{{ $order->items->sum('quantity') }} món</p>
                        </td>
                        <td>
                            <p class="font-semibold">{{ $order->customer_name }}</p>
                            <p class="text-xs text-slate-500">{{ $order->customer_phone }}</p>
                        </td>
                        <td>{{ $order->branch?->name ?: 'Chưa chọn' }}</td>
                        <td>{{ $order->fulfillmentLabel() }}</td>
                        <td class="font-semibold">{{ format_money($order->total) }}</td>
                        <td><span class="status-badge status-{{ $order->statusTone() }}">{{ $order->statusLabel('vi') }}</span></td>
                        <td>{{ business_time($order->created_at, $order->branch)?->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.orders.show', $order) }}" class="admin-btn-mini">Chi tiết</a>
                                <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" data-confirm="Xóa đơn hàng này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-slate-500">Chưa có đơn hàng.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $orders->links() }}</div>
@endsection

@prepend('scripts')
<script>
(function () {
    const bulkBar   = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');
    const selectAll = document.getElementById('selectAll');
    const checks    = () => document.querySelectorAll('.order-check:checked');
    const allChecks = () => document.querySelectorAll('.order-check');

    function syncRowState () {
        allChecks().forEach(cb => {
            cb.closest('[data-bulk-select-row]')?.classList.toggle('bg-amber-50', cb.checked);
        });
    }

    function updateBar () {
        const n = checks().length;
        bulkBar.classList.toggle('hidden', n === 0);
        bulkCount.textContent = n;
        selectAll.checked = n > 0 && n === allChecks().length;
        selectAll.indeterminate = n > 0 && n < allChecks().length;
        syncRowState();
    }

    selectAll.addEventListener('change', () => {
        allChecks().forEach(cb => cb.checked = selectAll.checked);
        updateBar();
    });

    allChecks().forEach(cb => cb.addEventListener('change', updateBar));

    document.addEventListener('click', (e) => {
        const row = e.target.closest('[data-bulk-select-row]');
        if (!row) return;
        if (e.target.closest('a, button, input, select, textarea, label, form')) return;

        const cb = row.querySelector('.order-check');
        if (!cb) return;

        cb.checked = !cb.checked;
        cb.dispatchEvent(new Event('change', { bubbles: true }));
    });

    // Inject hidden inputs before submit
    ['bulkCancelForm', 'bulkDestroyForm'].forEach(formId => {
        document.getElementById(formId).addEventListener('submit', function (e) {
            const checked = checks();
            if (checked.length === 0) { e.preventDefault(); return; }
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type  = 'hidden';
                input.name  = 'ids[]';
                input.value = cb.value;
                this.appendChild(input);
            });
        });
    });

    // data-confirm on bulk forms
    ['bulkCancelForm', 'bulkDestroyForm'].forEach(formId => {
        const form = document.getElementById(formId);
        form.addEventListener('submit', function (e) {
            const msg = this.dataset.confirm;
            if (! confirm(msg)) e.preventDefault();
        });
    });
})();
</script>
@endprepend
