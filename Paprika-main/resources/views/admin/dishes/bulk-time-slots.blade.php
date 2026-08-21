@extends('admin.layouts.app')

@section('title', 'Chỉnh khung giờ hàng loạt')

@section('content')
    @php
        $dishSlotPayload = collect($dishes ?? [])->map(fn ($dish) => [
            'id' => $dish->id,
            'name' => $dish->name,
            'slots' => $dish->timeSlots->map(fn ($slot) => [
                'id' => $slot->id,
                'branch_id' => $slot->branch_id,
                'branch' => $slot->branch?->name,
                'name' => $slot->name,
                'start_time' => substr((string) $slot->start_time, 0, 5),
                'end_time' => substr((string) $slot->end_time, 0, 5),
            ])->values()->all(),
        ])->values()->all();
    @endphp

    <form method="POST" action="{{ route('admin.dishes.bulk-time-slots.update') }}" class="admin-form">
        @csrf
        @method('PUT')

        @foreach ($dishIds as $id)
            <input type="hidden" name="ids[]" value="{{ $id }}">
        @endforeach

        <div class="grid gap-5 lg:grid-cols-2">
            <div class="lg:col-span-2">
                <p class="text-sm text-slate-600">Đang chọn <strong>{{ count($dishIds) }}</strong> món.</p>
            </div>

            <div>
                <label class="admin-label" for="branch_id">Cơ sở</label>
                <select id="branch_id" name="branch_id" class="admin-input" required>
                    <option value="">Chọn cơ sở</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="admin-label" for="mode">Chế độ</label>
                <select id="mode" name="mode" class="admin-input" required>
                    <option value="replace">Thay thế (ghi đè)</option>
                    <option value="add">Thêm (giữ khung giờ hiện tại)</option>
                    <option value="clear">Xóa hết (theo cơ sở)</option>
                </select>
                @error('mode') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="lg:col-span-2">
                <label class="admin-label" for="time_slot_ids">Khung giờ</label>
                <select id="time_slot_ids" name="time_slot_ids[]" class="admin-input" multiple size="8">
                    @foreach ($branches as $branch)
                        <optgroup label="{{ $branch->name }}">
                            @foreach (\App\Models\DishTimeSlot::query()->where('branch_id', $branch->id)->orderByDesc('id')->get() as $slot)
                                <option value="{{ $slot->id }}">
                                    {{ $slot->name }} ({{ substr((string) $slot->start_time, 0, 5) }}-{{ substr((string) $slot->end_time, 0, 5) }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-slate-500">Chỉ các khung giờ thuộc cơ sở được chọn sẽ được áp dụng.</p>
                <p id="branch-guard" class="mt-2 hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">Vui lòng chọn cơ sở trước khi cập nhật khung giờ.</p>
                <p id="slot-guard" class="mt-2 hidden text-xs text-rose-600">Bạn đang chọn chế độ thêm/thay thế nhưng chưa chọn khung giờ nào.</p>
                @error('time_slot_ids') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">Xem trước</p>
                            <p class="text-xs text-slate-500">Hiển thị thay đổi dự kiến theo từng món (theo cơ sở đã chọn).</p>
                        </div>
                        <p class="text-xs text-slate-500">Chế độ: <span id="preview-mode" class="font-semibold text-slate-800">-</span></p>
                    </div>

                    <div id="preview" class="mt-3 grid gap-3"></div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold">Khung giờ hiện tại (tham khảo)</p>
                    <p class="text-xs text-slate-500">Danh sách khung giờ hiện tại của từng món. Dùng để đối chiếu nhanh.</p>

                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-600">
                                    <th class="py-2 pr-4">Món</th>
                                    <th class="py-2">Khung giờ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach (($dishes ?? []) as $dish)
                                    <tr>
                                        <td class="py-2 pr-4 font-medium text-slate-800">{{ $dish->name }}</td>
                                        <td class="py-2">
                                            @php
                                                $groups = $dish->timeSlots->groupBy(fn ($s) => $s->branch?->name ?? 'Khác');
                                            @endphp

                                            @if ($dish->timeSlots->isEmpty())
                                                <span class="text-xs text-slate-500">(Chưa có)</span>
                                            @else
                                                <div class="flex flex-col gap-2">
                                                    @foreach ($groups as $branchName => $slots)
                                                        <div>
                                                            <span class="text-xs font-semibold text-slate-700">{{ $branchName }}:</span>
                                                            <div class="mt-1 flex flex-wrap gap-2">
                                                                @foreach ($slots as $slot)
                                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-800">{{ $slot->name }} ({{ substr((string) $slot->start_time, 0, 5) }}-{{ substr((string) $slot->end_time, 0, 5) }})</span>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button id="submit-btn" type="submit" class="admin-btn-primary">Cập nhật</button>
            <a href="{{ route('admin.dishes.index') }}" class="admin-btn-secondary">Quay lại</a>
        </div>

        <script>
            (function () {
                const dishData = @json($dishSlotPayload);

                const branchSelect = document.getElementById('branch_id');
                const modeSelect = document.getElementById('mode');
                const slotSelect = document.getElementById('time_slot_ids');
                const preview = document.getElementById('preview');
                const previewMode = document.getElementById('preview-mode');
                const submitBtn = document.getElementById('submit-btn');
                const branchGuard = document.getElementById('branch-guard');
                const slotGuard = document.getElementById('slot-guard');
                const form = submitBtn?.closest('form');

                function selectedSlotIds() {
                    if (!slotSelect) return [];
                    return Array.from(slotSelect.selectedOptions).map((o) => parseInt(o.value, 10)).filter(Boolean);
                }

                function safe(v) {
                    return String(v ?? '').replace(/[&<>\"']/g, (c) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;',
                    }[c]));
                }

                function slotLabel(slot) {
                    return `${slot.name} (${slot.start_time}-${slot.end_time})`;
                }

                function render() {
                    const branchId = parseInt(branchSelect?.value || '0', 10);
                    const mode = String(modeSelect?.value || 'replace');
                    const selectedIds = selectedSlotIds();

                    if (previewMode) previewMode.textContent = mode;

                    const needsSlots = mode !== 'clear';
                    const invalid = !branchId || (needsSlots && selectedIds.length === 0);

                    if (submitBtn) submitBtn.disabled = invalid;
                    if (branchGuard) branchGuard.classList.toggle('hidden', !!branchId);
                    if (slotGuard) slotGuard.classList.toggle('hidden', !(needsSlots && selectedIds.length === 0));

                    if (!preview) return;

                    const byId = new Map();
                    dishData.forEach((d) => byId.set(d.id, d));

                    const optionSlots = new Map();
                    Array.from(slotSelect?.options || []).forEach((o) => {
                        const id = parseInt(o.value, 10);
                        if (!id) return;

                        const m = String(o.textContent || '').match(/\((\d{2}:\d{2})-(\d{2}:\d{2})\)$/);
                        const name = String(o.textContent || '').replace(/\s*\(\d{2}:\d{2}-\d{2}:\d{2}\)\s*$/, '');

                        optionSlots.set(id, {
                            id,
                            name,
                            start_time: m ? m[1] : '',
                            end_time: m ? m[2] : '',
                        });
                    });

                    let html = '';

                    dishData.forEach((dish) => {
                        const currentForBranch = (dish.slots || []).filter((s) => s.branch_id === branchId);

                        let nextSlots = currentForBranch.slice();
                        if (mode === 'clear') {
                            nextSlots = [];
                        } else if (mode === 'replace') {
                            nextSlots = selectedIds.map((id) => optionSlots.get(id)).filter(Boolean);
                        } else if (mode === 'add') {
                            const existing = new Set(currentForBranch.map((s) => s.id));
                            const added = selectedIds
                                .filter((id) => !existing.has(id))
                                .map((id) => optionSlots.get(id))
                                .filter(Boolean);
                            nextSlots = currentForBranch.concat(added);
                        }

                        const toRemove = currentForBranch
                            .filter((c) => !nextSlots.some((n) => n.id === c.id))
                            .map(slotLabel);
                        const toAdd = nextSlots
                            .filter((n) => !currentForBranch.some((c) => c.id === n.id))
                            .map(slotLabel);

                        const currentLabel = currentForBranch.length
                            ? currentForBranch.map(slotLabel).join(', ')
                            : '(Chưa có)';
                        const nextLabel = nextSlots.length
                            ? nextSlots.map(slotLabel).join(', ')
                            : '(Chưa có)';

                        html += `<div class="rounded-xl border border-slate-200 bg-slate-50 p-3">`;
                        html += `<p class="font-semibold text-slate-900">${safe(dish.name)}</p>`;
                        html += `<p class="mt-1 text-xs text-slate-600"><span class="font-semibold">Hiện tại:</span> ${safe(currentLabel)}</p>`;
                        html += `<p class="mt-1 text-xs text-slate-600"><span class="font-semibold">Sau cập nhật:</span> ${safe(nextLabel)}</p>`;

                        if (toAdd.length || toRemove.length) {
                            html += `<div class="mt-2 flex flex-wrap gap-2">`;
                            toAdd.forEach((t) => (html += `<span class="rounded-full bg-emerald-100 px-3 py-1 text-xs text-emerald-900">+ ${safe(t)}</span>`));
                            toRemove.forEach((t) => (html += `<span class="rounded-full bg-rose-100 px-3 py-1 text-xs text-rose-900">- ${safe(t)}</span>`));
                            html += `</div>`;
                        } else {
                            html += `<p class="mt-2 text-xs text-slate-500">Không thay đổi.</p>`;
                        }

                        html += `</div>`;
                    });

                    preview.innerHTML = html;
                }

                [branchSelect, modeSelect, slotSelect].forEach((el) => el?.addEventListener('change', render));

                form?.addEventListener('submit', (event) => {
                    const branchId = parseInt(branchSelect?.value || '0', 10);
                    const mode = String(modeSelect?.value || 'replace');
                    const needsSlots = mode !== 'clear';
                    const selectedIds = selectedSlotIds();
                    const invalid = !branchId || (needsSlots && selectedIds.length === 0);

                    if (!invalid) return;

                    event.preventDefault();
                    render();

                    const target = !branchId ? branchSelect : slotSelect;
                    target?.focus();
                    target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });

                render();
            })();
        </script>
    </form>
@endsection
