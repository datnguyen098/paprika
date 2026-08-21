@php
    $translationFields = [
        ['name' => 'name', 'label' => 'Category name'],
        ['name' => 'slug', 'label' => 'English slug'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 4, 'wide' => true],
        ['name' => 'meta_title', 'label' => 'Meta title'],
        ['name' => 'meta_description', 'label' => 'Meta description'],
    ];
@endphp

<div class="admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ danh mục">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
        <button type="button" class="admin-tab-button" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="admin-label" for="name">Tên danh mục</label>
                <input id="name" name="name" value="{{ old('name', $category->name) }}" class="admin-input" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $category->slug) }}" class="admin-input" placeholder="Để trống để tự sinh">
                @error('slug') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="type">Loại danh mục</label>
                <select id="type" name="type" class="admin-input" required>
                    <option value="dish" @selected(old('type', $category->type) === 'dish')>Món ăn</option>
                    <option value="post" @selected(old('type', $category->type) === 'post')>Bài viết</option>
                </select>
                @error('type') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="sort_order">Thứ tự</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="admin-input">
                @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="description">Mô tả</label>
                <textarea id="description" name="description" rows="4" class="admin-input">{{ old('description', $category->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="image">Ảnh danh mục</label>
                @if ($category->image)
                    <img src="{{ media_url($category->image) }}" alt="{{ $category->name }}" class="mb-3 h-36 w-full rounded-xl object-cover">
                @endif
                <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
                <p class="mt-2 text-sm text-slate-500">Chấp nhận JPG, PNG, WEBP, SVG. Ảnh lớn sẽ được resize/tối ưu khi upload.</p>
                @error('image') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="meta_title">Meta title</label>
                <input id="meta_title" name="meta_title" value="{{ old('meta_title', $category->meta_title) }}" class="admin-input">
                @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="meta_description">Meta description</label>
                <input id="meta_description" name="meta_description" value="{{ old('meta_description', $category->meta_description) }}" class="admin-input">
                @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))>
                Hiển thị
            </label>
        </div>
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="en" hidden>
        @include('admin.partials.translation-fields', ['model' => $category, 'locale' => 'en', 'fields' => $translationFields])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $category, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $category->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.categories.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
