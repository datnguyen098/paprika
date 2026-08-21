@extends('admin.layouts.app')

@section('title', $title)

@section('content')
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @method('PUT')

        <div class="grid gap-6 md:grid-cols-2">
            @foreach ($imageKeys as $key => $label)
                <div class="rounded-2xl border border-slate-200 p-4">
                    <label for="{{ $key }}" class="admin-label">{{ $label }}</label>
                    @if (setting($key))
                        <img src="{{ media_url(setting($key)) }}" alt="{{ $label }}" class="mb-3 h-28 w-full rounded-xl object-contain bg-slate-50">
                    @endif
                    <input id="{{ $key }}" type="file" name="{{ $key }}" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
                    <p class="mt-2 text-sm text-slate-500">Chấp nhận JPG, PNG, WEBP, SVG. Dung lượng tối đa 10MB.</p>
                    @error($key) <p class="form-error">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="admin-btn-primary">Cập nhật nhận diện</button>
            <a href="{{ route('admin.dashboard') }}" class="admin-btn-secondary">Quay lại</a>
        </div>
    </form>
@endsection
