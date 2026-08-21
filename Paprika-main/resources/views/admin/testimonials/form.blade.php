@php
    $translationFields = [
        ['name' => 'role', 'label' => 'Short role'],
        ['name' => 'content', 'label' => 'Review content', 'type' => 'textarea', 'rows' => 5, 'wide' => true],
    ];
@endphp

<div class="admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ review">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
        <button type="button" class="admin-tab-button" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="admin-label" for="name">Tên khách</label>
                <input id="name" name="name" value="{{ old('name', $testimonial->name) }}" class="admin-input" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="role">Mô tả ngắn</label>
                <input id="role" name="role" value="{{ old('role', $testimonial->role) }}" class="admin-input">
                @error('role') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="content">Nội dung review</label>
                <textarea id="content" name="content" rows="5" class="admin-input" required>{{ old('content', $testimonial->content) }}</textarea>
                @error('content') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="rating">Rating</label>
                <select id="rating" name="rating" class="admin-input" required>
                    @for ($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" @selected((int) old('rating', $testimonial->rating ?: 5) === $i)>{{ $i }} sao</option>
                    @endfor
                </select>
                @error('rating') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="sort_order">Thứ tự</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $testimonial->sort_order ?? 0) }}" class="admin-input">
                @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="avatar">Ảnh khách hàng</label>
                @if ($testimonial->avatar)
                    <img src="{{ media_url($testimonial->avatar) }}" alt="{{ $testimonial->name }}" class="mb-3 h-24 w-24 rounded-full object-cover">
                @endif
                <input id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
                @error('avatar') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $testimonial->is_active ?? true))>
                Hiển thị
            </label>
        </div>
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="en" hidden>
        @include('admin.partials.translation-fields', ['model' => $testimonial, 'locale' => 'en', 'fields' => $translationFields])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $testimonial, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $testimonial->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.testimonials.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
