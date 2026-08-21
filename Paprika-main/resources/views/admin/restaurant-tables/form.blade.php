<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="admin-label" for="branch_id">Cơ sở</label>
        @if ($branches->count() > 1)
            <select id="branch_id" name="branch_id" class="admin-input" required>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $table->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="branch_id" value="{{ old('branch_id', $table->branch_id ?: $branches->first()?->id) }}">
            <div class="admin-input bg-slate-50">{{ $branches->first()?->name ?: 'Paprika Patras' }}</div>
        @endif
        @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="code">Mã bàn</label>
        <input id="code" name="code" value="{{ old('code', $table->code) }}" class="admin-input" placeholder="T1" required>
        @error('code') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="name">Tên bàn</label>
        <input id="name" name="name" value="{{ old('name', $table->name) }}" class="admin-input" placeholder="Bàn 1" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="seats">Số ghế</label>
        <input id="seats" type="number" min="1" max="40" name="seats" value="{{ old('seats', $table->seats ?? 2) }}" class="admin-input" required>
        @error('seats') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="zone">Khu vực</label>
        <input id="zone" name="zone" value="{{ old('zone', $table->zone) }}" class="admin-input" placeholder="Sảnh chính">
        @error('zone') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="status">Trạng thái</label>
        <select id="status" name="status" class="admin-input" required>
            @foreach (\App\Models\RestaurantTable::STATUS_LABELS as $status => $label)
                <option value="{{ $status }}" @selected(old('status', $table->status ?? 'active') === $status)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="sort_order">Thứ tự</label>
        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $table->sort_order ?? 0) }}" class="admin-input">
        @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 font-semibold text-slate-700">
        <input type="checkbox" name="is_joinable" value="1" @checked(old('is_joinable', $table->is_joinable ?? false))>
        Có thể ghép bàn khi cần
    </label>

    <div class="lg:col-span-2">
        <label class="admin-label" for="note">Ghi chú nội bộ</label>
        <textarea id="note" name="note" rows="3" class="admin-input">{{ old('note', $table->note) }}</textarea>
        @error('note') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $table->exists ? 'Cập nhật' : 'Lưu bàn' }}</button>
    <a href="{{ route('admin.restaurant-tables.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
