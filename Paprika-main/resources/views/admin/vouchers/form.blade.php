@php
    $translationFields = [
        ['name' => 'name', 'label' => 'Tên voucher'],
        ['name' => 'description', 'label' => 'Mô tả', 'type' => 'textarea', 'rows' => 4, 'wide' => true],
    ];
    $selectedType = old('discount_type', $voucher->discount_type ?: \App\Models\Voucher::TYPE_PERCENT);
    $discountValue = old('discount_value', $selectedType === \App\Models\Voucher::TYPE_PERCENT
        ? number_format(($voucher->discount_value ?: 0) / 100, 2, '.', '')
        : number_format(($voucher->discount_value ?: 0) / 100, 2, '.', ''));
@endphp

<div class="admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ voucher">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
        <button type="button" class="admin-tab-button" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
        <div class="grid gap-5 lg:grid-cols-2" data-voucher-form>
            <div>
                <label class="admin-label" for="code">Mã voucher</label>
                <input id="code" name="code" value="{{ old('code', $voucher->code) }}" class="admin-input" required>
                @error('code') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="name">Tên voucher</label>
                <input id="name" name="name" value="{{ old('name', $voucher->name) }}" class="admin-input" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="description">Mô tả</label>
                <textarea id="description" name="description" rows="3" class="admin-input">{{ old('description', $voucher->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="discount_type">Loại giảm</label>
                <select id="discount_type" name="discount_type" class="admin-input" data-voucher-type required>
                    <option value="percent" @selected($selectedType === 'percent')>Giảm theo phần trăm</option>
                    <option value="fixed" @selected($selectedType === 'fixed')>Giảm số tiền cố định</option>
                    <option value="free_shipping" @selected($selectedType === 'free_shipping')>Miễn phí giao hàng</option>
                </select>
                @error('discount_type') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div data-voucher-value-wrap>
                <label class="admin-label" for="discount_value" data-voucher-value-label>Giá trị giảm</label>
                <input id="discount_value" type="number" min="0" step="0.01" name="discount_value" value="{{ $discountValue }}" class="admin-input">
                <p class="mt-1 text-xs text-slate-500" data-voucher-value-help>Nhập 10 cho 10% hoặc 5.5 cho €5,50 tùy loại giảm.</p>
                @error('discount_value') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div data-voucher-percent-only>
                <label class="admin-label" for="max_discount_amount">Giảm tối đa EUR</label>
                <input id="max_discount_amount" type="number" min="0" step="0.01" name="max_discount_amount" value="{{ old('max_discount_amount', $voucher->max_discount_amount !== null ? number_format($voucher->max_discount_amount / 100, 2, '.', '') : '') }}" class="admin-input">
                @error('max_discount_amount') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="min_order_amount">Đơn tối thiểu EUR</label>
                <input id="min_order_amount" type="number" min="0" step="0.01" name="min_order_amount" value="{{ old('min_order_amount', number_format(($voucher->min_order_amount ?: 0) / 100, 2, '.', '')) }}" class="admin-input">
                @error('min_order_amount') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="branch_id">Cơ sở áp dụng</label>
                <select id="branch_id" name="branch_id" class="admin-input">
                    <option value="">Tất cả cơ sở</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $voucher->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="starts_at">Bắt đầu</label>
                <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($voucher->starts_at)->format('Y-m-d\\TH:i')) }}" class="admin-input">
                @error('starts_at') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="ends_at">Kết thúc</label>
                <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($voucher->ends_at)->format('Y-m-d\\TH:i')) }}" class="admin-input">
                @error('ends_at') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="usage_limit_total">Giới hạn tổng lượt dùng</label>
                <input id="usage_limit_total" type="number" min="1" name="usage_limit_total" value="{{ old('usage_limit_total', $voucher->usage_limit_total) }}" class="admin-input">
                @error('usage_limit_total') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="usage_limit_per_customer">Giới hạn mỗi khách</label>
                <input id="usage_limit_per_customer" type="number" min="1" name="usage_limit_per_customer" value="{{ old('usage_limit_per_customer', $voucher->usage_limit_per_customer) }}" class="admin-input">
                @error('usage_limit_per_customer') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="sort_order">Thứ tự</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $voucher->sort_order ?? 0) }}" class="admin-input">
                @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-wrap gap-5 lg:col-span-2">
                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $voucher->is_active ?? true))>
                    Bật voucher
                </label>
                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $voucher->is_public ?? true))>
                    Hiện public ở checkout
                </label>
                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $voucher->is_default ?? false))>
                    Đặt làm mặc định
                </label>
            </div>
        </div>
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="en" hidden>
        @include('admin.partials.translation-fields', ['model' => $voucher, 'locale' => 'en', 'fields' => $translationFields])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $voucher, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $voucher->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.vouchers.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>

@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-voucher-form]');
            if (!root) return;

            const type = root.querySelector('[data-voucher-type]');
            const valueWrap = root.querySelector('[data-voucher-value-wrap]');
            const valueLabel = root.querySelector('[data-voucher-value-label]');
            const valueHelp = root.querySelector('[data-voucher-value-help]');
            const percentOnly = root.querySelector('[data-voucher-percent-only]');

            const render = () => {
                const current = type?.value || 'percent';
                if (valueWrap) valueWrap.classList.toggle('hidden', current === 'free_shipping');
                if (percentOnly) percentOnly.classList.toggle('hidden', current !== 'percent');
                if (valueLabel) valueLabel.textContent = current === 'percent' ? 'Phần trăm giảm (%)' : 'Số tiền giảm EUR';
                if (valueHelp) valueHelp.textContent = current === 'percent' ? 'Nhập 10 cho 10%.' : 'Nhập 5.5 cho €5,50.';
            };

            type?.addEventListener('change', render);
            render();
        })();
    </script>
@endpush
