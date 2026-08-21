<header
    class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur"
    data-admin-notifications-url="{{ route('admin.notifications.index') }}"
>
    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3">
            <button type="button" data-admin-sidebar-toggle class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold lg:hidden">
                Menu
            </button>
            <div>
                <p class="text-sm text-slate-500">Quản trị nội dung</p>
                <h1 class="text-lg font-bold text-slate-950">@yield('title', 'Admin')</h1>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <button type="button" class="admin-bell" data-admin-bell aria-expanded="false" aria-label="Thông báo admin">
                    <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
                        <path d="M12 22a2.6 2.6 0 0 0 2.45-1.75h-4.9A2.6 2.6 0 0 0 12 22Zm7-6.5V11a7 7 0 0 0-5-6.7V3a2 2 0 1 0-4 0v1.3A7 7 0 0 0 5 11v4.5L3.5 17v1h17v-1L19 15.5Z" />
                    </svg>
                    <span class="admin-bell-count hidden" data-admin-notification-count>0</span>
                </button>

                <div class="admin-notification-panel hidden" data-admin-notification-panel>
                    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                        <p class="font-bold text-slate-950">Thông báo mới</p>
                        <span class="text-xs font-semibold text-slate-500" data-admin-notification-summary>Đang tải</span>
                    </div>
                    <div class="max-h-96 overflow-y-auto" data-admin-notification-list>
                        <p class="px-4 py-5 text-sm text-slate-500">Đang tải thông báo...</p>
                    </div>
                </div>
            </div>

            <a href="{{ route('home') }}" target="_blank" class="hidden rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:inline-flex">
                Xem website
            </a>
            <span class="hidden text-sm font-semibold text-slate-700 md:inline">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Đăng xuất</button>
            </form>
        </div>
    </div>
</header>
