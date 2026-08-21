<!doctype html>
<html lang="{{ current_locale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('storefront.partials.js-translations')
    @include('partials.seo')
    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ asset('storefront/template.css') }}">
    <link rel="preload" href="{{ asset('storefront/chat-widget.css') }}?v={{ filemtime(public_path('storefront/chat-widget.css')) }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('storefront/chat-widget.css') }}?v={{ filemtime(public_path('storefront/chat-widget.css')) }}"></noscript>
</head>
<body class="storefront min-h-screen bg-[#FDFBF7] text-stone-900 font-sans flex flex-col selection:bg-[#B91C1C]/15">
    @php
        $cartService = app(\App\Services\CartService::class);
        $cartItems = $cartService->items();
        $cartCount = $cartService->count();
        $cartSubtotal = $cartService->subtotal();
        $brandName = localized_setting('restaurant_name', 'Paprika');
        $primaryBranch = primary_branch();
        $hotline = $primaryBranch?->hotline ?: $primaryBranch?->phone ?: setting('hotline', setting('phone', '0947 361 515'));
        $phoneHref = preg_replace('/\D+/', '', $hotline);
        $cartBranches = \App\Models\Branch::active()->get();
        $activeBranch = active_branch();
        $routeName = request()->route()?->getName() ?? '';
        $hideFloatingUtilities = str_ends_with($routeName, 'checkout.create') || str_ends_with($routeName, 'checkout.success');
        $loaderMessage = match (current_locale()) {
            'en' => 'Preparing your next page...',
            'el' => 'Ετοιμάζουμε την επόμενη σελίδα...',
            default => 'Đang chuẩn bị trang tiếp theo...',
        };
    @endphp

    <div class="page-transition-loader" data-page-loader hidden aria-live="polite" aria-busy="true">
        <div class="page-transition-loader__glow" aria-hidden="true"></div>
        <div class="page-transition-loader__card">
            <div class="page-transition-loader__logo-wrap">
                <div class="page-transition-loader__orbit" aria-hidden="true"></div>
                <img src="{{ media_url(setting('logo_header', '/paprika/logo-hs.webp')) }}" alt="" class="page-transition-loader__logo" width="96" height="96">
            </div>
            <div class="page-transition-loader__brand">
                <strong>{{ $brandName }}</strong>
                <span>{{ $loaderMessage }}</span>
            </div>
            <div class="page-transition-loader__bar" aria-hidden="true">
                <span></span>
            </div>
        </div>
    </div>

    @include('storefront.partials.header')
    @unless($hideFloatingUtilities)
        @include('storefront.partials.pending-viva-payment')
    @endunless

    @if (session('success') || session('error') || session('info') || session('warning'))
        <div
            class="fixed inset-x-0 top-4 z-55 mx-auto w-[min(28rem,calc(100vw-2rem))]"
            data-toast-stack
        >
            <div class="bg-white rounded-2xl border border-stone-200 p-4 shadow-2xl flex items-start gap-3 animate-slideIn" role="alert" data-flash-toast>
                <div class="p-2 rounded-xl shrink-0 {{ session('error') ? 'bg-rose-50 text-rose-800' : (session('warning') ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200') }}">
                    @include('storefront.partials.icon', ['name' => session('error') ? 'flame' : (session('warning') ? 'bell' : 'check'), 'class' => 'w-5 h-5'])
                </div>
                <div class="flex-grow space-y-0.5">
                    <span class="block text-xs font-black text-stone-900 uppercase">{{ session('error') ? 'Notice' : (session('warning') ? 'Warning' : 'Success') }}</span>
                    <p class="text-stone-500 text-xs sm:text-sm leading-snug">{{ session('success') ?: session('error') ?: session('warning') ?: session('info') }}</p>
                </div>
                <button type="button" data-dismiss-toast class="p-1 hover:bg-stone-50 rounded text-stone-400" aria-label="Dismiss">@include('storefront.partials.icon', ['name' => 'x', 'class' => 'w-4 h-4'])</button>
            </div>
        </div>
    @endif

    <main id="main-content" class="flex-grow pb-20 sm:pb-0">
        @yield('content')
    </main>

    @include('storefront.partials.footer')
    @include('storefront.partials.cart-drawer', ['branches' => $cartBranches])
    @unless($hideFloatingUtilities)
        @include('storefront.partials.chat-widget')
    @endunless
    @include('storefront.partials.mobile-nav', ['hideFloatingContact' => $hideFloatingUtilities])
    @if (($globalPopupPromotion ?? null) && str_ends_with(Route::currentRouteName() ?? '', 'home'))
        @include('storefront.partials.promo-popup', ['promotion' => $globalPopupPromotion])
    @endif

    <script src="{{ asset('storefront/storefront.js') }}?v={{ filemtime(public_path('storefront/storefront.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
