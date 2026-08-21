@php
    $accentColor = old('accent_color', $promotion->accent_color ?: '#047857');
    $selectedPlacement = old('placement', $promotion->placement ?: 'home');
    $buttonLink = old('button_link', $promotion->button_link);
    $translationFields = [
        ['name' => 'title', 'label' => 'Promotion title'],
        ['name' => 'subtitle', 'label' => 'Subtitle'],
        ['name' => 'badge', 'label' => 'Badge'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 4, 'wide' => true],
        ['name' => 'button_text', 'label' => 'Button text'],
    ];

    if (! is_string($accentColor) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $accentColor)) {
        $accentColor = '#047857';
    }

    $accentPalette = [
        ['name' => 'Xanh lá', 'color' => '#047857'],
        ['name' => 'Xanh rêu', 'color' => '#166534'],
        ['name' => 'Xanh ngọc', 'color' => '#0f766e'],
        ['name' => 'Nâu gỗ', 'color' => '#92400e'],
        ['name' => 'Hổ phách', 'color' => '#b45309'],
        ['name' => 'Đỏ đất', 'color' => '#be123c'],
        ['name' => 'Tím sen', 'color' => '#7c3aed'],
        ['name' => 'Xanh trời', 'color' => '#0284c7'],
    ];

    $quickLinks = [
        ['label' => 'Trang chủ', 'url' => '/'],
        ['label' => 'Thực đơn', 'url' => '/thuc-don'],
        ['label' => 'Đặt bàn', 'url' => '/dat-ban'],
        ['label' => 'Đặt bàn', 'url' => '/dat-ban'],
        ['label' => 'Blog', 'url' => '/blog'],
        ['label' => 'Giới thiệu', 'url' => '/gioi-thieu'],
    ];
@endphp

<div class="admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ khuyến mãi">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
        <button type="button" class="admin-tab-button" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="admin-label" for="title">Tiêu đề</label>
                <input id="title" name="title" value="{{ old('title', $promotion->title) }}" class="admin-input" required>
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="subtitle">Phụ đề</label>
                <input id="subtitle" name="subtitle" value="{{ old('subtitle', $promotion->subtitle) }}" class="admin-input">
                @error('subtitle') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="badge">Nhãn sale/quảng cáo</label>
                <input id="badge" name="badge" value="{{ old('badge', $promotion->badge) }}" class="admin-input">
                @error('badge') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="accent_color">Màu nhấn</label>
                <div class="admin-color-picker" data-admin-color-picker>
                    <div class="admin-color-main">
                        <input id="accent_color_picker" type="color" value="{{ $accentColor }}" class="admin-color-input" data-color-picker aria-label="Chọn màu nhấn">
                        <input id="accent_color" type="hidden" name="accent_color" value="{{ $accentColor }}" data-color-value>
                        <span class="admin-color-code" data-color-code>{{ strtoupper($accentColor) }}</span>
                    </div>
                    <div class="admin-color-palette" aria-label="Bảng màu gợi ý">
                        @foreach ($accentPalette as $color)
                            <button type="button" class="admin-color-swatch" data-color-swatch="{{ $color['color'] }}" style="background: {{ $color['color'] }}" title="{{ $color['name'] }}" aria-label="{{ $color['name'] }}"></button>
                        @endforeach
                    </div>
                </div>
                @error('accent_color') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="description">Mô tả</label>
                <textarea id="description" name="description" rows="4" class="admin-input">{{ old('description', $promotion->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="button_text">Text nút</label>
                <input id="button_text" name="button_text" value="{{ old('button_text', $promotion->button_text) }}" class="admin-input">
                @error('button_text') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="button_link">Link nút</label>
                <div class="grid gap-2" data-admin-link-picker>
                    <select class="admin-input" data-link-suggestion aria-label="Chọn nhanh link nút">
                        <option value="">Chọn nhanh đường dẫn có sẵn</option>
                        @foreach ($quickLinks as $link)
                            <option value="{{ $link['url'] }}" @selected($buttonLink === $link['url'])>{{ $link['label'] }} - {{ $link['url'] }}</option>
                        @endforeach
                    </select>
                    <input id="button_link" name="button_link" value="{{ $buttonLink }}" class="admin-input">
                </div>
                @error('button_link') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="placement">Vị trí hiển thị</label>
                <select id="placement" name="placement" class="admin-input" required>
                    <option value="home" @selected($selectedPlacement === 'home')>Khối quảng cáo trang chủ</option>
                    <option value="popup" @selected($selectedPlacement === 'popup')>Popup khi vào web</option>
                </select>
                @error('placement') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="template">Mẫu thiết kế</label>
                <select id="template" name="template" class="admin-input" required>
                    <option value="split" @selected(old('template', $promotion->template ?: 'split') === 'split')>Split ảnh + nội dung</option>
                    <option value="center" @selected(old('template', $promotion->template) === 'center')>Center nổi bật</option>
                    <option value="minimal" @selected(old('template', $promotion->template) === 'minimal')>Minimal gọn</option>
                </select>
                @error('template') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="starts_at">Bắt đầu</label>
                <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($promotion->starts_at)->format('Y-m-d\\TH:i')) }}" class="admin-input">
                @error('starts_at') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="ends_at">Kết thúc</label>
                <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($promotion->ends_at)->format('Y-m-d\\TH:i')) }}" class="admin-input">
                @error('ends_at') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="sort_order">Thứ tự</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $promotion->sort_order ?? 0) }}" class="admin-input">
                @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="image">Ảnh quảng cáo</label>
                @if ($promotion->image)
                    <img src="{{ media_url($promotion->image) }}" alt="{{ $promotion->title }}" class="mb-3 h-44 w-full rounded-xl object-cover">
                @endif
                <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
                @error('image') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div @class(['lg:col-span-2', 'hidden' => $selectedPlacement !== 'popup']) data-popup-only>
                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="show_once" value="1" @checked(old('show_once', $promotion->show_once ?? true)) @disabled($selectedPlacement !== 'popup')>
                    Popup chỉ hiện một lần cho mỗi trình duyệt
                </label>
            </div>
            <label class="flex items-center gap-2 font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion->is_active ?? true))>
                Bật hiển thị
            </label>
        </div>
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="en" hidden>
        @include('admin.partials.translation-fields', ['model' => $promotion, 'locale' => 'en', 'fields' => $translationFields])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $promotion, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $promotion->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.promotions.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
