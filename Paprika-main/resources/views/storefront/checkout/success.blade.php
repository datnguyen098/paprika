@extends('storefront.layouts.app')

@section('content')
    @php
        $latestPayment = $order->payments->first();
        $isViva = $order->payment_method === 'viva' || $latestPayment?->method === 'viva';
        $isPaid = $order->payment_status === 'paid';
        $paymentFailed = $isViva && $latestPayment?->status === 'failed';
        $paymentPending = $isViva && ! $isPaid && ! $paymentFailed;
        $canRetryPayment = app(\App\Support\PendingVivaPayment::class)->canStartNewCheckout($order);

        $tone = $paymentFailed ? 'rose' : ($paymentPending ? 'amber' : 'emerald');
        $icon = $paymentFailed ? 'flame' : ($paymentPending ? 'bell' : 'check');
        $badge = $paymentFailed
            ? __('site.checkout_success.badge_payment_failed')
            : ($paymentPending ? __('site.checkout_success.badge_payment_pending') : __('site.checkout_success.badge'));
        $message = $paymentFailed
            ? __('site.checkout_success.message_payment_failed')
            : ($paymentPending ? __('site.checkout_success.message_payment_pending') : __('site.checkout_success.message'));
        $paymentLabel = $paymentFailed
            ? __('site.checkout_success.failed')
            : ($paymentPending ? __('site.checkout_success.awaiting_payment') : ($isPaid ? __('site.checkout_success.paid') : __('site.checkout_success.pending')));
    @endphp

    <div class="bg-[#FDFBF7] py-16 px-4 sm:px-6 lg:px-8 max-w-xl mx-auto" id="checkout-success-view">
        <div class="bg-white rounded-3xl border border-stone-200 p-8 text-center space-y-6 shadow-xl animate-fadeIn">
            <div @class([
                'w-16 h-16 mx-auto rounded-full flex items-center justify-center border-2',
                'bg-emerald-50 text-emerald-800 border-emerald-300' => $tone === 'emerald',
                'bg-amber-50 text-amber-800 border-amber-300' => $tone === 'amber',
                'bg-rose-50 text-rose-800 border-rose-300' => $tone === 'rose',
            ])>
                @include('storefront.partials.icon', ['name' => $icon, 'class' => 'w-8 h-8'])
            </div>
            <div class="space-y-2">
                <span @class([
                    'text-[10px] uppercase font-mono tracking-widest px-3 py-1 rounded-full font-bold',
                    'bg-emerald-100 text-[#064E3B]' => $tone === 'emerald',
                    'bg-amber-100 text-amber-900' => $tone === 'amber',
                    'bg-rose-100 text-rose-900' => $tone === 'rose',
                ])>{{ $badge }}</span>
                <h1 class="text-2xl sm:text-3xl font-black text-stone-900 tracking-tight italic uppercase">{{ $order->code }}</h1>
                <p class="text-stone-500 text-sm max-w-md mx-auto">{{ $message }}</p>
            </div>
            <div class="bg-stone-50 rounded-2xl border border-stone-200 p-5 text-left space-y-3 text-xs">
                <div class="flex justify-between"><span class="text-stone-400 font-bold uppercase text-[9px]">{{ __('site.checkout_success.status') }}</span><strong>{{ $order->statusLabel() }}</strong></div>
                <div class="flex justify-between"><span class="text-stone-400 font-bold uppercase text-[9px]">{{ __('site.checkout_success.payment') }}</span><strong>{{ $paymentLabel }}</strong></div>
                <div class="flex justify-between border-t border-stone-200 pt-3 font-black text-sm"><span>{{ __('site.checkout_success.total') }}</span><span class="font-mono text-[#064E3B]">{{ format_money($order->total) }}</span></div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                @if($canRetryPayment)
                    <form method="POST" action="{{ localized_route('order.retry-payment', ['order' => $order->code]) }}">
                        @csrf
                        <button type="submit" class="w-full py-4 px-6 bg-[#064E3B] hover:bg-[#043427] text-white text-sm uppercase tracking-widest font-black rounded-xl transition shadow-md">{{ __('site.checkout_success.retry_payment') }}</button>
                    </form>
                @endif
                <a href="{{ localized_route('menu.index') }}" class="py-4 px-6 bg-[#B91C1C] hover:bg-[#991B1B] text-white text-sm uppercase tracking-widest font-black rounded-xl transition shadow-md">{{ __('site.checkout_success.order_more') }}</a>
                <a href="{{ localized_route('order.lookup') }}" class="py-4 px-6 bg-[#064E3B] hover:bg-[#043427] text-white text-sm uppercase tracking-widest font-extrabold rounded-xl transition flex items-center justify-center gap-2">@include('storefront.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4']) {{ __('site.footer_block.track_order') }}</a>
            </div>
        </div>
    </div>
@endsection
