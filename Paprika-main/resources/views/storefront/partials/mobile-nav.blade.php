@php
    $routeName = request()->route()?->getName() ?? '';
    $isHome = str_ends_with($routeName, 'home');
    $isMenu = str_ends_with($routeName, 'menu.index');
    $isBooking = str_ends_with($routeName, 'reservations.create');
    $isCart = str_ends_with($routeName, 'cart.index');
@endphp

<nav class="fixed bottom-0 left-0 right-0 z-40 bg-[#064E3B] border-t border-[#043427] sm:hidden shadow-lg" id="mobile-sticky-bottom-navigation" aria-label="Mobile">
    <div class="grid grid-cols-4 gap-1 py-1.5 px-2">
        <a href="{{ localized_route('home') }}" class="flex flex-col items-center justify-center p-2 rounded-xl transition {{ $isHome ? 'text-[#FFD700] bg-[#043427]' : 'text-stone-300' }}">
            @include('storefront.partials.icon', ['name' => 'home', 'class' => 'w-5 h-5'])
            <span class="text-[10px] uppercase font-bold tracking-wider mt-1 block">{{ __('site.mobile_nav.home') }}</span>
        </a>
        <a href="{{ localized_route('menu.index') }}" class="flex flex-col items-center justify-center p-2 rounded-xl transition {{ $isMenu ? 'text-[#FFD700] bg-[#043427]' : 'text-stone-300' }}">
            @include('storefront.partials.icon', ['name' => 'compass', 'class' => 'w-5 h-5'])
            <span class="text-[10px] uppercase font-bold tracking-wider mt-1 block">{{ __('site.mobile_nav.menu') }}</span>
        </a>
        <a href="{{ localized_route('reservations.create') }}" class="flex flex-col items-center justify-center p-2 rounded-xl transition {{ $isBooking ? 'text-[#FFD700] bg-[#043427]' : 'text-stone-300' }}">
            @include('storefront.partials.icon', ['name' => 'calendar', 'class' => 'w-5 h-5'])
            <span class="text-[10px] uppercase font-bold tracking-wider mt-1 block">{{ __('site.mobile_nav.booking') }}</span>
        </a>
        <button type="button" data-open-cart class="flex flex-col items-center justify-center p-2 rounded-xl relative transition {{ $isCart ? 'text-[#FFD700] bg-[#043427]' : 'text-stone-300' }}">
            <div class="relative">
                @include('storefront.partials.icon', ['name' => 'cart', 'class' => 'w-5 h-5'])
                <span data-cart-count-badge class="absolute -top-1.5 -right-2 bg-[#B91C1C] text-white text-[9px] font-bold h-4 min-w-4 px-1 items-center justify-center rounded-full border border-[#064E3B] {{ $cartCount > 0 ? 'flex' : 'hidden' }}">{{ $cartCount }}</span>
            </div>
            <span class="text-[10px] uppercase font-bold tracking-wider mt-1 block">{{ __('site.mobile_nav.cart') }}</span>
        </button>
    </div>
</nav>

@unless($hideFloatingContact ?? false)
    <div class="fixed bottom-20 sm:bottom-6 left-6 z-40" id="floating-contact-panel">
        <a href="tel:{{ $phoneHref }}" class="p-4 rounded-full shadow-2xl transition duration-300 flex items-center justify-center border hover:scale-110 bg-[#064E3B] text-white border-[#043427] hover:bg-[#B91C1C]" title="{{ __('site.mobile_nav.call_aria') }} {{ $brandName }}" aria-label="{{ __('site.mobile_nav.call_aria') }} {{ $brandName }}">
            @include('storefront.partials.icon', ['name' => 'phone', 'class' => 'w-5 h-5'])
        </a>
    </div>
@endunless
