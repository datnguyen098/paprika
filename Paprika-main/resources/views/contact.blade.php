@extends('storefront.layouts.app')

@section('content')
    <section class="bg-[#064E3B] text-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.28em] text-[#F7C85A]">Contact</p>
                <h1 class="mt-4 text-4xl font-black uppercase italic tracking-tight sm:text-5xl">Liên hệ Paprika</h1>
                <p class="mt-4 max-w-xl text-sm leading-7 text-white/80">Gọi cho quán hoặc gửi lời nhắn, đội ngũ Paprika sẽ phản hồi sớm nhất.</p>
            </div>
            <div class="rounded-2xl bg-white p-5 text-stone-900 shadow-2xl">
                @foreach ($branches as $branch)
                    <div class="rounded-xl bg-stone-50 p-4">
                        <h2 class="text-lg font-black text-[#064E3B]">{{ $branch->name }}</h2>
                        <p class="mt-2 text-sm text-stone-600">{{ $branch->address }}</p>
                        <p class="mt-2 text-sm font-bold text-stone-900">{{ $branch->hotline ?: $branch->phone }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
        <form method="POST" action="{{ localized_route('contact.store') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="text-xs font-black uppercase tracking-widest text-stone-500">Tên</label>
                    <input id="name" name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border border-stone-200 px-4 py-3 text-sm" required>
                    @error('name') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="text-xs font-black uppercase tracking-widest text-stone-500">Số điện thoại</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" class="mt-2 w-full rounded-xl border border-stone-200 px-4 py-3 text-sm">
                    @error('phone') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="email" class="text-xs font-black uppercase tracking-widest text-stone-500">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border border-stone-200 px-4 py-3 text-sm">
                    @error('email') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="message" class="text-xs font-black uppercase tracking-widest text-stone-500">Nội dung</label>
                    <textarea id="message" name="message" rows="5" class="mt-2 w-full rounded-xl border border-stone-200 px-4 py-3 text-sm" required>{{ old('message') }}</textarea>
                    @error('message') <p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
            <button class="mt-5 rounded-full bg-[#B91C1C] px-6 py-3 text-sm font-black uppercase text-white">Gửi liên hệ</button>
        </form>
    </section>
@endsection
