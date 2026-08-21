@php
    $title = $promotion->localized('title');
    $subtitle = $promotion->localized('subtitle');
    $description = $promotion->localized('description');
    $badge = $promotion->localized('badge') ?: __('site.home_hero.popup_fallback_badge');
    $buttonText = $promotion->localized('button_text') ?: __('site.home_hero.promo_default_button');
    $buttonLink = $promotion->button_link ?: localized_route('menu.index');
    $image = $promotion->image ? media_variant_url($promotion->image, 'hero', media_url($promotion->image)) : media_variant_url('/paprika/menu/banh-mi.webp', 'hero', media_url('/paprika/menu/banh-mi.webp'));
@endphp

<div
    data-promo-popup
    data-promo-id="{{ $promotion->id }}"
    data-show-once="{{ $promotion->show_once ? '1' : '0' }}"
    hidden
    style="display: none;"
    class="hidden fixed inset-0 z-[90] items-center justify-center overflow-y-auto bg-[#022C22]/72 p-3 text-stone-950 backdrop-blur-md sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="promo-popup-title"
>
    <button type="button" data-promo-close class="absolute inset-0 h-full w-full cursor-default" aria-label="Đóng ưu đãi"></button>

    <article class="relative grid w-full max-w-4xl overflow-hidden rounded-[1.75rem] border border-white/20 bg-[#FFFDF7] shadow-2xl sm:grid-cols-[0.95fr_1.05fr]" onclick="event.stopPropagation()">
        <button type="button" data-promo-close class="absolute right-3 top-3 z-20 flex h-10 w-10 items-center justify-center rounded-full border border-white/50 bg-white/90 text-[#064E3B] shadow-lg backdrop-blur transition hover:bg-white" aria-label="Đóng ưu đãi">
            @include('storefront.partials.icon', ['name' => 'x', 'class' => 'h-5 w-5'])
        </button>

        <div class="relative min-h-52 overflow-hidden bg-[#064E3B] sm:min-h-[31rem]">
            <img src="{{ $image }}" alt="{{ $title }}" class="h-full min-h-52 w-full object-cover sm:min-h-[31rem]" loading="lazy">
            <div class="absolute inset-0 bg-gradient-to-t from-[#022C22]/88 via-[#022C22]/26 to-transparent sm:bg-gradient-to-r sm:from-[#022C22]/82 sm:via-[#022C22]/20 sm:to-transparent"></div>
            <div class="absolute bottom-4 left-4 right-4 rounded-2xl border border-white/20 bg-white/12 p-4 text-white shadow-xl backdrop-blur">
                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-[#FFD700]">{{ __('site.home_hero.popup_today') }}</p>
                <p class="mt-2 text-sm font-semibold leading-5 text-white/90">{{ __('site.home_hero.popup_hint') }}</p>
            </div>
        </div>

        <div class="relative flex flex-col justify-center overflow-hidden p-6 sm:p-9">
            <div class="pointer-events-none absolute right-[-4.5rem] top-[-4.5rem] h-40 w-40 rounded-full border-[1.6rem] border-[#064E3B]/8"></div>
            <div class="pointer-events-none absolute bottom-[-5rem] left-[-5rem] h-44 w-44 rounded-full bg-[#B91C1C]/8 blur-2xl"></div>

            <div class="relative space-y-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-[#B91C1C] px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-white shadow-sm">
                        @include('storefront.partials.icon', ['name' => 'flame', 'class' => 'h-3.5 w-3.5 text-[#FFD700]'])
                        {{ $badge }}
                    </span>
                    <span class="rounded-full border border-[#064E3B]/15 bg-[#064E3B]/8 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-[#064E3B]">{{ __('site.home_hero.popup_limited') }}</span>
                </div>

                <div class="space-y-3">
                    <p class="text-[11px] font-black uppercase tracking-[0.28em] text-stone-400">{{ $subtitle ?: __('site.home_hero.popup_subtitle') }}</p>
                    <h2 id="promo-popup-title" class="text-3xl font-black uppercase italic leading-none tracking-tight text-[#064E3B] sm:text-5xl">
                        {{ $title }}
                    </h2>
                    @if ($description)
                        <p class="text-sm font-medium leading-7 text-stone-600">{{ $description }}</p>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-2 rounded-2xl border border-stone-200 bg-stone-50 p-2 text-center">
                    <div class="rounded-xl bg-white p-3">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-stone-400">{{ __('site.home_hero.popup_stat_order') }}</span>
                        <strong class="mt-1 block text-sm text-[#064E3B]">{{ __('site.home_hero.popup_stat_online') }}</strong>
                    </div>
                    <div class="rounded-xl bg-white p-3">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-stone-400">{{ __('site.home_hero.popup_stat_pickup') }}</span>
                        <strong class="mt-1 block text-sm text-[#064E3B]">{{ __('site.home_hero.popup_stat_fast') }}</strong>
                    </div>
                    <div class="rounded-xl bg-white p-3">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-stone-400">{{ __('site.home_hero.popup_stat_pay') }}</span>
                        <strong class="mt-1 block text-sm text-[#B91C1C]">Viva</strong>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $buttonLink }}" data-promo-action class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-full bg-[#B91C1C] px-6 py-3 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-red-950/15 transition hover:bg-[#991B1B]">
                        {{ $buttonText }}
                        @include('storefront.partials.icon', ['name' => 'arrow-right', 'class' => 'h-4 w-4'])
                    </a>
                    <button type="button" data-promo-close class="inline-flex min-h-12 items-center justify-center rounded-full border border-stone-200 bg-white px-6 py-3 text-xs font-black uppercase tracking-widest text-[#064E3B] transition hover:bg-stone-50">
                        {{ __('site.home_hero.popup_later') }}
                    </button>
                </div>
            </div>
        </div>
    </article>
</div>
