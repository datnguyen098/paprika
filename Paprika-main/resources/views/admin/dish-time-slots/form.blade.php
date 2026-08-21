@php
    $translationFields = [
        ['name' => 'name', 'label' => 'Name'],
    ];
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="admin-label" for="branch_id">Cơ sở</label>
        <select id="branch_id" name="branch_id" class="admin-input" required>
            <option value="">Chọn cơ sở</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((string) old('branch_id', $slot->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="name">Tên (VI)</label>
        <input id="name" name="name" value="{{ old('name', $slot->name) }}" class="admin-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="start_date">Từ ngày</label>
        <input id="start_date" type="date" name="start_date" value="{{ old('start_date', $slot->start_date?->toDateString()) }}" class="admin-input">
        @error('start_date') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="end_date">Đến ngày</label>
        <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $slot->end_date?->toDateString()) }}" class="admin-input">
        @error('end_date') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="start_time">Giờ bắt đầu</label>
        <input id="start_time" type="time" name="start_time" value="{{ old('start_time', $slot->start_time ? substr((string) $slot->start_time, 0, 5) : null) }}" class="admin-input" required>
        @error('start_time') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="end_time">Giờ kết thúc</label>
        <input id="end_time" type="time" name="end_time" value="{{ old('end_time', $slot->end_time ? substr((string) $slot->end_time, 0, 5) : null) }}" class="admin-input" required>
        @error('end_time') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 font-semibold text-slate-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $slot->is_active ?? true))>
        Kích hoạt
    </label>
</div>

<div class="mt-6 admin-form-tabs" data-admin-tabs>
    <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ nội dung">
        <button type="button" class="admin-tab-button is-active" data-admin-tab="en">English</button>
        <button type="button" class="admin-tab-button" data-admin-tab="el">Ελληνικά</button>
    </div>

    <div class="admin-tab-panel is-active" data-admin-tab-panel="en">
        @include('admin.partials.translation-fields', ['model' => $slot, 'locale' => 'en', 'fields' => $translationFields])
    </div>

    <div class="admin-tab-panel" data-admin-tab-panel="el" hidden>
        @include('admin.partials.translation-fields', ['model' => $slot, 'locale' => 'el', 'localeLabel' => 'Ελληνικά', 'fields' => $translationFields])
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $slot->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.dish-time-slots.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
