@extends('storefront.layouts.app')

@section('content')
    <article>
        <section class="bg-[#064E3B] text-white">
            <div class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
                <p class="text-xs font-black uppercase tracking-[0.28em] text-[#F7C85A]">Paprika Blog</p>
                <h1 class="mt-4 text-4xl font-black uppercase italic tracking-tight sm:text-5xl">{{ $post->localized('title') }}</h1>
                @if ($post->thumbnail)
                    <img src="{{ media_url($post->thumbnail) }}" alt="{{ $post->localized('title') }}" class="mt-8 aspect-[16/9] w-full rounded-2xl object-cover shadow-2xl" loading="lazy">
                @endif
            </div>
        </section>

        <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="prose prose-stone max-w-none prose-headings:font-black prose-headings:text-[#064E3B] prose-a:text-[#B91C1C]">
                {!! $post->localized('content') !!}
            </div>
        </section>
    </article>
@endsection
