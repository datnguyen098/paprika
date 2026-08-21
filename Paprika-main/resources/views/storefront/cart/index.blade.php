@extends('storefront.layouts.app')

@section('content')
    <div class="bg-[#FDFBF7] py-10 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
        <div class="text-center space-y-2 mb-8">
            <span class="text-[#B91C1C] text-xs font-black uppercase tracking-widest block">{{ __('site.cart_page.eyebrow') }}</span>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-950 italic uppercase">{{ __('site.cart_page.title') }}</h1>
        </div>

        <div data-cart-page-content>
            @include('storefront.cart.partials.content', ['items' => $items, 'subtotal' => $subtotal, 'branches' => $branches])
        </div>
    </div>
@endsection
