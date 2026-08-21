@php
    $brandParts = preg_split('/\s+/', trim($brandName), 2);
    $brandPrimary = $brandParts[0] ?? $brandName;
    $brandSecondary = $brandParts[1] ?? '';
    $logoHeader = setting('logo_header', '/paprika/logo-hs.webp');
    $brandWordmark = setting('brand_wordmark', '/paprika/wordmark.webp');
@endphp

<header class="sticky top-0 z-40 bg-[#064E3B] text-white shadow-lg border-b border-[#043427]" id="site-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <a href="{{ localized_route('home') }}" class="flex min-w-0 items-center gap-2 sm:gap-3 cursor-pointer group" aria-label="{{ $brandName }}">
                @if ($logoHeader)
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white p-1 shadow-md ring-1 ring-white/30 transition group-hover:scale-105">
                        <img src="{{ media_url($logoHeader) }}" alt="HS logo" class="h-full w-full rounded-full object-contain" width="48" height="48" loading="eager">
                    </span>
                @endif

                @if ($brandWordmark)
                    <span class="flex min-w-0 flex-col">
                        <img src="{{ media_url($brandWordmark) }}" alt="Paprika" class="h-8 w-auto max-w-[138px] object-contain object-left sm:h-9 sm:max-w-[180px] lg:max-w-[210px]" width="608" height="212" loading="eager">
                        <span class="hidden sm:block text-[9px] font-black uppercase tracking-[0.22em] text-white/65">{{ __('site.header.brand_tagline') }}</span>
                    </span>
                @else
                    <div class="text-xl sm:text-2xl font-black italic tracking-tighter flex items-center gap-1.5 font-heading">
                        <span class="bg-[#B91C1C] text-white px-2.5 py-1 rounded inline-block transition group-hover:scale-105 shadow-md">{{ $brandPrimary }}</span>
                        @if ($brandSecondary)
                            <span class="text-white">{{ $brandSecondary }}</span>
                        @endif
                    </div>
                @endif
            </a>

            <nav class="hidden md:flex items-center space-x-6 text-sm font-semibold uppercase tracking-wider opacity-90" aria-label="{{ __('site.header.nav_aria') }}">
                @foreach ($headerMenus as $item)
                    @php($isActive = \App\Support\StorefrontNavigation::isActive($item))
                    <a
                        href="{{ $item['url'] }}"
                        @if ($item['open_new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif
                        @class([
                            'py-1 text-xs uppercase tracking-widest font-bold border-b-2 transition-all',
                            'px-3 flex items-center gap-1.5 rounded-full bg-[#B91C1C]/80 hover:bg-[#B91C1C] hover:border-transparent' => $item['featured'] ?? false,
                            'border-[#B91C1C] text-white' => $isActive && empty($item['featured']),
                            'border-white text-white bg-[#B91C1C]' => $isActive && !empty($item['featured']),
                            'border-transparent text-white/80 hover:text-white hover:border-white/50' => ! $isActive && empty($item['featured']),
                            'border-transparent text-white/90 hover:text-white' => ! $isActive && !empty($item['featured']),
                        ])
                    >
                        @if ($item['featured'] ?? false)
                            @include('storefront.partials.icon', ['name' => 'check', 'class' => 'w-3.5 h-3.5'])
                        @endif
                        {{ $item['title'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                @include('storefront.partials.locale-switcher', ['variant' => 'desktop'])

                @if (($cartBranches ?? collect())->count() > 1)
                    <form method="POST" action="{{ localized_route('branch.set') }}" class="hidden sm:block">
                        @csrf
                        <input type="hidden" name="redirect" value="{{ url()->current() }}">
                        <select name="branch_id" class="h-10 rounded-full bg-white/10 border border-white/20 px-3 text-[10px] font-bold uppercase tracking-widest text-white" onchange="this.form.submit()">
                            @foreach (($cartBranches ?? collect())->sortBy('sort_order') as $branch)
                                <option value="{{ $branch->id }}" @selected((string) ($activeBranch?->id) === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <a href="{{ localized_route('reservations.create') }}" class="flex items-center gap-1.5 px-2.5 py-2 sm:px-4 sm:py-2.5 bg-white/15 hover:bg-white/25 rounded-full transition border border-white/20" title="{{ __('site.header.booking') }}">
                    @include('storefront.partials.icon', ['name' => 'calendar', 'class' => 'w-5 h-5 text-white'])
                    <span class="hidden sm:inline text-[10px] font-bold uppercase tracking-widest">{{ __('site.header.booking') }}</span>
                </a>

                <button type="button" data-open-cart class="relative flex items-center gap-2 px-3 py-2.5 sm:px-4 bg-[#B91C1C] hover:bg-[#991B1B] rounded-full shadow-md transition group border border-transparent" aria-label="{{ __('site.header.cart_open_aria') }}">
                    @include('storefront.partials.icon', ['name' => 'cart', 'class' => 'w-4 h-4 text-white'])
                    <span class="hidden sm:inline text-xs uppercase tracking-widest font-extrabold text-white">{{ __('site.header.cart') }}</span>
                    <span data-cart-count-badge class="absolute -top-1.5 -right-1.5 bg-white text-[#B91C1C] text-[10px] font-black h-5 min-w-5 px-1 items-center justify-center rounded-full border border-[#B91C1C] shadow-lg {{ $cartCount > 0 ? 'flex' : 'hidden' }}">{{ $cartCount }}</span>
                </button>

                <button type="button" data-mobile-menu-toggle class="md:hidden p-2 rounded-full text-white hover:bg-white/10" aria-label="{{ __('site.header.menu_open_aria') }}" aria-expanded="false">
                    @include('storefront.partials.icon', ['name' => 'menu', 'class' => 'w-6 h-6'])
                </button>
            </div>
        </div>
    </div>

    <div data-mobile-menu hidden class="md:hidden bg-[#043427] border-t border-white/10 py-4 px-4 space-y-3 animate-fadeIn">
        <div class="flex flex-col gap-1 px-1">
            @foreach ($headerMenus as $item)
                @php($isActive = \App\Support\StorefrontNavigation::isActive($item))
                <a
                    href="{{ $item['url'] }}"
                    @if ($item['open_new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif
                    @class([
                        'w-full text-left px-4 py-3 rounded-xl font-bold uppercase tracking-widest text-xs transition',
                        'flex items-center gap-2 bg-[#B91C1C]/80 hover:bg-[#B91C1C]' => $item['featured'] ?? false,
                        'bg-[#B91C1C] text-white border-l-4 border-white pl-3' => $isActive,
                        'text-white/80 hover:bg-white/10 hover:text-white' => ! $isActive && empty($item['featured']),
                        'text-white/90 hover:text-white' => ! $isActive && !empty($item['featured']),
                    ])
                >
                    @if ($item['featured'] ?? false)
                        @include('storefront.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4'])
                    @endif
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>
        <hr class="border-white/10">
    </div>
</header>
