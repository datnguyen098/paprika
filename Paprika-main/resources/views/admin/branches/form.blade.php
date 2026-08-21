@php
    $moneyInput = fn ($amount) => $amount === null ? '' : number_format(((int) $amount) / 100, 2, '.', '');
    $defaultZones = [
        ['label' => 'Dưới 1km', 'min_distance_km' => '0', 'max_distance_km' => '1', 'fee' => '0', 'sort_order' => 0, 'is_active' => true],
        ['label' => '1-3km', 'min_distance_km' => '1', 'max_distance_km' => '3', 'fee' => '150', 'sort_order' => 1, 'is_active' => true],
        ['label' => '3-5km', 'min_distance_km' => '3', 'max_distance_km' => '5', 'fee' => '300', 'sort_order' => 2, 'is_active' => true],
        ['label' => '5-6km', 'min_distance_km' => '5', 'max_distance_km' => '6', 'fee' => '450', 'sort_order' => 3, 'is_active' => true],
    ];
    $existingZones = $branch->exists
        ? $branch->deliveryZones->map(fn ($zone) => [
            'id' => $zone->id,
            'label' => $zone->label,
            'min_distance_km' => $zone->min_distance_km,
            'max_distance_km' => $zone->max_distance_km,
            'fee' => $moneyInput($zone->fee),
            'sort_order' => $zone->sort_order,
            'is_active' => $zone->is_active,
        ])->all()
        : $defaultZones;
    $deliveryZones = old('delivery_zones', $existingZones);
    $blankZoneCount = max(2, 6 - count($deliveryZones));
    for ($i = 0; $i < $blankZoneCount; $i++) {
        $deliveryZones[] = ['label' => '', 'min_distance_km' => '', 'max_distance_km' => '', 'fee' => '0.00', 'sort_order' => count($deliveryZones), 'is_active' => true];
    }
    $timezones = \App\Support\TimezoneOptions::business();
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="admin-label" for="name">Tên cơ sở</label>
        <input id="name" name="name" value="{{ old('name', $branch->name) }}" class="admin-input" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="slug">Slug</label>
        <input id="slug" name="slug" value="{{ old('slug', $branch->slug) }}" class="admin-input" placeholder="Tự sinh nếu bỏ trống">
        @error('slug') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="city">Tỉnh/thành</label>
        <input id="city" name="city" value="{{ old('city', $branch->city) }}" class="admin-input">
        @error('city') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="timezone">Múi giờ</label>
        <select id="timezone" name="timezone" class="admin-input">
            <option value="">— Mặc định hệ thống —</option>
            @foreach ($timezones as $value => $label)
                <option value="{{ $value }}" @selected(old('timezone', $branch->timezone) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Để trống sẽ dùng múi giờ mặc định. Thời gian hiển thị (đặt bàn, đơn hàng...) sẽ theo múi giờ cơ sở này.</p>
        @error('timezone') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="sort_order">Thứ tự</label>
        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $branch->sort_order ?? 0) }}" class="admin-input">
        @error('sort_order') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="lg:col-span-2">
        <label class="admin-label" for="address">Địa chỉ</label>
        <textarea id="address" name="address" rows="3" class="admin-input">{{ old('address', $branch->address) }}</textarea>
        @error('address') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="hotline">Hotline ưu tiên</label>
        <input id="hotline" name="hotline" value="{{ old('hotline', $branch->hotline) }}" class="admin-input">
        @error('hotline') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="phone">Số điện thoại</label>
        <input id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" class="admin-input">
        @error('phone') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $branch->email) }}" class="admin-input">
        @error('email') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="opening_hours">Giờ mở cửa hiển thị</label>
        <input id="opening_hours" name="opening_hours" value="{{ old('opening_hours', $branch->opening_hours) }}" class="admin-input" placeholder="09:00 - 14:00 | 16:00 - 21:00 hằng ngày">
        @error('opening_hours') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="lg:col-span-2">
        @php
            $branchOpenDaysRaw = session()->hasOldInput('open_days') ? old('open_days', []) : $branch->open_days;
            $branchOpenDays = filled($branchOpenDaysRaw) ? \App\Support\OpenDays::normalize($branchOpenDaysRaw) : [];
        @endphp
        <span class="admin-label">Ngày mở cửa riêng cho cơ sở</span>
        <div class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (\App\Support\OpenDays::options() as $day => $dayLabel)
                <label class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-100">
                    <input type="checkbox" name="open_days[]" value="{{ $day }}" @checked(in_array($day, $branchOpenDays, true))>
                    {{ $dayLabel }}
                </label>
            @endforeach
        </div>
        <p class="mt-1 text-xs text-slate-500">Để trống nếu muốn cơ sở dùng ngày mở cửa trong setting tổng thể.</p>
        @error('open_days') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="reservation_time_slots">Khung giờ nhận đặt bàn</label>
        <input id="reservation_time_slots" name="reservation_time_slots" value="{{ old('reservation_time_slots', $branch->reservation_time_slots) }}" class="admin-input" placeholder="09:00-14:00,16:00-21:00">
        <p class="mt-1 text-xs text-slate-500">Có thể nhập nhiều khung, cách nhau bằng dấu phẩy.</p>
        @error('reservation_time_slots') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="reservation_last_booking_time">Giờ cuối nhận đặt</label>
        <input id="reservation_last_booking_time" type="time" name="reservation_last_booking_time" value="{{ old('reservation_last_booking_time', $branch->reservation_last_booking_time) }}" class="admin-input">
        @error('reservation_last_booking_time') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="reservation_last_order_buffer_minutes">Ngừng nhận trước giờ đóng bếp</label>
        <input id="reservation_last_order_buffer_minutes" type="number" min="0" max="240" name="reservation_last_order_buffer_minutes" value="{{ old('reservation_last_order_buffer_minutes', $branch->reservation_last_order_buffer_minutes) }}" class="admin-input">
        @error('reservation_last_order_buffer_minutes') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="lg:col-span-2">
        <label class="admin-label" for="description">Mô tả ngắn</label>
        <textarea id="description" name="description" rows="4" class="admin-input">{{ old('description', $branch->description) }}</textarea>
        @error('description') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <section class="lg:col-span-2 rounded-2xl border border-emerald-100 bg-emerald-50/50 p-5">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-base font-black text-slate-950">Bán online & giao hàng</h3>
                <p class="mt-1 text-sm text-slate-600">Cấu hình riêng cho từng cơ sở: nhận đơn, ngưỡng tối thiểu, giới hạn km và bảng phí ship.</p>
            </div>
            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold uppercase tracking-widest text-emerald-800 ring-1 ring-emerald-100">Theo cơ sở</span>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <label class="flex items-center gap-2 rounded-xl bg-white p-3 text-sm font-semibold text-slate-700 ring-1 ring-emerald-100">
                <input type="checkbox" name="accepts_online_orders" value="1" @checked(old('accepts_online_orders', $branch->accepts_online_orders ?? true))>
                Nhận đơn online
            </label>
            <label class="flex items-center gap-2 rounded-xl bg-white p-3 text-sm font-semibold text-slate-700 ring-1 ring-emerald-100">
                <input type="checkbox" name="accepts_pickup_orders" value="1" @checked(old('accepts_pickup_orders', $branch->accepts_pickup_orders ?? true))>
                Cho tự đến lấy
            </label>
            <label class="flex items-center gap-2 rounded-xl bg-white p-3 text-sm font-semibold text-slate-700 ring-1 ring-emerald-100">
                <input type="checkbox" name="accepts_delivery_orders" value="1" @checked(old('accepts_delivery_orders', $branch->accepts_delivery_orders ?? true))>
                Cho giao hàng
            </label>
        </div>

        <div class="mt-4 rounded-2xl border border-emerald-200 bg-white p-4">
            <label class="flex items-start gap-3">
                <input type="checkbox" name="auto_delivery_quote_enabled" value="1" class="mt-1" @checked(old('auto_delivery_quote_enabled', $branch->auto_delivery_quote_enabled ?? false))>
                <span>
                    <span class="block text-sm font-black text-slate-950">Tự động tính phí ship theo địa chỉ</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                        Bật để hệ thống dùng tọa độ cơ sở và địa chỉ khách để tính km rồi áp bảng phí. Tắt để phí ship là 0 EUR trên đơn online, shipper/admin tự xác nhận và thu trực tiếp.
                    </span>
                </span>
            </label>
            @error('auto_delivery_quote_enabled') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-4 rounded-2xl border border-emerald-200 bg-white p-4">
            <label class="flex items-start gap-3">
                <input type="checkbox" name="accepts_offline_payment" value="1" class="mt-1" @checked(old('accepts_offline_payment', $branch->accepts_offline_payment ?? true))>
                <span>
                    <span class="block text-sm font-black text-slate-950">Cho thanh toán tại quán</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">
                        Bật để khách có thể chọn thanh toán bằng tiền mặt hoặc chuyển khoản trực tiếp khi nhận hàng. Tắt để chỉ cho thanh toán online qua Viva.
                    </span>
                </span>
            </label>
            @error('accepts_offline_payment') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-4">
            <label class="admin-label" for="order_notification_email">Email nhận thông báo đơn hàng</label>
            <input id="order_notification_email" name="order_notification_email" type="email" value="{{ old('order_notification_email', $branch->order_notification_email ?? '') }}" class="admin-input" placeholder="admin@paprika.com">
            <p class="mt-1 text-xs text-slate-500">Email nhận email thông báo khi có đơn mới cho cơ sở này. Để trống sẽ dùng email mặc định ở Cài đặt.</p>
            @error('order_notification_email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            <div>
                <label class="admin-label" for="delivery_min_order_amount">Đơn tối thiểu để giao (€)</label>
                <input id="delivery_min_order_amount" name="delivery_min_order_amount" type="number" min="0" step="0.01" value="{{ old('delivery_min_order_amount') ?? $moneyInput($branch->delivery_min_order_amount ?? 0) }}" class="admin-input">
                @error('delivery_min_order_amount') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="delivery_free_order_amount">Miễn ship từ (€)</label>
                <input id="delivery_free_order_amount" name="delivery_free_order_amount" type="number" min="0" step="0.01" value="{{ old('delivery_free_order_amount') ?? ($branch->delivery_free_order_amount !== null ? $moneyInput($branch->delivery_free_order_amount) : '') }}" class="admin-input" placeholder="Để trống nếu không dùng">
                @error('delivery_free_order_amount') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="delivery_max_distance_km">Khoảng cách tối đa (km)</label>
                <input id="delivery_max_distance_km" name="delivery_max_distance_km" type="number" min="0" step="0.1" value="{{ old('delivery_max_distance_km', $branch->delivery_max_distance_km) }}" class="admin-input">
                @error('delivery_max_distance_km') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="delivery_origin_latitude">Vĩ độ xuất phát</label>
                <input id="delivery_origin_latitude" name="delivery_origin_latitude" type="number" step="0.0000001" value="{{ old('delivery_origin_latitude', $branch->delivery_origin_latitude) }}" class="admin-input" placeholder="Tuỳ chọn">
                @error('delivery_origin_latitude') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="delivery_origin_longitude">Kinh độ xuất phát</label>
                <input id="delivery_origin_longitude" name="delivery_origin_longitude" type="number" step="0.0000001" value="{{ old('delivery_origin_longitude', $branch->delivery_origin_longitude) }}" class="admin-input" placeholder="Tuỳ chọn">
                @error('delivery_origin_longitude') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="admin-label" for="delivery_note">Ghi chú ship</label>
                <input id="delivery_note" name="delivery_note" value="{{ old('delivery_note', $branch->delivery_note) }}" class="admin-input" placeholder="VD: Ship trong nội thành Patras">
                @error('delivery_note') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h4 class="text-sm font-black uppercase tracking-widest text-emerald-900">Bảng phí theo km</h4>
                <p class="text-xs text-slate-500">Dòng có “đến km” trống sẽ là mốc cuối.</p>
            </div>
            <div class="space-y-3">
                @foreach ($deliveryZones as $index => $zone)
                    <div class="grid gap-3 rounded-2xl bg-white p-4 ring-1 ring-emerald-100 lg:grid-cols-[1.2fr_0.8fr_0.8fr_0.8fr_0.55fr_0.55fr]">
                        <input type="hidden" name="delivery_zones[{{ $index }}][id]" value="{{ $zone['id'] ?? '' }}">
                        <div>
                            <label class="admin-label">Tên mốc</label>
                            <input name="delivery_zones[{{ $index }}][label]" value="{{ $zone['label'] ?? '' }}" class="admin-input" placeholder="VD: Dưới 1km">
                        </div>
                        <div>
                            <label class="admin-label">Từ km</label>
                            <input name="delivery_zones[{{ $index }}][min_distance_km]" type="number" min="0" step="0.1" value="{{ $zone['min_distance_km'] ?? '' }}" class="admin-input">
                        </div>
                        <div>
                            <label class="admin-label">Đến km</label>
                            <input name="delivery_zones[{{ $index }}][max_distance_km]" type="number" min="0" step="0.1" value="{{ $zone['max_distance_km'] ?? '' }}" class="admin-input" placeholder="Trống">
                        </div>
                        <div>
                            <label class="admin-label">Phí (€)</label>
                            <input name="delivery_zones[{{ $index }}][fee]" type="number" min="0" step="0.01" value="{{ $zone['fee'] ?? '0.00' }}" class="admin-input">
                        </div>
                        <div>
                            <label class="admin-label">Thứ tự</label>
                            <input name="delivery_zones[{{ $index }}][sort_order]" type="number" min="0" value="{{ $zone['sort_order'] ?? $index }}" class="admin-input">
                        </div>
                        <div class="flex flex-col justify-end gap-2">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="delivery_zones[{{ $index }}][is_active]" value="1" @checked($zone['is_active'] ?? true)>
                                Bật
                            </label>
                            @if (! empty($zone['id']))
                                <label class="flex items-center gap-2 text-sm font-semibold text-rose-700">
                                    <input type="checkbox" name="delivery_zones[{{ $index }}][delete]" value="1">
                                    Xoá
                                </label>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @error('delivery_zones') <p class="form-error">{{ $message }}</p> @enderror
        </div>
    </section>
    <div class="lg:col-span-2">
        <label class="admin-label" for="google_map_iframe">Google Map iframe</label>
        <textarea id="google_map_iframe" name="google_map_iframe" rows="5" class="admin-input">{{ old('google_map_iframe', $branch->google_map_iframe) }}</textarea>
        @error('google_map_iframe') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="facebook_url">Facebook URL</label>
        <input id="facebook_url" name="facebook_url" value="{{ old('facebook_url', $branch->facebook_url) }}" class="admin-input">
        @error('facebook_url') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="zalo_url">Zalo URL</label>
        <input id="zalo_url" name="zalo_url" value="{{ old('zalo_url', $branch->zalo_url) }}" class="admin-input">
        @error('zalo_url') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div class="lg:col-span-2">
        <label class="admin-label" for="image">Ảnh đại diện cơ sở</label>
        @if ($branch->image)
            <img src="{{ media_variant_url($branch->image, 'card') }}" alt="{{ $branch->name }}" class="mb-3 h-52 w-full rounded-xl object-cover">
        @endif
        <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
        @error('image') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="meta_title">Meta title</label>
        <input id="meta_title" name="meta_title" value="{{ old('meta_title', $branch->meta_title) }}" class="admin-input">
        @error('meta_title') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="admin-label" for="meta_description">Meta description</label>
        <input id="meta_description" name="meta_description" value="{{ old('meta_description', $branch->meta_description) }}" class="admin-input">
        @error('meta_description') <p class="form-error">{{ $message }}</p> @enderror
    </div>
    <label class="flex items-center gap-2 font-semibold text-slate-700">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active ?? true))>
        Hiển thị cơ sở
    </label>
</div>

<div class="mt-6 flex flex-wrap gap-3">
    <button type="submit" class="admin-btn-primary">{{ $branch->exists ? 'Cập nhật' : 'Lưu' }}</button>
    <a href="{{ route('admin.branches.index') }}" class="admin-btn-secondary">Quay lại</a>
</div>
