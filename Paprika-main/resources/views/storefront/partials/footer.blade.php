@php
    $footerLogo = setting('logo_footer', setting('logo_header', '/paprika/logo-hs.webp'));
    $footerWordmark = setting('brand_wordmark', '/paprika/wordmark.webp');
    $branchOpeningHours = $primaryBranch?->opening_hours ?: __('site.footer_block.opening_default');
    $branchAddress = $primaryBranch?->address ?: '';
    $footerPhones = collect([
        $primaryBranch?->hotline,
        $primaryBranch?->phone,
        setting('hotline'),
        setting('phone'),
        $hotline ?? null,
    ])
        ->filter(fn ($phone) => filled($phone))
        ->unique(fn ($phone) => preg_replace('/\D+/', '', (string) $phone))
        ->values();
@endphp

<footer class="bg-[#032219] text-[#EFECE6] border-t-8 border-[#B91C1C]" id="app-footer">
    <div class="bg-[#042C21] py-6 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-[#B91C1C]/10 rounded-lg text-[#B91C1C]">
                    @include('storefront.partials.icon', ['name' => 'phone', 'class' => 'w-5 h-5 text-[#B91C1C]'])
                </div>
                <div>
                    <span class="block text-xs uppercase tracking-wider text-[#A2C7B4] font-medium">{{ __('site.footer_block.hotline_label') }}</span>
                    <span class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        @foreach ($footerPhones as $phone)
                            @php $phoneLinkHref = preg_replace('/\D+/', '', (string) $phone); @endphp
                            <a href="tel:{{ $phoneLinkHref }}" class="text-lg font-bold text-white font-mono hover:text-[#FFD700]">{{ $phone }}</a>
                            @unless ($loop->last)
                                <span class="text-lg font-bold text-[#A2C7B4]/60" aria-hidden="true">-</span>
                            @endunless
                        @endforeach
                    </span>
                </div>
            </div>
            <p class="text-sm text-[#A2C7B4] text-center md:text-right max-w-md font-sans">
                {{ __('site.footer_block.tagline') }}
            </p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    @if ($footerLogo)
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white p-1 shadow-md ring-1 ring-white/20">
                            <img src="{{ media_url($footerLogo) }}" alt="HS logo" class="h-full w-full rounded-full object-contain" width="48" height="48" loading="lazy">
                        </span>
                    @endif
                    @if ($footerWordmark)
                        <img src="{{ media_url($footerWordmark) }}" alt="{{ $brandName }}" class="h-9 w-auto max-w-[170px] object-contain object-left" width="608" height="212" loading="lazy">
                    @else
                        <span class="font-extrabold text-lg tracking-tight font-sans italic uppercase">{{ $brandName }}</span>
                    @endif
                </div>
                <p class="text-sm text-[#B5CFB7] leading-relaxed">
                    {{ __('site.footer_block.description') }}
                </p>
            </div>

            <div>
                <h3 class="text-white text-xs uppercase tracking-widest font-black mb-5 border-b border-white/10 pb-2">{{ __('site.footer_block.explore') }}</h3>
                <ul class="space-y-3 text-xs text-[#B5CFB7] uppercase tracking-wider font-extrabold">
                    @foreach ($footerMenus as $item)
                        <li>
                            <a
                                href="{{ $item['url'] }}"
                                @if ($item['open_new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif
                                class="hover:text-white transition flex items-center gap-1.5 {{ ($item['featured'] ?? false) ? 'font-black text-[#B91C1C]' : '' }}"
                            >
                                @include('storefront.partials.icon', ['name' => ($item['featured'] ?? false) ? 'check' : 'arrow-right', 'class' => 'w-3.5 h-3.5 text-[#B91C1C]'])
                                {{ $item['title'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="space-y-4">
                <h3 class="text-white text-xs uppercase tracking-widest font-black mb-5 border-b border-white/10 pb-2 font-heading">{{ __('site.footer_block.service') }}</h3>
                <div class="flex gap-3 text-xs text-[#B5CFB7]">
                    @include('storefront.partials.icon', ['name' => 'clock', 'class' => 'w-5 h-5 text-[#FFD700] shrink-0 mt-0.5'])
                    <div>
                        <span class="block font-semibold text-white uppercase tracking-wider mb-0.5">{{ __('site.footer_block.opening_hours') }}</span>
                        <span class="block">{{ $branchOpeningHours }}</span>
                    </div>
                </div>
                <div class="flex gap-3 text-xs text-[#B5CFB7]">
                    @include('storefront.partials.icon', ['name' => 'map-pin', 'class' => 'w-5 h-5 text-[#FFD700] shrink-0 mt-0.5'])
                    <div>
                        <span class="block font-semibold text-white uppercase tracking-wider mb-0.5">{{ __('site.footer_block.address') }}</span>
                        <span class="block">{{ $branchAddress }}</span>
                    </div>
                </div>
                <div class="flex gap-3 text-xs text-[#B5CFB7]">
                    @include('storefront.partials.icon', ['name' => 'phone', 'class' => 'w-5 h-5 text-[#FFD700] shrink-0 mt-0.5'])
                    <div>
                        <span class="block font-semibold text-white uppercase tracking-wider mb-0.5">{{ __('site.footer_block.phone') }}</span>
                        @foreach ($footerPhones as $phone)
                            @php $phoneLinkHref = preg_replace('/\D+/', '', (string) $phone); @endphp
                            <a href="tel:{{ $phoneLinkHref }}" class="block hover:text-[#FFD700] transition">{{ $phone }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-white text-xs uppercase tracking-widest font-black mb-5 border-b border-white/10 pb-2 font-heading">{{ __('site.footer_block.newsletter') }}</h3>
                <p class="text-xs text-[#B5CFB7] leading-relaxed">{{ __('site.footer_block.newsletter_text') }}</p>
                <form action="{{ localized_route('contact.store') }}" method="POST" class="space-y-2">
                    @csrf
                    <div class="relative">
                        <input type="email" name="email" placeholder="{{ __('site.footer_block.email_placeholder') }}" required class="w-full bg-[#042C21] border border-white/10 rounded-xl px-4 py-3 text-xs text-white placeholder-emerald-900/60 focus:outline-none focus:ring-1 focus:ring-[#B91C1C] transition">
                        <button type="submit" class="absolute right-2 top-2 bg-[#B91C1C] hover:bg-[#991B1B] p-1.5 rounded-lg transition" aria-label="{{ __('site.footer_block.subscribe_aria') }}">
                            @include('storefront.partials.icon', ['name' => 'mail', 'class' => 'w-4 h-4 text-white'])
                        </button>
                    </div>
                    <input type="hidden" name="name" value="{{ __('site.footer_block.subscribe_name') }}">
                    <input type="hidden" name="phone" value="{{ $hotline }}">
                    <input type="hidden" name="message" value="{{ __('site.footer_block.subscribe_message') }}">
                </form>
            </div>
        </div>

        <div class="border-t border-white/5 mt-16 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-center">
            <p class="text-xs text-[#B5CFB7]/50">&copy; {{ date('Y') }} {{ $brandName }}. {{ __('site.footer_block.copyright') }}</p>
            <div class="flex gap-4 text-xs text-[#B5CFB7]/40">
                <a href="{{ localized_route('contact') }}" class="hover:text-white transition">{{ __('site.footer_block.contact') }}</a>
                <a href="{{ localized_route('order.lookup') }}" class="hover:text-white transition text-[#B91C1C] font-bold">{{ __('site.footer_block.nav_order') }}</a>
            </div>
        </div>
    </div>
</footer>
