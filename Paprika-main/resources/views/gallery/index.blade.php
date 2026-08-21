@extends('storefront.layouts.app')

@section('content')
    <section class="bg-[#064E3B] text-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-xs font-black uppercase tracking-[0.28em] text-[#F7C85A]">{{ __('site.gallery_page.eyebrow') }}</p>
            <h1 class="mt-4 text-4xl font-black uppercase italic tracking-tight sm:text-5xl">{{ __('site.gallery_page.title') }}</h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-white/75 sm:text-base">{{ __('site.gallery_page.description') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        @php($images = $branches->flatMap->galleryImages->merge($sharedImages))
        <div class="gallery-masonry-grid">
            @forelse ($images as $image)
                <figure class="gallery-figure">
                    <img
                        src="{{ media_url($image->image) }}"
                        alt="{{ $image->localized('alt_text') ?: $image->localized('title') ?: 'Paprika Patras' }}"
                        class="gallery-figure-img"
                        loading="lazy"
                    >
                    <figcaption class="gallery-figcaption">
                        @if ($image->localized('title'))
                            <span class="gallery-figure-title">{{ $image->localized('title') }}</span>
                        @endif
                        @if ($image->localized('description'))
                            <p class="gallery-figure-desc">{{ $image->localized('description') }}</p>
                        @endif
                        @if ($image->branch)
                            <span class="gallery-figure-branch">{{ $image->branch->name }}</span>
                        @endif
                    </figcaption>
                </figure>
            @empty
                <p class="rounded-2xl bg-white p-6 text-stone-600 shadow-sm ring-1 ring-stone-200 col-span-full">{{ __('site.gallery_page.empty') }}</p>
            @endforelse
        </div>
    </section>
@endsection
