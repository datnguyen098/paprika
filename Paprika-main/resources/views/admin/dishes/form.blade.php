@php
    $priceInputValue = fn ($value) => filled($value) ? number_format(((int) $value) / 100, 2, '.', '') : null;
    $translationFields = [
        ['name' => 'name', 'label' => 'Dish name'],
        ['name' => 'slug', 'label' => 'English slug'],
        ['name' => 'description', 'label' => 'Short description', 'type' => 'textarea', 'rows' => 4, 'wide' => true],
        ['name' => 'content', 'label' => 'Detail content', 'type' => 'textarea', 'rows' => 8, 'wide' => true],
        ['name' => 'ingredients', 'label' => 'Ingredients', 'type' => 'textarea', 'rows' => 4, 'wide' => true],
        ['name' => 'meta_title', 'label' => 'Meta title'],
        ['name' => 'meta_keywords', 'label' => 'Meta keywords'],
        ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'textarea', 'rows' => 3, 'wide' => true],
    ];
    $optionGroups = collect(old('option_groups', $dish->exists
        ? $dish->optionGroups->map(fn ($group) => [
            'id' => $group->id,
            'name' => $group->name,
            'type' => $group->type,
            'description' => $group->description,
            'is_required' => $group->is_required,
            'is_active' => $group->is_active,
            'min_select' => $group->min_select,
            'max_select' => $group->max_select,
            'sort_order' => $group->sort_order,
            'translations' => $group->translations->mapWithKeys(fn ($t) => [$t->locale => ['name' => $t->name, 'description' => $t->description]])->all(),
            'options' => $group->options->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'description' => $option->description,
                'price_delta' => $priceInputValue($option->price_delta),
                'is_default' => $option->is_default,
                'is_active' => $option->is_active,
                'sort_order' => $option->sort_order,
                'translations' => $option->translations->mapWithKeys(fn ($t) => [$t->locale => ['name' => $t->name, 'description' => $t->description]])->all(),
            ])->all(),
        ])->all()
        : []
    ));
@endphp

<div class="admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ nội dung">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
        <button type="button" class="admin-tab-button" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
        <div class="grid gap-5 lg:grid-cols-2">
            <div>
                <label class="admin-label" for="name">Tên món</label>
                <input id="name" name="name" value="{{ old('name', $dish->name) }}" class="admin-input" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="slug">Slug</label>
                <input id="slug" name="slug" value="{{ old('slug', $dish->slug) }}" class="admin-input" placeholder="Để trống để tự sinh">
                @error('slug') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="category_id">Danh mục</label>
                <select id="category_id" name="category_id" class="admin-input" required>
                    <option value="">Chọn danh mục</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $dish->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="sort_order">Thứ tự</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $dish->sort_order ?? 0) }}" class="admin-input">
                @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="description">Mô tả ngắn</label>
                <textarea id="description" name="description" rows="4" class="admin-input" required>{{ old('description', $dish->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="content">Nội dung chi tiết</label>
                <textarea id="content" name="content" rows="8" class="admin-input">{{ old('content', $dish->content) }}</textarea>
                @error('content') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="ingredients">Thành phần</label>
                <textarea id="ingredients" name="ingredients" rows="4" class="admin-input">{{ old('ingredients', $dish->ingredients) }}</textarea>
                @error('ingredients') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="price">Giá EUR</label>
                <input id="price" type="number" min="0" step="0.01" name="price" value="{{ old('price', $priceInputValue($dish->price)) }}" class="admin-input" required>
                @error('price') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="sale_price">Giá khuyến mãi EUR</label>
                <input id="sale_price" type="number" min="0" step="0.01" name="sale_price" value="{{ old('sale_price', $priceInputValue($dish->sale_price)) }}" class="admin-input">
                @error('sale_price') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            @include('admin.dishes.option-groups', ['optionGroups' => $optionGroups])
            <div class="lg:col-span-2">
                <label class="admin-label" for="image">Ảnh đại diện</label>
                @if ($dish->image)
                    <img src="{{ media_url($dish->image) }}" alt="{{ $dish->name }}" class="mb-3 h-44 w-full rounded-xl object-cover">
                @endif
                <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
                <p class="mt-2 text-sm text-slate-500">Chấp nhận JPG, PNG, WEBP, SVG. Ảnh lớn sẽ được resize/tối ưu khi upload.</p>
                @error('image') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="gallery">Gallery nhiều ảnh</label>
                @if ($dish->gallery)
                    <div class="mb-3 grid gap-3 sm:grid-cols-3">
                        @foreach ($dish->gallery as $image)
                            <label class="rounded-xl border border-slate-200 p-2">
                                <img src="{{ media_url($image) }}" alt="Gallery {{ $dish->name }}" class="h-24 w-full rounded-lg object-cover">
                                <span class="mt-2 flex items-center gap-2 text-xs">
                                    <input type="checkbox" name="remove_gallery[]" value="{{ $image }}">
                                    Xóa ảnh này
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <input id="gallery" type="file" name="gallery[]" multiple accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
                <p class="mt-2 text-sm text-slate-500">Có thể chọn nhiều ảnh. Mỗi ảnh sẽ được tối ưu tự động.</p>
                @error('gallery.*') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="meta_title">Meta title</label>
                <input id="meta_title" name="meta_title" value="{{ old('meta_title', $dish->meta_title) }}" class="admin-input">
                @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="meta_keywords">Meta keywords</label>
                <input id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords', $dish->meta_keywords) }}" class="admin-input">
                @error('meta_keywords') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="admin-label" for="meta_description">Meta description</label>
                <textarea id="meta_description" name="meta_description" rows="3" class="admin-input">{{ old('meta_description', $dish->meta_description) }}</textarea>
                @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 font-semibold text-slate-700">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $dish->is_featured))>
                Món nổi bật
            </label>
            <label class="flex items-center gap-2 font-semibold text-slate-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $dish->is_active ?? true))>
                Hiển thị
            </label>
        </div>
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="en" hidden>
        @include('admin.partials.translation-fields', ['model' => $dish, 'locale' => 'en', 'fields' => $translationFields])
        @include('admin.partials.option-translation-fields', ['optionGroups' => $optionGroups, 'locale' => 'en'])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $dish, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
        @include('admin.partials.option-translation-fields', ['optionGroups' => $optionGroups, 'locale' => 'el'])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $dish->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.dishes.index') }}" class="admin-btn-secondary">Quay lại</a>
    @if ($dish->exists)
        <a href="{{ localized_route('menu.show', ['dish' => $dish->localizedSlug()]) }}" target="_blank" class="admin-btn-secondary">Preview</a>
    @endif
</div>
