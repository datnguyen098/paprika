@extends('storefront.layouts.app')

@section('content')
    <section class="bg-[#064E3B] text-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-xs font-black uppercase tracking-[0.28em] text-[#F7C85A]">Paprika Patras</p>
            <h1 class="mt-4 max-w-3xl text-4xl font-black uppercase italic tracking-tight sm:text-5xl">{{ $page->localized('title') }}</h1>
            @if ($page->image)
                <img src="{{ media_url($page->image) }}" alt="{{ $page->localized('title') }}" class="mt-8 aspect-[16/7] w-full rounded-2xl object-cover shadow-2xl" loading="lazy">
            @endif
        </div>
    </section>

    <section class="bg-[#FDFBF7]">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <article class="prose prose-stone max-w-none prose-headings:font-black prose-headings:text-[#064E3B] prose-a:text-[#B91C1C]">
                {!! $page->localized('content') ?: '<p>Paprika phục vụ ẩm thực Việt Nam và món nướng Hy Lạp tại Patras với tinh thần ấm áp, nhanh gọn và chỉn chu.</p>' !!}
            </article>
        </div>
    </section>
@endsection
