@extends('storefront.layouts.app')

@section('content')
    <section class="bg-[#064E3B] text-white">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
            <p class="text-xs font-black uppercase tracking-[0.28em] text-[#F7C85A]">Blog</p>
            <h1 class="mt-4 text-4xl font-black uppercase italic tracking-tight sm:text-5xl">Tin từ Paprika</h1>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($posts as $post)
                <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-200">
                    @if ($post->thumbnail)
                        <img src="{{ media_url($post->thumbnail) }}" alt="{{ $post->localized('title') }}" class="aspect-[4/3] w-full object-cover" loading="lazy">
                    @endif
                    <div class="p-5">
                        <h2 class="text-xl font-black text-[#064E3B]">{{ $post->localized('title') }}</h2>
                        <p class="mt-2 line-clamp-3 text-sm leading-6 text-stone-600">{{ $post->localized('excerpt') }}</p>
                        <a href="{{ localized_route('blog.show', ['slug' => $post->localizedSlug()]) }}" class="mt-4 inline-flex text-sm font-black uppercase text-[#B91C1C]">Đọc tiếp</a>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl bg-white p-6 text-stone-600 shadow-sm ring-1 ring-stone-200">Paprika đang cập nhật bài viết mới.</p>
            @endforelse
        </div>
        <div class="mt-8">{{ $posts->links() }}</div>
    </section>
@endsection
