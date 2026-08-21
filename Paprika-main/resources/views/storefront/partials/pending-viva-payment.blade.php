@if ($pendingVivaPayment)
    <div class="sticky top-20 z-30 border-b border-amber-200 bg-amber-50/95 px-4 py-3 shadow-sm backdrop-blur" data-pending-viva-payment>
        <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-900">
                    @include('storefront.partials.icon', ['name' => 'bell', 'class' => 'h-4 w-4'])
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-amber-950">{{ __('site.pending_viva.title') }}</p>
                    <p class="mt-1 text-sm font-semibold leading-5 text-amber-900">
                        {{ __('site.pending_viva.message', ['order' => $pendingVivaPayment['order_code']]) }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <form method="POST" action="{{ $pendingVivaPayment['continue_url'] }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-[#064E3B] px-4 py-2 text-xs font-black uppercase tracking-widest text-white transition hover:bg-[#043427]">
                        {{ __('site.pending_viva.continue') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('payments.viva.reminder.dismiss') }}">
                    @csrf
                    <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-amber-200 bg-white/70 text-amber-900 transition hover:bg-white" aria-label="{{ __('site.pending_viva.dismiss') }}">
                        @include('storefront.partials.icon', ['name' => 'x', 'class' => 'h-4 w-4'])
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
