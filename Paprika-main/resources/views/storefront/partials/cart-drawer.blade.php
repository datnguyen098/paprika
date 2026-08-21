@php
    $deliveryFee = 0;
    $discount = 0;
@endphp

<div data-cart-drawer hidden class="fixed inset-0 z-50 overflow-hidden text-[#1A1A1A]" id="cart-sidebar-container" role="dialog" aria-modal="true" aria-label="{{ __('site.cart.drawer_aria') }}">
    <button type="button" data-close-cart class="absolute inset-0 bg-[#043427]/75 backdrop-blur-sm transition-opacity" aria-label="{{ __('site.cart.close_aria') }}"></button>

    <div class="absolute inset-y-0 right-0 flex max-w-full pl-10 sm:pl-10 pl-4">
        <div data-cart-drawer-content>
            @include('storefront.partials.cart-drawer-content', [
                'cartItems' => $cartItems,
                'cartCount' => $cartCount,
                'cartSubtotal' => $cartSubtotal,
                'deliveryFee' => $deliveryFee,
                'discount' => $discount,
                'branches' => $branches,
            ])
        </div>
    </div>
</div>
