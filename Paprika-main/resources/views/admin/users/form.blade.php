<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="admin-label" for="name">Họ tên</label>
        <input id="name" name="name" value="{{ old('name', $user->name) }}" class="admin-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="admin-input" required>
        @error('email') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="password">Mật khẩu</label>
        <input id="password" type="password" name="password" class="admin-input" @required(! $user->exists)>
        @error('password') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="password_confirmation">Nhập lại mật khẩu</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="admin-input" @required(! $user->exists)>
    </div>
    <div>
        <label class="admin-label" for="role">Loại tài khoản</label>
        <select id="role" name="role" class="admin-input" required>
            <option value="admin" @selected(old('role', $user->role ?: 'admin') === 'admin')>Admin</option>
            <option value="user" @selected(old('role', $user->role) === 'user')>Người dùng</option>
        </select>
        @error('role') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="role_id">Vai trò phân quyền</label>
        <select id="role_id" name="role_id" class="admin-input">
            <option value="">Toàn quyền mặc định</option>
            @foreach ($roles as $roleOption)
                <option value="{{ $roleOption->id }}" @selected((int) old('role_id', $user->role_id) === $roleOption->id)>
                    {{ $roleOption->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-2 text-sm text-slate-500">Nếu bỏ trống, tài khoản admin cũ sẽ tạm có toàn quyền để tránh tự khóa hệ thống.</p>
        @error('role_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="branch_id">Phạm vi cơ sở</label>
        <select id="branch_id" name="branch_id" class="admin-input">
            <option value="">Tất cả cơ sở</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) old('branch_id', $user->branch_id) === $branch->id)>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-2 text-sm text-slate-500">Nếu chọn một cơ sở, tài khoản chỉ thấy đặt bàn, liên hệ và chat của cơ sở đó.</p>
        @error('branch_id') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="avatar">Avatar</label>
        @if ($user->avatar)
            <img src="{{ media_url($user->avatar) }}" alt="{{ $user->name }}" class="mb-3 h-20 w-20 rounded-full object-cover">
        @endif
        <input id="avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="sr-only" data-file-input data-file-target="avatar-file-name">
        <label for="avatar" class="inline-flex min-h-12 cursor-pointer items-center rounded-xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-700">
            Chọn ảnh
        </label>
        <span id="avatar-file-name" class="ml-3 text-sm text-slate-500">Chưa chọn file</span>
        <p class="mt-2 text-sm text-slate-500">Chấp nhận JPG, PNG, WEBP. Dung lượng tối đa 10MB.</p>
        @error('avatar') <p class="form-error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $user->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.users.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-file-input]').forEach((input) => {
                input.addEventListener('change', () => {
                    const target = document.getElementById(input.dataset.fileTarget);
                    if (target) {
                        target.textContent = input.files?.[0]?.name || 'Chưa chọn file';
                    }
                });
            });
        </script>
    @endpush
@endonce
