@php
    $price = (int) ($dish->sale_price ?: $dish->price);
    $dishName = $dish->localized('name');
    $dishSlug = $dish->localizedSlug();
    $variant = $variant ?? 'menu';
    $imageUrl = media_variant_url($dish->image, 'card');
    $availability = dish_availability($dish);
    $availabilityLabel = $availability->label();
    $canAddToCart = true;
    $optionsJson = $dish->activeOptionGroups->map(fn ($g) => [
        'id' => $g->id,
        'name' => $g->localized('name'),
        'type' => $g->type,
        'desc' => $g->localized('description'),
        'options' => $g->activeOptions->map(fn ($o) => [
            'id' => $o->id,
            'name' => $o->localized('name'),
            'price' => (int) $o->price_delta,
            'default' => $o->is_default,
        ])->values(),
    ])->values()->toJson();
@endphp

@if ($variant === 'home')
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-stone-100 flex flex-col transition-all duration-300 hover:shadow-xl hover:border-[#064E3B]/20 group-hover:scale-[1.02]">
        <a href="{{ localized_route('menu.show', ['slug' => $dishSlug]) }}" class="h-52 sm:h-56 bg-stone-100 rounded-2xl mb-5 overflow-hidden relative block">
            <img src="{{ $imageUrl }}" alt="{{ $dishName }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-110" width="480" height="360" loading="lazy">
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            @if ($dish->is_featured)
                <span class="absolute top-3 left-3 bg-[#FFD700] text-[#043427] text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider shadow-lg flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                    {{ __('site.product_card.featured') }}
                </span>
            @endif
            @if ($availabilityLabel)
                <span class="absolute top-3 right-3 bg-black/70 text-white text-[9px] font-black px-2.5 py-1 rounded-full uppercase tracking-wider shadow-lg">
                    {{ $availabilityLabel }}
                </span>
            @endif
        </a>
        <div class="flex-1 flex flex-col">
            <span class="text-[#B91C1C] text-[10px] font-black uppercase tracking-wider mb-2">{{ $dish->category->localized('name') ?? '' }}</span>
            <h3 class="font-black uppercase text-sm mb-2 italic text-stone-900 group-hover:text-[#064E3B] transition-colors leading-tight">
                <a href="{{ localized_route('menu.show', ['slug' => $dishSlug]) }}">{{ $dishName }}</a>
            </h3>
            <p class="text-xs text-stone-500 mb-4 line-clamp-2 leading-relaxed flex-1">{{ Str::limit($dish->localized('description'), 100) }}</p>
            <div class="mt-auto pt-4 border-t border-stone-100 flex items-center justify-between">
                @if (show_dish_prices())
                    <div class="flex flex-col">
                        <span class="text-[9px] uppercase tracking-wider font-bold text-stone-400">{{ __('site.product_card.price') }}</span>
                        <span class="font-black text-xl text-[#064E3B]">{{ format_money($price) }}</span>
                    </div>
                @endif
                <form method="POST" action="{{ localized_route('cart.add', $dish) }}" data-ajax-cart-form>
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button
                        type="submit"
                        @class([
                            'w-12 h-12 text-white rounded-full flex items-center justify-center font-extrabold text-lg transition-all shadow-lg',
                            'bg-[#064E3B] hover:bg-[#B91C1C] hover:shadow-xl hover:scale-110' => true,
                        ])
                        title="{{ __('site.product_card.add_to_cart') }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
@else
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-stone-100 flex flex-col group transition-all hover:shadow-md hover:border-stone-200">
        <div class="h-40 bg-stone-100 rounded-xl mb-4 overflow-hidden relative shrink-0">
            <a href="{{ localized_route('menu.show', ['slug' => $dishSlug]) }}">
                <img src="{{ $imageUrl }}" alt="{{ $dishName }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" width="480" height="360" loading="lazy">
            </a>
            @if ($dish->is_featured)
                <span class="absolute top-2 left-2 bg-[#064E3B] text-white text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">{{ __('site.product_card.featured') }}</span>
            @endif
            @if ($availabilityLabel)
                <span class="absolute top-2 right-2 bg-black/70 text-white text-[9px] font-black px-2 py-0.5 rounded uppercase tracking-wider">{{ $availabilityLabel }}</span>
            @endif
        </div>
        <h3 class="font-black uppercase text-sm mb-1 italic text-stone-900 group-hover:text-[#064E3B] transition-colors leading-tight">
            <a href="{{ localized_route('menu.show', ['slug' => $dishSlug]) }}">{{ $dishName }}</a>
        </h3>
        <p class="text-[11px] text-stone-500 mb-4 line-clamp-2 leading-relaxed">{{ \Illuminate\Support\Str::limit($dish->localized('description'), 92) }}</p>
        <div class="mt-auto pt-3 border-t border-stone-100 flex items-center justify-between">
            @if (show_dish_prices())
                <div class="flex flex-col">
                    <span class="text-[8px] uppercase tracking-wider font-extrabold text-stone-400">{{ __('site.product_card.price') }}</span>
                    <span class="font-black text-lg text-[#064E3B]">{{ format_money($price) }}</span>
                </div>
            @endif
            <button
                type="button"
                data-open-customizer
                data-dish-name="{{ $dishName }}"
                data-dish-image="{{ $imageUrl }}"
                data-add-url="{{ localized_route('cart.add', $dish) }}"
                data-dish-price="{{ $price }}"
                data-dish-options="{{ $optionsJson }}"
                @class([
                    'text-white w-8 h-8 rounded-full flex items-center justify-center transition-colors font-extrabold text-sm shadow-sm',
                    'bg-[#064E3B] hover:bg-[#B91C1C]' => true,
                ])
                title="{{ __('site.product_card.add_to_cart') }}"
            >+</button>
        </div>
    </div>
@endif
