@extends('admin.layouts.app')

@section('title', $title)

@section('content')
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @method('PUT')

        <div class="admin-form-tabs" data-admin-tabs>
            <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ SEO">
                <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
                @foreach ($translationKeyGroups as $locale => $translationKeys)
                    <button type="button" class="admin-tab-button" data-admin-tab="{{ $locale }}">{{ $locale === 'el' ? 'Ελληνικά' : 'English' }}</button>
                @endforeach
            </div>

            <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach ($keys as $key => $label)
                        <div @class(['lg:col-span-2' => in_array($key, ['default_meta_description', 'default_meta_keywords', 'google_analytics_code', 'google_search_console', 'facebook_pixel_code', 'robots_txt_content'])])>
                            <label for="{{ $key }}" class="admin-label">{{ $label }}</label>
                            @if (in_array($key, ['default_meta_description', 'default_meta_keywords', 'google_analytics_code', 'google_search_console', 'facebook_pixel_code', 'robots_txt_content']))
                                <textarea id="{{ $key }}" name="{{ $key }}" rows="4" class="admin-input">{{ old($key, setting($key)) }}</textarea>
                            @else
                                <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, setting($key)) }}" class="admin-input">
                            @endif
                            @error($key) <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    @foreach ($imageKeys as $key => $label)
                        <div class="lg:col-span-2">
                            <label for="{{ $key }}" class="admin-label">{{ $label }}</label>
                            @if (setting($key))
                                <img src="{{ media_url(setting($key)) }}" alt="{{ $label }}" class="mb-3 h-36 w-full rounded-xl object-cover">
                            @endif
                            <input id="{{ $key }}" type="file" name="{{ $key }}" accept=".jpg,.jpeg,.png,.webp,.svg,image/jpeg,image/png,image/webp,image/svg+xml" class="admin-input">
                            <p class="mt-2 text-sm text-slate-500">Chấp nhận JPG, PNG, WEBP, SVG. Dung lượng tối đa 10MB.</p>
                            @error($key) <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            @foreach ($translationKeyGroups as $locale => $translationKeys)
                <div class="admin-tab-panel" data-admin-tab-panel="{{ $locale }}" hidden>
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 text-sm text-emerald-900">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold">Bản dịch {{ $locale === 'el' ? 'Ελληνικά' : 'English' }}</p>
                                <p class="mt-1 text-emerald-800/80">SEO {{ $locale === 'el' ? 'tiếng Hy Lạp' : 'tiếng Anh' }} sẽ được dùng khi khách truy cập URL /{{ $locale }}. Nếu để trống, hệ thống dùng SEO tiếng Việt làm fallback.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="admin-btn-mini" data-deepl-translate data-deepl-url="{{ route('admin.translations.translate') }}">Dịch tự động</button>
                                <button type="button" class="admin-btn-mini" data-copy-translation>Copy từ tiếng Việt</button>
                            </div>
                        </div>
                        <div class="mt-3 hidden rounded-xl border bg-white p-3 text-sm" data-deepl-inline-status></div>
                    </div>
                    <div class="mt-5 grid gap-5 lg:grid-cols-2">
                        @foreach ($translationKeys as $key => $label)
                            @php $sourceKey = \Illuminate\Support\Str::replaceLast('_'.$locale, '', $key); @endphp
                            <div @class(['lg:col-span-2' => str_contains($key, 'default_meta_description') || str_contains($key, 'default_meta_keywords')])>
                                <label for="{{ $key }}" class="admin-label">{{ $label }}</label>
                                @if (str_contains($key, 'default_meta_description') || str_contains($key, 'default_meta_keywords'))
                                    <textarea id="{{ $key }}" name="{{ $key }}" rows="4" class="admin-input" data-copy-field="{{ $sourceKey }}">{{ old($key, setting($key)) }}</textarea>
                                @else
                                    <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, setting($key)) }}" class="admin-input" data-copy-field="{{ $sourceKey }}">
                                @endif
                                @error($key) <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="admin-btn-primary">Lưu SEO</button>
            <a href="{{ route('admin.dashboard') }}" class="admin-btn-secondary">Quay lại</a>
        </div>
    </form>
@endsection
