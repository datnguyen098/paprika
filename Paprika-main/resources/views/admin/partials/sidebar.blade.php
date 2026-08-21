@php
    $authUser = auth()->user();
    $initials = collect(explode(' ', trim($authUser?->name ?? 'Paprika')))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');

    $sidebarCounts = [
        'orders' => \App\Support\BranchAccess::apply(\App\Models\Order::query(), $authUser)->whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
        'new_paid_orders' => \App\Support\BranchAccess::apply(\App\Models\Order::query(), $authUser)->where('status', 'pending')->where('payment_status', 'paid')->count(),
        'reservations' => \App\Support\BranchAccess::apply(\App\Models\Reservation::query(), $authUser)->where('status', 'pending')->count(),
        'contacts' => \App\Support\BranchAccess::apply(\App\Models\Contact::query(), $authUser)->where('status', 'new')->count(),
        'chat' => \App\Support\BranchAccess::applyViaRelation(\App\Models\ChatMessage::query(), $authUser, 'chatSession')->where('sender', 'visitor')->where('is_read', false)->count(),
    ];

    $items = [
        ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'permission' => 'dashboard.view'],
        ['label' => 'Cài đặt website', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'permission' => 'settings.view'],
        ['label' => 'Logo & nhận diện', 'route' => 'admin.identity.edit', 'active' => 'admin.identity.*', 'permission' => 'identity.view'],
        ['label' => 'Cơ sở', 'route' => 'admin.branches.index', 'active' => 'admin.branches.*', 'permission' => 'branches.view'],
        ['label' => 'Bàn ăn', 'route' => 'admin.restaurant-tables.index', 'active' => 'admin.restaurant-tables.*', 'permission' => 'restaurant_tables.view'],
        ['label' => 'Banner trang chủ', 'route' => 'admin.banners.index', 'active' => 'admin.banners.*', 'permission' => 'banners.view'],
        ['label' => 'Danh mục', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'permission' => 'categories.view'],
        ['label' => 'Món ăn / Sản phẩm', 'route' => 'admin.dishes.index', 'active' => 'admin.dishes.*', 'permission' => 'dishes.view'],
        ['label' => 'Khung giờ món', 'route' => 'admin.dish-time-slots.index', 'active' => 'admin.dish-time-slots.*', 'permission' => 'dishes.update'],
        ['label' => 'Cấu hình biến thể', 'route' => 'admin.dish-option-settings.edit', 'active' => 'admin.dish-option-settings.*', 'permission' => 'dishes.update'],
        ['label' => 'Đơn hàng', 'route' => 'admin.orders.index', 'active' => 'admin.orders.*', 'badge' => 'orders', 'permission' => 'orders.view'],
        ['label' => 'Voucher', 'route' => 'admin.vouchers.index', 'active' => 'admin.vouchers.*', 'permission' => 'vouchers.view'],
        ['label' => 'Bếp', 'route' => 'admin.kitchen.index', 'active' => 'admin.kitchen.*', 'permission' => 'kitchen.view'],
        ['label' => 'Bài viết', 'route' => 'admin.posts.index', 'active' => 'admin.posts.*', 'permission' => 'posts.view'],
        ['label' => 'Review khách hàng', 'route' => 'admin.testimonials.index', 'active' => 'admin.testimonials.*', 'permission' => 'testimonials.view'],
        ['label' => 'Khuyến mãi / Popup', 'route' => 'admin.promotions.index', 'active' => 'admin.promotions.*', 'permission' => 'promotions.view'],
        ['label' => 'Không gian quán', 'route' => 'admin.gallery.index', 'active' => 'admin.gallery.*', 'permission' => 'gallery.view'],
        ['label' => 'Trang tĩnh', 'route' => 'admin.pages.index', 'active' => 'admin.pages.*', 'permission' => 'pages.view'],
        ['label' => 'Menu điều hướng', 'route' => 'admin.menus.index', 'active' => 'admin.menus.*', 'permission' => 'menus.view'],
        ['label' => 'Đặt bàn', 'route' => 'admin.reservations.index', 'active' => 'admin.reservations.*', 'badge' => 'reservations', 'permission' => 'reservations.view'],
        ['label' => 'Liên hệ', 'route' => 'admin.contacts.index', 'active' => 'admin.contacts.*', 'badge' => 'contacts', 'permission' => 'contacts.view'],
        ['label' => 'Chat online', 'route' => 'admin.chats.index', 'active' => 'admin.chats.*', 'badge' => 'chat', 'permission' => 'chats.view'],
        ['label' => 'SEO', 'route' => 'admin.seo.edit', 'active' => 'admin.seo.*', 'permission' => 'seo.view'],
        ['label' => 'Dịch tự động', 'route' => 'admin.translations.settings', 'active' => 'admin.translations.*', 'permission' => 'translations.view'],
        ['label' => 'Tài khoản admin', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'permission' => 'users.view'],
        ['label' => 'Phân quyền', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'permission' => 'roles.view'],
    ];
@endphp

<aside data-admin-sidebar class="hidden w-full shrink-0 border-r border-slate-200 bg-white lg:block lg:min-h-screen lg:w-72">
    <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-5">
        @if ($authUser?->avatar)
            <img src="{{ media_url($authUser->avatar) }}" alt="{{ $authUser->name }}" class="h-10 w-10 rounded-xl object-cover">
        @else
            <div class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-700 font-bold text-white">{{ $initials ?: 'P' }}</div>
        @endif
        <div>
            <p class="font-bold text-emerald-950">Paprika</p>
            <p class="text-xs text-slate-500">Admin Panel</p>
        </div>
    </div>

    <nav class="space-y-1 p-4">
        @foreach ($items as $item)
            @php
                if (! $authUser?->hasPermission($item['permission'])) {
                    continue;
                }

                $count = !empty($item['badge']) ? ($sidebarCounts[$item['badge']] ?? 0) : 0;
            @endphp
            <a
                href="{{ route($item['route']) }}"
                @class([
                    'flex items-center justify-between gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition',
                    'bg-emerald-700 text-white shadow-sm' => request()->routeIs($item['active']),
                    'text-slate-600 hover:bg-emerald-50 hover:text-emerald-800' => ! request()->routeIs($item['active']),
                ])
            >
                <span>{{ $item['label'] }}</span>
                @if (!empty($item['badge']))
                    <span
                        data-admin-badge="{{ $item['badge'] }}"
                        @class(['admin-sidebar-badge', 'hidden' => $count < 1])
                    >
                        {{ $count }}
                    </span>
                @endif
            </a>
        @endforeach
    </nav>
</aside>
