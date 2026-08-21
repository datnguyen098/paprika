<div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-slate-50 p-4" data-dish-options-builder>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="text-lg font-black text-slate-950">Biến thể và tùy chọn món</h3>
            <p class="mt-1 text-sm text-slate-500">Quản lý size, độ cay, topping thêm hoặc thành phần muốn bỏ. Giá cộng thêm nhập bằng EUR.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if (!empty($optionPresets ?? []))
                <select class="admin-input max-w-56" data-option-preset-select>
                    <option value="">Chọn bộ cấu hình</option>
                    @foreach ($optionPresets as $presetIndex => $preset)
                        <option value="{{ $presetIndex }}">{{ $preset['name'] ?? 'Bộ cấu hình' }}</option>
                    @endforeach
                </select>
                <button type="button" class="admin-btn-secondary" data-apply-option-preset>Áp dụng</button>
            @endif
            <a href="{{ route('admin.dish-option-settings.edit') }}" class="admin-btn-secondary">Cấu hình chung</a>
            <button type="button" class="admin-btn-secondary" data-add-option-group>Thêm nhóm</button>
        </div>
    </div>

    @error('option_groups') <p class="form-error">{{ $message }}</p> @enderror

    <div class="mt-4 space-y-4" data-option-groups>
        @foreach ($optionGroups as $groupIndex => $group)
            <div class="rounded-2xl border border-slate-200 bg-white p-4" data-option-group>
                <div class="grid gap-3 lg:grid-cols-12">
                    <input type="hidden" name="option_groups[{{ $groupIndex }}][id]" value="{{ $group['id'] ?? '' }}">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Tên nhóm</label>
                        <input name="option_groups[{{ $groupIndex }}][name]" value="{{ $group['name'] ?? '' }}" class="admin-input" placeholder="Ví dụ: Kích cỡ">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Kiểu chọn</label>
                        <select name="option_groups[{{ $groupIndex }}][type]" class="admin-input">
                            <option value="single" @selected(($group['type'] ?? 'single') === 'single')>Chọn một</option>
                            <option value="multiple" @selected(($group['type'] ?? '') === 'multiple')>Chọn nhiều</option>
                            <option value="exclude" @selected(($group['type'] ?? '') === 'exclude')>Bỏ thành phần</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Min</label>
                        <input type="number" min="0" name="option_groups[{{ $groupIndex }}][min_select]" value="{{ $group['min_select'] ?? 0 }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Max</label>
                        <input type="number" min="0" name="option_groups[{{ $groupIndex }}][max_select]" value="{{ $group['max_select'] ?? (($group['type'] ?? 'single') === 'single' ? 1 : 0) }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-1">
                        <label class="admin-label">Thứ tự</label>
                        <input type="number" min="0" name="option_groups[{{ $groupIndex }}][sort_order]" value="{{ $group['sort_order'] ?? $groupIndex }}" class="admin-input">
                    </div>
                    <div class="flex items-end justify-end lg:col-span-2">
                        <button type="button" class="admin-btn-danger" data-remove-option-group>Xóa nhóm</button>
                    </div>
                    <div class="lg:col-span-12">
                        <label class="admin-label">Mô tả ngắn</label>
                        <input name="option_groups[{{ $groupIndex }}][description]" value="{{ $group['description'] ?? '' }}" class="admin-input" placeholder="Hiện trên trang chi tiết món">
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="option_groups[{{ $groupIndex }}][is_required]" value="1" @checked((bool) ($group['is_required'] ?? false))>
                        Bắt buộc
                    </label>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="option_groups[{{ $groupIndex }}][is_active]" value="1" @checked((bool) ($group['is_active'] ?? true))>
                        Hiển thị
                    </label>
                </div>

                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-sm font-black uppercase tracking-wide text-slate-500">Lựa chọn</p>
                        <button type="button" class="admin-btn-mini" data-add-option>Thêm lựa chọn</button>
                    </div>
                    <div class="space-y-2" data-options>
                        @foreach (collect($group['options'] ?? []) as $optionIndex => $option)
                            <div class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 lg:grid-cols-12" data-option>
                                <input type="hidden" name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][id]" value="{{ $option['id'] ?? '' }}">
                                <div class="lg:col-span-3">
                                    <label class="admin-label">Tên lựa chọn</label>
                                    <input name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][name]" value="{{ $option['name'] ?? '' }}" class="admin-input" placeholder="Regular, Large, Hot...">
                                </div>
                                <div class="lg:col-span-3">
                                    <label class="admin-label">Mô tả</label>
                                    <input name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][description]" value="{{ $option['description'] ?? '' }}" class="admin-input">
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="admin-label">Giá +/- EUR</label>
                                    <input type="number" step="0.01" name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][price_delta]" value="{{ $option['price_delta'] ?? '0.00' }}" class="admin-input">
                                </div>
                                <div>
                                    <label class="admin-label">Thứ tự</label>
                                    <input type="number" min="0" name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][sort_order]" value="{{ $option['sort_order'] ?? $optionIndex }}" class="admin-input">
                                </div>
                                <label class="flex items-end gap-2 pb-3 text-sm font-bold text-slate-700">
                                    <input type="checkbox" name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][is_default]" value="1" @checked((bool) ($option['is_default'] ?? false))>
                                    Mặc định
                                </label>
                                <label class="flex items-end gap-2 pb-3 text-sm font-bold text-slate-700">
                                    <input type="checkbox" name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][is_active]" value="1" @checked((bool) ($option['is_active'] ?? true))>
                                    Bật
                                </label>
                                <div class="flex items-end justify-end">
                                    <button type="button" class="admin-btn-danger" data-remove-option>Xóa</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <template data-option-group-template>
        <div class="rounded-2xl border border-slate-200 bg-white p-4" data-option-group>
            <div class="grid gap-3 lg:grid-cols-12">
                <input type="hidden" name="option_groups[__GROUP__][id]" value="">
                <div class="lg:col-span-3">
                    <label class="admin-label">Tên nhóm</label>
                    <input name="option_groups[__GROUP__][name]" class="admin-input" placeholder="Ví dụ: Kích cỡ">
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label">Kiểu chọn</label>
                    <select name="option_groups[__GROUP__][type]" class="admin-input">
                        <option value="single">Chọn một</option>
                        <option value="multiple">Chọn nhiều</option>
                        <option value="exclude">Bỏ thành phần</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label">Min</label>
                    <input type="number" min="0" name="option_groups[__GROUP__][min_select]" value="0" class="admin-input">
                </div>
                <div class="lg:col-span-2">
                    <label class="admin-label">Max</label>
                    <input type="number" min="0" name="option_groups[__GROUP__][max_select]" value="1" class="admin-input">
                </div>
                <div class="lg:col-span-1">
                    <label class="admin-label">Thứ tự</label>
                    <input type="number" min="0" name="option_groups[__GROUP__][sort_order]" value="0" class="admin-input">
                </div>
                <div class="flex items-end justify-end lg:col-span-2">
                    <button type="button" class="admin-btn-danger" data-remove-option-group>Xóa nhóm</button>
                </div>
                <div class="lg:col-span-12">
                    <label class="admin-label">Mô tả ngắn</label>
                    <input name="option_groups[__GROUP__][description]" class="admin-input" placeholder="Hiện trên trang chi tiết món">
                </div>
                <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="option_groups[__GROUP__][is_required]" value="1">
                    Bắt buộc
                </label>
                <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                    <input type="checkbox" name="option_groups[__GROUP__][is_active]" value="1" checked>
                    Hiển thị
                </label>
            </div>
            <div class="mt-4">
                <div class="mb-2 flex items-center justify-between gap-3">
                    <p class="text-sm font-black uppercase tracking-wide text-slate-500">Lựa chọn</p>
                    <button type="button" class="admin-btn-mini" data-add-option>Thêm lựa chọn</button>
                </div>
                <div class="space-y-2" data-options></div>
            </div>
        </div>
    </template>

    <template data-option-template>
        <div class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 lg:grid-cols-12" data-option>
            <input type="hidden" name="option_groups[__GROUP__][options][__OPTION__][id]" value="">
            <div class="lg:col-span-3">
                <label class="admin-label">Tên lựa chọn</label>
                <input name="option_groups[__GROUP__][options][__OPTION__][name]" class="admin-input" placeholder="Regular, Large, Hot...">
            </div>
            <div class="lg:col-span-3">
                <label class="admin-label">Mô tả</label>
                <input name="option_groups[__GROUP__][options][__OPTION__][description]" class="admin-input">
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label">Giá +/- EUR</label>
                <input type="number" step="0.01" name="option_groups[__GROUP__][options][__OPTION__][price_delta]" value="0.00" class="admin-input">
            </div>
            <div>
                <label class="admin-label">Thứ tự</label>
                <input type="number" min="0" name="option_groups[__GROUP__][options][__OPTION__][sort_order]" value="0" class="admin-input">
            </div>
            <label class="flex items-end gap-2 pb-3 text-sm font-bold text-slate-700">
                <input type="checkbox" name="option_groups[__GROUP__][options][__OPTION__][is_default]" value="1">
                Mặc định
            </label>
            <label class="flex items-end gap-2 pb-3 text-sm font-bold text-slate-700">
                <input type="checkbox" name="option_groups[__GROUP__][options][__OPTION__][is_active]" value="1" checked>
                Bật
            </label>
            <div class="flex items-end justify-end">
                <button type="button" class="admin-btn-danger" data-remove-option>Xóa</button>
            </div>
        </div>
    </template>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('[data-dish-options-builder]').forEach((builder) => {
            const groups = builder.querySelector('[data-option-groups]');
            const groupTemplate = builder.querySelector('[data-option-group-template]');
            const optionTemplate = builder.querySelector('[data-option-template]');
            const optionPresets = @json($optionPresets ?? []);
            let groupCounter = groups.querySelectorAll('[data-option-group]').length;

            const nextGroupIndex = () => groupCounter++;

            const groupIndexOf = (group) => group
                .querySelector('input[name^="option_groups["]')
                ?.name
                .match(/option_groups\[([^\]]+)]/)?.[1] || '0';

            const setField = (scope, suffix, value) => {
                const field = scope.querySelector(`[name$="${suffix}"]`);
                if (!field) return;

                if (field.type === 'checkbox') {
                    field.checked = Boolean(value);
                    return;
                }

                field.value = value ?? '';
            };

            const addOption = (group, optionData = {}) => {
                const groupIndex = groupIndexOf(group);
                const options = group.querySelector('[data-options]');
                const optionIndex = `${Date.now()}${options.querySelectorAll('[data-option]').length}`;
                const html = optionTemplate.innerHTML
                    .replaceAll('__GROUP__', String(groupIndex))
                    .replaceAll('__OPTION__', String(optionIndex));

                options.insertAdjacentHTML('beforeend', html);
                const option = options.lastElementChild;
                setField(option, '[name]', optionData.name || '');
                setField(option, '[description]', optionData.description || '');
                setField(option, '[price_delta]', optionData.price_delta || '0.00');
                setField(option, '[sort_order]', optionData.sort_order ?? options.querySelectorAll('[data-option]').length - 1);
                setField(option, '[is_default]', optionData.is_default || false);
                setField(option, '[is_active]', optionData.is_active ?? true);
                option?.querySelector('input[name$="[name]"]')?.focus();

                return option;
            };

            const addGroup = (groupData = {}) => {
                const groupIndex = nextGroupIndex();
                groups.insertAdjacentHTML('beforeend', groupTemplate.innerHTML.replaceAll('__GROUP__', String(groupIndex)));
                const group = groups.lastElementChild;

                setField(group, '[name]', groupData.name || '');
                setField(group, '[type]', groupData.type || 'single');
                setField(group, '[min_select]', groupData.min_select ?? 0);
                setField(group, '[max_select]', groupData.max_select ?? (groupData.type === 'single' ? 1 : 0));
                setField(group, '[sort_order]', groupData.sort_order ?? groups.querySelectorAll('[data-option-group]').length - 1);
                setField(group, '[description]', groupData.description || '');
                setField(group, '[is_required]', groupData.is_required || false);
                setField(group, '[is_active]', groupData.is_active ?? true);

                (groupData.options || [{}]).forEach((option) => addOption(group, option));
                group.querySelector('input[name$="[name]"]')?.focus();

                return group;
            };

            builder.querySelector('[data-add-option-group]')?.addEventListener('click', () => {
                addGroup();
            });

            builder.querySelector('[data-apply-option-preset]')?.addEventListener('click', () => {
                const selected = builder.querySelector('[data-option-preset-select]')?.value;
                const preset = selected !== '' ? optionPresets[Number(selected)] : null;

                if (!preset || !Array.isArray(preset.groups)) {
                    return;
                }

                preset.groups.forEach((group) => addGroup(group));
            });

            builder.addEventListener('click', (event) => {
                const removeGroup = event.target.closest('[data-remove-option-group]');
                const removeOption = event.target.closest('[data-remove-option]');
                const addOptionButton = event.target.closest('[data-add-option]');

                if (removeGroup) {
                    removeGroup.closest('[data-option-group]')?.remove();
                }

                if (removeOption) {
                    removeOption.closest('[data-option]')?.remove();
                }

                if (addOptionButton) {
                    addOption(addOptionButton.closest('[data-option-group]'), {});
                }
            });
        });
    </script>
@endpush
