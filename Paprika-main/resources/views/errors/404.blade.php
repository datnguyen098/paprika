@extends('storefront.layouts.app')

@section('content')
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#064E3B] via-[#043427] to-[#022119]"></div>
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 30%, #FFD700 0, transparent 40%), radial-gradient(circle at 80% 70%, #B91C1C 0, transparent 40%);"></div>

        <div class="relative mx-auto grid min-h-[80vh] max-w-5xl place-items-center px-4 py-20 text-center sm:px-6 lg:px-8">
            <div class="w-full">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-[11px] font-bold uppercase tracking-[0.32em] text-amber-300 ring-1 ring-white/15 backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                    {{ __('site.error_404.badge') }}
                </div>

                <h1 class="mt-6 font-heading text-[88px] font-black leading-none tracking-tighter text-white sm:text-[140px] lg:text-[180px]">
                    <span class="bg-gradient-to-b from-white to-white/40 bg-clip-text text-transparent">404</span>
                </h1>

                <h2 class="mt-2 text-2xl font-extrabold tracking-tight text-white sm:text-3xl lg:text-4xl">
                    {{ __('site.error_404.title') }}
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-white/70 sm:text-lg">
                    {{ __('site.error_404.description') }}
                </p>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ localized_route('home') }}" class="inline-flex items-center gap-2 rounded-full bg-[#B91C1C] px-7 py-3.5 text-xs font-extrabold uppercase tracking-widest text-white shadow-lg shadow-red-900/40 transition hover:bg-[#991B1B] hover:scale-105">
                        @include('storefront.partials.icon', ['name' => 'home', 'class' => 'w-4 h-4'])
                        {{ __('site.error_404.home') }}
                    </a>
                    <a href="{{ localized_route('menu.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/10 px-7 py-3.5 text-xs font-extrabold uppercase tracking-widest text-white ring-1 ring-white/20 backdrop-blur transition hover:bg-white/20">
                        @include('storefront.partials.icon', ['name' => 'cart', 'class' => 'w-4 h-4'])
                        {{ __('site.error_404.menu') }}
                    </a>
                </div>

                <div class="mx-auto mt-12 grid max-w-2xl gap-3 sm:grid-cols-3">
                    <a href="{{ localized_route('reservations.create') }}" class="group rounded-2xl border border-white/10 bg-white/5 p-4 text-left backdrop-blur transition hover:border-amber-300/50 hover:bg-white/10">
                        <span class="block text-[10px] font-bold uppercase tracking-widest text-amber-300">{{ __('site.error_404.booking_label') }}</span>
                        <span class="mt-1 block text-sm font-bold text-white group-hover:text-amber-200">{{ __('site.error_404.booking_action') }}</span>
                    </a>
                    <a href="{{ localized_route('contact') }}" class="group rounded-2xl border border-white/10 bg-white/5 p-4 text-left backdrop-blur transition hover:border-amber-300/50 hover:bg-white/10">
                        <span class="block text-[10px] font-bold uppercase tracking-widest text-amber-300">{{ __('site.error_404.contact_label') }}</span>
                        <span class="mt-1 block text-sm font-bold text-white group-hover:text-amber-200">{{ __('site.error_404.contact_action') }}</span>
                    </a>
                    <a href="tel:{{ preg_replace('/\D+/', '', setting('hotline', setting('phone', '0947361515'))) }}" class="group rounded-2xl border border-white/10 bg-white/5 p-4 text-left backdrop-blur transition hover:border-amber-300/50 hover:bg-white/10">
                        <span class="block text-[10px] font-bold uppercase tracking-widest text-amber-300">{{ __('site.error_404.hotline_label') }}</span>
                        <span class="mt-1 block text-sm font-bold text-white group-hover:text-amber-200">{{ setting('hotline', setting('phone', '0947 361 515')) }} →</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
