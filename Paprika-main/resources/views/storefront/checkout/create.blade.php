@extends('storefront.layouts.app')

@section('content')
    @php
        $deliveryFee = 0;
        $discount = 0;
        $total = $subtotal + $deliveryFee - $discount;
        $fulfillment = old('fulfillment_method', 'pickup');
        $payment = old('payment_method', 'viva');
        $selectedBranchId = (string) old('branch_id', $branches->first()?->id);
        $deliveryQuoteUrl = localized_route('checkout.delivery-quote');
        $addressSuggestUrl = localized_route('checkout.address-suggest');
        $addressReverseUrl = localized_route('checkout.address-reverse');
        $voucherPreviewUrl = localized_route('checkout.voucher-preview');
        $voucherClearUrl = localized_route('checkout.voucher-clear');
        $branchRules = $branches->mapWithKeys(fn ($branch) => [
            $branch->id => [
                'name' => $branch->name,
                'city' => $branch->city,
                'address' => $branch->address,
                'acceptsOnline' => (bool) $branch->accepts_online_orders,
                'acceptsPickup' => (bool) $branch->accepts_pickup_orders,
                'acceptsDelivery' => (bool) $branch->accepts_delivery_orders,
                'acceptsOfflinePayment' => (bool) ($branch->accepts_offline_payment ?? true),
                'autoQuote' => (bool) $branch->auto_delivery_quote_enabled,
                'hasOrigin' => $branch->delivery_origin_latitude !== null && $branch->delivery_origin_longitude !== null,
                'minOrder' => (int) $branch->delivery_min_order_amount,
                'freeOrder' => $branch->delivery_free_order_amount !== null ? (int) $branch->delivery_free_order_amount : null,
                'maxDistance' => $branch->delivery_max_distance_km !== null ? (float) $branch->delivery_max_distance_km : null,
                'note' => str_starts_with($branch->delivery_note ?? '', 'site.')
                    ? (__($branch->delivery_note) ?: null)
                    : $branch->delivery_note,
            ],
        ])->all();
    @endphp

    <div class="mx-auto max-w-7xl flex-grow bg-[#FDFBF7] px-4 py-10 text-stone-900 sm:px-6 lg:px-8" id="checkout-form-view">
        <div class="mb-8 space-y-2 text-center">
            <span class="block text-xs font-black uppercase tracking-widest text-[#B91C1C]">{{ __('site.checkout.eyebrow') }}</span>
            <h1 class="text-2xl font-black uppercase italic tracking-tight text-stone-950 sm:text-3xl">{{ __('site.checkout.title') }}</h1>
            <p class="mx-auto max-w-sm text-sm text-stone-500">{{ __('site.checkout.description') }}</p>
        </div>

        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-12">
            <form id="checkout-order-form" method="POST" action="{{ localized_route('checkout.store') }}" class="space-y-6 lg:col-span-7" data-fulfillment-form data-subtotal="{{ $subtotal }}" data-delivery-quote-url="{{ $deliveryQuoteUrl }}" data-address-suggest-url="{{ $addressSuggestUrl }}" data-address-reverse-url="{{ $addressReverseUrl }}" data-voucher-preview-url="{{ $voucherPreviewUrl }}" data-voucher-clear-url="{{ $voucherClearUrl }}" data-selected-voucher-code="{{ $selectedVoucherCode }}" data-msg-select-branch="{{ __('site.delivery_quote.select_branch') }}" data-msg-enter-address="{{ __('site.delivery_quote.enter_address') }}" data-msg-searching-address="{{ __('site.delivery_quote.searching_address') }}" data-msg-calculating="{{ __('site.delivery_quote.calculating') }}" data-msg-could-not-calculate="{{ __('site.delivery_quote.could_not_calculate') }}" data-msg-calculate-fee="{{ __('site.delivery_quote.calculate_fee') }}" data-msg-fee-shipper-confirm="{{ __('site.delivery_quote.fee_shipper_confirm') }}" data-msg-shipping-fee="{{ __('site.delivery_quote.shipping_fee') }}" data-msg-cannot-deliver="{{ __('site.delivery_quote.cannot_deliver') }}" data-msg-browser-no-geolocation="{{ __('site.delivery_quote.browser_no_geolocation') }}" data-msg-getting-location="{{ __('site.delivery_quote.getting_location') }}" data-msg-current-location="{{ __('site.delivery_quote.current_location') }}" data-msg-location-acquired="{{ __('site.delivery_quote.location_acquired') }}" data-msg-location-button="{{ __('site.delivery_quote.location_button') }}" data-msg-location-failed="{{ __('site.delivery_quote.location_failed') }}" data-msg-select-branch-shipping="{{ __('site.delivery_quote.select_branch_shipping') }}" data-msg-pickup-at-branch="{{ __('site.delivery_quote.pickup_at_branch') }}" data-msg-branch-no-delivery="{{ __('site.delivery_quote.branch_no_delivery') }}" data-msg-branch-auto-quote-off="{{ __('site.delivery_quote.branch_auto_quote_off') }}" data-msg-branch-no-origin="{{ __('site.delivery_quote.branch_no_origin') }}" data-msg-min-order="{{ __('site.delivery_quote.min_order') }}" data-msg-max-distance="{{ __('site.delivery_quote.max_distance') }}" data-msg-free-shipping-from="{{ __('site.delivery_quote.free_shipping_from') }}" data-msg-free-shipping="{{ __('site.delivery_quote.free_shipping') }}" data-msg-delivery-unavailable-area="{{ __('site.delivery_quote.delivery_unavailable_area') }}" data-msg-delivery-call-store="{{ __('site.delivery_quote.delivery_call_store') }}" data-msg-min-order-blocked="{{ __('site.delivery_quote.min_order_blocked') }}" data-msg-timeslot-note-prefix="{{ __('site.checkout.time_slot_choose_note_prefix') }}" data-msg-timeslot-note-focus="{{ __('site.checkout.time_slot_choose_note_focus') }}" data-msg-timeslot-note-suffix="{{ __('site.checkout.time_slot_choose_note_suffix') }}" novalidate>
                @csrf
                <input type="hidden" name="proceed_without_quote" value="0" data-proceed-flag>
                <input type="hidden" name="voucher_code" value="{{ $selectedVoucherCode }}" data-voucher-code-input>

                @error('min_order')
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $message }}
                </div>
                @enderror

                <section class="space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-stone-400">{{ __('site.checkout.step_method') }}</h3>
                    @php
                        $firstBranch = $branches->first();
                        $showPickup = $firstBranch && $firstBranch->accepts_pickup_orders;
                        $showDelivery = $firstBranch && $firstBranch->accepts_delivery_orders;
                    @endphp
                    <div class="grid grid-cols-1 gap-3 {{ $showPickup && $showDelivery ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}" data-choice-grid>
                        @if($showPickup)
                        <label class="cursor-pointer rounded-xl border p-3.5 text-left transition {{ $fulfillment === 'pickup' ? 'border-[#064E3B] bg-[#064E3B]/5' : 'border-stone-200 bg-stone-50' }}">
                            <input type="radio" name="fulfillment_method" value="pickup" class="sr-only" @checked($fulfillment === 'pickup')>
                            <span class="block text-xs font-bold uppercase tracking-wide">{{ __('site.checkout.pickup') }}</span>
                            <span class="mt-0.5 block text-[10px] text-stone-400">{{ __('site.checkout.pickup_hint') }}</span>
                        </label>
                        @endif
                        @if($showDelivery)
                        <label class="cursor-pointer rounded-xl border p-3.5 text-left transition {{ $fulfillment === 'delivery' ? 'border-[#064E3B] bg-[#064E3B]/5' : 'border-stone-200 bg-stone-50' }}">
                            <input type="radio" name="fulfillment_method" value="delivery" class="sr-only" @checked($fulfillment === 'delivery')>
                            <span class="block text-xs font-bold uppercase tracking-wide">{{ __('site.checkout.delivery') }}</span>
                            <span class="mt-0.5 block text-[10px] text-stone-400">{{ __('site.checkout.delivery_hint') }}</span>
                        </label>
                        @endif
                        <a href="{{ localized_route('reservations.create') }}" class="flex flex-col justify-center rounded-xl border border-stone-200 bg-stone-50 p-3.5 text-left transition hover:bg-stone-100">
                            <span class="block text-xs font-bold uppercase tracking-wide">{{ __('site.checkout.dine') }}</span>
                            <span class="mt-0.5 block text-[10px] font-bold text-[#B91C1C]">{{ __('site.checkout.dine_link') }}</span>
                        </a>
                    </div>
                </section>

                <section class="space-y-5 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h2 class="border-b border-stone-100 pb-2 text-sm font-extrabold uppercase tracking-wide text-stone-900">{{ __('site.checkout.step_info') }}</h2>

                    @if ($errors->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-medium text-rose-800" role="alert">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.checkout.name') }}</span>
                            <input name="customer_name" value="{{ old('customer_name') }}" required class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition @error('customer_name') border-rose-400 bg-rose-50 focus:border-rose-400 focus:ring-rose-400 @else border-stone-200 bg-stone-50 focus:border-[#064E3B] focus:ring-[#064E3B] @enderror">
                            @error('customer_name')
                            <span class="text-[10px] text-rose-600">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.checkout.phone') }}</span>
                            <input name="customer_phone" value="{{ old('customer_phone') }}" required inputmode="tel" class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition @error('customer_phone') border-rose-400 bg-rose-50 focus:border-rose-400 focus:ring-rose-400 @else border-stone-200 bg-stone-50 focus:border-[#064E3B] focus:ring-[#064E3B] @enderror">
                            @error('customer_phone')
                            <span class="text-[10px] text-rose-600">{{ $message }}</span>
                            @enderror
                        </label>
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.checkout.email') }}</span>
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition @error('customer_email') border-rose-400 bg-rose-50 focus:border-rose-400 focus:ring-rose-400 @else border-stone-200 bg-stone-50 focus:border-[#064E3B] focus:ring-[#064E3B] @enderror">
                            @error('customer_email')
                            <span class="text-[10px] text-rose-600">{{ $message }}</span>
                            @enderror
                        </label>
                        <div class="block space-y-1">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.checkout.branch') }}</span>
                            @if ($branches->count() <= 1 && $branches->first())
                                <input type="hidden" name="branch_id" value="{{ $branches->first()->id }}">
                                <div class="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm font-bold text-stone-900">
                                    {{ $branches->first()->name }}{{ $branches->first()->city ? ' - '.$branches->first()->city : '' }}
                                </div>
                            @else
                                <select name="branch_id" required class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-3 text-sm outline-none focus:border-[#064E3B]">
                                    <option value="">{{ __('site.checkout.branch_choose') }}</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected($selectedBranchId === (string) $branch->id)>{{ $branch->name }}{{ $branch->city ? ' - '.$branch->city : '' }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <label class="block space-y-1">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.checkout.time') }}</span>
                            <input type="time" name="requested_time" value="{{ old('requested_time') }}" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm outline-none">
                        </label>
                    </div>

                    <div data-fulfillment-panel="delivery" @if ($fulfillment !== 'delivery') hidden @endif class="space-y-4">
                        {{-- Approx address for fee calculation --}}
                        <div class="space-y-1">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.checkout.address_approx') }}</span>
                            <div class="relative">
                                <input name="delivery_address" value="{{ old('delivery_address') }}" autocomplete="street-address" placeholder="{{ __('site.checkout.address_placeholder') }}" class="w-full rounded-xl border px-4 py-3 pr-36 text-sm outline-none transition @error('delivery_address') border-rose-400 bg-rose-50 focus:border-rose-400 focus:ring-rose-400 @else border-stone-200 bg-stone-50 focus:border-[#064E3B] focus:ring-[#064E3B] @enderror" data-address-approx-input data-delivery-address-input>
                                @error('delivery_address')
                                <span class="text-[10px] text-rose-600">{{ $message }}</span>
                                @enderror

                                <div class="absolute inset-y-0 right-2 flex items-center gap-2">
                                    <div class="hidden h-4 w-4 animate-spin rounded-full border-2 border-stone-200 border-t-[#064E3B]" aria-hidden="true" data-address-loading></div>
                                    <button type="button" data-delivery-location-button class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-[10px] font-black uppercase tracking-widest text-[#064E3B] transition hover:bg-stone-50">{{ __('site.delivery_quote.location_button') }}</button>
                                    <button type="button" data-address-change-button class="hidden rounded-lg border border-stone-200 bg-white px-3 py-2 text-[10px] font-black uppercase tracking-widest text-[#064E3B] transition hover:bg-stone-50">{{ __('site.checkout.change_address') }}</button>
                                </div>

                                <div class="absolute left-0 right-0 top-full z-20 mt-2 hidden overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg" data-address-suggestions>
                                    <div class="max-h-64 overflow-y-auto">
                                        <div class="hidden px-4 py-3 text-xs font-bold text-stone-500" data-address-loading-row>{{ __('site.delivery_quote.searching_address') }}</div>
                                        <ul class="divide-y divide-stone-100" data-address-suggestions-list></ul>
                                    </div>
                                </div>
                            </div>
                            <p data-address-approx-hint class="text-[10px] leading-3 text-stone-400">{{ __('site.checkout.address_approx_hint') }}</p>
                        </div>
                        <p data-delivery-approx-location class="hidden text-[11px] leading-4 text-stone-400"><span data-delivery-approx-icon></span> <span data-delivery-approx-text></span></p>

                        {{-- Hidden fields --}}
                        <input type="hidden" name="delivery_latitude" value="{{ old('delivery_latitude') }}" data-delivery-latitude>
                        <input type="hidden" name="delivery_longitude" value="{{ old('delivery_longitude') }}" data-delivery-longitude>
                        <input type="hidden" name="delivery_place_id" value="{{ old('delivery_place_id') }}" data-delivery-place-id>
                        <input type="hidden" name="delivery_distance_km" value="{{ old('delivery_distance_km') }}" data-delivery-distance>
                        <input type="hidden" name="delivery_quote_address" data-delivery-quote-address>
                        <input type="hidden" name="delivery_quote_latitude" data-delivery-quote-latitude>
                        <input type="hidden" name="delivery_quote_longitude" data-delivery-quote-longitude>
                        <input type="hidden" name="delivery_quote_place_id" data-delivery-quote-place-id>
                        <input type="hidden" name="delivery_address_final" data-delivery-address-final-hidden>

                        {{-- Exact delivery address (always visible, editable after confirm) --}}
                        <div data-address-section-delivery class="space-y-1">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.checkout.address_delivery') }}</span>
                            <input name="delivery_address_final" autocomplete="street-address" placeholder="{{ __('site.checkout.address_delivery_placeholder') }}" class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition @error('delivery_address_final') border-rose-400 bg-rose-50 focus:border-rose-400 focus:ring-rose-400 @else border-stone-200 bg-stone-50 focus:border-[#064E3B] focus:ring-[#064E3B] @enderror" data-delivery-address-final>
                            @error('delivery_address_final')
                            <span class="text-[10px] text-rose-600">{{ $message }}</span>
                            @enderror
                            <p class="text-[10px] leading-3 text-stone-400">{{ __('site.checkout.address_locked_note') }}</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-[1fr_auto]">
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-xs leading-5 text-emerald-950">
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#064E3B]">{{ __('site.delivery_quote.title') }}</p>
                                <p data-delivery-rule-summary class="mt-1 text-stone-600">{{ __('site.delivery_quote.hint') }}</p>
                                <p data-delivery-quote-summary class="mt-2 hidden font-bold text-[#064E3B]"></p>
                                <p data-delivery-branch-note class="mt-2 hidden text-[11px] text-stone-500"></p>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-1">
                                <button type="button" data-delivery-quote-button class="rounded-xl bg-[#064E3B] px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-[#043829]">{{ __('site.delivery_quote.calculate_fee') }}</button>
                                <button type="button" data-delivery-confirm-button class="hidden rounded-xl bg-[#064E3B] px-5 py-3 text-xs font-black uppercase tracking-widest text-white shadow-sm transition hover:bg-[#043829]">{{ __('site.checkout.confirm_address') }}</button>
                            </div>
                        </div>
                        <div data-delivery-message class="hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800"></div>
                    </div>

                    <label class="block space-y-1">
                        <span class="text-xs font-bold text-stone-600">{{ __('site.checkout.note') }}</span>
                        <textarea name="note" rows="3" class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm outline-none">{{ old('note') }}</textarea>
                    </label>
                </section>

                <section class="space-y-4 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xs font-extrabold uppercase tracking-wider text-stone-400">{{ __('site.checkout.step_payment') }}</h3>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-choice-grid>
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border p-3.5 text-left transition {{ $payment === 'offline' ? 'border-[#064E3B] bg-[#064E3B]/5' : 'border-stone-200 bg-stone-50' }}" data-payment-offline-card>
                            <input type="radio" name="payment_method" value="offline" class="mt-1" @checked($payment === 'offline')>
                            <div>
                                <span class="block text-xs font-bold uppercase">{{ __('site.checkout.pay_offline') }}</span>
                                <span class="mt-0.5 block text-[10px] text-stone-400">{{ __('site.checkout.pay_offline_hint') }}</span>
                            </div>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2.5 rounded-xl border p-3.5 text-left transition {{ $payment === 'viva' ? 'border-[#064E3B] bg-[#064E3B]/5' : 'border-stone-200 bg-stone-50' }}">
                            <input type="radio" name="payment_method" value="viva" class="mt-1" @checked($payment === 'viva')>
                            <div>
                                <span class="block text-xs font-bold uppercase">{{ __('site.checkout.pay_card') }}</span>
                                <span class="mt-0.5 block text-[10px] text-stone-400">{{ __('site.checkout.pay_card_hint') }}</span>
                            </div>
                        </label>
                    </div>
                </section>

                <div class="hidden rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-xs text-amber-800" data-shipping-uncalculated-warning>
                    {!! __('site.checkout.shipping_uncalculated_warning') !!}
                </div>

            </form>

            <aside class="lg:col-span-5">
                <div class="space-y-5 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm lg:sticky lg:top-28">
                    <h3 class="font-heading text-xs font-extrabold uppercase tracking-wider text-[#064E3B]">{{ __('site.checkout.summary') }}</h3>
                    <div class="max-h-[300px] space-y-4 overflow-y-auto pr-2 no-scrollbar" data-checkout-summary-items>
                        @foreach ($items as $item)
                            <div class="flex items-start gap-4 border-b border-stone-100 pb-4" data-dish-name="{{ $item['dish']->localized('name') }}">
                                <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-stone-50">
                                    <img src="{{ media_variant_url($item['dish']->image, 'thumb') }}" alt="{{ $item['dish']->localized('name') }}" class="h-full w-full object-cover" loading="lazy">
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between gap-3">
                                        <h4 class="text-xs font-bold uppercase text-stone-900">{{ $item['dish']->localized('name') }}</h4>
                                        <span class="hidden shrink-0 rounded-full bg-amber-50 px-2 py-1 text-[9px] font-black uppercase tracking-widest text-amber-800 border border-amber-200" data-timeslot-badge>
                                            {{ __('site.cart.unavailable_time_slot') }}
                                        </span>
                                    </div>
                                    @if ($item['summary'] !== __('site.cart.standard_recipe') && $item['summary'] !== 'Công thức tiêu chuẩn')
                                        <p class="mt-1 text-[10px] leading-4 text-stone-500">{{ $item['summary'] }}</p>
                                    @endif
                                    <div class="mt-1 flex justify-between text-[10px]">
                                        <span>{{ __('site.checkout.qty') }}: {{ $item['quantity'] }}</span>
                                        <strong class="font-mono">{{ format_money($item['line_total']) }}</strong>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="space-y-3 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4" data-voucher-panel>
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-[#064E3B]">{{ __('site.voucher.checkout_title') }}</p>
                                <p class="mt-1 text-[10px] text-stone-500">{{ __('site.voucher.checkout_hint') }}</p>
                            </div>
                            <button type="button" class="hidden text-[10px] font-black uppercase tracking-widest text-rose-700 hover:text-rose-900" data-voucher-clear>{{ __('site.voucher.remove') }}</button>
                        </div>
                        @if ($publicVouchers->isNotEmpty())
                            <div class="space-y-2" data-voucher-options>
                                @foreach ($publicVouchers as $voucher)
                                    <button type="button" data-voucher-option data-voucher-code="{{ $voucher->code }}" data-voucher-id="{{ $voucher->id }}" class="w-full rounded-xl border border-emerald-100 bg-white px-3 py-2 text-left transition hover:border-[#064E3B]">
                                        <span class="flex items-center justify-between gap-3">
                                            <span class="font-mono text-xs font-black text-emerald-950">{{ $voucher->code }}</span>
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[9px] font-black uppercase text-amber-800">{{ $voucher->displayValue() }}</span>
                                        </span>
                                        <span class="mt-1 block text-xs font-bold text-stone-800">{{ $voucher->localized('name') }}</span>
                                        @if ($voucher->localized('description'))
                                            <span class="mt-0.5 block text-[10px] leading-4 text-stone-500">{{ $voucher->localized('description') }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                            <input type="text" inputmode="text" autocomplete="off" class="rounded-xl border border-emerald-100 bg-white px-3 py-2 text-xs font-bold uppercase tracking-wider outline-none focus:border-[#064E3B]" placeholder="{{ __('site.voucher.code_placeholder') }}" data-voucher-manual-input>
                            <button type="button" class="rounded-xl bg-[#064E3B] px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white transition hover:bg-[#043829]" data-voucher-apply>{{ __('site.voucher.apply') }}</button>
                        </div>
                        <p class="hidden text-[11px] font-semibold" data-voucher-message></p>
                    </div>
                    <div class="space-y-2.5 border-t border-stone-200 pt-2 text-xs">
                        <div class="flex justify-between text-[10px] uppercase tracking-widest text-stone-500">
                            <span>{{ __('site.checkout.subtotal') }}</span>
                            <span class="font-extrabold text-stone-800">{{ format_money($subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-[10px] uppercase tracking-widest text-stone-500">
                            <span>{{ __('site.checkout.delivery_fee') }}</span>
                            <span class="font-extrabold text-stone-800" data-summary-delivery>{{ __('site.checkout.delivery_pending') }}</span>
                        </div>
                        <div class="hidden flex justify-between text-[10px] uppercase tracking-widest text-emerald-700" data-summary-discount-row>
                            <span>{{ __('site.voucher.discount') }}</span>
                            <span class="font-extrabold" data-summary-discount>-{{ format_money(0) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-stone-200 pt-3 text-sm font-black uppercase">
                            <span>{{ __('site.checkout.total') }}</span>
                            <span class="font-mono text-[#064E3B]" data-summary-total>{{ format_money($total) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl border border-stone-100 bg-stone-50 p-3 text-[10px] text-stone-500">
                        @include('storefront.partials.icon', ['name' => 'shield', 'class' => 'w-4 h-4 text-[#064E3B]'])
                        <span>{{ __('site.checkout.privacy') }}</span>
                    </div>
                    <div class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold leading-5 text-amber-800" data-timeslot-warning-wrap>
                        <div class="flex flex-col gap-3">
                            <div data-timeslot-warning></div>
                            <a href="{{ localized_route('cart.index') }}" class="inline-flex w-full items-center justify-center rounded-lg border border-amber-300 bg-white px-3 py-2 text-[10px] font-black uppercase tracking-widest text-amber-900 transition hover:bg-amber-100 sm:w-auto">
                                {{ __('site.checkout.back_to_cart') }}
                            </a>
                        </div>
                    </div>
                    <button type="submit" form="checkout-order-form" class="w-full rounded-xl bg-[#B91C1C] py-4 text-xs font-black uppercase tracking-widest text-white shadow-lg transition hover:bg-[#991B1B]" data-checkout-submit>
                        {{ __('site.checkout.submit') }} - <span data-submit-total>{{ format_money($total) }}</span>
                    </button>
                </div>
            </aside>
        </div>
    </div>
@endsection

{{-- Modal: shipping not calculated warning --}}
<div class="hidden fixed inset-0 z-[100] items-center justify-center" data-shipping-modal id="shipping-uncalculated-modal">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-modal-close></div>
    <div class="relative z-10 mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-black uppercase italic text-[#B91C1C]">{!! __('site.checkout.shipping_uncalculated_modal_title') !!}</h3>
            <button type="button" data-modal-close class="rounded-full p-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-900">
                @include('storefront.partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
            </button>
        </div>
        <div class="mb-6 space-y-3 text-sm leading-relaxed text-stone-600">
            {!! __('site.checkout.shipping_uncalculated_modal_body') !!}
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <button type="button" data-modal-btn-calc class="flex-1 rounded-xl border border-stone-200 bg-stone-50 py-3 text-xs font-bold uppercase tracking-widest text-stone-700 transition hover:bg-stone-100">
                {{ __('site.checkout.shipping_uncalculated_modal_btn_calc') }}
            </button>
            <button type="button" data-modal-btn-proceed class="flex-1 rounded-xl bg-[#B91C1C] py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-[#991B1B]">
                {{ __('site.checkout.shipping_uncalculated_modal_btn_proceed') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (() => {
            const branchRules = {{ \Illuminate\Support\Js::from($branchRules) }};
            const form = document.querySelector('[data-fulfillment-form]');
            if (!form) return;

            const subtotal = Number(form.dataset.subtotal || 0);
            const quoteUrl = form.dataset.deliveryQuoteUrl;
            const suggestUrl = form.dataset.addressSuggestUrl;
            const reverseUrl = form.dataset.addressReverseUrl;
            const csrf = form.querySelector('input[name="_token"]')?.value;
            const branchInput = form.querySelector('[name="branch_id"]');
            const latitudeInput = form.querySelector('[data-delivery-latitude]');
            const longitudeInput = form.querySelector('[data-delivery-longitude]');
            const placeIdInput = form.querySelector('[data-delivery-place-id]');
            const distanceInput = form.querySelector('[data-delivery-distance]');
            const deliveryPanel = form.querySelector('[data-fulfillment-panel="delivery"]');
            const deliveryMessage = form.querySelector('[data-delivery-message]');
            const ruleSummary = form.querySelector('[data-delivery-rule-summary]');
            const branchNote = form.querySelector('[data-delivery-branch-note]');
            const quoteSummary = form.querySelector('[data-delivery-quote-summary]');
            const quoteButton = form.querySelector('[data-delivery-quote-button]');
            const confirmButton = form.querySelector('[data-delivery-confirm-button]');
            const locationButton = form.querySelector('[data-delivery-location-button]');
            const addressFinalInput = form.querySelector('[data-delivery-address-final]');
            const addressFinalHidden = form.querySelector('[data-delivery-address-final-hidden]');
            const addressApproxInput = form.querySelector('[data-address-approx-input]');
            const addressApproxHint = form.querySelector('[data-address-approx-hint]');
            const addressChangeButton = form.querySelector('[data-address-change-button]');
            const quoteAddressInput = form.querySelector('[data-delivery-quote-address]');
            const quoteLatitudeInput = form.querySelector('[data-delivery-quote-latitude]');
            const quoteLongitudeInput = form.querySelector('[data-delivery-quote-longitude]');
            const quotePlaceIdInput = form.querySelector('[data-delivery-quote-place-id]');
            const deliverySummary = document.querySelector('[data-summary-delivery]');
            const totalSummary = document.querySelector('[data-summary-total]');
            const submitTotal = document.querySelector('[data-submit-total]');
            const submitBtn = document.querySelector('[data-checkout-submit]');
            const suggestionsBox = form.querySelector('[data-address-suggestions]');
            const suggestionsList = form.querySelector('[data-address-suggestions-list]');
            const approxLocation = form.querySelector('[data-delivery-approx-location]');
            const approxText = form.querySelector('[data-delivery-approx-text]');
            const msg = form.dataset;
            const deliveryPendingText = @json(__('site.checkout.delivery_pending'));
            const voucherPreviewUrl = form.dataset.voucherPreviewUrl;
            const voucherClearUrl = form.dataset.voucherClearUrl;
            const voucherCodeInput = form.querySelector('[data-voucher-code-input]');
            const voucherManualInput = document.querySelector('[data-voucher-manual-input]');
            const voucherApplyButton = document.querySelector('[data-voucher-apply]');
            const voucherClearButton = document.querySelector('[data-voucher-clear]');
            const voucherMessage = document.querySelector('[data-voucher-message]');
            const voucherDiscountRow = document.querySelector('[data-summary-discount-row]');
            const voucherDiscountSummary = document.querySelector('[data-summary-discount]');
            const voucherOptions = document.querySelectorAll('[data-voucher-option]');

            const debounce = (fn, wait = 300) => {
                let t;
                return (...args) => {
                    window.clearTimeout(t);
                    t = window.setTimeout(() => fn(...args), wait);
                };
            };

            // --- Timeslot availability guard (cart items vs requested time) ---
            const availabilityUrl = @json(localized_route('checkout.availability'));
            const requestedTimeInput = form.querySelector('[name="requested_time"]');
            const timeslotWarning = document.querySelector('[data-timeslot-warning]');
            const timeslotWarningWrap = document.querySelector('[data-timeslot-warning-wrap]');
            const summaryItemsRoot = document.querySelector('[data-checkout-summary-items]');

            let timeslotBlocked = false;
            let availabilityRequestId = 0;

            function escapeTimeslotHtml(value) {
                return String(value || '').replace(/[&<>"']/g, (ch) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                }[ch]));
            }

            function setTimeslotWarning(message, interactiveNote = false) {
                const text = (message || '').trim();
                if (timeslotWarning) {
                    if (text && interactiveNote) {
                        timeslotWarning.innerHTML = `${escapeTimeslotHtml(text)} <span>${escapeTimeslotHtml(msg.msgTimeslotNotePrefix || '')}<button type="button" data-timeslot-focus class="font-black underline decoration-amber-500 underline-offset-2 hover:text-amber-950 focus:outline-none focus:ring-2 focus:ring-amber-400 rounded-sm"><strong>${escapeTimeslotHtml(msg.msgTimeslotNoteFocus || '')}</strong></button>${escapeTimeslotHtml(msg.msgTimeslotNoteSuffix || '')}</span>`;
                    } else {
                        timeslotWarning.textContent = text;
                    }
                }
                if (timeslotWarningWrap) timeslotWarningWrap.classList.toggle('hidden', !text);
            }

            timeslotWarning?.addEventListener('click', (event) => {
                const focusButton = event.target.closest('[data-timeslot-focus]');
                if (!focusButton || !requestedTimeInput) return;

                requestedTimeInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(() => {
                    requestedTimeInput.focus({ preventScroll: true });
                    try {
                        requestedTimeInput.showPicker?.();
                    } catch (e) {}
                }, 250);
            });

            function updateSummaryTimeslotBadges(unavailableNames) {
                if (!summaryItemsRoot) return;
                const list = Array.isArray(unavailableNames) ? unavailableNames : [];
                const blockedSet = new Set(list.map(v => String(v || '').trim()).filter(Boolean));

                summaryItemsRoot.querySelectorAll('[data-dish-name]').forEach((row) => {
                    const name = String(row.getAttribute('data-dish-name') || '').trim();
                    const isBlocked = name && blockedSet.has(name);

                    row.classList.toggle('opacity-70', isBlocked);

                    const badge = row.querySelector('[data-timeslot-badge]');
                    if (badge) badge.classList.toggle('hidden', !isBlocked);
                });
            }

            async function refreshTimeslotAvailability() {
                if (!availabilityUrl || !branchInput) return;
                const branchId = branchInput.value;
                if (!branchId) return;

                const requestId = ++availabilityRequestId;

                const fd = new FormData();
                fd.append('branch_id', branchId);
                if (requestedTimeInput?.value) fd.append('requested_time', requestedTimeInput.value);

                try {
                    const response = await fetch(availabilityUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: fd,
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (requestId !== availabilityRequestId) return;

                    if (!response.ok) {
                        timeslotBlocked = false;
                        setTimeslotWarning('');
                        updateSummaryTimeslotBadges([]);
                        render();
                        return;
                    }

                    const unavailableNames = Array.isArray(payload.unavailable_names) ? payload.unavailable_names : [];
                    timeslotBlocked = !!payload.blocked;

                    setTimeslotWarning(timeslotBlocked ? (payload.message || payload.items_message || '') : '', !!payload.interactive_note);
                    updateSummaryTimeslotBadges(unavailableNames);
                    render();
                } catch (e) {
                    if (requestId !== availabilityRequestId) return;
                    timeslotBlocked = false;
                    setTimeslotWarning('');
                    updateSummaryTimeslotBadges([]);
                    render();
                }
            }

            const requestAvailabilityIfReady = debounce(() => {
                refreshTimeslotAvailability();
            }, 250);

            // Initial check
            refreshTimeslotAvailability();

            // --- End timeslot availability guard ---

            let quoteState = null;
            let voucherState = null;
            let selectedVoucherCode = (form.dataset.selectedVoucherCode || '').trim().toUpperCase();
            let voucherRequestId = 0;
            let activeSuggestionRequest = 0;
            let suppressAddressInputHandler = false;
            let addressLocked = false;
            let modalProceeding = false;

            const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (ch) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[ch]));

            const hideSuggestions = () => {
                if (suggestionsBox) suggestionsBox.classList.add('hidden');
                if (suggestionsList) suggestionsList.innerHTML = '';
            };

            const showSuggestions = () => {
                if (suggestionsBox) suggestionsBox.classList.remove('hidden');
            };

            const setCoords = ({ latitude, longitude, place_id, formatted_address }) => {
                if (latitudeInput) latitudeInput.value = latitude ?? '';
                if (longitudeInput) longitudeInput.value = longitude ?? '';
                if (placeIdInput) placeIdInput.value = place_id ?? '';

                const formatted = formatted_address || '';
                const matchesInput = formatted && (formatted === (addressApproxInput?.value || '').trim());

                if (approxText) approxText.textContent = matchesInput ? '' : formatted;
                if (approxLocation) {
                    approxLocation.classList.toggle('hidden', !formatted || matchesInput);
                }
            };

            const clearCoords = () => {
                setCoords({ latitude: '', longitude: '', place_id: '', formatted_address: '' });
                if (latitudeInput) latitudeInput.value = '';
                if (longitudeInput) longitudeInput.value = '';
                if (placeIdInput) placeIdInput.value = '';
                if (quoteLatitudeInput) quoteLatitudeInput.value = '';
                if (quoteLongitudeInput) quoteLongitudeInput.value = '';
                if (quotePlaceIdInput) quotePlaceIdInput.value = '';
                if (quoteAddressInput) quoteAddressInput.value = '';
                if (approxLocation) approxLocation.classList.add('hidden');
            };

            const formatMoney = (amount) => new Intl.NumberFormat(@json(app()->getLocale()), {
                style: 'currency',
                currency: 'EUR',
            }).format((Number(amount) || 0) / 100);

            const selectedMethod = () => form.querySelector('[name="fulfillment_method"]:checked')?.value || 'pickup';
            const selectedBranch = () => branchRules[branchInput?.value] || null;
            const currentShippingFee = () => {
                if (selectedMethod() !== 'delivery') return 0;
                if (!quoteState?.available || quoteState?.manual) return 0;

                return Number(quoteState.fee || 0);
            };

            const setMessage = (message, type = 'error') => {
                if (!deliveryMessage) return;
                deliveryMessage.textContent = message || '';
                deliveryMessage.classList.toggle('hidden', !message);
                deliveryMessage.classList.toggle('border-rose-200', type === 'error');
                deliveryMessage.classList.toggle('bg-rose-50', type === 'error');
                deliveryMessage.classList.toggle('text-rose-800', type === 'error');
                deliveryMessage.classList.toggle('border-emerald-200', type !== 'error');
                deliveryMessage.classList.toggle('bg-emerald-50', type !== 'error');
                deliveryMessage.classList.toggle('text-emerald-900', type !== 'error');
            };

            const setVoucherMessage = (message, type = 'success') => {
                if (!voucherMessage) return;
                voucherMessage.textContent = message || '';
                voucherMessage.classList.toggle('hidden', !message);
                voucherMessage.classList.toggle('text-emerald-700', type === 'success');
                voucherMessage.classList.toggle('text-rose-700', type !== 'success');
            };

            const updateVoucherOptionStyles = () => {
                voucherOptions.forEach((button) => {
                    const active = selectedVoucherCode && button.dataset.voucherCode === selectedVoucherCode;
                    button.classList.toggle('border-[#064E3B]', active);
                    button.classList.toggle('bg-white', !active);
                    button.classList.toggle('bg-emerald-100/80', active);
                });

                if (voucherClearButton) {
                    voucherClearButton.classList.toggle('hidden', !selectedVoucherCode && !voucherState);
                }
            };

            function clearVoucherState(sendRequest = false) {
                selectedVoucherCode = '';
                voucherState = null;
                if (voucherCodeInput) voucherCodeInput.value = '';
                if (voucherManualInput) voucherManualInput.value = '';
                setVoucherMessage('');
                updateVoucherOptionStyles();
                render();

                if (sendRequest && voucherClearUrl) {
                    fetch(voucherClearUrl, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                    }).catch(() => {});
                }
            }

            async function refreshVoucher(code = selectedVoucherCode, showErrors = false) {
                const normalizedCode = String(code || '').trim().toUpperCase();
                if (!voucherPreviewUrl || !normalizedCode) {
                    voucherState = null;
                    if (voucherCodeInput) voucherCodeInput.value = '';
                    setVoucherMessage('');
                    updateVoucherOptionStyles();
                    render();
                    return;
                }

                selectedVoucherCode = normalizedCode;
                const requestId = ++voucherRequestId;

                try {
                    const response = await fetch(voucherPreviewUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            voucher_code: normalizedCode,
                            branch_id: branchInput?.value || null,
                            fulfillment_method: selectedMethod(),
                            shipping_fee: currentShippingFee(),
                        }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (requestId !== voucherRequestId) return;

                    if (payload.valid) {
                        voucherState = payload;
                        if (voucherCodeInput) voucherCodeInput.value = payload.voucher?.code || normalizedCode;
                        setVoucherMessage(payload.message || '', 'success');
                    } else {
                        voucherState = null;
                        if (voucherCodeInput) voucherCodeInput.value = '';
                        if (showErrors || payload.voucher?.code === normalizedCode) {
                            setVoucherMessage(payload.message || @json(__('site.voucher.not_available')), 'error');
                        }
                    }
                } catch {
                    if (requestId !== voucherRequestId) return;
                    voucherState = null;
                    if (voucherCodeInput) voucherCodeInput.value = '';
                    if (showErrors) setVoucherMessage(@json(__('site.voucher.preview_failed')), 'error');
                }

                updateVoucherOptionStyles();
                render();
            }

            function applyManualVoucher() {
                const code = String(voucherManualInput?.value || '').trim();
                if (!code) return;

                refreshVoucher(code, true);
            }

            const setAddressLoading = (isLoading) => {
                const spinner = form.querySelector('[data-address-loading]');
                const loadingRow = form.querySelector('[data-address-loading-row]');

                if (spinner) spinner.classList.toggle('hidden', !isLoading);
                if (loadingRow) loadingRow.classList.toggle('hidden', !isLoading);
            };

            const lockAddress = () => {
                if (!quoteState) return;

                // Write quote coords to locked _quote_* fields
                if (quoteAddressInput) quoteAddressInput.value = quoteState.formatted_address || addressApproxInput?.value || '';
                if (quoteLatitudeInput && quoteState.latitude !== null) quoteLatitudeInput.value = quoteState.latitude;
                if (quoteLongitudeInput && quoteState.longitude !== null) quoteLongitudeInput.value = quoteState.longitude;
                if (quotePlaceIdInput && quoteState.place_id) quotePlaceIdInput.value = quoteState.place_id;

                // Prefill final delivery address with approx address
                if (addressFinalInput) addressFinalInput.value = addressApproxInput?.value || '';
                if (addressFinalHidden) addressFinalHidden.value = addressApproxInput?.value || '';

                // Lock approx input: disable it, hide location button, show change button, hide hint
                if (addressApproxInput) {
                    addressApproxInput.disabled = true;
                    addressApproxInput.classList.remove('bg-stone-50', 'focus:border-[#064E3B]', 'focus:ring-1', 'focus:ring-[#064E3B]');
                    addressApproxInput.classList.add('bg-emerald-50/40', 'text-stone-500', 'cursor-not-allowed');
                }
                if (locationButton) locationButton.classList.add('hidden');
                if (addressChangeButton) addressChangeButton.classList.remove('hidden');
                if (addressApproxHint) addressApproxHint.classList.add('hidden');
                hideSuggestions();

                // Hide confirm button (fee is now locked in)
                if (confirmButton) confirmButton.classList.add('hidden');

                addressLocked = true;
            };

            const unlockAddress = () => {
                // Clear locked fields
                if (quoteAddressInput) quoteAddressInput.value = '';
                if (quoteLatitudeInput) quoteLatitudeInput.value = '';
                if (quoteLongitudeInput) quoteLongitudeInput.value = '';
                if (quotePlaceIdInput) quotePlaceIdInput.value = '';
                if (addressFinalInput) addressFinalInput.value = '';
                if (addressFinalHidden) addressFinalHidden.value = '';

                // Restore approx input: enable it, show location button, hide change button, show hint
                if (addressApproxInput) {
                    addressApproxInput.disabled = false;
                    addressApproxInput.classList.add('bg-stone-50', 'focus:border-[#064E3B]', 'focus:ring-1', 'focus:ring-[#064E3B]');
                    addressApproxInput.classList.remove('bg-emerald-50/40', 'text-stone-500', 'cursor-not-allowed');
                }
                if (locationButton) locationButton.classList.remove('hidden');
                if (addressChangeButton) addressChangeButton.classList.add('hidden');
                if (addressApproxHint) addressApproxHint.classList.remove('hidden');

                // Restore quote button
                if (quoteButton) {
                    quoteButton.classList.remove('hidden');
                    quoteButton.disabled = false;
                    quoteButton.textContent = msg.msgCalculateFee;
                }

                addressLocked = false;
            };

            const clearQuote = () => {
                quoteState = null;
                if (distanceInput) distanceInput.value = '';
                if (quoteSummary) {
                    quoteSummary.textContent = '';
                    quoteSummary.classList.add('hidden');
                }
                if (addressLocked) unlockAddress();
                refreshVoucher(selectedVoucherCode, false);
            };

            const applyQuote = (payload) => {
                quoteState = payload;
                // Write to locked _quote_* fields (not main fields, which stay for fallback)
                if (quoteAddressInput) quoteAddressInput.value = payload.formatted_address || addressApproxInput?.value || '';
                if (quoteLatitudeInput && payload.latitude !== null) quoteLatitudeInput.value = payload.latitude || '';
                if (quoteLongitudeInput && payload.longitude !== null) quoteLongitudeInput.value = payload.longitude || '';
                if (quotePlaceIdInput && payload.place_id) quotePlaceIdInput.value = payload.place_id;
                if (distanceInput && payload.distance_km !== null) distanceInput.value = payload.distance_km || '';
                // Update approx location display text (does not touch form fields)
                setCoords({
                    latitude: payload.latitude,
                    longitude: payload.longitude,
                    place_id: payload.place_id,
                    formatted_address: payload.formatted_address || null,
                });

                if (quoteSummary) {
                    if (payload.manual) {
                        quoteSummary.textContent = payload.message || msg.msgFeeShipperConfirm;
                    } else {
                        const distance = payload.distance_label ? `${payload.distance_label} - ` : '';
                        const isFree = (Number(payload.fee || 0) === 0) && !payload.manual;
                        if (isFree) {
                            const zoneLabel = (payload.zone_label || msg.msgFreeShipping || '').trim();
                            const distanceKm = payload.distance_km !== null ? Number(payload.distance_km) : null;
                            const isUnder1km = (distanceKm !== null) && Number.isFinite(distanceKm) && distanceKm < 1;

                            if (isUnder1km) {
                                quoteSummary.textContent = `${distance}${zoneLabel}: ${msg.msgFreeShipping}`;
                            } else {
                                quoteSummary.textContent = `${distance}${zoneLabel}`;
                            }
                        } else {
                            quoteSummary.textContent = `${distance}${payload.zone_label || msg.msgShippingFee}: ${payload.fee_formatted}`;
                        }
                    }
                    quoteSummary.classList.remove('hidden');
                }

                const blockedDeliveryHelp = [
                    msg.msgDeliveryUnavailableArea,
                    msg.msgDeliveryCallStore,
                    selectedBranch()?.phone ? `${msg.msgCall}: ${selectedBranch().phone}` : '',
                ].filter(Boolean).join(' ');
                const baseMessage = payload.available ? (payload.message || '') : (payload.message || msg.msgCannotDeliver);
                const finalMessage = (!payload.available && blockedDeliveryHelp)
                    ? [baseMessage, blockedDeliveryHelp].filter(Boolean).join(' ')
                    : baseMessage;

                setMessage(finalMessage, payload.available ? 'success' : 'error');

                // Show confirm button when fee is available
                if (payload.available && confirmButton) {
                    confirmButton.classList.remove('hidden');
                }

                render();
                refreshVoucher(selectedVoucherCode, false);
            };

            const requestQuote = async () => {
                const branch = selectedBranch();
                if (!branch) {
                    setMessage(msg.msgSelectBranch, 'error');
                    return;
                }
                if (!branch.acceptsDelivery) {
                    setMessage(msg.msgBranchNoDelivery, 'error');
                    return;
                }
                if (selectedMethod() !== 'delivery') return;
                if (!addressApproxInput?.value.trim() && !latitudeInput?.value && !longitudeInput?.value) {
                    setMessage(msg.msgEnterAddress, 'error');
                    return;
                }

                quoteButton.disabled = true;
                quoteButton.textContent = msg.msgCalculating;
                setAddressLoading(true);

                try {
                    const response = await fetch(quoteUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: new FormData(form),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || msg.msgCouldNotCalculate);
                    applyQuote(payload);
                } catch (error) {
                    clearQuote();
                    setMessage(error.message || msg.msgCouldNotCalculate, 'error');
                    render();
                } finally {
                    quoteButton.disabled = false;
                    quoteButton.textContent = msg.msgCalculateFee;
                    setAddressLoading(false);
                }
            };

            const requestQuoteIfReady = debounce(() => {
                if (selectedMethod() !== 'delivery') return;
                const branch = selectedBranch();
                // Only auto-quote when delivery is configured.
                if (!branch?.autoQuote) return;
                if (!branch?.acceptsDelivery) return;
                if (!branch?.hasOrigin) return;
                if (!addressApproxInput?.value.trim() && !latitudeInput?.value && !longitudeInput?.value) return;
                requestQuote();
            }, 350);

            const fetchSuggestions = debounce(async () => {
                if (selectedMethod() !== 'delivery') return;
                const branch = selectedBranch();
                if (!branch) return;
                if (!branch.acceptsDelivery) return;

                const q = addressApproxInput?.value?.trim();
                if (!q || q.length < 3) {
                    setAddressLoading(false);
                    hideSuggestions();
                    return;
                }
                if (!suggestUrl) return;

                const requestId = ++activeSuggestionRequest;
                setAddressLoading(true);

                try {
                    const response = await fetch(suggestUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ q, branch_id: branchInput?.value || null }),
                    });

                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'Could not fetch suggestions');
                    if (requestId !== activeSuggestionRequest) return;

                    const suggestions = Array.isArray(payload.suggestions) ? payload.suggestions : [];
                    if (!suggestions.length) {
                        hideSuggestions();
                        return;
                    }

                    if (!suggestionsList) return;
                    suggestionsList.innerHTML = suggestions.map((s, idx) => {
                        const formatted = escapeHtml(s.formatted);
                        return `
                            <li>
                                <button type="button" class="w-full px-4 py-3 text-left text-sm hover:bg-stone-50" data-suggestion-idx="${idx}">
                                    <div class="text-xs font-bold text-stone-900">${formatted}</div>
                                </button>
                            </li>
                        `;
                    }).join('');

                    suggestionsList.querySelectorAll('button[data-suggestion-idx]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            const idx = Number(btn.dataset.suggestionIdx);
                            const picked = suggestions[idx];
                            if (!picked) return;

                            suppressAddressInputHandler = true;
                            addressApproxInput.value = picked.formatted || '';
                            setCoords({
                                latitude: picked.latitude,
                                longitude: picked.longitude,
                                place_id: picked.place_id,
                                formatted_address: picked.formatted || null,
                            });
                            suppressAddressInputHandler = false;

                            hideSuggestions();
                            clearQuote();
                            setMessage('', 'success');
                            render();
                            requestQuoteIfReady();
                        });
                    });

                    showSuggestions();
                } catch {
                    hideSuggestions();
                } finally {
                    if (requestId === activeSuggestionRequest) {
                        setAddressLoading(false);
                    }
                }
            }, 300);

            const reverseGeocodeAndQuote = async (latitude, longitude) => {
                if (!reverseUrl) {
                    requestQuoteIfReady();
                    return;
                }

                try {
                    const response = await fetch(reverseUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ latitude, longitude }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || 'Reverse geocode failed');

                    suppressAddressInputHandler = true;
                    if (payload.formatted_address) {
                        addressApproxInput.value = payload.formatted_address;
                    }
                    setCoords({
                        latitude: latitude,
                        longitude: longitude,
                        place_id: (payload.place_id && payload.place_id.length <= 255) ? payload.place_id : '',
                        formatted_address: payload.formatted_address ?? null,
                    });
                    suppressAddressInputHandler = false;

                    clearQuote();
                    setMessage(payload.formatted_address ? '' : msg.msgLocationAcquired, 'success');
                    render();
                    requestQuoteIfReady();
                } catch {
                    setMessage(msg.msgLocationAcquired, 'success');
                    clearQuote();
                    render();
                    requestQuoteIfReady();
                }
            };

            const useCurrentLocation = () => {
                if (!navigator.geolocation) {
                    setMessage(msg.msgBrowserNoGeolocation, 'error');
                    return;
                }

                locationButton.disabled = true;
                locationButton.textContent = msg.msgGettingLocation;
                navigator.geolocation.getCurrentPosition((position) => {
                    const lat = Number(position.coords.latitude.toFixed(7));
                    const lng = Number(position.coords.longitude.toFixed(7));
                    setCoords({
                        latitude: lat,
                        longitude: lng,
                        place_id: '',
                        formatted_address: msg.msgCurrentLocation,
                    });

                    setMessage(msg.msgLocationAcquired, 'success');
                    hideSuggestions();
                    clearQuote();
                    render();

                    reverseGeocodeAndQuote(lat, lng);

                    locationButton.disabled = false;
                    locationButton.textContent = msg.msgLocationButton;
                }, () => {
                    setMessage(msg.msgLocationFailed, 'error');
                    locationButton.disabled = false;
                    locationButton.textContent = msg.msgLocationButton;
                }, {enableHighAccuracy: true, timeout: 10000});
            };

            const updateChoiceStyles = () => {
                form.querySelectorAll('[data-choice-grid] label').forEach((label) => {
                    if (label.classList.contains('hidden')) return;
                    const input = label.querySelector('input[type="radio"]');
                    if (!input) return;
                    label.classList.toggle('border-[#064E3B]', input.checked);
                    label.classList.toggle('bg-[#064E3B]/5', input.checked);
                    label.classList.toggle('border-stone-200', !input.checked);
                    label.classList.toggle('bg-stone-50', !input.checked);
                });
            };

            const render = () => {
                const method = selectedMethod();
                const branch = selectedBranch();
                const autoQuote = branch?.autoQuote ?? false;

                // Only show/hide the entire delivery panel — all child buttons (Tính phí, Vị trí hiện tại)
                // are inside it and automatically follow.
                if (deliveryPanel) {
                    deliveryPanel.hidden = method !== 'delivery';
                }

                // When auto-quote is OFF, hide the quote button and current location button
                // — the restaurant confirms the fee manually.
                if (quoteButton) {
                    quoteButton.classList.toggle('hidden', !autoQuote);
                }
                if (locationButton) {
                    locationButton.classList.toggle('hidden', !autoQuote);
                }

                // Approx location display is always visible when in delivery mode
                // (the text content is set by setCoords when coordinates are available).
                if (approxLocation) {
                    const hasCoords = method === 'delivery' && (latitudeInput?.value || longitudeInput?.value);
                    approxLocation.classList.toggle('hidden', !hasCoords);
                }

                if (!branch || method !== 'delivery' || !branch.acceptsDelivery) {
                    hideSuggestions();
                }

                if (ruleSummary) {
                    if (!branch) {
                        ruleSummary.textContent = msg.msgSelectBranchShipping;
                    } else if (method === 'pickup') {
                        ruleSummary.textContent = branch.address || msg.msgPickupAtBranch;
                    } else if (!branch.acceptsDelivery) {
                        ruleSummary.textContent = msg.msgBranchNoDelivery;
                    } else if (!branch.autoQuote) {
                        ruleSummary.textContent = msg.msgBranchAutoQuoteOff;
                    } else if (!branch.hasOrigin) {
                        ruleSummary.textContent = msg.msgBranchNoOrigin;
                    } else {
                        const max = branch.maxDistance !== null ? `, ${msg.msgMaxDistance} ${branch.maxDistance.toFixed(1)} km` : '';
                        const free = branch.freeOrder !== null ? `, ${msg.msgFreeShippingFrom} ${formatMoney(branch.freeOrder)}` : '';
                        const minOrder = branch.minOrder !== null ? branch.minOrder : null;
                        ruleSummary.textContent = `${msg.msgMinOrder} ${formatMoney(minOrder || 0)}${max}${free}.`;
                    }
                }

                if (branchNote) {
                    const note = branch?.note || '';
                    branchNote.textContent = note;
                    branchNote.classList.toggle('hidden', !note || method !== 'delivery' || !branch?.acceptsDelivery);
                }

                let feeText = method === 'delivery' ? deliveryPendingText : formatMoney(0);
                let shippingFee = 0;

                if (method === 'delivery' && quoteState?.available) {
                    feeText = quoteState.manual ? deliveryPendingText : quoteState.fee_formatted;
                    shippingFee = quoteState.manual ? 0 : Number(quoteState.fee || 0);
                }

                const discount = voucherState?.valid ? Number(voucherState.discount_total || 0) : 0;
                const totalText = formatMoney(Math.max(0, subtotal + shippingFee - discount));

                if (deliverySummary) deliverySummary.textContent = feeText;
                if (voucherDiscountRow) voucherDiscountRow.classList.toggle('hidden', discount < 1);
                if (voucherDiscountSummary) voucherDiscountSummary.textContent = `-${formatMoney(discount)}`;
                if (totalSummary) totalSummary.textContent = totalText;
                if (submitTotal) submitTotal.textContent = totalText;

                // --- Min order guard: disable submit when below delivery minimum ---
                const minOrderBlockActive = method === 'delivery'
                    && branch
                    && branch.acceptsDelivery
                    && branch.minOrder !== null
                    && branch.minOrder > 0
                    && subtotal < branch.minOrder;

                // --- Shipping quote hard block (viva only): when delivery requires auto-quote but quote is unavailable ---
                const requiresQuote = method === 'delivery' && branch?.acceptsDelivery && (branch?.autoQuote ?? false);
                const shippingQuoteHardBlocked = requiresQuote && !!quoteState && quoteState.available === false;

                // --- Payment method always available in the IIFE outer scope ---
                const paymentMethod = form.querySelector('[name="payment_method"]:checked')?.value;

                // If either guard is active, disable the button.
                // Otherwise enable it. The button state is never "forgotten" because
                // render() is called on every change event.
                if (submitBtn) {
                    if (minOrderBlockActive) {
                        submitBtn.disabled = true;
                        submitBtn.classList.remove('bg-[#B91C1C]', 'hover:bg-[#991B1B]', 'shadow-lg');
                        submitBtn.classList.add('bg-stone-300', 'cursor-not-allowed');
                    } else if (shippingQuoteHardBlocked && (paymentMethod === 'viva') && !modalProceeding) {
                        submitBtn.disabled = true;
                        submitBtn.classList.remove('bg-[#B91C1C]', 'hover:bg-[#991B1B]', 'shadow-lg');
                        submitBtn.classList.add('bg-stone-300', 'cursor-not-allowed');
                    } else if (timeslotBlocked) {
                        submitBtn.disabled = true;
                        submitBtn.classList.remove('bg-[#B91C1C]', 'hover:bg-[#991B1B]', 'shadow-lg');
                        submitBtn.classList.add('bg-stone-300', 'cursor-not-allowed');
                    } else {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('bg-stone-300', 'cursor-not-allowed');
                        submitBtn.classList.add('bg-[#B91C1C]', 'hover:bg-[#991B1B]', 'shadow-lg');
                    }
                }

                // Clear the modal proceed flag only after the manual proceed submission has been allowed.
                if (modalProceeding && (!submitBtn || !submitBtn.disabled)) {
                    modalProceeding = false;
                }

                if (minOrderBlockActive && branch) {
                    const remaining = branch.minOrder - subtotal;
                    const formattedRemaining = formatMoney(remaining);
                    const formattedMin = formatMoney(branch.minOrder);
                    const blockedMsg = (msg.msgMinOrderBlocked || '')
                        .replace(':amount', formattedMin)
                        .replace(':more', formattedRemaining);
                    setMessage(blockedMsg, 'error');
                }

                if (shippingQuoteHardBlocked) {
                    setMessage(msg.msgCouldNotCalculate, 'error');
                }

                // --- Show/hide fulfillment method cards based on branch settings ---
                const pickupCard = form.querySelector('[data-choice-grid] label:has([value="pickup"])');
                const deliveryCard = form.querySelector('[data-choice-grid] label:has([value="delivery"])');

                if (pickupCard) pickupCard.classList.toggle('hidden', !branch || !branch.acceptsPickup);
                if (deliveryCard) deliveryCard.classList.toggle('hidden', !branch || !branch.acceptsDelivery);

                // If the currently selected method is now hidden, switch to an available card
                const currentMethod = selectedMethod();
                const pickupAvailable = pickupCard && !pickupCard.classList.contains('hidden');
                const deliveryAvailable = deliveryCard && !deliveryCard.classList.contains('hidden');

                if (currentMethod === 'pickup' && !pickupAvailable) {
                    if (deliveryAvailable) {
                        form.querySelector('[name="fulfillment_method"][value="delivery"]')?.click();
                    }
                    // else: only dine-in available — leave as-is; render() will show the delivery panel hidden
                } else if (currentMethod === 'delivery' && !deliveryAvailable) {
                    if (pickupAvailable) {
                        form.querySelector('[name="fulfillment_method"][value="pickup"]')?.click();
                    }
                    // else: only dine-in available — leave as-is
                }

                // Offline payment card: show only when branch allows offline payment
                const offlineCard = form.querySelector('[data-payment-offline-card]');
                if (offlineCard) {
                    offlineCard.classList.toggle('hidden', !branch || !branch.acceptsOfflinePayment);
                    // Auto-switch to Viva if offline is hidden while selected
                    if (!branch || !branch.acceptsOfflinePayment) {
                        const offlineRadio = form.querySelector('[name="payment_method"][value="offline"]');
                        if (offlineRadio?.checked) {
                            form.querySelector('[name="payment_method"][value="viva"]')?.click();
                        }
                    }
                }

                updateChoiceStyles();
            };

            form.addEventListener('change', (event) => {
                if (event.target.matches('[name="branch_id"], [name="fulfillment_method"], [name="payment_method"]')) {
                    clearQuote();
                    setMessage('', 'success');
                    hideSuggestions();
                    requestQuoteIfReady();
                    refreshVoucher(selectedVoucherCode, false);
                }

                if (event.target.matches('[name="branch_id"], [name="requested_time"]')) {
                    requestAvailabilityIfReady();
                }

                render();
            });
            addressApproxInput?.addEventListener('input', () => {
                if (suppressAddressInputHandler) return;

                // Show loading immediately while the customer is typing.
                // If the query is too short, fetchSuggestions will turn it off.
                setAddressLoading(true);

                clearCoords();
                clearQuote();
                setMessage('', 'success');

                // Sync approx address to final input so browser autofill flows through
                if (addressFinalInput) addressFinalInput.value = addressApproxInput?.value || '';
                if (addressFinalHidden) addressFinalHidden.value = addressApproxInput?.value || '';

                render();

                fetchSuggestions();
            });
            addressApproxInput?.addEventListener('focus', () => fetchSuggestions());

            document.addEventListener('click', (event) => {
                if (!suggestionsBox || suggestionsBox.classList.contains('hidden')) return;
                const target = event.target;
                if (!(target instanceof Node)) return;
                if (suggestionsBox.contains(target) || addressApproxInput?.contains(target)) return;
                hideSuggestions();
            });

            quoteButton?.addEventListener('click', requestQuote);
            voucherOptions.forEach((button) => {
                button.addEventListener('click', () => {
                    refreshVoucher(button.dataset.voucherCode, true);
                });
            });
            voucherApplyButton?.addEventListener('click', () => {
                applyManualVoucher();
            });
            voucherManualInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyManualVoucher();
                }
            });
            voucherClearButton?.addEventListener('click', () => clearVoucherState(true));
            confirmButton?.addEventListener('click', lockAddress);
            addressChangeButton?.addEventListener('click', () => {
                clearQuote();
                setMessage('', 'success');
                render();
            });
            addressFinalInput?.addEventListener('input', () => {
                // Sync the hidden field so it submits with the form
                if (addressFinalHidden) addressFinalHidden.value = addressFinalInput.value || '';
            });
            addressFinalInput?.addEventListener('change', () => {
                // Also sync on change (covers some browser autofill cases)
                if (addressFinalHidden) addressFinalHidden.value = addressFinalInput.value || '';
            });
            locationButton?.addEventListener('click', useCurrentLocation);
            updateVoucherOptionStyles();
            if (selectedVoucherCode) {
                refreshVoucher(selectedVoucherCode, false);
            }
            render();

            // --- Shipping uncalculated modal logic ---
            const modal = document.getElementById('shipping-uncalculated-modal');
            const warningEl = form.querySelector('[data-shipping-uncalculated-warning]');

            // #endregion

            function openModal() {
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }

            modal?.querySelectorAll('[data-modal-close]').forEach(el => {
                el.addEventListener('click', closeModal);
            });

            modal?.querySelector('[data-modal-btn-calc]')?.addEventListener('click', () => {
                closeModal();
                addressApproxInput?.focus();
                addressApproxInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            modal?.querySelector('[data-modal-btn-proceed]')?.addEventListener('click', () => {
                modalProceeding = true;
                const flag = form.querySelector('[data-proceed-flag]');
                if (flag) flag.value = '1';
                closeModal();
                form.requestSubmit ? form.requestSubmit() : form.submit();
            });

            function updateShippingWarning(currentMethod) {
                if (!warningEl) return;
                if (currentMethod === 'delivery' && !quoteState) {
                    warningEl.classList.remove('hidden');
                } else {
                    warningEl.classList.add('hidden');
                }
            }

            // Intercept form submit
            form.addEventListener('submit', (e) => {
                const currentMethod = selectedMethod();
                const paymentMethod = form.querySelector('[name="payment_method"]:checked')?.value;

                const needsShipping = currentMethod === 'delivery';
                const noQuote = !quoteState;
                const quoteFailed = !!(quoteState && quoteState.available === false);

                if (needsShipping && (noQuote || quoteFailed) && !modalProceeding) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    openModal();
                    return false;
                }
            }, true);

            // Override render to also update warning
            const originalRender = render;
            render = function() {
                originalRender();
                updateShippingWarning(selectedMethod());
            };
            updateShippingWarning(selectedMethod());

            render();
        })();
    </script>
@endpush
