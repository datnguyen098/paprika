<section class="rounded-2xl border border-slate-200 bg-slate-50 p-4" data-preset>
    <div class="grid gap-3 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <label class="admin-label">Tên bộ cấu hình</label>
            <input name="presets[{{ $presetIndex }}][name]" value="{{ $preset['name'] ?? '' }}" class="admin-input" placeholder="Ví dụ: Đồ ăn">
        </div>
        <div class="lg:col-span-3">
            <label class="admin-label">Mã nội bộ</label>
            <input name="presets[{{ $presetIndex }}][slug]" value="{{ $preset['slug'] ?? '' }}" class="admin-input" placeholder="food">
        </div>
        <div class="lg:col-span-5">
            <label class="admin-label">Mô tả</label>
            <input name="presets[{{ $presetIndex }}][description]" value="{{ $preset['description'] ?? '' }}" class="admin-input" placeholder="Gợi ý khi áp dụng vào món">
        </div>
        <div class="flex items-end justify-end">
            <button type="button" class="admin-btn-danger" data-remove-preset>Xóa bộ</button>
        </div>
    </div>

    <div class="mt-4 space-y-3" data-preset-groups>
        @foreach (collect($preset['groups'] ?? []) as $groupIndex => $group)
            <div class="rounded-2xl border border-slate-200 bg-white p-4" data-preset-group>
                <div class="grid gap-3 lg:grid-cols-12">
                    <div class="lg:col-span-3">
                        <label class="admin-label">Tên nhóm</label>
                        <input name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][name]" value="{{ $group['name'] ?? '' }}" class="admin-input" placeholder="Kích cỡ">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Kiểu chọn</label>
                        <select name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][type]" class="admin-input">
                            <option value="single" @selected(($group['type'] ?? 'single') === 'single')>Chọn một</option>
                            <option value="multiple" @selected(($group['type'] ?? '') === 'multiple')>Chọn nhiều</option>
                            <option value="exclude" @selected(($group['type'] ?? '') === 'exclude')>Bỏ thành phần</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Min</label>
                        <input type="number" min="0" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][min_select]" value="{{ $group['min_select'] ?? 0 }}" class="admin-input">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="admin-label">Max</label>
                        <input type="number" min="0" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][max_select]" value="{{ $group['max_select'] ?? 1 }}" class="admin-input">
                    </div>
                    <div>
                        <label class="admin-label">Thứ tự</label>
                        <input type="number" min="0" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][sort_order]" value="{{ $group['sort_order'] ?? $groupIndex }}" class="admin-input">
                    </div>
                    <div class="flex items-end justify-end lg:col-span-2">
                        <button type="button" class="admin-btn-danger" data-remove-preset-group>Xóa nhóm</button>
                    </div>
                    <div class="lg:col-span-12">
                        <label class="admin-label">Mô tả nhóm</label>
                        <input name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][description]" value="{{ $group['description'] ?? '' }}" class="admin-input">
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][is_required]" value="1" @checked((bool) ($group['is_required'] ?? false))>
                        Bắt buộc
                    </label>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                        <input type="checkbox" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][is_active]" value="1" @checked((bool) ($group['is_active'] ?? true))>
                        Hiển thị
                    </label>
                </div>

                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-sm font-black uppercase tracking-wide text-slate-500">Lựa chọn</p>
                        <button type="button" class="admin-btn-mini" data-add-preset-option>Thêm lựa chọn</button>
                    </div>
                    <div class="space-y-2" data-preset-options>
                        @foreach (collect($group['options'] ?? []) as $optionIndex => $option)
                            <div class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 lg:grid-cols-12" data-preset-option>
                                <div class="lg:col-span-3">
                                    <label class="admin-label">Tên lựa chọn</label>
                                    <input name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][options][{{ $optionIndex }}][name]" value="{{ $option['name'] ?? '' }}" class="admin-input">
                                </div>
                                <div class="lg:col-span-3">
                                    <label class="admin-label">Mô tả</label>
                                    <input name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][options][{{ $optionIndex }}][description]" value="{{ $option['description'] ?? '' }}" class="admin-input">
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="admin-label">Giá +/- EUR</label>
                                    <input type="number" step="0.01" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][options][{{ $optionIndex }}][price_delta]" value="{{ $option['price_delta'] ?? '0.00' }}" class="admin-input">
                                </div>
                                <div>
                                    <label class="admin-label">Thứ tự</label>
                                    <input type="number" min="0" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][options][{{ $optionIndex }}][sort_order]" value="{{ $option['sort_order'] ?? $optionIndex }}" class="admin-input">
                                </div>
                                <label class="flex items-end gap-2 pb-3 text-sm font-bold text-slate-700">
                                    <input type="checkbox" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][options][{{ $optionIndex }}][is_default]" value="1" @checked((bool) ($option['is_default'] ?? false))>
                                    Mặc định
                                </label>
                                <label class="flex items-end gap-2 pb-3 text-sm font-bold text-slate-700">
                                    <input type="checkbox" name="presets[{{ $presetIndex }}][groups][{{ $groupIndex }}][options][{{ $optionIndex }}][is_active]" value="1" @checked((bool) ($option['is_active'] ?? true))>
                                    Bật
                                </label>
                                <div class="flex items-end justify-end">
                                    <button type="button" class="admin-btn-danger" data-remove-preset-option>Xóa</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" class="admin-btn-mini mt-4" data-add-preset-group>Thêm nhóm lựa chọn</button>
</section>
