@extends('storefront.layouts.app')

@section('content')
    @php
        $latestPayment = $order->payments->first();
        $canRetryPayment = app(\App\Support\PendingVivaPayment::class)->canStartNewCheckout($order);
    @endphp

    <div class="bg-[#FDFBF7]">
        <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-[#064E3B]">{{ __('site.order_lookup.track_eyebrow') }}</p>
                        <h1 class="font-heading text-2xl font-black tracking-tight text-stone-950 sm:text-3xl">#{{ $order->code }}</h1>
                        <p class="mt-2 flex flex-wrap items-center gap-2 text-sm font-semibold text-stone-600">
                            <span class="status-badge status-{{ $order->statusTone() }}">{{ $order->statusLabel() }}</span>
                            <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-black text-stone-600">{{ $order->fulfillmentLabel() }}</span>
                            <span class="text-stone-400">·</span>
                            <span>{{ business_time($order->created_at, $order->branch)?->format('d/m/Y H:i') }}</span>
                        </p>
                    </div>

                    <a href="{{ localized_route('order.lookup') }}" class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-stone-200 bg-white px-5 py-3 text-xs font-black uppercase tracking-widest text-[#064E3B] transition hover:bg-stone-50">
                        {{ __('site.order_lookup.back_to_lookup') }}
                    </a>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-stone-500">{{ __('site.order_lookup.total') }}</p>
                        <p class="mt-2 text-2xl font-black text-stone-950">{{ format_money($order->total) }}</p>
                    </div>
                    <div class="rounded-2xl bg-stone-50 p-4">
                        <p class="text-xs font-black uppercase tracking-widest text-stone-500">{{ __('site.order_lookup.payment') }}</p>
                        @php
                            $paymentStatusLabels = ['unpaid' => __('site.order_lookup.unpaid'), 'paid' => __('site.order_lookup.paid')];
                            $paymentStatus = $order->payment_status;
                        @endphp
                        <p class="mt-2 text-lg font-black text-stone-950">{{ $paymentStatusLabels[$paymentStatus] ?? $paymentStatus }}</p>
                        @if ($canRetryPayment)
                            <form method="POST" action="{{ localized_route('order.retry-payment', ['order' => $order->code]) }}" class="mt-3">
                                @csrf
                                <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-2xl bg-[#064E3B] px-4 py-2 text-xs font-black uppercase tracking-widest text-white transition hover:bg-[#043427]">
                                    {{ __('site.checkout_success.retry_payment') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-8 rounded-2xl border border-stone-200 bg-white">
                    <div class="border-b border-stone-200 p-4">
                        <h2 class="text-sm font-black uppercase tracking-widest text-stone-900">{{ __('site.order_lookup.items_title') }}</h2>
                    </div>
                    <div class="divide-y divide-stone-200">
                        @foreach ($order->items as $item)
                            @php
                                $optionsSnapshot = $item->options_snapshot;
                                $optionSummary = is_array($optionsSnapshot)
                                    ? collect($optionsSnapshot)
                                        ->groupBy(fn ($option) => $option['group_name'] ?? 'Options')
                                        ->map(function ($options, $groupName) {
                                            $optionNames = $options
                                                ->pluck('name')
                                                ->filter()
                                                ->implode(', ');

                                            return $optionNames === '' ? null : "{$groupName}: {$optionNames}";
                                        })
                                        ->filter()
                                        ->values()
                                        ->implode(' | ')
                                    : trim((string) $optionsSnapshot);
                            @endphp
                            <div class="flex items-start justify-between gap-4 p-4">
                                <div>
                                    <p class="text-sm font-black text-stone-950">{{ $item->dish?->localized('name') ?? $item->dish_name }}</p>
                                    @if ($optionSummary !== '')
                                        <p class="mt-1 text-xs text-stone-500">{{ $optionSummary }}</p>
                                    @endif
                                    @if ($item->customization_note)
                                        <p class="mt-1 text-xs text-stone-500">{{ $item->customization_note }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-stone-950">×{{ $item->quantity }}</p>
                                    <p class="mt-1 text-xs font-semibold text-stone-500">{{ format_money($item->line_total) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="mt-6 text-xs leading-5 text-stone-500">{{ __('site.order_lookup.track_note') }}</p>
            </div>
        </div>
    </div>
@endsection
