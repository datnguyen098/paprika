@extends('storefront.layouts.app')

@section('content')
    @php
        $currentPrice = (int) ($dish->sale_price ?: $dish->price);
        $dishName = $dish->localized('name');
        $dishDescription = $dish->localized('description');
        $categoryName = $dish->category?->localized('name') ?: __('site.menu.eyebrow');
        $categorySlug = $dish->category?->slug ?: 'do-an-viet-nam';
        $fallbackImageUrl = media_variant_url($dish->image, 'card', media_url($dish->image, media_variant_url('/paprika/cover.jpg', 'hero')));
        $imageUrl = media_variant_url($dish->image, 'large', $fallbackImageUrl);
        $imageSrcset = media_srcset($dish->image, ['card', 'large', 'hero']);
        $optionGroups = $dish->activeOptionGroups ?? collect();
        $hasOptions = $optionGroups->isNotEmpty();
        $availability = dish_availability($dish);
        $availabilityLabel = $availability->label();
        $canAddToCart = true;
        $showUnavailableNowNotice = ! $availability->available;

        $calories = [
            'beef-pho' => 540,
            'chicken-pho' => 470,
            'fried-nem' => 410,
            'pho-rolls' => 320,
            'banh-mi' => 520,
            'greek-salad' => 360,
            'souvlaki-skewers' => 390,
            'gyros' => 640,
            'bifteki' => 610,
            'lamb-chops' => 720,
            'mineral-water' => 0,
            'soft-drink' => 140,
            'iced-tea' => 110,
            'greek-coffee' => 60,
        ][$dish->slug] ?? ($categorySlug === 'do-uong' ? 120 : 520);

        $nutritionFactor = max($calories, 80) / 1000;
        $nutritionRows = [
            ['label' => __('site.dish_detail.nutrition_energy'), 'value' => number_format($calories * 4.184, 0, ',', '.').' kJ / '.$calories.' kcal'],
            ['label' => __('site.dish_detail.nutrition_fat'), 'value' => number_format(30 * $nutritionFactor, 1, ',', '.').' g'],
            ['label' => __('site.dish_detail.nutrition_carbs'), 'value' => number_format(46 * $nutritionFactor, 1, ',', '.').' g'],
            ['label' => __('site.dish_detail.nutrition_protein'), 'value' => number_format(24 * $nutritionFactor, 1, ',', '.').' g'],
            ['label' => __('site.dish_detail.nutrition_salt'), 'value' => number_format(1.7 * $nutritionFactor, 2, ',', '.').' g'],
        ];

        $allergens = [
            ['name' => __('site.dish_detail.allergen_gluten'), 'contains' => in_array($dish->slug, ['banh-mi', 'gyros', 'fried-nem'], true)],
            ['name' => __('site.dish_detail.allergen_dairy'), 'contains' => in_array($dish->slug, ['greek-salad', 'gyros'], true)],
            ['name' => __('site.dish_detail.allergen_soy'), 'contains' => $categorySlug === 'do-an-viet-nam'],
            ['name' => __('site.dish_detail.allergen_sesame'), 'contains' => in_array($dish->slug, ['banh-mi', 'pho-rolls', 'souvlaki-skewers'], true)],
            ['name' => __('site.dish_detail.allergen_egg'), 'contains' => in_array($dish->slug, ['fried-nem', 'banh-mi'], true)],
            ['name' => __('site.dish_detail.allergen_mustard'), 'contains' => $categorySlug === 'do-an-hy-lap'],
        ];
    @endphp

    <span
        class="sr-only"
        data-track-view="view_dish"
        data-track-category="dish"
        data-track-label="{{ $dishName }}"
        data-item-name="{{ $dishName }}"
        @if (show_dish_prices()) data-value="{{ $currentPrice }}" data-currency="EUR" @endif
        data-facebook-event="ViewDish"
    ></span>

    <section class="bg-[#FDFBF7] pb-36 sm:pb-12" data-product-detail data-base-price="{{ $currentPrice }}" data-locale-note-prefix="{{ __('site.dish_detail.note_prefix') }}" data-locale-standard="{{ __('site.dish_detail.standard_recipe') }}" data-locale-unit-suffix="{{ __('site.dish_detail.unit_suffix') }}">
        <div class="mx-auto max-w-7xl px-0 sm:px-6 lg:px-8">
            <div class="grid min-h-[calc(100svh-5rem)] overflow-hidden bg-white shadow-xl shadow-stone-900/5 sm:my-6 sm:rounded-[2rem] lg:grid-cols-[minmax(0,1fr)_480px]">
                <div class="relative flex min-h-[430px] flex-col justify-end overflow-hidden bg-stone-100 lg:min-h-[720px]">
                    <img
                        src="{{ $imageUrl }}"
                        @if ($imageSrcset) srcset="{{ $imageSrcset }}" @endif
                        alt="{{ $dishName }}"
                        class="absolute inset-0 h-full w-full object-cover"
                        fetchpriority="high"
                        sizes="(max-width: 1024px) 100vw, 58vw"
                        onerror="this.onerror=null;this.removeAttribute('srcset');this.src='{{ $fallbackImageUrl }}';"
                    >
                    <div class="absolute inset-0 bg-gradient-to-t from-[#032219] via-[#032219]/25 to-black/10"></div>

                    <div class="absolute left-4 top-4 z-10 flex flex-wrap items-center gap-2">
                        <a href="{{ localized_route('menu.index') }}" class="rounded-full bg-white/95 px-3 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-[#064E3B] shadow-sm">{{ __('site.dish_detail.menu_chip') }}</a>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#B91C1C] px-3 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-white shadow-sm">
                            @include('storefront.partials.icon', ['name' => 'flame', 'class' => 'h-3.5 w-3.5 text-[#FFD700]'])
                            {{ $categoryName }}
                        </span>
                        @if ($dish->is_featured)
                            <span class="rounded-full bg-[#064E3B] px-3 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-white shadow-sm">{{ __('site.dish_detail.featured') }}</span>
                        @endif
                        @if ($availabilityLabel)
                            <span class="rounded-full bg-black/40 px-3 py-2 text-[10px] font-black uppercase tracking-[0.16em] text-white shadow-sm">{{ $availabilityLabel }}</span>
                        @endif
                    </div>

                    <div class="relative z-10 p-5 text-white sm:p-8">
                        <nav class="mb-4 flex flex-wrap items-center gap-2 text-[10px] font-black uppercase tracking-[0.18em] text-white/55" aria-label="Breadcrumb">
                            <a href="{{ localized_route('home') }}" class="hover:text-white">{{ __('site.dish_detail.breadcrumb_home') }}</a>
                            <span>/</span>
                            <a href="{{ localized_route('menu.index') }}" class="hover:text-white">{{ __('site.dish_detail.breadcrumb_menu') }}</a>
                        </nav>
                        <h1 class="max-w-3xl text-4xl font-black uppercase leading-[0.92] tracking-tight sm:text-5xl lg:text-6xl">{{ $dishName }}</h1>
                        @if ($dishDescription)
                            <p class="mt-4 max-w-2xl text-sm font-medium leading-7 text-white/78 sm:text-base">{{ $dishDescription }}</p>
                        @endif

                        <div class="mt-6 grid grid-cols-3 gap-2 border-t border-white/15 pt-4 text-[10px] font-black uppercase tracking-[0.12em] text-[#FFD700] sm:flex sm:gap-4">
                            <div class="rounded-2xl bg-white/10 p-3">
                                <span class="block text-white/55">{{ __('site.dish_detail.prep') }}</span>
                                <span class="mt-1 block text-white">{{ __('site.dish_detail.prep_value') }}</span>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-3">
                                <span class="block text-white/55">{{ __('site.dish_detail.energy') }}</span>
                                <span class="mt-1 block text-white">{{ $calories }} kcal</span>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-3">
                                <span class="block text-white/55">{{ __('site.dish_detail.branch') }}</span>
                                <span class="mt-1 block text-white">{{ __('site.dish_detail.branch_value') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ localized_route('cart.add', $dish) }}" class="flex min-h-[640px] flex-col bg-white lg:max-h-[calc(100svh-7rem)]" data-detail-form data-ajax-cart-form>
                    @csrf
                    <input type="hidden" name="quantity" value="1" data-detail-quantity-input>

                    <div class="sticky top-0 z-20 border-b border-stone-200 bg-white p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[#064E3B]">{{ __('site.dish_detail.detail_title') }}</p>
                                <h2 class="mt-1 text-xl font-black tracking-tight text-stone-950">{{ $dishName }}</h2>
                            </div>
                            @if (show_dish_prices())
                                <div class="text-right">
                                    <span class="block text-[10px] font-black uppercase tracking-[0.16em] text-stone-400">{{ __('site.dish_detail.price_from') }}</span>
                                    <strong class="block text-2xl font-black text-[#064E3B]">{{ format_money($currentPrice) }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 grid grid-cols-3 gap-1 rounded-2xl bg-stone-100 p-1" role="tablist" aria-label="{{ __('site.dish_detail.tabs_aria') }}">
                            <button type="button" class="rounded-xl bg-white px-2 py-3 text-[10px] font-black uppercase tracking-[0.12em] text-[#064E3B] shadow-sm" data-detail-tab="customise" aria-selected="true">{{ __('site.dish_detail.tab_customise') }}</button>
                            <button type="button" class="rounded-xl px-2 py-3 text-[10px] font-black uppercase tracking-[0.12em] text-stone-500" data-detail-tab="nutrition" aria-selected="false">{{ __('site.dish_detail.tab_nutrition') }}</button>
                            <button type="button" class="rounded-xl px-2 py-3 text-[10px] font-black uppercase tracking-[0.12em] text-stone-500" data-detail-tab="allergens" aria-selected="false">{{ __('site.dish_detail.tab_allergens') }}</button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto bg-[#FDFBF7] p-4 sm:p-6 lg:no-scrollbar">
                        <div class="space-y-6" data-detail-panel="customise">
                            @if ($hasOptions)
                                @foreach ($optionGroups as $group)
                                    @php
                                        $isSingle = $group->type === \App\Models\DishOptionGroup::TYPE_SINGLE;
                                        $isExclude = $group->type === \App\Models\DishOptionGroup::TYPE_EXCLUDE;
                                        $hint = $isSingle
                                            ? __('site.dish_detail.hint_single')
                                            : ($isExclude ? __('site.dish_detail.hint_exclude') : __('site.dish_detail.hint_multiple'));
                                    @endphp
                                    <section class="space-y-3">
                                        <div class="flex items-baseline justify-between gap-4">
                                            <div>
                                                <h3 class="text-xs font-black uppercase tracking-[0.16em] text-stone-400">{{ $group->localized('name') }}</h3>
                                                @if ($group->localized('description'))
                                                    <p class="mt-1 text-[11px] font-medium leading-5 text-stone-400">{{ $group->localized('description') }}</p>
                                                @endif
                                            </div>
                                            <span class="shrink-0 text-[10px] font-semibold text-stone-400">{{ $hint }}</span>
                                        </div>

                                        <div class="grid gap-2 {{ $isExclude ? 'sm:grid-cols-2' : '' }}">
                                            @foreach ($group->activeOptions as $option)
                                                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-3.5 shadow-sm transition hover:border-[#064E3B]/40 has-[:checked]:border-[#064E3B] has-[:checked]:bg-[#064E3B]/5">
                                                    <span class="flex min-w-0 items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            name="option_ids[]"
                                                            value="{{ $option->id }}"
                                                            class="peer sr-only"
                                                            data-detail-option
                                                            data-option-group="{{ $group->id }}"
                                                            data-option-type="{{ $group->type }}"
                                                            data-option-label="{{ $group->localized('name') }}: {{ $option->localized('name') }}"
                                                            data-option-price="{{ (int) $option->price_delta }}"
                                                            @checked($option->is_default)
                                                        >
                                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center {{ $isSingle ? 'rounded-full' : 'rounded-md' }} border border-stone-300 peer-checked:border-[#064E3B] peer-checked:bg-[#064E3B] peer-checked:text-white">
                                                            @include('storefront.partials.icon', ['name' => 'check', 'class' => 'h-3 w-3'])
                                                        </span>
                                                        <span class="min-w-0">
                                                            <span class="block text-xs font-bold text-stone-850">{{ $option->localized('name') }}</span>
                                                            @if ($option->localized('description'))
                                                                <span class="mt-0.5 block text-[10px] leading-4 text-stone-400">{{ $option->localized('description') }}</span>
                                                            @endif
                                                        </span>
                                                    </span>
                                                    @if ((int) $option->price_delta !== 0)
                                                        <strong class="shrink-0 font-mono text-xs {{ $option->price_delta > 0 ? 'text-[#064E3B]' : 'text-[#B91C1C]' }}">
                                                            {{ $option->price_delta > 0 ? '+' : '-' }}{{ format_money(abs((int) $option->price_delta)) }}
                                                        </strong>
                                                    @elseif ($isSingle || $option->is_default)
                                                        <span class="shrink-0 rounded-full bg-stone-100 px-2 py-1 text-[9px] font-black uppercase text-stone-400">{{ __('site.dish_detail.included') }}</span>
                                                    @endif
                                                </label>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            @else
                                <div class="rounded-2xl border border-[#064E3B]/10 bg-[#064E3B]/5 p-4 text-sm leading-7 text-stone-600">
                                    {{ __('site.dish_detail.standard_recipe_box') }}
                                </div>
                            @endif

                            <label class="block space-y-2">
                                <span class="text-xs font-black uppercase tracking-[0.16em] text-stone-400">{{ __('site.dish_detail.note_label') }}</span>
                                <textarea name="customization_note" rows="3" maxlength="500" data-detail-note-input class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm leading-6 text-stone-700 outline-none transition focus:border-[#064E3B] focus:ring-4 focus:ring-[#064E3B]/10" placeholder="{{ __('site.dish_detail.note_placeholder') }}">{{ old('customization_note') }}</textarea>
                            </label>

                            <div class="flex items-start gap-3 rounded-2xl border border-[#064E3B]/10 bg-[#064E3B]/5 p-4 text-xs leading-6 text-stone-600">
                                @include('storefront.partials.icon', ['name' => 'shield', 'class' => 'mt-0.5 h-5 w-5 shrink-0 text-[#064E3B]'])
                                <div>
                                    <span class="block font-black uppercase tracking-[0.14em] text-[#064E3B]">{{ __('site.dish_detail.kitchen_summary') }}</span>
                                    <span data-detail-note-preview>{{ __('site.dish_detail.standard_recipe') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="hidden space-y-5" data-detail-panel="nutrition">
                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-800">
                                {{ __('site.dish_detail.nutrition_disclaimer') }}
                            </div>

                            <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
                                <div class="flex justify-between bg-stone-100 p-3 text-[10px] font-black uppercase tracking-[0.14em] text-stone-500">
                                    <span>{{ __('site.dish_detail.nutrition_col_field') }}</span>
                                    <span>{{ __('site.dish_detail.nutrition_col_value') }}</span>
                                </div>
                                @foreach ($nutritionRows as $row)
                                    <div class="flex items-center justify-between gap-4 border-t border-stone-100 p-4 text-sm">
                                        <span class="font-bold text-stone-800">{{ $row['label'] }}</span>
                                        <strong class="font-mono text-stone-950">{{ $row['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="hidden space-y-5" data-detail-panel="allergens">
                            <div class="flex gap-3 rounded-2xl border border-rose-100 bg-rose-50 p-4 text-xs leading-6 text-rose-800">
                                @include('storefront.partials.icon', ['name' => 'shield', 'class' => 'h-5 w-5 shrink-0 text-rose-700'])
                                <div>
                                    <strong class="block text-[10px] uppercase tracking-[0.14em]">{{ __('site.dish_detail.allergen_warning_title') }}</strong>
                                    {{ __('site.dish_detail.allergen_warning_text') }}
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($allergens as $allergen)
                                    <div class="flex items-center justify-between gap-3 rounded-2xl border bg-white p-4 {{ $allergen['contains'] ? 'border-rose-200 text-rose-900' : 'border-stone-200 text-stone-500' }}">
                                        <span class="text-xs font-bold">{{ $allergen['name'] }}</span>
                                        <span class="rounded-full px-2 py-1 text-[9px] font-black uppercase {{ $allergen['contains'] ? 'bg-[#B91C1C] text-white' : 'bg-stone-100 text-stone-400' }}">
                                            {{ $allergen['contains'] ? __('site.dish_detail.allergen_yes') : __('site.dish_detail.allergen_no') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="hidden border-t border-stone-200 bg-white p-5 sm:block">
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <div>
                                <span class="block text-[9px] font-black uppercase tracking-[0.18em] text-stone-400">{{ __('site.dish_detail.quantity') }}</span>
                                <div class="mt-1 flex items-center gap-3 rounded-full border border-stone-200 bg-stone-50 px-3 py-2">
                                    <button type="button" class="px-2 text-lg font-black text-stone-500 hover:text-stone-950" data-detail-qty-action="dec" aria-label="{{ __('site.dish_detail.qty_dec') }}">-</button>
                                    <span class="w-8 text-center font-mono text-sm font-black" data-detail-quantity-label>1</span>
                                    <button type="button" class="px-2 text-lg font-black text-[#064E3B] hover:text-[#B91C1C]" data-detail-qty-action="inc" aria-label="{{ __('site.dish_detail.qty_inc') }}">+</button>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="block text-[9px] font-black uppercase tracking-[0.18em] text-stone-400">{{ __('site.dish_detail.subtotal') }}</span>
                                <strong class="block font-mono text-2xl font-black text-[#064E3B]" data-detail-total>{{ format_money($currentPrice) }}</strong>
                                <span class="mt-0.5 block text-[10px] font-bold text-stone-400" data-detail-unit-price>{{ format_money($currentPrice) }} {{ __('site.dish_detail.unit_suffix') }}</span>
                            </div>
                        </div>

                        <button
                            @disabled(! $canAddToCart)
                            @class([
                                'flex min-h-14 w-full items-center justify-between gap-3 rounded-2xl px-5 text-sm font-black uppercase tracking-[0.14em] text-white shadow-xl shadow-red-900/15 transition active:scale-[0.99]',
                                'bg-[#B91C1C] hover:bg-[#991B1B]' => $canAddToCart,
                                'bg-stone-300 cursor-not-allowed' => ! $canAddToCart,
                            ])
                        >
                            <span>{{ __('site.dish_detail.add_to_cart') }}</span>
                            <span class="rounded-full bg-black/15 px-3 py-1 font-mono {{ $canAddToCart ? 'text-[#FFD700]' : 'text-white' }}" data-detail-total-inline>{{ format_money($currentPrice) }}</span>
                        </button>
                    </div>

                    <div class="fixed bottom-20 left-3 right-3 z-30 rounded-3xl border border-stone-200 bg-white p-3 shadow-2xl shadow-stone-950/20 sm:hidden">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 rounded-full border border-stone-200 bg-stone-50 px-2 py-1.5">
                                <button type="button" class="h-9 w-9 rounded-full text-lg font-black text-stone-500" data-detail-qty-action="dec" aria-label="{{ __('site.dish_detail.qty_dec') }}">-</button>
                                <span class="w-7 text-center font-mono text-sm font-black" data-detail-quantity-label>1</span>
                                <button type="button" class="h-9 w-9 rounded-full text-lg font-black text-[#064E3B]" data-detail-qty-action="inc" aria-label="{{ __('site.dish_detail.qty_inc') }}">+</button>
                            </div>
                            <div class="text-right">
                                <span class="block text-[9px] font-black uppercase tracking-[0.16em] text-stone-400">{{ __('site.dish_detail.subtotal') }}</span>
                                <strong class="font-mono text-xl font-black text-[#064E3B]" data-detail-total>{{ format_money($currentPrice) }}</strong>
                            </div>
                        </div>
                        <button
                            @disabled(! $canAddToCart)
                            @class([
                                'flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl px-4 text-xs font-black uppercase tracking-[0.14em] text-white shadow-lg',
                                'bg-[#B91C1C]' => $canAddToCart,
                                'bg-stone-300 cursor-not-allowed' => ! $canAddToCart,
                            ])
                        >
                            @include('storefront.partials.icon', ['name' => 'cart', 'class' => 'h-4 w-4'])
                            {{ __('site.dish_detail.add_to_cart') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @if ($relatedDishes->isNotEmpty() || $pairingDishes->isNotEmpty())
        <section class="bg-white py-10 sm:py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-7 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.22em] text-[#B91C1C]">{{ __('site.dish_detail.related_eyebrow') }}</p>
                        <h2 class="mt-2 text-3xl font-black uppercase tracking-tight text-stone-950">{{ __('site.dish_detail.related_title') }}</h2>
                    </div>
                    <a href="{{ localized_route('menu.index') }}" class="text-xs font-black uppercase tracking-[0.16em] text-[#064E3B] hover:text-[#B91C1C]">{{ __('site.dish_detail.related_link') }}</a>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($relatedDishes->merge($pairingDishes)->unique('id')->take(4) as $suggestedDish)
                        @include('storefront.components.product-card', ['dish' => $suggestedDish])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-product-detail]').forEach(function (root) {
            var basePrice = parseInt(root.getAttribute('data-base-price') || '0', 10);
            var quantity = 1;
            var quantityInput = root.querySelector('[data-detail-quantity-input]');
            var quantityLabels = root.querySelectorAll('[data-detail-quantity-label]');
            var totalLabels = root.querySelectorAll('[data-detail-total], [data-detail-total-inline]');
            var unitPriceLabels = root.querySelectorAll('[data-detail-unit-price]');
            var noteInput = root.querySelector('[data-detail-note-input]');
            var notePreview = root.querySelector('[data-detail-note-preview]');

            function formatEuro(cents) {
                return '€' + (cents / 100).toFixed(2).replace('.', ',');
            }

            function selectedOptions() {
                return Array.from(root.querySelectorAll('[data-detail-option]:checked'));
            }

            function unitPrice() {
                return selectedOptions().reduce(function (sum, input) {
                    return sum + parseInt(input.getAttribute('data-option-price') || '0', 10);
                }, basePrice);
            }

            function sync() {
                var price = Math.max(0, unitPrice());
                if (quantityInput) quantityInput.value = String(quantity);
                quantityLabels.forEach(function (label) {
                    label.textContent = String(quantity);
                });
                totalLabels.forEach(function (label) {
                    label.textContent = formatEuro(price * quantity);
                });
                unitPriceLabels.forEach(function (label) {
                    var unitSuffix = root.getAttribute('data-locale-unit-suffix') || '/ each';
                    label.textContent = formatEuro(price) + ' ' + unitSuffix;
                });

                var optionText = selectedOptions().map(function (input) {
                    return input.getAttribute('data-option-label');
                });
                var note = noteInput ? noteInput.value.trim() : '';
                var notePrefix = root.getAttribute('data-locale-note-prefix') || 'Ghi chú: ';
                var standard = root.getAttribute('data-locale-standard') || 'Công thức tiêu chuẩn';
                var parts = optionText;
                if (note) parts.push(notePrefix + note);
                if (notePreview) notePreview.textContent = parts.join(' | ') || standard;
            }

            root.querySelectorAll('[data-detail-tab]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var target = tab.getAttribute('data-detail-tab');
                    root.querySelectorAll('[data-detail-tab]').forEach(function (item) {
                        var active = item === tab;
                        item.setAttribute('aria-selected', active ? 'true' : 'false');
                        item.classList.toggle('bg-white', active);
                        item.classList.toggle('text-[#064E3B]', active);
                        item.classList.toggle('shadow-sm', active);
                        item.classList.toggle('text-stone-500', !active);
                    });
                    root.querySelectorAll('[data-detail-panel]').forEach(function (panel) {
                        panel.classList.toggle('hidden', panel.getAttribute('data-detail-panel') !== target);
                    });
                });
            });

            root.querySelectorAll('[data-detail-option]').forEach(function (input) {
                input.addEventListener('change', function () {
                    if (input.checked && input.getAttribute('data-option-type') === 'single') {
                        root.querySelectorAll('[data-option-group="' + input.getAttribute('data-option-group') + '"]').forEach(function (peer) {
                            if (peer !== input) peer.checked = false;
                        });
                    }
                    sync();
                });
            });

            root.querySelectorAll('[data-detail-qty-action]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var delta = button.getAttribute('data-detail-qty-action') === 'inc' ? 1 : -1;
                    quantity = Math.min(99, Math.max(1, quantity + delta));
                    sync();
                });
            });

            if (noteInput) noteInput.addEventListener('input', sync);
            sync();
        });
    </script>
@endpush
