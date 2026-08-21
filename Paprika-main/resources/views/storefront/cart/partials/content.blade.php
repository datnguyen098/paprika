@php
    $deliveryFee = 0;
    $discount = 0;
    $total = $subtotal + $deliveryFee - $discount;

    // Guard: hiện warning khi đơn dưới minOrder của delivery (theo branch mặc định)
    $defaultBranch = $branches->sortBy('sort_order')->first();
    $minOrderAmount = (int) ($defaultBranch?->delivery_min_order_amount ?? 0);
    $hasDeliveryMin = $minOrderAmount > 0;
    $belowMinOrder = $hasDeliveryMin && $subtotal < $minOrderAmount;
    $remainingForMin = $minOrderAmount - $subtotal;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    @if($belowMinOrder && count($items) > 0)
    <div class="lg:col-span-12">
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 font-semibold">
            {{ __('site.cart.min_order_warning', [
                'amount' => format_money($minOrderAmount),
                'more' => format_money($remainingForMin),
            ]) }}
        </div>
    </div>
    @endif

    <div class="lg:col-span-7 bg-white rounded-2xl border border-stone-200 p-6 shadow-sm space-y-4">
        @forelse ($items as $item)
            <div class="flex gap-4 pb-4 border-b border-stone-100 items-start">
                <div class="w-16 h-16 rounded-xl bg-stone-100 overflow-hidden shrink-0">
                    <img src="{{ media_variant_url($item['dish']->image, 'thumb') }}" alt="{{ $item['dish']->localized('name') }}" class="w-full h-full object-cover" loading="lazy">
                </div>
                <div class="flex-1 min-w-0">
                    <h2 class="text-xs font-bold uppercase text-stone-950">{{ $item['dish']->localized('name') }}</h2>
                    <p class="text-[10px] text-stone-400">{{ format_money($item['unit_price']) }} {{ __('site.cart.unit_suffix') }}</p>
                    @if (count($item['selected_options'] ?? []) > 0 || $item['customization_note'])
                        <p class="mt-1 text-[10px] leading-4 text-stone-500">{{ $item['summary'] }}</p>
                    @endif
                    @if (! ($item['available'] ?? true))
                        <p class="mt-1 text-[10px] leading-4 text-rose-700 font-semibold">
                            {{ __('site.cart.unavailable_time_slot') }}
                        </p>
                    @elseif (! empty($item['availability_label']))
                        <p class="mt-1 text-[10px] leading-4 text-stone-400">
                            {{ $item['availability_label'] }}
                        </p>
                    @endif
                    <form method="POST" action="{{ localized_route('cart.update') }}" class="flex items-center gap-2 border rounded-full px-2 py-0.5 border-stone-200 bg-white mt-2 w-max" data-qty-stepper data-ajax-cart-form>
                        @csrf
                        @method('PATCH')
                        <button type="button" data-qty-action="dec" class="text-xs font-bold px-1">-</button>
                        <input type="number" name="quantities[{{ $item['line_key'] }}]" value="{{ $item['quantity'] }}" min="0" max="99" class="text-xs font-bold w-8 text-center border-0 bg-transparent outline-none">
                        <button type="button" data-qty-action="inc" class="text-xs font-bold px-1">+</button>
                    </form>
                </div>
                <div class="text-right">
                    <strong class="text-xs font-mono block">{{ format_money($item['line_total']) }}</strong>
                    <form method="POST" action="{{ localized_route('cart.remove', $item['line_key']) }}" class="mt-2" data-ajax-cart-form>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-[10px] text-[#B91C1C] font-bold uppercase">{{ __('site.cart_page.remove') }}</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-center py-12 space-y-4">
                <p class="text-stone-500 text-sm">{{ __('site.cart_page.empty') }}</p>
                <a href="{{ localized_route('menu.index') }}" class="inline-block px-5 py-2 bg-[#064E3B] text-white rounded-full text-[10px] font-black uppercase">{{ __('site.cart_page.view_menu') }}</a>
            </div>
        @endforelse
    </div>

    <aside class="lg:col-span-5">
        <div class="bg-white rounded-2xl border border-stone-200 p-6 shadow-sm space-y-4 lg:sticky lg:top-28">
            <h2 class="text-xs uppercase font-extrabold text-[#064E3B]">{{ __('site.cart_page.summary') }}</h2>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between uppercase tracking-widest text-stone-500"><span>{{ __('site.cart_page.subtotal') }}</span><strong>{{ format_money($subtotal) }}</strong></div>
                <div class="flex justify-between uppercase tracking-widest text-stone-500"><span>{{ __('site.cart_page.delivery') }}</span><strong>{{ __('site.cart_page.delivery_pending') }}</strong></div>
                <div class="flex justify-between font-black text-base border-t border-stone-200 pt-3"><span>{{ __('site.cart_page.total') }}</span><span class="text-[#064E3B] font-mono">{{ format_money($total) }}</span></div>
            </div>
            @if (count($items) > 0)
                @if($belowMinOrder)
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-center text-xs text-rose-700 font-semibold">
                    {{ __('site.cart.min_order_warning', [
                        'amount' => format_money($minOrderAmount),
                        'more' => format_money($remainingForMin),
                    ]) }}
                </div>
                @endif
                <a href="{{ localized_route('checkout.create') }}"
                   @if($belowMinOrder) onclick="return false;" @endif
                   class="block w-full text-center py-4 {{ $belowMinOrder ? 'bg-stone-300 cursor-not-allowed' : 'bg-[#B91C1C] hover:bg-[#991B1B] shadow-xl' }} text-white font-black rounded-xl uppercase tracking-widest transition">
                    {{ __('site.cart_page.checkout') }}
                </a>
            @endif
            <button type="button" data-open-cart class="w-full py-3 border border-stone-200 rounded-xl text-[10px] font-bold uppercase text-[#064E3B]">{{ __('site.cart_page.open_drawer') }}</button>
        </div>
    </aside>
</div>
