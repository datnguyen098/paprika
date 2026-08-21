@php
    $translationFields = [
        ['name' => 'title', 'label' => 'Gallery title'],
        ['name' => 'slug', 'label' => 'English slug'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 4, 'wide' => true],
        ['name' => 'alt_text', 'label' => 'Alt text'],
        ['name' => 'meta_title', 'label' => 'Meta title'],
        ['name' => 'meta_description', 'label' => 'Meta description'],
    ];
@endphp

<div class="admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ ảnh">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
        <button type="button" class="admin-tab-button" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="admin-label" for="title">Tiêu đề</label>
                <input id="title" name="title" value="{{ old('title', $galleryImage->title) }}" class="admin-input" required>
                @error('title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $galleryImage->slug) }}" class="admin-input" placeholder="Tự sinh nếu bỏ trống">
                @error('slug') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="description">Mô tả ngắn</label>
                <textarea id="description" name="description" rows="4" class="admin-input">{{ old('description', $galleryImage->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="location">Vị trí</label>
                <select id="location" name="location" class="admin-input" required>
                    <option value="space" @selected(old('location', $galleryImage->location ?: 'space') === 'space')>Không gian quán</option>
                    <option value="kitchen" @selected(old('location', $galleryImage->location) === 'kitchen')>Bếp / nguyên liệu</option>
                    <option value="event" @selected(old('location', $galleryImage->location) === 'event')>Sự kiện</option>
                </select>
                @error('location') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="branch_id">Cơ sở</label>
                <select id="branch_id" name="branch_id" class="admin-input">
                    <option value="">Dùng chung / chưa gắn cơ sở</option>
                    @foreach (($branches ?? collect()) as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $galleryImage->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="sort_order">Thứ tự</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $galleryImage->sort_order ?? 0) }}" class="admin-input">
                @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="image">Ảnh</label>
                @if ($galleryImage->image)
                    <img src="{{ media_url($galleryImage->image) }}" alt="{{ $galleryImage->title }}" class="mb-3 h-52 w-full rounded-xl object-cover">
                @endif
                <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input" @required(! $galleryImage->exists)>
                <p class="mt-2 text-sm text-slate-500">Chấp nhận JPG, PNG, WEBP, SVG. Dung lượng tối đa 10MB.</p>
                @error('image') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="alt_text">Alt text</label>
                <input id="alt_text" name="alt_text" value="{{ old('alt_text', $galleryImage->alt_text) }}" class="admin-input" placeholder="Mô tả ảnh để tốt cho SEO">
                @error('alt_text') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="meta_title">Meta title</label>
                <input id="meta_title" name="meta_title" value="{{ old('meta_title', $galleryImage->meta_title) }}" class="admin-input">
                @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="meta_description">Meta description</label>
                <input id="meta_description" name="meta_description" value="{{ old('meta_description', $galleryImage->meta_description) }}" class="admin-input">
                @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 font-semibold text-slate-700">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $galleryImage->is_featured ?? true))>
                Hiển thị nổi bật ở trang chủ
            </label>
            <label class="flex items-center gap-2 font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $galleryImage->is_active ?? true))>
                Hiển thị
            </label>
        </div>
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="en" hidden>
        @include('admin.partials.translation-fields', ['model' => $galleryImage, 'locale' => 'en', 'fields' => $translationFields])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $galleryImage, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $galleryImage->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.gallery.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
