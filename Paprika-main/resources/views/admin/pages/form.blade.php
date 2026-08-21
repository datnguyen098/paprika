@php
    $translationFields = [
        ['name' => 'title', 'label' => 'Page title'],
        ['name' => 'slug', 'label' => 'English slug'],
        ['name' => 'content', 'label' => 'Content', 'type' => 'textarea', 'rows' => 14, 'wide' => true],
        ['name' => 'meta_title', 'label' => 'Meta title'],
        ['name' => 'meta_keywords', 'label' => 'Meta keywords'],
        ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'textarea', 'rows' => 3, 'wide' => true],
    ];
@endphp

<div class="admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ trang">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
        <button type="button" class="admin-tab-button" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="admin-label" for="title">Tiêu đề</label>
                <input id="title" name="title" value="{{ old('title', $page->title) }}" class="admin-input" required>
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $page->slug) }}" class="admin-input" placeholder="Để trống để tự sinh">
                @error('slug') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="template">Template</label>
                <input id="template" name="template" value="{{ old('template', $page->template) }}" class="admin-input" placeholder="default, about...">
                @error('template') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 self-end font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $page->is_active ?? true))>
                Hiển thị
            </label>
            <div class="lg:col-span-2">
                <label class="admin-label" for="content">Nội dung</label>
                <textarea id="content" name="content" rows="14" class="admin-input" required>{{ old('content', $page->content) }}</textarea>
                @error('content') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="image">Ảnh đại diện</label>
                @if ($page->image)
                    <img src="{{ media_url($page->image) }}" alt="{{ $page->title }}" class="mb-3 h-44 w-full rounded-xl object-cover">
                @endif
                <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
                <p class="mt-2 text-sm text-slate-500">Chấp nhận JPG, PNG, WEBP, SVG. Ảnh lớn sẽ được resize/tối ưu khi upload.</p>
                @error('image') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="meta_title">Meta title</label>
                <input id="meta_title" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="admin-input">
                @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="meta_keywords">Meta keywords</label>
                <input id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}" class="admin-input">
                @error('meta_keywords') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="meta_description">Meta description</label>
                <textarea id="meta_description" name="meta_description" rows="3" class="admin-input">{{ old('meta_description', $page->meta_description) }}</textarea>
                @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="en" hidden>
        @include('admin.partials.translation-fields', ['model' => $page, 'locale' => 'en', 'fields' => $translationFields])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $page, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $page->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.pages.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
