@php
    $deliveryFee = $deliveryFee ?? 0;
    $discount = $discount ?? 0;
    $total = $cartSubtotal + $deliveryFee - $discount;

    // Guard: hiện warning khi đơn dưới minOrder của delivery (theo branch mặc định)
    $branches = $branches ?? collect();
    $defaultBranch = $branches->sortBy('sort_order')->first();
    $minOrderAmount = (int) ($defaultBranch?->delivery_min_order_amount ?? 0);
    $hasDeliveryMin = $minOrderAmount > 0;
    $belowMinOrder = $hasDeliveryMin && $cartSubtotal < $minOrderAmount;
    $remainingForMin = $minOrderAmount - $cartSubtotal;
@endphp

<div class="flex h-full w-screen max-w-sm flex-col border-l border-stone-200 bg-[#FDFBF7] shadow-2xl sm:max-w-sm max-w-[92vw]">
    <div class="flex items-center justify-between border-b border-stone-200 bg-[#FDFBF7] p-6">
        <h2 class="flex items-center gap-2 text-lg font-black uppercase italic">
            {{ __('site.cart.drawer_title') }}
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#B91C1C] font-sans text-[10px] font-bold not-italic text-white">{{ $cartCount }}</span>
        </h2>
        <button type="button" data-close-cart class="rounded-full p-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-900" aria-label="{{ __('site.cart.close') }}">
            @include('storefront.partials.icon', ['name' => 'x', 'class' => 'w-5 h-5'])
        </button>
    </div>

    <div class="flex-1 space-y-6 overflow-y-auto bg-white p-6 no-scrollbar">
        @forelse ($cartItems as $item)
            <div class="flex shrink-0 items-start gap-4 border-b border-stone-100 pb-4">
                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-stone-100">
                    <img src="{{ media_variant_url($item['dish']->image, 'thumb') }}" alt="{{ $item['dish']->localized('name') }}" class="h-full w-full object-cover" loading="lazy">
                </div>
                <div class="min-w-0 flex-1 space-y-1">
                    <h4 class="text-xs font-bold uppercase leading-tight text-stone-950">{{ $item['dish']->localized('name') }}</h4>
                    <p class="text-[10px] text-stone-400">{{ format_money($item['unit_price']) }} {{ __('site.cart.unit_suffix') }}</p>
                    @if ($item['summary'] !== __('site.cart.standard_recipe'))
                        <p class="text-[10px] leading-4 text-stone-500">{{ $item['summary'] }}</p>
                    @endif
                    @if (! ($item['available'] ?? true))
                        <p class="text-[10px] leading-4 text-rose-700 font-semibold">
                            {{ __('site.cart.unavailable_time_slot') }}
                        </p>
                    @elseif (! empty($item['availability_label']))
                        <p class="text-[10px] leading-4 text-stone-400">
                            {{ $item['availability_label'] }}
                        </p>
                    @endif
                    <div class="mt-2 flex items-center justify-between pt-1">
                        <form method="POST" action="{{ localized_route('cart.update') }}" class="flex items-center gap-2 rounded-full border border-stone-200 bg-white px-2 py-0.5" data-qty-stepper data-ajax-cart-form>
                            @csrf
                            @method('PATCH')
                            <button type="button" data-qty-action="dec" class="px-1 text-xs font-bold text-stone-400 hover:text-stone-800" aria-label="{{ __('site.cart.qty_dec') }}">-</button>
                            <input type="number" name="quantities[{{ $item['line_key'] }}]" value="{{ $item['quantity'] }}" min="0" max="99" class="w-8 border-0 bg-transparent px-0.5 text-center font-mono text-xs font-bold outline-none" aria-label="{{ __('site.cart.qty_label') }}">
                            <button type="button" data-qty-action="inc" class="px-1 text-xs font-bold text-[#064E3B] hover:text-[#B91C1C]" aria-label="{{ __('site.cart.qty_inc') }}">+</button>
                        </form>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-extrabold text-stone-900">{{ format_money($item['line_total']) }}</span>
                            <form method="POST" action="{{ localized_route('cart.remove', $item['line_key']) }}" data-ajax-cart-form>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-stone-300 transition hover:text-[#B91C1C]" aria-label="{{ __('site.cart.remove') }}">
                                    @include('storefront.partials.icon', ['name' => 'trash', 'class' => 'w-3.5 h-3.5'])
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="flex h-full flex-col items-center justify-center space-y-4 py-8 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full border border-stone-200 bg-stone-50">
                    @include('storefront.partials.icon', ['name' => 'cart', 'class' => 'w-6 h-6 text-stone-300'])
                </div>
                <h3 class="text-sm font-extrabold text-stone-800">{{ __('site.cart.empty_title') }}</h3>
                <p class="max-w-xs text-[11px] leading-relaxed text-stone-500">{{ __('site.cart.empty_text') }}</p>
                <a href="{{ localized_route('menu.index') }}" data-close-cart class="rounded-full bg-[#064E3B] px-5 py-2 text-[10px] font-black uppercase tracking-widest text-white transition hover:bg-[#B91C1C]">{{ __('site.cart.view_menu') }}</a>
            </div>
        @endforelse
    </div>

    @if ($cartCount > 0)
        <div class="space-y-4 border-t border-stone-100 bg-white p-6">
            @if($belowMinOrder)
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-center text-[11px] text-rose-700 font-semibold">
                {{ __('site.cart.min_order_warning', [
                    'amount' => format_money($minOrderAmount),
                    'more' => format_money($remainingForMin),
                ]) }}
            </div>
            @endif
            <div class="space-y-2 border-t border-stone-100 pt-2">
                <div class="flex justify-between text-[11px] uppercase tracking-widest text-stone-500">
                    <span>{{ __('site.cart.subtotal') }}</span>
                    <span class="font-extrabold text-stone-800">{{ format_money($cartSubtotal) }}</span>
                </div>
                <div class="flex justify-between text-[11px] uppercase tracking-widest text-stone-500">
                    <span>{{ __('site.cart.delivery_fee') }}</span>
                    <span class="font-extrabold text-stone-800">{{ $deliveryFee > 0 ? format_money($deliveryFee) : __('site.cart.delivery_pending') }}</span>
                </div>
                <div class="flex justify-between border-t border-stone-200 pt-2 text-lg font-black uppercase italic">
                    <span>{{ __('site.cart.total') }}</span>
                    <span class="font-mono font-black not-italic text-[#064E3B]">{{ format_money($total) }}</span>
                </div>
            </div>
            <a href="{{ localized_route('checkout.create') }}"
               @if($belowMinOrder) onclick="return false;" @endif
               class="flex w-full items-center justify-center gap-1.5 rounded-xl py-4 font-black uppercase tracking-widest text-white shadow-xl transition {{ $belowMinOrder ? 'bg-stone-300 cursor-not-allowed' : 'bg-[#B91C1C] hover:bg-[#991B1B]' }}">
                {{ __('site.cart.checkout') }}
            </a>
            <a href="{{ localized_route('cart.index') }}" class="block text-center text-[10px] font-bold uppercase tracking-widest text-[#064E3B] hover:text-[#B91C1C]">{{ __('site.cart.view_full') }}</a>
        </div>
    @endif
</div>
