@php
    $branch = $branch ?? primary_branch();
    $title = $title ?? __('site.branch_map.title');
    $eyebrow = $eyebrow ?? __('site.branch_map.eyebrow');
    $description = $description ?? __('site.branch_map.description');
    $mapHeight = $mapHeight ?? 'h-72 sm:h-80';
    $compact = (bool) ($compact ?? false);
    $embedUrl = branch_map_embed_url($branch);
    $directionsUrl = branch_map_directions_url($branch);
    $phone = $branch?->hotline ?: $branch?->phone;
@endphp

<div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm">
    <div class="grid gap-0 {{ $compact ? '' : 'lg:grid-cols-[0.8fr_1.2fr]' }}">
        <div class="space-y-4 p-5 sm:p-6 {{ $compact ? '' : 'lg:p-8' }}">
            <div class="inline-flex items-center gap-2 rounded-full bg-[#064E3B]/8 px-3 py-1 text-[10px] font-black uppercase tracking-widest text-[#064E3B]">
                @include('storefront.partials.icon', ['name' => 'map-pin', 'class' => 'h-3.5 w-3.5 text-[#B91C1C]'])
                {{ $eyebrow }}
            </div>

            <div class="space-y-2">
                <h2 class="{{ $compact ? 'text-xl' : 'text-2xl sm:text-3xl' }} font-black uppercase italic tracking-tight text-stone-950">
                    {{ $title }}
                </h2>
                <p class="text-sm leading-6 text-stone-500">{{ $description }}</p>
            </div>

            <div class="space-y-3 rounded-2xl bg-stone-50 p-4 text-sm text-stone-700">
                <p class="font-extrabold text-[#064E3B]">{{ $branch?->name ?: __('site.branch_map.default_name') }}</p>
                @if ($branch?->address)
                    <p class="flex gap-2 text-xs leading-5">
                        @include('storefront.partials.icon', ['name' => 'map-pin', 'class' => 'mt-0.5 h-4 w-4 shrink-0 text-[#B91C1C]'])
                        <span>{{ $branch->address }}</span>
                    </p>
                @endif
                @if ($phone)
                    <p class="flex gap-2 text-xs leading-5">
                        @include('storefront.partials.icon', ['name' => 'phone', 'class' => 'mt-0.5 h-4 w-4 shrink-0 text-[#B91C1C]'])
                        <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="font-bold hover:text-[#064E3B]">{{ $phone }}</a>
                    </p>
                @endif
                @if ($branch?->opening_hours)
                    <p class="flex gap-2 text-xs leading-5">
                        @include('storefront.partials.icon', ['name' => 'clock', 'class' => 'mt-0.5 h-4 w-4 shrink-0 text-[#B91C1C]'])
                        <span>{{ $branch->opening_hours }}</span>
                    </p>
                @endif
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-[#064E3B] px-5 py-3 text-xs font-black uppercase tracking-widest text-white transition hover:bg-[#043427]">
                    {{ __('site.branch_map.directions') }}
                    @include('storefront.partials.icon', ['name' => 'arrow-right', 'class' => 'h-4 w-4'])
                </a>
                @if ($phone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-stone-200 bg-white px-5 py-3 text-xs font-black uppercase tracking-widest text-[#064E3B] transition hover:bg-stone-50">
                        {{ __('site.branch_map.call_store') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="relative min-h-64 border-t border-stone-100 bg-stone-100 {{ $compact ? '' : 'lg:border-l lg:border-t-0' }}">
            <iframe
                src="{{ $embedUrl }}"
                title="{{ ($branch?->name ?: __('site.branch_map.default_name')) . ' map' }}"
                class="{{ $mapHeight }} w-full"
                style="border:0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen
            ></iframe>
        </div>
    </div>
</div>
