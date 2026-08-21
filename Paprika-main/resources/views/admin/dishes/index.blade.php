@extends('admin.layouts.app')

@section('title', 'Món ăn / Sản phẩm')

@section('content')
    <div class="admin-page-head">
        <form class="admin-filter flex w-full flex-nowrap items-center gap-2 overflow-x-auto" method="GET" id="dish-filter-form">
            <input type="hidden" name="ids" id="bulk-ids" value="">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm món..." class="admin-input">
            <select name="category_id" class="admin-input">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="admin-input">
                <option value="">Tất cả trạng thái</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            <select name="has_time_slots" class="admin-input">
                <option value="">Khung giờ: tất cả</option>
                <option value="yes" @selected(request('has_time_slots') === 'yes')>Có khung giờ</option>
                <option value="no" @selected(request('has_time_slots') === 'no')>Chưa có khung giờ</option>
            </select>
            <button class="admin-btn-secondary shrink-0">Lọc</button>
            <button type="button" class="admin-btn-secondary shrink-0" id="bulk-time-slots-btn" disabled>Chỉnh khung giờ (bulk)</button>
            <a href="{{ route('admin.dishes.create') }}" class="admin-btn-primary shrink-0">Thêm món</a>
        </form>
    </div>

    <script>
        (function () {
            const bulkBtn = document.getElementById('bulk-time-slots-btn');
            const bulkIds = document.getElementById('bulk-ids');

            const modal = document.getElementById('dish-time-slots-modal');
            const modalTitle = document.getElementById('dish-time-slots-modal-title');
            const modalBody = document.getElementById('dish-time-slots-modal-body');

            function selectedIds() {
                return Array.from(document.querySelectorAll('input[data-bulk-dish]:checked'))
                    .map((el) => el.value)
                    .filter(Boolean);
            }

            function refreshBulk() {
                const ids = selectedIds();
                bulkBtn.disabled = ids.length === 0;
                bulkBtn.classList.toggle('opacity-50', ids.length === 0);
                bulkBtn.classList.toggle('cursor-not-allowed', ids.length === 0);
                bulkIds.value = ids.join(',');

                document.querySelectorAll('input[data-bulk-dish]').forEach((cb) => {
                    cb.closest('[data-bulk-select-row]')?.classList.toggle('bg-amber-50', cb.checked);
                });

                const all = document.getElementById('bulk-all');
                const checks = Array.from(document.querySelectorAll('input[data-bulk-dish]'));
                if (all) {
                    all.checked = checks.length > 0 && ids.length === checks.length;
                    all.indeterminate = ids.length > 0 && ids.length < checks.length;
                }
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
            }

            function groupedSlotsHtml(slots) {
                const groups = new Map();
                (slots || []).forEach((s) => {
                    const key = s.branch || 'Khác';
                    if (!groups.has(key)) groups.set(key, []);
                    groups.get(key).push(s);
                });

                const safe = (v) => String(v ?? '').replace(/[&<>\"']/g, (c) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                }[c]));

                if (!slots || slots.length === 0) {
                    return '<p class="text-sm text-slate-600">Chưa gán khung giờ nào.</p>';
                }

                let html = '';
                Array.from(groups.entries()).forEach(([branch, rows]) => {
                    html += `<div class="mb-4 last:mb-0">`;
                    html += `<p class="text-sm font-semibold text-slate-800">${safe(branch)}</p>`;
                    html += `<div class="mt-2 flex flex-wrap gap-2">`;
                    rows.forEach((r) => {
                        html += `<span class="rounded-full bg-slate-200 px-3 py-1 text-xs text-slate-800">${safe(r.name)} (${safe(r.start_time)}-${safe(r.end_time)})</span>`;
                    });
                    html += `</div>`;
                    html += `</div>`;
                });

                return html;
            }

            function openModal(dishName, slots) {
                if (!modal || !modalTitle || !modalBody) return;

                modalTitle.textContent = dishName || 'Khung giờ';
                modalBody.innerHTML = groupedSlotsHtml(slots);

                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
            }

            document.addEventListener('change', (e) => {
                if (e.target && e.target.matches('input[data-bulk-dish], #bulk-all')) {
                    if (e.target.id === 'bulk-all') {
                        const checked = e.target.checked;
                        document.querySelectorAll('input[data-bulk-dish]').forEach((cb) => (cb.checked = checked));
                    }
                    refreshBulk();
                }
            });

            document.addEventListener('click', (e) => {
                const row = e.target?.closest?.('[data-bulk-select-row]');
                if (!row) return;
                if (e.target.closest('a, button, input, select, textarea, label, form')) return;

                const cb = row.querySelector('input[data-bulk-dish]');
                if (!cb) return;

                cb.checked = !cb.checked;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            });

            bulkBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const ids = selectedIds();
                if (ids.length === 0) return;

                const base = "{{ route('admin.dishes.bulk-time-slots.edit') }}";
                const qs = ids.map((id) => "ids%5B%5D=" + encodeURIComponent(id)).join("&");
                window.location.href = base + (base.includes("?") ? "&" : "?") + qs;
            });

            function parseSlots(btn) {
                let slots = [];
                try {
                    slots = JSON.parse(btn.getAttribute('data-time-slots') || '[]');
                } catch (e) {
                    slots = [];
                }
                return slots;
            }

            function ensurePopoverFilled(btn) {
                const wrapper = btn.closest('[data-time-slots-popover-wrap]');
                const popover = wrapper?.querySelector('[data-time-slots-popover]');
                if (!popover) return;

                if (popover.getAttribute('data-filled') === '1') return;

                const slots = parseSlots(btn);
                popover.innerHTML = groupedSlotsHtml(slots);
                popover.setAttribute('data-filled', '1');
            }

            document.addEventListener('mouseover', (e) => {
                const btn = e.target?.closest?.('[data-open-time-slots-modal]');
                if (!btn) return;
                ensurePopoverFilled(btn);
            });

            document.addEventListener('focusin', (e) => {
                const btn = e.target?.closest?.('[data-open-time-slots-modal]');
                if (!btn) return;
                ensurePopoverFilled(btn);
            });

            document.addEventListener('click', (e) => {
                const btn = e.target?.closest?.('[data-open-time-slots-modal]');
                if (!btn) return;

                e.preventDefault();
                const dishName = btn.getAttribute('data-dish-name') || '';
                const slots = parseSlots(btn);
                openModal(dishName, slots);
            });

            document.querySelectorAll('[data-close-time-slots-modal]').forEach((btn) => {
                btn.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeModal();
            });

            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
            }

            refreshBulk();
        })();
    </script>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:36px;">
                        <input type="checkbox" id="bulk-all">
                    </th>
                    <th>Ảnh</th>
                    <th>Tên món</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Nổi bật</th>
                    <th>Trạng thái</th>
                    <th>Khung giờ</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dishes as $dish)
                    <tr class="cursor-pointer transition" data-bulk-select-row>
                        <td>
                            <input type="checkbox" data-bulk-dish value="{{ $dish->id }}">
                        </td>
                        <td>
                            @if ($dish->image)
                                <img src="{{ media_url($dish->image) }}" alt="{{ $dish->name }}" class="h-16 w-20 rounded-xl object-cover">
                            @endif
                        </td>
                        <td>
                            <p class="font-semibold">{{ $dish->name }}</p>
                            <p class="text-xs text-slate-500">{{ $dish->slug }}</p>
                        </td>
                        <td>{{ $dish->category?->name }}</td>
                        <td>
                            @if ($dish->sale_price)
                                <span class="font-semibold text-emerald-800">{{ format_money($dish->sale_price) }}</span>
                                <span class="block text-xs text-slate-400 line-through">{{ format_money($dish->price) }}</span>
                            @else
                                {{ format_money($dish->price) }}
                            @endif
                        </td>
                        <td>{{ $dish->is_featured ? 'Có' : 'Không' }}</td>
                        <td><span class="status-badge {{ $dish->is_active ? 'status-active' : 'status-inactive' }}">{{ $dish->is_active ? 'active' : 'inactive' }}</span></td>
                        <td>
                            @php
                                $slotPayload = $dish->timeSlots
                                    ->map(fn ($slot) => [
                                        'branch' => $slot->branch?->name,
                                        'name' => $slot->name,
                                        'start_time' => substr((string) $slot->start_time, 0, 5),
                                        'end_time' => substr((string) $slot->end_time, 0, 5),
                                    ])
                                    ->values()
                                    ->all();
                            @endphp

                            <div class="group relative inline-flex" data-time-slots-popover-wrap>
                                <button
                                    type="button"
                                    class="admin-btn-mini {{ count($slotPayload) === 0 ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}"
                                    data-time-slots='@json($slotPayload)'
                                    data-dish-name="{{ $dish->name }}"
                                    data-open-time-slots-modal
                                >
                                    {{ count($slotPayload) }}
                                </button>

                                <div class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 hidden w-80 -translate-x-1/2 rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-xl group-hover:block group-focus-within:block" data-time-slots-popover></div>
                            </div>
                        </td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <a href="{{ localized_route('menu.show', ['dish' => $dish->localizedSlug()]) }}" target="_blank" class="admin-btn-mini">Preview</a>
                                <a href="{{ route('admin.dishes.edit', $dish) }}" class="admin-btn-mini">Sửa</a>
                                <form method="POST" action="{{ route('admin.dishes.destroy', $dish) }}" data-confirm="Xóa món này?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="admin-btn-danger">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-slate-500">Chưa có món ăn.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">{{ $dishes->links() }}</div>

    <div id="dish-time-slots-modal" class="fixed inset-0 z-50 hidden bg-black/40 p-4" aria-hidden="true">
        <div class="mx-auto mt-12 w-full max-w-xl rounded-2xl bg-white p-5 shadow-xl">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-slate-500">Khung giờ của món</p>
                    <h3 id="dish-time-slots-modal-title" class="text-lg font-semibold text-slate-900">Khung giờ</h3>
                </div>
                <button type="button" class="admin-btn-secondary" data-close-time-slots-modal>Đóng</button>
            </div>

            <div id="dish-time-slots-modal-body" class="mt-4"></div>
        </div>
    </div>
@endsection
