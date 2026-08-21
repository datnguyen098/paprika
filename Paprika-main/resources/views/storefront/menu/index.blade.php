@extends('storefront.layouts.app')

@section('content')
    <div class="bg-[#FDFBF7] text-[#1A1A1A] min-h-screen" id="menu-section-container">
        <section class="relative bg-[#064E3B] text-white py-12 px-6 sm:px-12 border-b border-[#043427] overflow-hidden">
            <img src="{{ media_variant_url('/paprika/cover.jpg', 'hero') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-25" loading="lazy" aria-hidden="true">
            <div class="absolute inset-0 bg-[#043427]/80" aria-hidden="true"></div>
            <div class="relative max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                <div class="space-y-1">
                    <span class="bg-[#B91C1C] text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded">{{ __('site.menu.hero_badge') }}</span>
                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight leading-none italic uppercase font-heading">{{ __('site.menu.hero_title') }}</h1>
                    <p class="text-white/80 text-xs sm:text-sm max-w-lg leading-relaxed">{{ __('site.menu.hero_description') }}</p>
                </div>
                <div class="flex gap-4 sm:gap-6 bg-black/15 p-4 rounded-xl border border-white/10 shrink-0">
                    <div class="text-center px-1"><span class="block text-xl font-black text-[#FFD700]">{{ $dishes->total() }}+</span><span class="block text-[9px] text-white/70 uppercase font-bold tracking-wider">{{ __('site.menu.dishes_count') }}</span></div>
                    <div class="border-l border-white/10"></div>
                    <div class="text-center px-1"><span class="block text-xl font-black text-[#FFD700]">{{ $categories->count() }}</span><span class="block text-[9px] text-white/70 uppercase font-bold tracking-wider">{{ __('site.menu.categories_count') }}</span></div>
                </div>
            </div>
        </section>

        <nav class="border-b border-stone-200 bg-white shadow-sm py-3 px-6 sm:px-12 flex gap-3 overflow-x-auto no-scrollbar z-10 sticky top-20" aria-label="{{ __('site.menu.categories_nav') }}">
            <div class="max-w-7xl mx-auto w-full flex items-center gap-2">
                <a href="{{ localized_route('menu.index', request()->filled('q') ? ['q' => $search] : []) }}" class="px-5 py-2 rounded-full text-xs font-bold uppercase tracking-widest flex-shrink-0 transition-all {{ blank($selectedCategory) ? 'bg-[#064E3B] text-white shadow-sm' : 'bg-stone-100 hover:bg-stone-200 text-stone-600' }}">{{ __('site.menu.all') }}</a>
                @foreach ($categories as $category)
                    @php $slug = $category->localizedSlug(); @endphp
                    <a href="{{ localized_route('menu.index', array_filter(['category' => $slug, 'q' => $search])) }}" class="px-5 py-2 rounded-full text-xs font-bold uppercase tracking-widest flex-shrink-0 transition-all {{ $selectedCategory === $slug || $selectedCategory === $category->slug ? 'bg-[#064E3B] text-white shadow-sm' : 'bg-stone-100 hover:bg-stone-200 text-stone-600' }}">{{ $category->localized('name') }}</a>
                @endforeach
            </div>
        </nav>

        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <form action="{{ localized_route('menu.index') }}" method="GET" class="mb-8 bg-white p-4 rounded-xl border border-stone-200/80 shadow-sm max-w-xl">
                @if ($selectedCategory)<input type="hidden" name="category" value="{{ $selectedCategory }}">@endif
                <label for="menu-search-input" class="block text-[10px] uppercase font-bold text-stone-400 tracking-widest mb-2">{{ __('site.menu.search_label') }}</label>
                <div class="relative">
                    <input id="menu-search-input" type="search" name="q" value="{{ $search }}" placeholder="{{ __('site.menu.search_placeholder') }}" class="w-full bg-stone-50 border border-stone-200 focus:border-[#064E3B] focus:ring-1 focus:ring-[#064E3B] rounded-lg pl-9 pr-3 py-2 text-sm outline-none transition">
                    @include('storefront.partials.icon', ['name' => 'search', 'class' => 'w-3.5 h-3.5 text-stone-400 absolute left-3 top-3'])
                </div>
            </form>

            <div class="flex items-center justify-between border-b border-stone-200 pb-3 mb-6">
                <span class="text-[10px] font-black text-[#064E3B] uppercase tracking-wider">{{ $selectedCategory ? __('site.menu.filtered_menu') : __('site.menu.all_items') }}</span>
                <span class="text-xs text-stone-500 font-medium">{{ __('site.menu.matches') }}: <strong>{{ $dishes->count() }}</strong></span>
            </div>

            @if ($dishes->isEmpty())
                <div class="bg-white border border-stone-200 p-12 rounded-3xl text-center space-y-4 shadow-sm">
                    <h3 class="font-extrabold text-stone-800 text-base">{{ __('site.menu.empty_title') }}</h3>
                    <p class="text-stone-500 text-xs max-w-sm mx-auto">{{ __('site.menu.empty_text') }}</p>
                    <a href="{{ localized_route('menu.index') }}" class="inline-block px-5 py-2 bg-[#064E3B] hover:bg-[#B91C1C] text-white text-[10px] font-black uppercase tracking-widest rounded-full transition shadow">{{ __('site.menu.clear_filters') }}</a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="menu-items-cards-grid">
                    @foreach ($dishes as $dish)
                        @include('storefront.components.product-card', ['dish' => $dish, 'variant' => 'menu'])
                    @endforeach
                </div>
                @if ($dishes->hasPages())
                    <div class="mt-10 flex justify-center">{{ $dishes->links() }}</div>
                @endif
            @endif
        </section>

        <div data-customizer hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-4" style="background: rgba(4, 50, 39, 0.75); backdrop-filter: blur(4px);">
            <div class="relative flex max-h-[82svh] w-full max-w-[22.5rem] flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white text-stone-900 shadow-2xl animate-slideIn sm:max-h-[calc(100svh-2rem)] sm:max-w-2xl sm:rounded-3xl" id="menu-customizer-modal" onclick="event.stopPropagation()">
                <button type="button" data-close-customizer class="absolute right-3 top-3 z-20 hidden h-9 w-9 items-center justify-center rounded-full border border-stone-200 bg-white text-xl font-black leading-none text-stone-700 shadow-lg shadow-stone-900/10 transition hover:border-[#B91C1C]/30 hover:bg-[#B91C1C] hover:text-white sm:flex" aria-label="{{ __('site.cart.close') }}">&times;</button>
                {{-- Mobile: stacked. Desktop: side-by-side --}}
                <div class="flex min-h-0 flex-col sm:flex-row">
                    {{-- Image panel --}}
                    <div class="relative hidden shrink-0 bg-stone-200 sm:block sm:h-auto sm:min-h-0 sm:w-56 sm:self-stretch">
                        <img data-customizer-image src="" alt="" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-stone-950/70 via-stone-950/20 to-transparent"></div>
                    </div>
                    {{-- Content panel --}}
                    <div class="flex min-h-0 flex-1 flex-col">
                        {{-- Mobile-only header --}}
                        <div class="flex items-start gap-3 px-3.5 pb-2 pt-3 sm:hidden">
                            <div class="min-w-0 flex-1">
                                <p class="text-[9px] font-black uppercase tracking-[0.14em] text-[#B91C1C]" data-customizer-price-from></p>
                                <h3 class="mt-0.5 text-sm font-extrabold leading-tight text-stone-900 line-clamp-2" data-customizer-name></h3>
                            </div>
                            <button type="button" data-close-customizer class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-stone-200 bg-white text-lg font-black leading-none text-stone-700 shadow-sm transition hover:border-[#B91C1C]/30 hover:bg-[#B91C1C] hover:text-white" aria-label="{{ __('site.cart.close') }}">&times;</button>
                        </div>
                        {{-- Desktop-only header --}}
                        <div class="hidden sm:block px-4 pt-4 pr-14 pb-0">
                            <p class="text-[9px] font-black uppercase tracking-[0.14em] text-[#B91C1C]" data-customizer-price-from></p>
                            <h3 class="font-extrabold text-base leading-tight text-stone-900 mt-0.5 line-clamp-1" data-customizer-name></h3>
                        </div>
                        {{-- Options --}}
                        <div class="min-h-0 flex-1 overflow-y-auto px-3 py-2 space-y-2 no-scrollbar max-h-[42svh] sm:max-h-[48svh] sm:px-4 sm:py-3 sm:space-y-2.5" data-customizer-options-container
                     data-hint-single="{{ __('site.dish_detail.hint_single') }}"
                     data-hint-multiple="{{ __('site.dish_detail.hint_multiple') }}"
                     data-hint-exclude="{{ __('site.dish_detail.hint_exclude') }}"
                     data-included="{{ __('site.dish_detail.included') }}"
                     data-default="{{ __('site.dish_detail.standard_recipe') }}">
                    <p class="text-[10px] text-stone-400 text-center">{{ __('site.menu.loading_options') }}</p>
                </div>
                        {{-- Footer --}}
                        <div class="shrink-0 border-t border-stone-200 bg-stone-50/50 px-3 py-2 space-y-1.5 sm:px-4 sm:py-2.5 sm:space-y-2">
                            <div class="flex items-start gap-2 text-[10px] leading-5 text-stone-500 min-h-[20px]">
                                <span class="shrink-0 text-[#064E3B] font-black text-[9px] uppercase tracking-wider">{{ __('site.menu.summary') }}:</span>
                                <span class="min-w-0 flex-1 whitespace-normal break-words" data-customizer-summary>{{ __('site.dish_detail.standard_recipe') }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-1 rounded-full border border-stone-200 bg-white px-1.5 py-0.5">
                                    <button type="button" class="w-6 h-6 rounded-full text-xs font-black text-stone-500 hover:text-stone-900" data-customizer-qty="dec" aria-label="{{ __('site.dish_detail.qty_dec') }}">&minus;</button>
                                    <span class="w-5 text-center font-mono text-xs font-black" data-customizer-qty-label>1</span>
                                    <button type="button" class="w-6 h-6 rounded-full text-xs font-black text-[#064E3B] hover:text-[#B91C1C]" data-customizer-qty="inc" aria-label="{{ __('site.dish_detail.qty_inc') }}">+</button>
                                </div>
                                <div class="text-right">
                                    <strong class="block font-mono text-lg font-black text-[#064E3B]" data-customizer-total></strong>
                                </div>
                            </div>
                            <label class="block">
                                <textarea name="customization_note" rows="1" maxlength="500" data-customizer-note class="w-full rounded-lg border border-stone-200 bg-white px-2.5 py-1.5 text-[11px] leading-5 text-stone-700 outline-none focus:border-[#064E3B] resize-none" placeholder="{{ __('site.dish_detail.note_placeholder') }}"></textarea>
                            </label>
                            <form data-customizer-form method="POST" action="" data-ajax-cart-form data-close-customizer-on-success>
                                @csrf
                                <input type="hidden" name="quantity" value="1" data-customizer-qty-input>
                                <div data-customizer-option-inputs></div>
                                <input type="hidden" name="customization_note" value="" data-customizer-note-input>
                                <button type="submit" class="flex h-9 w-full items-center justify-center gap-1.5 rounded-xl bg-[#B91C1C] px-3 text-[10px] font-black uppercase tracking-[0.1em] text-white shadow-lg shadow-red-900/15 transition hover:bg-[#991B1B] active:scale-[0.99] sm:h-10 sm:text-[11px]">
                                    @include('storefront.partials.icon', ['name' => 'cart', 'class' => 'w-3.5 h-3.5'])
                                    {{ __('site.dish_detail.add_to_cart') }} &mdash; <span class="font-mono text-[#FFD700]" data-customizer-total-inline></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
