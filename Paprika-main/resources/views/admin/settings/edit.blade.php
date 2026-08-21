@extends('admin.layouts.app')

@section('title', $title)

@section('content')
    <form method="POST" action="{{ $action }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        @csrf
        @method('PUT')

        <div class="admin-form-tabs" data-admin-tabs>
            <div class="admin-tab-nav" role="tablist" aria-label="Ngôn ngữ cấu hình website">
                <button type="button" class="admin-tab-button is-active" data-admin-tab="vi">Tiếng Việt</button>
                @foreach ($translationKeyGroups as $locale => $translationKeys)
                    <button type="button" class="admin-tab-button" data-admin-tab="{{ $locale }}">{{ $locale === 'el' ? 'Ελληνικά' : 'English' }}</button>
                @endforeach
            </div>

            <div class="admin-tab-panel is-active" data-admin-tab-panel="vi">
                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach ($keys as $key => $label)
                        <div @class(['lg:col-span-2' => in_array($key, ['short_description', 'footer_description'])])>
                            <label for="{{ $key }}" class="admin-label">{{ $label }}</label>
                            @if (in_array($key, ['short_description', 'footer_description']))
                                <textarea id="{{ $key }}" name="{{ $key }}" rows="4" class="admin-input">{{ old($key, setting($key)) }}</textarea>
                            @elseif ($key === 'show_dish_prices')
                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 font-semibold text-slate-700">
                                    <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, setting($key, '1')) === '1')>
                                    Hiện giá món ăn ở trang chủ, thực đơn và chi tiết món
                                </label>
                            @elseif ($key === 'disable_offline_payment')
                                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 font-semibold text-slate-700">
                                    <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, setting($key)) === '1')>
                                    Tắt thanh toán tại quán — khách chỉ thấy tùy chọn thanh toán online
                                </label>
                            @elseif ($key === 'default_locale')
                                <select id="{{ $key }}" name="{{ $key }}" class="admin-input">
                                    <option value="vi" @selected(old($key, setting($key, 'vi')) === 'vi')>Tiếng Việt</option>
                                    <option value="en" @selected(old($key, setting($key, 'vi')) === 'en')>English</option>
                                    <option value="el" @selected(old($key, setting($key, 'vi')) === 'el')>Ελληνικά</option>
                                </select>
                            @elseif ($key === 'business_timezone')
                                @php $timezones = \App\Support\TimezoneOptions::business(); @endphp
                                <select id="{{ $key }}" name="{{ $key }}" class="admin-input">
                                    @foreach ($timezones as $value => $label)
                                        <option value="{{ $value }}" @selected(old($key, setting($key, config('app.timezone'))) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-slate-500">Dùng cho giờ đặt hàng, khung giờ bán món và ngày vận hành. Nếu cơ sở có múi giờ riêng thì cơ sở sẽ ưu tiên hơn.</p>
                            @elseif ($key === 'open_days')
                                @php
                                    $selectedOpenDays = \App\Support\OpenDays::normalize(
                                        session()->hasOldInput('_open_days_present')
                                            ? old($key, [])
                                            : setting($key, implode(',', \App\Support\OpenDays::DEFAULT_DAYS))
                                    );
                                @endphp
                                <input type="hidden" name="_open_days_present" value="1">
                                <div class="grid gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 lg:grid-cols-4">
                                    @foreach (\App\Support\OpenDays::options() as $day => $dayLabel)
                                        <label class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-100">
                                            <input type="checkbox" name="{{ $key }}[]" value="{{ $day }}" @checked(in_array($day, $selectedOpenDays, true))>
                                            {{ $dayLabel }}
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-slate-500">Checkout chỉ nhận đơn trong ngày hiện tại và sẽ chặn nếu hôm nay không nằm trong danh sách này.</p>
                            @else
                                <input id="{{ $key }}" name="{{ $key }}" value="{{ old($key, setting($key)) }}" class="admin-input">
                            @endif
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
                                <p class="mt-1 text-emerald-800/80">Các trường này dùng cho bản {{ $locale === 'el' ? 'tiếng Hy Lạp' : 'tiếng Anh' }} ở header, footer, trang liên hệ và các meta mặc định. Có thể để trống để hệ thống tự dùng bản tiếng Việt.</p>
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
                            <div @class(['lg:col-span-2' => str_contains($key, 'short_description') || str_contains($key, 'footer_description')])>
                                <label for="{{ $key }}" class="admin-label">{{ $label }}</label>
                                @if (str_contains($key, 'short_description') || str_contains($key, 'footer_description'))
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
            <button type="submit" class="admin-btn-primary">Lưu cài đặt</button>
            <a href="{{ route('admin.dashboard') }}" class="admin-btn-secondary">Quay lại</a>
        </div>
    </form>
@endsection
