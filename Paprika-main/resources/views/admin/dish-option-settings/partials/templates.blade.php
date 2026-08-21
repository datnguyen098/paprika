<template data-preset-template>
    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4" data-preset>
        <div class="grid gap-3 lg:grid-cols-12">
            <div class="lg:col-span-3">
                <label class="admin-label">Tên bộ cấu hình</label>
                <input name="presets[__PRESET__][name]" class="admin-input" placeholder="Ví dụ: Món nướng">
            </div>
            <div class="lg:col-span-3">
                <label class="admin-label">Mã nội bộ</label>
                <input name="presets[__PRESET__][slug]" class="admin-input" placeholder="grill">
            </div>
            <div class="lg:col-span-5">
                <label class="admin-label">Mô tả</label>
                <input name="presets[__PRESET__][description]" class="admin-input">
            </div>
            <div class="flex items-end justify-end">
                <button type="button" class="admin-btn-danger" data-remove-preset>Xóa bộ</button>
            </div>
        </div>
        <div class="mt-4 space-y-3" data-preset-groups></div>
        <button type="button" class="admin-btn-mini mt-4" data-add-preset-group>Thêm nhóm lựa chọn</button>
    </section>
</template>

<template data-preset-group-template>
    <div class="rounded-2xl border border-slate-200 bg-white p-4" data-preset-group>
        <div class="grid gap-3 lg:grid-cols-12">
            <div class="lg:col-span-3">
                <label class="admin-label">Tên nhóm</label>
                <input name="presets[__PRESET__][groups][__GROUP__][name]" class="admin-input" placeholder="Kích cỡ">
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label">Kiểu chọn</label>
                <select name="presets[__PRESET__][groups][__GROUP__][type]" class="admin-input">
                    <option value="single">Chọn một</option>
                    <option value="multiple">Chọn nhiều</option>
                    <option value="exclude">Bỏ thành phần</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label">Min</label>
                <input type="number" min="0" name="presets[__PRESET__][groups][__GROUP__][min_select]" value="0" class="admin-input">
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label">Max</label>
                <input type="number" min="0" name="presets[__PRESET__][groups][__GROUP__][max_select]" value="1" class="admin-input">
            </div>
            <div>
                <label class="admin-label">Thứ tự</label>
                <input type="number" min="0" name="presets[__PRESET__][groups][__GROUP__][sort_order]" value="0" class="admin-input">
            </div>
            <div class="flex items-end justify-end lg:col-span-2">
                <button type="button" class="admin-btn-danger" data-remove-preset-group>Xóa nhóm</button>
            </div>
            <div class="lg:col-span-12">
                <label class="admin-label">Mô tả nhóm</label>
                <input name="presets[__PRESET__][groups][__GROUP__][description]" class="admin-input">
            </div>
            <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                <input type="checkbox" name="presets[__PRESET__][groups][__GROUP__][is_required]" value="1">
                Bắt buộc
            </label>
            <label class="flex items-center gap-2 text-sm font-bold text-slate-700">
                <input type="checkbox" name="presets[__PRESET__][groups][__GROUP__][is_active]" value="1" checked>
                Hiển thị
            </label>
        </div>
        <div class="mt-4">
            <div class="mb-2 flex items-center justify-between gap-3">
                <p class="text-sm font-black uppercase tracking-wide text-slate-500">Lựa chọn</p>
                <button type="button" class="admin-btn-mini" data-add-preset-option>Thêm lựa chọn</button>
            </div>
            <div class="space-y-2" data-preset-options></div>
        </div>
    </div>
</template>

<template data-preset-option-template>
    <div class="grid gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 lg:grid-cols-12" data-preset-option>
        <div class="lg:col-span-3">
            <label class="admin-label">Tên lựa chọn</label>
            <input name="presets[__PRESET__][groups][__GROUP__][options][__OPTION__][name]" class="admin-input" placeholder="Regular">
        </div>
        <div class="lg:col-span-3">
            <label class="admin-label">Mô tả</label>
            <input name="presets[__PRESET__][groups][__GROUP__][options][__OPTION__][description]" class="admin-input">
        </div>
        <div class="lg:col-span-2">
            <label class="admin-label">Giá +/- EUR</label>
            <input type="number" step="0.01" name="presets[__PRESET__][groups][__GROUP__][options][__OPTION__][price_delta]" value="0.00" class="admin-input">
        </div>
        <div>
            <label class="admin-label">Thứ tự</label>
            <input type="number" min="0" name="presets[__PRESET__][groups][__GROUP__][options][__OPTION__][sort_order]" value="0" class="admin-input">
        </div>
        <label class="flex items-end gap-2 pb-3 text-sm font-bold text-slate-700">
            <input type="checkbox" name="presets[__PRESET__][groups][__GROUP__][options][__OPTION__][is_default]" value="1">
            Mặc định
        </label>
        <label class="flex items-end gap-2 pb-3 text-sm font-bold text-slate-700">
            <input type="checkbox" name="presets[__PRESET__][groups][__GROUP__][options][__OPTION__][is_active]" value="1" checked>
            Bật
        </label>
        <div class="flex items-end justify-end">
            <button type="button" class="admin-btn-danger" data-remove-preset-option>Xóa</button>
        </div>
    </div>
</template>
