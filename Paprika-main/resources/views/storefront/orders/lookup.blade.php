@extends('storefront.layouts.app')

@section('content')
    <div class="bg-[#FDFBF7]">
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="space-y-2">
                    <p class="text-xs font-black uppercase tracking-widest text-[#064E3B]">{{ __('site.order_lookup.eyebrow') }}</p>
                    <h1 class="font-heading text-2xl font-black tracking-tight text-stone-950 sm:text-3xl">{{ __('site.order_lookup.title') }}</h1>
                    <p class="text-sm leading-6 text-stone-600">{{ __('site.order_lookup.description') }}</p>
                </div>

                <form method="GET" action="{{ localized_route('order.lookup.results') }}" class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <label class="sr-only" for="order_lookup_query">{{ __('site.order_lookup.query_label') }}</label>
                    <input
                        id="order_lookup_query"
                        name="query"
                        value="{{ old('query', $query ?? '') }}"
                        placeholder="{{ __('site.order_lookup.query_placeholder') }}"
                        class="w-full rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-stone-900 shadow-sm outline-none transition focus:border-[#064E3B] focus:ring-2 focus:ring-[#064E3B]/20"
                        autocomplete="email"
                        required
                    >
                    <button type="submit" class="group inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#064E3B] to-[#0F6B50] px-6 py-3 text-xs font-black uppercase tracking-[0.18em] text-white shadow-lg shadow-emerald-950/15 ring-1 ring-white/10 transition hover:-translate-y-0.5 hover:from-[#043427] hover:to-[#064E3B] hover:shadow-xl hover:shadow-emerald-950/20 focus:outline-none focus:ring-2 focus:ring-[#064E3B]/25 active:translate-y-0 sm:min-w-40">
                        <span>{{ __('site.order_lookup.submit') }}</span>
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/15 text-sm leading-none transition group-hover:bg-white/25 group-hover:translate-x-0.5" aria-hidden="true">→</span>
                    </button>
                </form>

                @isset($orders)
                    <div class="mt-8 border-t border-stone-200 pt-6">
                        <h2 class="text-sm font-black uppercase tracking-widest text-stone-900">{{ __('site.order_lookup.results_title') }}</h2>

                        @if ($orders->isEmpty())
                            <p class="mt-3 text-sm text-stone-600">{{ __('site.order_lookup.empty') }}</p>
                        @else
                            <div class="mt-4 space-y-3">
                                @foreach ($orders as $order)
                                    @php
                                        $paymentStatusLabels = ['unpaid' => __('site.order_lookup.unpaid'), 'paid' => __('site.order_lookup.paid')];
                                        $paymentTone = $order->payment_status === 'paid'
                                            ? 'bg-emerald-100 text-emerald-800'
                                            : 'bg-rose-50 text-rose-700 ring-1 ring-rose-100';
                                        $reusableVivaPayment = app(\App\Support\PendingVivaPayment::class)->reusableCheckoutPayment($order);
                                    @endphp
                                    <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm transition hover:border-stone-300 hover:bg-stone-50">
                                        <a href="{{ localized_route('order.track', ['order' => $order->code]) }}" class="block">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-black text-stone-950">#{{ $order->code }}</p>
                                                    <p class="mt-1 text-xs font-semibold text-stone-500">{{ business_time($order->created_at, $order->branch)?->format('d/m/Y H:i') }}</p>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-black text-stone-600">
                                                        {{ $order->fulfillmentLabel() }}
                                                    </span>
                                                    <span class="status-badge status-{{ $order->statusTone() }}">{{ $order->statusLabel() }}</span>
                                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-black {{ $paymentTone }}">
                                                        {{ $paymentStatusLabels[$order->payment_status] ?? $order->payment_status }}
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="mt-3 text-sm font-black text-[#064E3B]">{{ format_money($order->total) }}</p>
                                        </a>
                                        @if ($reusableVivaPayment)
                                            <form method="POST" action="{{ route('payments.viva.continue', ['order' => $order->code]) }}" class="mt-3">
                                                @csrf
                                                <button type="submit" class="inline-flex min-h-9 w-full items-center justify-center rounded-xl bg-[#064E3B] px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white transition hover:bg-[#043427] sm:w-auto">
                                                    {{ __('site.pending_viva.continue') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-6 text-xs leading-5 text-stone-500">{{ __('site.order_lookup.privacy_note') }}</p>
                    </div>
                @endisset
            </div>
        </div>
    </div>
@endsection
