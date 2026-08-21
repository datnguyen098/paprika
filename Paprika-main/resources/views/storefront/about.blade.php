@extends('storefront.layouts.app')

@section('content')
<div class="bg-[#FDFBF7] py-6 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-16 text-stone-900 animate-fadeIn" id="about-section-container">

    {{-- 1. HERO --}}
    <section class="text-center max-w-3xl mx-auto space-y-4" id="about-hero">
        <div class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-[#B91C1C]/10 text-[#B91C1C] text-[10px] font-black uppercase tracking-widest">
            @include('storefront.partials.icon', ['name' => 'flame', 'class' => 'w-3 h-3 text-[#FFD700]'])
            {{ __('site.about.page_badge') }}
        </div>
        <h1 class="text-3xl sm:text-5xl font-black italic uppercase tracking-tight text-[#064E3B] leading-none">
            {{ __('site.about.page_title_prefix') }} <span class="text-[#B91C1C]">{{ __('site.about.page_title_highlight') }}</span> {{ __('site.about.page_title_suffix') }}
        </h1>
        <p class="text-sm sm:text-base text-stone-550 leading-relaxed font-medium">
            {{ __('site.about.page_description') }}
        </p>
    </section>

    {{-- 2. BENTO GRID --}}
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6" id="about-bento-grid">
        <div class="md:col-span-2 bg-[#064E3B] text-white rounded-3xl p-8 flex flex-col justify-between space-y-8 relative overflow-hidden shadow-xl border border-[#043427]">
            <div class="absolute top-0 right-0 translate-x-12 -translate-y-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="space-y-4">
                <span class="bg-white/10 text-[#FFD700] text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded w-fit block">
                    {{ __('site.about.philosophy_badge') }}
                </span>
                <h2 class="text-2xl sm:text-3xl font-black uppercase italic leading-tight">
                    {{ __('site.about.philosophy_title') }}
                </h2>
                <p class="text-white/80 text-xs sm:text-sm leading-relaxed">
                    {{ __('site.about.philosophy_text') }}
                </p>
            </div>
            <div class="flex gap-6 pt-4 border-t border-white/10 text-[11px] font-bold uppercase tracking-widest text-[#FFD700]">
                <div class="flex items-center gap-1.5">
                    @include('storefront.partials.icon', ['name' => 'check', 'class' => 'w-4 h-4 text-[#FFD700]'])
                    <span>{{ __('site.about.philosophy_tag_1') }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    @include('storefront.partials.icon', ['name' => 'flame', 'class' => 'w-4 h-4 text-[#FFD700]'])
                    <span>{{ __('site.about.philosophy_tag_2') }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-3xl p-8 flex flex-col justify-between space-y-6 shadow-md">
            <div class="p-3 bg-red-50 text-[#B91C1C] rounded-2xl w-fit">
                @include('storefront.partials.icon', ['name' => 'shield', 'class' => 'w-6 h-6'])
            </div>
            <div class="space-y-2">
                <h3 class="text-lg font-black uppercase text-stone-900 leading-tight">
                    {{ __('site.about.safety_title') }}
                </h3>
                <p class="text-stone-500 text-xs leading-relaxed">
                    {{ __('site.about.safety_text') }}
                </p>
            </div>
            <div class="text-[10px] font-extrabold uppercase tracking-wider text-rose-700 bg-rose-50 px-3.5 py-2 rounded-xl border border-rose-100 flex items-center justify-between">
                <span>{{ __('site.about.safety_tag') }}</span>
                <span class="text-lg">🏅</span>
            </div>
        </div>
    </section>

    {{-- 3. INGREDIENTS --}}
    <section class="space-y-6" id="ingredients-matrix">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div class="space-y-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-[#B91C1C]">{{ __('site.about.ingredients_eyebrow') }}</span>
                <h3 class="text-2xl sm:text-3xl font-black italic uppercase text-[#064E3B]">{{ __('site.about.ingredients_title') }}</h3>
            </div>
            <p class="text-stone-500 text-xs sm:max-w-md">
                {{ __('site.about.ingredients_text') }}
            </p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ([
                ['emoji' => '🌶️', 'title' => __('site.about.ingredient_1_title'), 'desc' => __('site.about.ingredient_1_desc')],
                ['emoji' => '🥑', 'title' => __('site.about.ingredient_2_title'), 'desc' => __('site.about.ingredient_2_desc')],
                ['emoji' => '🍞', 'title' => __('site.about.ingredient_3_title'), 'desc' => __('site.about.ingredient_3_desc')],
                ['emoji' => '🌿', 'title' => __('site.about.ingredient_4_title'), 'desc' => __('site.about.ingredient_4_desc')],
            ] as $ing)
                <div class="bg-white border border-stone-200 rounded-2xl p-5 hover:border-[#064E3B] transition-all group duration-300 shadow-sm hover:shadow-md">
                    <span class="text-3xl mb-3 block group-hover:scale-110 transition-transform origin-left">{{ $ing['emoji'] }}</span>
                    <h4 class="font-extrabold text-stone-900 text-sm mb-1">{{ $ing['title'] }}</h4>
                    <p class="text-stone-500 text-xs leading-relaxed">{{ $ing['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 4. TIMELINE --}}
    <section class="bg-stone-100/60 border border-stone-200/80 rounded-3xl p-6 sm:p-10 space-y-8" id="about-timeline-workspace">
        <div class="text-center max-w-xl mx-auto space-y-2">
            <span class="text-[10px] font-black uppercase tracking-wider text-stone-500">{{ __('site.about.timeline_eyebrow') }}</span>
            <h3 class="text-2xl sm:text-3xl font-black italic uppercase text-stone-900">{{ __('site.about.timeline_title') }}</h3>
            <p class="text-stone-500 text-xs">{{ __('site.about.timeline_text') }}</p>
        </div>
        <div class="space-y-4 max-w-2xl mx-auto">
            @foreach ([
                ['year' => 2018, 'subtitle' => __('site.about.timeline_1_subtitle'), 'title' => __('site.about.timeline_1_title'), 'description' => __('site.about.timeline_1_description')],
                ['year' => 2023, 'subtitle' => __('site.about.timeline_2_subtitle'), 'title' => __('site.about.timeline_2_title'), 'description' => __('site.about.timeline_2_description')],
                ['year' => 2025, 'subtitle' => __('site.about.timeline_3_subtitle'), 'title' => __('site.about.timeline_3_title'), 'description' => __('site.about.timeline_3_description')],
                ['year' => 2026, 'subtitle' => __('site.about.timeline_4_subtitle'), 'title' => __('site.about.timeline_4_title'), 'description' => __('site.about.timeline_4_description')],
            ] as $event)
                <div class="bg-white rounded-2xl p-6 sm:p-8 border border-stone-150 shadow-sm space-y-3">
                    <div class="flex justify-between items-baseline gap-2">
                        <span class="text-3xl font-black text-[#0B3B24] font-mono select-none">{{ $event['year'] }}</span>
                        <span class="text-[10px] uppercase font-bold text-[#B91C1C] tracking-widest font-mono">{{ $event['subtitle'] }}</span>
                    </div>
                    <h4 class="text-lg font-black uppercase text-stone-900 leading-tight">{{ $event['title'] }}</h4>
                    <p class="text-stone-500 text-xs sm:text-sm leading-relaxed font-medium">{{ $event['description'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 5. TEAM --}}
    <section class="space-y-6" id="about-team-workspace">
        <div class="text-center max-w-lg mx-auto space-y-2">
            <span class="text-[10px] font-black uppercase tracking-wider text-[#B91C1C]">{{ __('site.about.team_eyebrow') }}</span>
            <h3 class="text-2xl sm:text-3xl font-black italic uppercase text-[#064E3B]">{{ __('site.about.team_title') }}</h3>
            <p class="text-stone-500 text-xs">{{ __('site.about.team_text') }}</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php($chefImage = file_exists(public_path('paprika/chef-a-buu.webp')) ? '/paprika/chef-a-buu.webp' : null)
            @php($nutritionImage = file_exists(public_path('paprika/nutrition-specialist-ai.webp')) ? '/paprika/nutrition-specialist-ai.webp' : null)
            @php($founderImage = file_exists(public_path('paprika/founder-theodoris-malataras.webp')) ? '/paprika/founder-theodoris-malataras.webp' : null)
            @foreach ([
                ['avatar' => '🍳', 'image' => $chefImage, 'color' => 'bg-amber-105 border-amber-400 text-amber-800', 'name' => __('site.about.team_1_name'), 'role' => __('site.about.team_1_role'), 'bio' => __('site.about.team_1_bio')],
                ['avatar' => '🥗', 'image' => $nutritionImage, 'color' => 'bg-emerald-105 border-emerald-400 text-emerald-800', 'name' => __('site.about.team_2_name'), 'role' => __('site.about.team_2_role'), 'bio' => __('site.about.team_2_bio')],
                ['avatar' => '🔥', 'image' => $founderImage, 'color' => 'bg-rose-105 border-rose-400 text-rose-800', 'name' => __('site.about.team_3_name'), 'role' => __('site.about.team_3_role'), 'bio' => __('site.about.team_3_bio')],
            ] as $member)
                <div class="bg-white border border-stone-250/70 rounded-3xl p-6 shadow-sm flex flex-col justify-between hover:scale-[1.01] transition-transform">
                    <div class="space-y-4">
                        @if (! empty($member['image']))
                            <div class="aspect-[4/3] overflow-hidden rounded-2xl border border-stone-200 bg-stone-100 shadow-sm">
                                <img
                                    src="{{ media_url($member['image']) }}"
                                    alt="{{ $member['name'] }}"
                                    class="h-full w-full object-cover object-center"
                                    width="640"
                                    height="480"
                                    loading="lazy"
                                >
                            </div>
                        @else
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl border {{ $member['color'] }}">
                                {{ $member['avatar'] }}
                            </div>
                        @endif
                        <div class="space-y-1">
                            <h4 class="font-extrabold text-stone-900 text-base leading-tight">{{ $member['name'] }}</h4>
                            <span class="text-[10px] uppercase font-black text-emerald-800 tracking-wider block">{{ $member['role'] }}</span>
                        </div>
                        <p class="text-stone-500 text-xs leading-relaxed font-sans font-medium">{{ $member['bio'] }}</p>
                    </div>
                    <div class="pt-4 border-t border-stone-100 flex items-center gap-1.5 text-[9px] uppercase font-bold tracking-widest text-stone-400 mt-6">
                        @include('storefront.partials.icon', ['name' => 'clock', 'class' => 'w-3.5 h-3.5 text-stone-400'])
                        <span>{{ __('site.about.team_working') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- 6. LOCATION --}}
    <section id="about-location-map">
        @include('storefront.partials.branch-map', [
            'branch' => primary_branch(),
            'eyebrow' => __('site.branch_map.about_eyebrow'),
            'title' => __('site.branch_map.about_title'),
            'description' => __('site.branch_map.about_description'),
            'mapHeight' => 'h-80 sm:h-96',
        ])
    </section>

    {{-- 6. CTA --}}
    <section class="bg-gradient-to-r from-[#064E3B] to-[#043427] text-white rounded-3xl p-8 sm:p-12 text-center space-y-6 shadow-2xl relative overflow-hidden" id="about-cta-panel">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_left,rgba(185,28,28,0.12),transparent_45%)]"></div>
        <div class="max-w-2xl mx-auto space-y-4 relative z-10">
            <h3 class="text-2xl sm:text-4xl font-black uppercase italic tracking-tight leading-none text-white">
                {{ __('site.about.cta_title') }}
            </h3>
            <p class="text-white/70 text-xs sm:text-sm font-medium leading-relaxed">
                {{ __('site.about.cta_text') }}
            </p>
            <div class="flex flex-col sm:flex-row justify-center items-center gap-3 pt-4 select-none">
                <a href="{{ localized_route('menu.index') }}" class="w-full sm:w-auto px-8 py-3.5 bg-[#B91C1C] hover:bg-[#991B1B] text-white font-black uppercase text-xs tracking-wider rounded-xl transition duration-150 shadow-lg shadow-black/20 flex items-center justify-center gap-2 group">
                    <span>{{ __('site.about.cta_menu') }}</span>
                    @include('storefront.partials.icon', ['name' => 'arrow-right', 'class' => 'w-4 h-4'])
                </a>
                <a href="{{ localized_route('reservations.create') }}" class="w-full sm:w-auto px-8 py-3.5 bg-white/10 hover:bg-white/20 border border-white/25 text-white font-black uppercase text-xs tracking-wider rounded-xl transition">
                    {{ __('site.about.cta_book') }}
                </a>
            </div>
        </div>
    </section>

</div>
@endsection
