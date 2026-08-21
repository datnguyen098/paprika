@extends('storefront.layouts.app')

@section('content')
    @php
        $heroImage = $hero?->image ?: setting('default_background');
        $heroTitle = $hero?->localized('title') ?: localized_setting('restaurant_name', 'Paprika');
        $heroSubtitle = $hero?->localized('subtitle') ?: localized_setting('restaurant_tagline', __('site.home_hero.subtitle_default'));
        $bestSellers = $featuredDishes->take(3);
    @endphp

    <div class="bg-[#FDFBF7] text-stone-900" id="home-section-container">
        {{-- Hero Section - Premium Editorial Style --}}
        <section class="relative min-h-[420px] sm:min-h-[480px] lg:min-h-[520px] overflow-hidden bg-[#043427]">
            {{-- Ambient background elements --}}
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <div class="absolute -top-24 -right-24 w-[420px] h-[420px] border-[32px] border-[#FFD700]/[0.06] rounded-full animate-pulse-slow"></div>
                <div class="absolute top-1/3 -left-16 w-64 h-64 border-[20px] border-white/[0.04] rounded-full"></div>
                <div class="absolute bottom-0 right-[20%] w-40 h-40 bg-[#B91C1C]/10 rounded-full blur-3xl"></div>
                <div class="absolute top-1/4 right-1/4 w-32 h-32 bg-[#FFD700]/10 rounded-full blur-2xl animate-float"></div>
            </div>

            {{-- Main content container --}}
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-8 lg:px-12 py-12 sm:py-16 lg:py-20">
                <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
                    {{-- Left: Text content --}}
                    <div class="space-y-6 lg:space-y-8 animate-fade-in-up">
                        <div>
                            <span class="inline-flex items-center gap-2 bg-[#B91C1C] text-white text-[10px] font-black px-4 py-2 rounded-full uppercase mb-6 tracking-widest">
                                <span class="w-1.5 h-1.5 bg-[#FFD700] rounded-full animate-pulse"></span>
                                {{ __('site.home_hero.badge') }}
                            </span>
                            <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black text-white leading-none mb-4 italic uppercase tracking-tight font-heading animate-slide-up">
                                {{ $heroTitle }}
                            </h1>
                            <p class="text-white/80 text-base sm:text-lg lg:text-xl mb-8 max-w-lg leading-relaxed font-sans animate-slide-up animation-delay-100">
                                {{ $heroSubtitle }}
                            </p>
                        </div>

                        <div class="animate-slide-up animation-delay-200">
                            <a href="{{ localized_route('menu.index') }}" class="group bg-[#B91C1C] hover:bg-[#991B1B] text-white font-extrabold px-8 py-4 rounded-full transition-all text-sm uppercase tracking-widest shadow-lg shadow-red-900/30 hover:shadow-xl hover:scale-[1.02] inline-flex items-center gap-2">
                                {{ __('site.home_hero.cta_order') }}
                                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>

                        {{-- Stats --}}
                        <div class="grid grid-cols-3 gap-4 sm:gap-6 pt-6 border-t border-white/10 animate-slide-up animation-delay-300">
                            <div class="text-center sm:text-left">
                                <span class="block text-2xl sm:text-3xl font-black text-white leading-none">100%</span>
                                <span class="text-[10px] sm:text-xs uppercase font-bold tracking-wider text-white/60 mt-1 block">{{ __('site.home_hero.stat_fresh') }}</span>
                            </div>
                            <div class="text-center sm:text-left">
                                <span class="block text-2xl sm:text-3xl font-black text-white leading-none">{{ __('site.home_hero.stat_fast') }}</span>
                                <span class="text-[10px] sm:text-xs uppercase font-bold tracking-wider text-white/60 mt-1 block">{{ __('site.home_hero.stat_pickup') }}</span>
                            </div>
                            <div class="text-center sm:text-left">
                                <span class="block text-2xl sm:text-3xl font-black text-white leading-none">{{ __('site.home_hero.stat_easy') }}</span>
                                <span class="text-[10px] sm:text-xs uppercase font-bold tracking-wider text-white/60 mt-1 block">{{ __('site.home_hero.stat_payment') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Hero image --}}
                    <div class="relative hidden lg:flex justify-center items-center animate-fade-in animation-delay-200">
                        <div class="relative">
                            {{-- Decorative rings --}}
                            <div class="absolute -inset-8 border-2 border-[#FFD700]/10 rounded-full animate-spin-slow"></div>
                            <div class="absolute -inset-16 border border-white/5 rounded-full"></div>
                            
                            {{-- Main image container --}}
                            <div class="relative w-[380px] h-[480px] rounded-[2rem] overflow-hidden shadow-2xl shadow-black/40 border border-white/10 transform rotate-3 hover:rotate-0 transition-transform duration-700">
                                <img 
                                    src="{{ media_variant_url($heroImage, 'hero', 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=600&q=80') }}" 
                                    alt="{{ $heroTitle }}" 
                                    class="w-full h-full object-cover"
                                    width="380"
                                    height="480"
                                    loading="eager"
                                    fetchpriority="high"
                                >
                            </div>

                            {{-- Floating accent elements --}}
                            <div class="absolute -top-4 -right-4 w-20 h-20 bg-[#B91C1C] rounded-2xl flex items-center justify-center shadow-lg animate-float">
                                <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom wave --}}
            <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-[#FDFBF7] to-transparent"></div>
        </section>

        {{-- Promotions Section - Editorial Cards --}}
        @if ($promotions->isNotEmpty())
            <section class="px-4 sm:px-6 lg:px-8 pb-8 -mt-8 relative z-20">
                <div class="max-w-6xl mx-auto">
                    <div class="flex items-end justify-between mb-6">
                        <div>
                            <span class="text-[#B91C1C] text-[10px] font-black uppercase tracking-widest">{{ __('site.home.promo_eyebrow') ?? 'Ưu đãi đặc biệt' }}</span>
                            <h2 class="text-2xl sm:text-3xl font-black text-stone-950 mt-1">{{ __('site.home.promo_title') }}</h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-{{ $promotions->count() >= 2 ? 2 : 1 }} gap-5">
                        @foreach ($promotions as $promo)
                            <article class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#064E3B] to-[#022C22] text-white cursor-pointer hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                                {{-- Background image if exists --}}
                                @if($promo->image)
                                    <div class="absolute inset-0 overflow-hidden">
                                        <img 
                                            src="{{ media_variant_url($promo->image, 'card') }}" 
                                            alt="" 
                                            class="w-full h-full object-cover opacity-40 group-hover:opacity-50 group-hover:scale-105 transition-all duration-700"
                                            width="640"
                                            height="360"
                                            loading="lazy"
                                        >
                                    </div>
                                @endif

                                {{-- Gradient overlay --}}
                                <div class="absolute inset-0 bg-gradient-to-r from-[#064E3B]/90 via-[#064E3B]/70 to-transparent"></div>
                                
                                {{-- Decorative elements --}}
                                <div class="absolute top-0 right-0 w-40 h-40 bg-[#FFD700]/10 rounded-full blur-3xl"></div>
                                <div class="absolute bottom-0 left-0 w-32 h-32 bg-[#B91C1C]/20 rounded-full blur-2xl"></div>

                                <div class="relative p-6 sm:p-8 min-h-[180px] sm:min-h-[200px] flex flex-col justify-center">
                                    {{-- Badge --}}
                                    @if ($promo->localized('badge'))
                                        <span class="inline-flex items-center gap-1.5 w-fit bg-[#FFD700] text-[#043427] text-[9px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full mb-4">
                                            <span class="w-1.5 h-1.5 bg-[#043427] rounded-full animate-pulse"></span>
                                            {{ $promo->localized('badge') }}
                                        </span>
                                    @endif

                                    <h3 class="text-xl sm:text-2xl font-black mb-2 group-hover:text-[#FFD700] transition-colors">
                                        {{ $promo->localized('title') }}
                                    </h3>
                                    
                                    <p class="text-white/75 text-sm max-w-md leading-relaxed mb-4">
                                        {{ $promo->localized('subtitle') ?: $promo->localized('description') }}
                                    </p>

                                    @if ($promo->button_link)
                                        <a 
                                            href="{{ $promo->button_link }}" 
                                            class="inline-flex items-center gap-2 bg-white text-[#064E3B] font-bold text-sm px-5 py-2.5 rounded-full hover:bg-[#FFD700] hover:text-[#043427] transition-all w-fit group/btn"
                                        >
                                            {{ $promo->localized('button_text') ?: __('site.home_hero.promo_default_button') }}
                                            <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- Best Sellers Section --}}
        <section class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <div class="text-center space-y-2 mb-10">
                <span class="text-[#B91C1C] font-black uppercase text-xs tracking-widest font-heading">{{ __('site.home_hero.bestseller_eyebrow') }}</span>
                <h2 class="text-3xl sm:text-4xl font-black text-stone-950 tracking-tight italic uppercase">{{ __('site.home_hero.bestseller_title') }}</h2>
                <p class="text-stone-500 text-sm max-w-lg mx-auto mt-3">{{ __('site.home.bestseller_desc') ?? 'Những món ăn được yêu thích nhất tại Paprika Patras' }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($bestSellers as $dish)
                    @include('storefront.components.product-card', ['dish' => $dish, 'variant' => 'home'])
                @endforeach
            </div>
            <div class="text-center mt-10">
                <a href="{{ localized_route('menu.index') }}" class="inline-flex items-center gap-3 px-8 py-4 bg-[#064E3B] hover:bg-[#B91C1C] text-white text-sm font-bold uppercase tracking-widest rounded-full transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] group">
                    {{ __('site.home_hero.view_full_menu') }}
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </section>

        @if ($homeGalleryImages->isNotEmpty())
            <section class="py-14 sm:py-16 px-4 sm:px-6 lg:px-8 bg-[#064E3B] text-white overflow-hidden">
                <div class="max-w-7xl mx-auto">
                    <div class="grid lg:grid-cols-[0.85fr_1.15fr] gap-8 lg:gap-12 items-center">
                        <div class="space-y-5">
                            <span class="inline-flex items-center gap-2 bg-[#B91C1C] text-white text-[10px] font-black px-4 py-2 rounded-full uppercase tracking-widest">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#FFD700]"></span>
                                {{ __('site.home.gallery_eyebrow') }}
                            </span>
                            <div class="space-y-3">
                                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black uppercase italic leading-tight font-heading">
                                    {{ __('site.home.gallery_title') }}
                                </h2>
                                <p class="text-white/72 text-sm sm:text-base leading-relaxed max-w-xl">
                                    {{ __('site.home.gallery_description') }}
                                </p>
                            </div>
                            <a href="{{ localized_route('gallery.index') }}" class="inline-flex items-center gap-3 rounded-full bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-[#064E3B] shadow-lg transition hover:bg-[#FFD700] hover:text-[#043427]">
                                {{ __('site.home.gallery_link') }}
                                @include('storefront.partials.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])
                            </a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @foreach ($homeGalleryImages as $image)
                                <a href="{{ localized_route('gallery.index') }}" class="group relative min-h-[220px] overflow-hidden rounded-2xl bg-[#043427] shadow-xl ring-1 ring-white/10 sm:min-h-[300px] {{ $loop->iteration === 2 ? 'sm:mt-8' : '' }}" aria-label="{{ $image->localized('title') ?: __('site.home.gallery_link') }}">
                                    <img
                                        src="{{ media_variant_url($image->image, 'card', media_url($image->image)) }}"
                                        alt="{{ $image->localized('alt_text') ?: $image->localized('title') ?: __('site.home.gallery_eyebrow') }}"
                                        class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                                        width="360"
                                        height="420"
                                        loading="lazy"
                                    >
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></div>
                                    <div class="absolute inset-x-0 bottom-0 p-4">
                                        <h3 class="line-clamp-2 text-sm font-black uppercase tracking-wide text-white">
                                            {{ $image->localized('title') ?: __('site.home.gallery_eyebrow') }}
                                        </h3>
                                        @if ($image->branch)
                                            <p class="mt-1 text-[11px] font-bold uppercase tracking-widest text-[#FFD700]">{{ $image->branch->name }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- Services Section - Premium Visual Cards --}}
        <section class="bg-gradient-to-b from-[#F9F7F2] to-[#FDFBF7] border-t border-stone-200/60 py-16 sm:py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto space-y-12">
                <div class="text-center space-y-3">
                    <span class="text-[#B91C1C] font-black text-xs uppercase tracking-widest">{{ __('site.home_hero.service_eyebrow') }}</span>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-stone-950 tracking-tight uppercase italic">{{ __('site.home_hero.service_title') }}</h2>
                    <p class="text-stone-500 text-sm max-w-lg mx-auto">{{ __('site.home.service_desc') ?? 'Chọn cách thưởng thức món ăn yêu thích của bạn' }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Delivery Card --}}
                    <a href="{{ localized_route('menu.index') }}" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#064E3B] to-[#022C22] text-white p-8 cursor-pointer hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 focus:outline-none focus:ring-4 focus:ring-[#FFD700]/40">
                        <div class="absolute inset-0 overflow-hidden rounded-3xl">
                            <div class="absolute inset-0 bg-gradient-to-br from-[#064E3B]/80 to-[#022C22]/90"></div>
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-[#FFD700]/10 rounded-full blur-2xl group-hover:bg-[#FFD700]/20 transition-all duration-500"></div>
                        </div>
                        
                        <div class="relative z-10 flex flex-col h-full min-h-[280px]">
                            <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center mb-6 group-hover:bg-[#FFD700]/20 transition-all duration-300">
                                <svg class="w-8 h-8 text-[#FFD700]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <span class="text-[#FFD700] text-[10px] font-black uppercase tracking-widest mb-2 block">{{ __('site.home.delivery_eyebrow') ?? 'Giao hàng' }}</span>
                                <h3 class="text-xl sm:text-2xl font-black uppercase mb-3">{{ __('site.home_hero.service_delivery_title') }}</h3>
                                <p class="text-white/70 text-sm leading-relaxed">{{ __('site.home_hero.service_delivery_text') }}</p>
                            </div>

                            <div class="mt-6 pt-6 border-t border-white/10">
                                <span class="inline-flex items-center gap-2 text-[#FFD700] font-bold text-sm group-hover:gap-3 transition-all">
                                    {{ __('site.home.order_now') ?? 'Đặt hàng ngay' }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>

                    {{-- Pickup Card --}}
                    <a href="{{ localized_route('menu.index') }}" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#B91C1C] to-[#7F1D1D] text-white p-8 cursor-pointer hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 focus:outline-none focus:ring-4 focus:ring-[#B91C1C]/30">
                        <div class="absolute inset-0 overflow-hidden rounded-3xl">
                            <div class="absolute inset-0 bg-gradient-to-br from-[#B91C1C]/80 to-[#7F1D1D]/90"></div>
                            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-all duration-500"></div>
                        </div>
                        
                        <div class="relative z-10 flex flex-col h-full min-h-[280px]">
                            <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center mb-6 group-hover:bg-white/20 transition-all duration-300">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <span class="text-white/80 text-[10px] font-black uppercase tracking-widest mb-2 block">{{ __('site.home.pickup_eyebrow') ?? 'Nhận tại quán' }}</span>
                                <h3 class="text-xl sm:text-2xl font-black uppercase mb-3">{{ __('site.home_hero.service_pickup_title') }}</h3>
                                <p class="text-white/70 text-sm leading-relaxed">{{ __('site.home_hero.service_pickup_text') }}</p>
                            </div>

                            <div class="mt-6 pt-6 border-t border-white/10">
                                <span class="inline-flex items-center gap-2 text-white font-bold text-sm group-hover:gap-3 transition-all">
                                    {{ __('site.home.order_now') ?? 'Đặt hàng ngay' }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>

                    {{-- Dine-in Card --}}
                    <a href="{{ localized_route('reservations.create') }}" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#92400E] to-[#78350F] text-white p-8 cursor-pointer hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 focus:outline-none focus:ring-4 focus:ring-[#FFD700]/40">
                        <div class="absolute inset-0 overflow-hidden rounded-3xl">
                            <div class="absolute inset-0 bg-gradient-to-br from-[#92400E]/80 to-[#78350F]/90"></div>
                            <div class="absolute top-10 right-10 w-32 h-32 bg-[#FFD700]/10 rounded-full blur-2xl group-hover:bg-[#FFD700]/20 transition-all duration-500"></div>
                        </div>
                        
                        <div class="relative z-10 flex flex-col h-full min-h-[280px]">
                            <div class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm flex items-center justify-center mb-6 group-hover:bg-white/20 transition-all duration-300">
                                <svg class="w-8 h-8 text-[#FFD700]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>

                            <div class="flex-1">
                                <span class="text-[#FFD700] text-[10px] font-black uppercase tracking-widest mb-2 block">{{ __('site.home.dinein_eyebrow') ?? 'Tại quán' }}</span>
                                <h3 class="text-xl sm:text-2xl font-black uppercase mb-3">{{ __('site.home_hero.service_dine_title') }}</h3>
                                <p class="text-white/70 text-sm leading-relaxed"><span class="text-[#FFD700] font-bold group-hover:underline">{{ __('site.home_hero.service_dine_link') }}</span> {{ __('site.home_hero.service_dine_text_after') }}</p>
                            </div>

                            <div class="mt-6 pt-6 border-t border-white/10">
                                <span class="inline-flex items-center gap-2 text-[#FFD700] font-bold text-sm group-hover:gap-3 transition-all">
                                    {{ __('site.home.book_now') ?? 'Đặt bàn ngay' }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </section>

        {{-- Branch Map Section --}}
        <section class="border-t border-stone-200/70 bg-[#FDFBF7] px-4 py-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                @include('storefront.partials.branch-map', [
                    'branch' => primary_branch(),
                    'eyebrow' => __('site.branch_map.home_eyebrow'),
                    'title' => __('site.branch_map.home_title'),
                    'description' => __('site.branch_map.home_description'),
                    'mapHeight' => 'h-72 sm:h-80',
                ])
            </div>
        </section>
    </div>
@endsection
