@extends('storefront.layouts.app')

@section('content')
    <div class="mx-auto max-w-7xl flex-grow bg-[#FDFBF7] px-3 py-5 text-stone-950 sm:px-6 sm:py-10 lg:px-8" id="booking-form-view">
        <div class="mb-5 overflow-hidden rounded-[1.75rem] bg-[#064E3B] text-white shadow-xl shadow-emerald-950/15 sm:mb-10 sm:rounded-[2rem]">
            <div class="relative isolate p-5 sm:p-8 lg:p-10">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full bg-[#B91C1C]/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 left-10 h-52 w-52 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div class="space-y-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-[#FFD700] ring-1 ring-white/15">
                            @include('storefront.partials.icon', ['name' => 'calendar', 'class' => 'h-3.5 w-3.5'])
                            {{ __('site.reservation.eyebrow') }}
                        </span>
                        <div class="space-y-2">
                            <h1 class="max-w-2xl text-3xl font-black uppercase italic leading-none tracking-tight sm:text-5xl">{{ __('site.reservation.title') }}</h1>
                            <p class="max-w-2xl text-sm leading-6 text-white/75 sm:text-base">{{ __('site.reservation.description') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 rounded-2xl bg-white/10 p-2 text-center ring-1 ring-white/15 sm:min-w-80">
                        <div class="rounded-xl bg-white/10 p-2.5">
                            <span class="block text-[9px] font-black uppercase tracking-widest text-white/55">{{ __('site.reservation.hold_minutes') }}</span>
                            <strong class="mt-1 block text-xs text-white">{{ __('site.reservation.state_picked') }}</strong>
                        </div>
                        <div class="rounded-xl bg-white/10 p-2.5">
                            <span class="block text-[9px] font-black uppercase tracking-widest text-white/55">{{ __('site.reservation.time') }}</span>
                            <strong class="mt-1 block text-xs text-white">{{ $openingHours->firstBookableTime() }}-{{ $openingHours->lastBookableTime() }}</strong>
                        </div>
                        <div class="rounded-xl bg-[#B91C1C] p-2.5">
                            <span class="block text-[9px] font-black uppercase tracking-widest text-white/70">{{ __('site.reservation.legend_free') }}</span>
                            <strong class="mt-1 block text-xs text-white">{{ $tables->where('status', 'active')->count() }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:gap-8 lg:items-start">
            <form method="POST" action="{{ localized_route('reservations.store') }}" class="space-y-5 rounded-[1.5rem] border border-stone-200 bg-white p-4 shadow-sm sm:rounded-3xl sm:p-8 lg:col-span-7" novalidate data-reservation-form data-availability-url="{{ localized_route('reservations.availability') }}">
                @csrf
                <input type="hidden" name="table_id" value="{{ old('table_id') }}" data-selected-table-input>

                <div class="flex items-center justify-between gap-3 border-b border-stone-100 pb-3">
                    <h2 class="flex items-center gap-2 text-[11px] font-black uppercase tracking-widest text-stone-500">
                        @include('storefront.partials.icon', ['name' => 'calendar', 'class' => 'h-4 w-4 text-[#B91C1C]'])
                        {{ __('site.reservation.section') }}
                    </h2>
                    <span class="hidden rounded-full bg-[#064E3B]/8 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-[#064E3B] sm:inline-flex">{{ __('site.reservation.hold_minutes') }}</span>
                </div>

                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-xs text-rose-800" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                    <label class="block space-y-1.5">
                        <span class="text-xs font-bold text-stone-600">{{ __('site.reservation.name') }}</span>
                        <input name="name" value="{{ old('name') }}" required class="min-h-12 w-full rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-base outline-none focus:border-[#064E3B] focus:ring-2 focus:ring-[#064E3B]/10 sm:text-sm">
                    </label>
                    <label class="block space-y-1.5">
                        <span class="text-xs font-bold text-stone-600">{{ __('site.reservation.phone') }}</span>
                        <input name="phone" value="{{ old('phone') }}" required inputmode="tel" class="min-h-12 w-full rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-base outline-none focus:border-[#064E3B] focus:ring-2 focus:ring-[#064E3B]/10 sm:text-sm">
                    </label>
                    <label class="block space-y-1.5">
                        <span class="text-xs font-bold text-stone-600">{{ __('site.reservation.email') }}</span>
                        <input type="email" name="email" value="{{ old('email') }}" class="min-h-12 w-full rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-base outline-none focus:border-[#064E3B] focus:ring-2 focus:ring-[#064E3B]/10 sm:text-sm">
                    </label>

                    @if ($branches->count() > 1)
                        <label class="block space-y-1.5">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.reservation.branch') }}</span>
                            <select name="branch_id" required class="min-h-12 w-full rounded-2xl border border-stone-200 bg-stone-50 px-3 py-3 text-base outline-none focus:border-[#064E3B] focus:ring-2 focus:ring-[#064E3B]/10 sm:text-sm" data-branch-input>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $selectedBranch?->id) === (string) $branch->id)>{{ $branch->name }}{{ $branch->city ? ' - '.$branch->city : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                    @else
                        <input type="hidden" name="branch_id" value="{{ old('branch_id', $selectedBranch?->id) }}" data-branch-input>
                        <div class="block space-y-1.5">
                            <span class="text-xs font-bold text-stone-600">{{ __('site.reservation.branch') }}</span>
                            <div class="flex min-h-12 w-full items-center gap-2 rounded-2xl border border-stone-200 bg-[#064E3B]/5 px-4 py-3 text-sm font-bold text-[#064E3B]">
                                @include('storefront.partials.icon', ['name' => 'map-pin', 'class' => 'h-4 w-4 text-[#B91C1C]'])
                                {{ $selectedBranch?->name ?: 'Paprika Patras' }}
                            </div>
                        </div>
                    @endif

                    <label class="block space-y-1.5">
                        <span class="text-xs font-bold text-stone-600">{{ __('site.reservation.date') }}</span>
                        <input type="date" name="reservation_date" value="{{ old('reservation_date', $businessToday) }}" min="{{ $businessToday }}" required class="min-h-12 w-full rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-base outline-none focus:border-[#064E3B] focus:ring-2 focus:ring-[#064E3B]/10 sm:text-sm" data-date-input>
                    </label>
                    <label class="block space-y-1.5">
                        <span class="text-xs font-bold text-stone-600">{{ __('site.reservation.time') }}</span>
                        <input type="time" name="reservation_time" value="{{ old('reservation_time', $openingHours->firstBookableTime()) }}" min="{{ $openingHours->firstBookableTime() }}" max="{{ $openingHours->lastBookableTime() }}" required class="min-h-12 w-full rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-base outline-none focus:border-[#064E3B] focus:ring-2 focus:ring-[#064E3B]/10 sm:text-sm" data-time-input>
                        <span class="text-[10px] font-semibold text-stone-400">{{ __('site.reservation.time_window') }} {{ $openingHours->firstBookableTime() }} - {{ $openingHours->lastBookableTime() }}</span>
                    </label>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-stone-600">{{ __('site.reservation.guests') }}</label>
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-8">
                        @foreach ([1, 2, 3, 4, 5, 6, 8, 10] as $num)
                            <label class="flex min-h-11 cursor-pointer items-center justify-center gap-1 rounded-2xl border text-sm font-black transition {{ (int) old('guests', 2) === $num ? 'bg-[#064E3B] border-[#064E3B] text-white shadow-md shadow-emerald-950/10' : 'bg-stone-50 border-stone-200 text-stone-700' }}">
                                <input type="radio" name="guests" value="{{ $num }}" class="sr-only" data-guests-input @checked((int) old('guests', 2) === $num)>
                                {{ $num }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="block space-y-1.5">
                    <span class="text-xs font-bold text-stone-600">{{ __('site.reservation.note') }}</span>
                    <textarea name="note" rows="3" placeholder="{{ __('site.reservation.note_placeholder') }}" class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-base outline-none focus:border-[#064E3B] focus:ring-2 focus:ring-[#064E3B]/10 sm:text-sm">{{ old('note') }}</textarea>
                </label>

                <button type="submit" class="min-h-14 w-full rounded-full bg-[#B91C1C] px-6 py-4 text-xs font-black uppercase tracking-widest text-white shadow-lg shadow-red-950/15 transition hover:bg-[#991B1B] active:scale-[0.99]">{{ __('site.reservation.submit') }}</button>
            </form>

            <div class="space-y-4 lg:col-span-5 lg:sticky lg:top-28">
                @include('storefront.partials.branch-map', [
                    'branch' => $selectedBranch,
                    'compact' => true,
                    'eyebrow' => __('site.branch_map.about_eyebrow'),
                    'title' => $selectedBranch?->name ?: __('site.branch_map.default_name'),
                    'description' => $selectedBranch?->address,
                    'mapHeight' => 'h-56 sm:h-64',
                ])

                <div class="overflow-hidden rounded-[1.5rem] border border-stone-200 bg-white shadow-sm sm:rounded-3xl" data-seat-map-card hidden>
                    <div class="relative h-36 overflow-hidden bg-stone-100 sm:h-48">
                        <img src="{{ media_variant_url('/paprika/store.jpg', 'large') }}" alt="Paprika Patras restaurant counter" class="h-full w-full object-cover" loading="lazy">
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/55 to-transparent p-4">
                            <p class="text-xs font-black uppercase tracking-widest text-white">{{ $selectedBranch?->name ?: 'Paprika Patras' }}</p>
                        </div>
                    </div>

                    <div class="space-y-4 p-4 sm:p-6">
                        <div class="space-y-1.5 border-b border-stone-100 pb-3">
                            <h3 class="flex items-center gap-1.5 font-heading text-sm font-extrabold uppercase tracking-wide text-stone-900">
                                @include('storefront.partials.icon', ['name' => 'grid', 'class' => 'w-4 h-4 text-[#B91C1C]'])
                                {{ __('site.reservation.seatmap_title') }}
                            </h3>
                            <p class="text-xs leading-5 text-stone-500">{{ __('site.reservation.seatmap_text') }}</p>
                        </div>

                        <div class="rounded-[1.25rem] border border-emerald-950 bg-[#043427] p-3 shadow-inner sm:p-4" data-seat-map>
                            <div class="flex h-7 w-full items-center justify-center rounded-lg border border-white/5 bg-[#064E3B] text-center font-mono text-[10px] font-bold uppercase tracking-widest text-stone-200">{{ __('site.reservation.reception') }}</div>
                            <div class="grid grid-cols-3 gap-2 py-3 text-center min-[380px]:grid-cols-4 sm:gap-3 sm:py-4" data-seat-map-grid>
                                @foreach ($tables as $table)
                                    @php
                                        $selected = (string) old('table_id') === (string) $table->id;
                                        $disabled = $table->status !== 'active';
                                    @endphp
                                    <button
                                        type="button"
                                        data-table-button
                                        data-table-id="{{ $table->id }}"
                                        data-table-code="{{ $table->code }}"
                                        data-table-name="{{ $table->name }}"
                                        data-table-seats="{{ $table->seats }}"
                                        @class([
                                            'min-h-12 rounded-xl flex flex-col items-center justify-center px-1 text-[10px] font-bold transition uppercase active:scale-95',
                                            'bg-[#B91C1C] text-white shadow-lg ring-2 ring-white scale-105' => $selected && ! $disabled,
                                            'is-reserved bg-stone-800 text-stone-500 cursor-not-allowed border border-stone-900' => $disabled,
                                            'bg-[#064E3B] text-white/90 hover:bg-white/10 border border-white/10' => ! $disabled && ! $selected,
                                        ])
                                        @if ($disabled) disabled @endif
                                    >
                                        <span class="font-mono text-[9px] opacity-70">{{ $table->code }} · {{ $table->seats }}</span>
                                        <span class="mt-0.5 leading-none" data-table-state>{{ $disabled ? $table->statusLabel() : ($selected ? __('site.reservation.state_picked') : __('site.reservation.state_free')) }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <div class="grid grid-cols-3 gap-1.5 border-t border-white/10 pt-3 text-[9px] font-bold text-white/70">
                                <span class="flex items-center justify-center gap-1"><span class="h-2.5 w-2.5 rounded bg-[#B91C1C]"></span> {{ __('site.reservation.legend_picked') }}</span>
                                <span class="flex items-center justify-center gap-1"><span class="h-2.5 w-2.5 rounded bg-[#064E3B]"></span> {{ __('site.reservation.legend_free') }}</span>
                                <span class="flex items-center justify-center gap-1"><span class="h-2.5 w-2.5 rounded bg-stone-800"></span> {{ __('site.reservation.legend_taken') }}</span>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-stone-50 p-3 text-xs leading-5 text-stone-600">
                            {{ __('site.reservation.hold_text') }} <strong class="text-[#064E3B]">{{ __('site.reservation.hold_minutes') }}</strong> {{ __('site.reservation.hold_text_after') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-reservation-form]');
            if (!form) return;

            const selectedInput = form.querySelector('[data-selected-table-input]');
            const branchInput = form.querySelector('[data-branch-input]');
            const dateInput = form.querySelector('[data-date-input]');
            const timeInput = form.querySelector('[data-time-input]');
            const tableButtons = Array.from(document.querySelectorAll('[data-table-button]'));

            const selectedClasses = ['bg-[#B91C1C]', 'text-white', 'shadow-lg', 'ring-2', 'ring-white', 'scale-105'];
            const freeClasses = ['bg-[#064E3B]', 'text-white/90', 'hover:bg-white/10', 'border', 'border-white/10'];
            const busyClasses = ['is-reserved', 'bg-stone-800', 'text-stone-500', 'cursor-not-allowed', 'border', 'border-stone-900'];

            const setClasses = (button, state) => {
                button.classList.remove(...selectedClasses, ...freeClasses, ...busyClasses);
                if (state === 'selected') button.classList.add(...selectedClasses);
                if (state === 'free') button.classList.add(...freeClasses);
                if (state === 'busy') button.classList.add(...busyClasses);
            };

            const choose = (button) => {
                if (button.disabled) return;
                selectedInput.value = button.dataset.tableId || '';
                tableButtons.forEach((candidate) => {
                    if (candidate.disabled) return;
                    const state = candidate === button ? 'selected' : 'free';
                    setClasses(candidate, state);
                    candidate.querySelector('[data-table-state]').textContent = candidate === button
                        ? @json(__('site.reservation.state_picked'))
                        : @json(__('site.reservation.state_free'));
                });
            };

            const refresh = async () => {
                if (!branchInput?.value || !dateInput?.value || !timeInput?.value) return;
                const guests = form.querySelector('[name="guests"]:checked')?.value || '2';
                const params = new URLSearchParams({
                    branch_id: branchInput.value,
                    reservation_date: dateInput.value,
                    reservation_time: timeInput.value,
                    guests,
                });

                try {
                    const response = await fetch(`${form.dataset.availabilityUrl}?${params}`, {headers: {Accept: 'application/json'}});
                    if (!response.ok) return;
                    const payload = await response.json();
                    const states = new Map((payload.tables || []).map((table) => [String(table.id), table]));
                    let selectedStillAvailable = false;

                    tableButtons.forEach((button) => {
                        const table = states.get(String(button.dataset.tableId));
                        const stateLabel = button.querySelector('[data-table-state]');
                        const available = !!table?.available;
                        button.disabled = !available;
                        setClasses(button, available ? 'free' : 'busy');
                        stateLabel.textContent = available ? @json(__('site.reservation.state_free')) : (table?.reason || @json(__('site.reservation.state_busy')));

                        if (available && selectedInput.value && selectedInput.value === String(button.dataset.tableId)) {
                            selectedStillAvailable = true;
                            setClasses(button, 'selected');
                            stateLabel.textContent = @json(__('site.reservation.state_picked'));
                        }
                    });

                    if (!selectedStillAvailable) {
                        selectedInput.value = '';
                    }
                } catch (error) {
                    console.warn('Could not refresh table availability', error);
                }
            };

            tableButtons.forEach((button) => button.addEventListener('click', () => choose(button)));
            [branchInput, dateInput, timeInput, ...form.querySelectorAll('[data-guests-input]')].forEach((input) => {
                input?.addEventListener('change', refresh);
            });

            form.querySelectorAll('[data-guests-input]').forEach((input) => {
                input.addEventListener('change', () => {
                    form.querySelectorAll('[data-guests-input]').forEach((radio) => {
                        const label = radio.closest('label');
                        if (!label) return;
                        if (radio.checked) {
                            label.classList.add('bg-[#064E3B]', 'border-[#064E3B]', 'text-white', 'shadow-md', 'shadow-emerald-950/10');
                            label.classList.remove('bg-stone-50', 'border-stone-200', 'text-stone-700');
                        } else {
                            label.classList.remove('bg-[#064E3B]', 'border-[#064E3B]', 'text-white', 'shadow-md', 'shadow-emerald-950/10');
                            label.classList.add('bg-stone-50', 'border-stone-200', 'text-stone-700');
                        }
                    });
                });
            });

            refresh();
        });
    </script>
@endpush
