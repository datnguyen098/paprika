@php
    $routeOptions = $routeOptions ?? collect();
    $translationFields = [
        ['name' => 'title', 'label' => 'Menu title'],
        ['name' => 'url', 'label' => 'URL', 'list' => 'admin-menu-route-options', 'placeholder' => 'route:menu.index hoặc https://...'],
    ];
@endphp

<datalist id="admin-menu-route-options">
    @foreach ($routeOptions as $option)
        <option value="{{ $option['value'] }}" label="{{ $option['label'] }} — {{ $option['sample'] }}"></option>
    @endforeach
</datalist>

<div class="admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ menu">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
        <button type="button" class="admin-tab-button" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="admin-label" for="title">Tiêu đề</label>
                <input id="title" name="title" value="{{ old('title', $menu->title) }}" class="admin-input" required>
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="url">URL</label>
                <input id="url" name="url" value="{{ old('url', $menu->url) }}" class="admin-input font-mono text-xs" list="admin-menu-route-options" placeholder="route:menu.index hoặc https://..." required>
                <p class="mt-1 text-xs text-slate-500">Gõ để tìm/chọn route nội bộ như <code>route:gallery.index</code>; route sẽ tự đổi URL theo VI/EN/EL. Link ngoài vẫn nhập tay bình thường.</p>
                @error('url') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="location">Vị trí</label>
                <select id="location" name="location" class="admin-input" required>
                    <option value="header" @selected(old('location', $menu->location ?: 'header') === 'header')>Header</option>
                    <option value="footer" @selected(old('location', $menu->location) === 'footer')>Footer</option>
                </select>
                @error('location') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="parent_id">Menu cha</label>
                <select id="parent_id" name="parent_id" class="admin-input">
                    <option value="">Không có</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $menu->parent_id) === (string) $parent->id)>{{ $parent->title }} ({{ $parent->location }})</option>
                    @endforeach
                </select>
                @error('parent_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="sort_order">Thứ tự</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $menu->sort_order ?? 0) }}" class="admin-input">
                @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end gap-5">
                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $menu->is_active ?? true))>
                    Hiển thị
                </label>
                <label class="flex items-center gap-2 font-semibold text-slate-700">
                    <input type="checkbox" name="open_new_tab" value="1" @checked(old('open_new_tab', $menu->open_new_tab))>
                    Mở tab mới
                </label>
            </div>
        </div>
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="en" hidden>
        @include('admin.partials.translation-fields', ['model' => $menu, 'locale' => 'en', 'fields' => $translationFields])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $menu, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $menu->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.menus.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
