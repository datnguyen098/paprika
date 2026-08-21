@extends('admin.layouts.app')

@section('title', 'Dịch tự động')

@section('content')
    @php
        $provider = old('translation_provider', setting('translation_provider') ?: 'microsoft');
        $count = $usage['character_count'] ?? null;
        $limit = $usage['character_limit'] ?? null;
        $percent = $limit ? min(100, round(($count / $limit) * 100, 1)) : null;
        $warningLimit = (int) (setting('translation_monthly_limit_warning') ?: 450000);
        $targetLocales = array_filter(config('locales.supported', []), fn ($cfg) => ($cfg['is_active'] ?? false) && ($cfg['prefix'] ?? null) !== null);
    @endphp

    <div class="mb-6 rounded-2xl bg-emerald-950 p-6 text-white shadow-sm">
        <p class="text-sm font-bold uppercase tracking-[0.25em] text-emerald-200">DeepL / Microsoft Translator</p>
        <h1 class="mt-2 text-2xl font-bold">Cài đặt dịch tự động</h1>
        <p class="mt-2 max-w-3xl text-sm leading-7 text-emerald-50/80">
            Dịch tự động chạy khi admin bấm nút "Dịch tự động" trong tab English hoặc Ελληνικά khi sửa nội dung. Kết quả đổ vào form để kiểm tra trước khi lưu.
        </p>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="POST" action="{{ route('admin.translations.settings.update') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @csrf
            @method('PUT')

            <div class="grid gap-5 lg:grid-cols-2">
                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 font-semibold text-slate-700 lg:col-span-2">
                    <input type="checkbox" name="translation_enabled" value="1" @checked(old('translation_enabled', setting('translation_enabled')) === '1')>
                    Bật dịch tự động
                </label>

                <div class="lg:col-span-2">
                    <label for="translation_provider" class="admin-label">Nhà cung cấp dịch</label>
                    <select id="translation_provider" name="translation_provider" class="admin-input" data-translation-provider>
                        <option value="microsoft" @selected($provider === 'microsoft')>Microsoft Translator API</option>
                        <option value="deepl" @selected($provider === 'deepl')>DeepL API</option>
                    </select>
                    <p class="mt-2 text-sm text-slate-500">DeepL có API đọc usage. Microsoft Translator thường xem quota trong Azure Portal.</p>
                    @error('translation_provider') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-2xl border border-slate-200 p-4 lg:col-span-2" data-provider-panel="deepl">
                    <h2 class="text-lg font-bold text-slate-950">DeepL</h2>
                    <div class="mt-4 grid gap-5 lg:grid-cols-2">
                        <div>
                            <label for="deepl_api_plan" class="admin-label">Gói API</label>
                            <select id="deepl_api_plan" name="deepl_api_plan" class="admin-input">
                                <option value="free" @selected(old('deepl_api_plan', setting('deepl_api_plan') ?: 'free') === 'free')>DeepL API Free / Developer</option>
                                <option value="pro" @selected(old('deepl_api_plan', setting('deepl_api_plan')) === 'pro')>DeepL API Pro</option>
                            </select>
                            @error('deepl_api_plan') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="admin-label">Ngôn ngữ đích</label>
                            <div class="mt-2 space-y-1">
                                @foreach ($targetLocales as $code => $cfg)
                                    <span class="inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">{{ $cfg['name'] }} → {{ config("locales.translation_targets.deepl.{$code}", 'N/A') }}</span>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-slate-400">Tự động chọn ngôn ngữ đích theo tab admin đang mở. Cấu hình trong <code>config/locales.php</code>.</p>
                        </div>

                        <div>
                            <label for="deepl_source_lang" class="admin-label">Ngôn ngữ nguồn</label>
                            <select id="deepl_source_lang" name="deepl_source_lang" class="admin-input">
                                <option value="VI" @selected(old('deepl_source_lang', setting('deepl_source_lang') ?: 'VI') === 'VI')>Tiếng Việt</option>
                            </select>
                            @error('deepl_source_lang') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="translation_monthly_limit_warning" class="admin-label">Mốc cảnh báo usage</label>
                            <input id="translation_monthly_limit_warning" type="number" min="1000" name="translation_monthly_limit_warning" value="{{ old('translation_monthly_limit_warning', $warningLimit) }}" class="admin-input">
                            @error('translation_monthly_limit_warning') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="lg:col-span-2">
                            <label for="deepl_api_key" class="admin-label">DeepL API key</label>
                            <input id="deepl_api_key" type="password" name="deepl_api_key" class="admin-input" autocomplete="new-password" placeholder="{{ $hasDeepLKey ? 'Đã lưu API key. Nhập key mới nếu muốn thay đổi.' : 'Dán DeepL API key tại đây' }}">
                            <p class="mt-2 text-sm text-slate-500">Key được lưu mã hóa trong database. Quota DeepL có thể thay đổi theo loại tài khoản, nên admin sẽ đọc usage trực tiếp từ API khi provider hỗ trợ.</p>
                            @error('deepl_api_key') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        @if ($hasDeepLKey)
                            <label class="flex items-center gap-2 font-semibold text-red-700 lg:col-span-2">
                                <input type="checkbox" name="clear_deepl_api_key" value="1">
                                Xóa DeepL API key hiện tại
                            </label>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-4 lg:col-span-2" data-provider-panel="microsoft">
                    <h2 class="text-lg font-bold text-slate-950">Microsoft Translator</h2>
                    <p class="mt-1 text-sm text-slate-500">Dùng Azure AI Translator. Quota và chi phí xem trong Azure Portal theo pricing tier của resource Translator.</p>
                    <div class="mt-4 grid gap-5 lg:grid-cols-2">
                        <div>
                            <label for="microsoft_translator_region" class="admin-label">Azure region</label>
                            <input id="microsoft_translator_region" name="microsoft_translator_region" value="{{ old('microsoft_translator_region', setting('microsoft_translator_region')) }}" class="admin-input" placeholder="eastasia, southeastasia...">
                            @error('microsoft_translator_region') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">Ngôn ngữ đích</label>
                            <div class="mt-2 space-y-1">
                                @foreach ($targetLocales as $code => $cfg)
                                    <span class="inline-block rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">{{ $cfg['name'] }} → {{ config("locales.translation_targets.microsoft.{$code}", 'N/A') }}</span>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-slate-400">Tự động chọn ngôn ngữ đích theo tab admin đang mở. Cấu hình trong <code>config/locales.php</code>.</p>
                        </div>
                        <div class="lg:col-span-2">
                            <label for="microsoft_translator_endpoint" class="admin-label">Endpoint</label>
                            <input id="microsoft_translator_endpoint" name="microsoft_translator_endpoint" value="{{ old('microsoft_translator_endpoint', setting('microsoft_translator_endpoint') ?: 'https://api.cognitive.microsofttranslator.com') }}" class="admin-input">
                            @error('microsoft_translator_endpoint') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="lg:col-span-2">
                            <label for="microsoft_translator_key" class="admin-label">Microsoft Translator key</label>
                            <input id="microsoft_translator_key" type="password" name="microsoft_translator_key" class="admin-input" autocomplete="new-password" placeholder="{{ $hasMicrosoftKey ? 'Đã lưu Microsoft key. Nhập key mới nếu muốn thay đổi.' : 'Dán Azure Translator key tại đây' }}">
                            @error('microsoft_translator_key') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        @if ($hasMicrosoftKey)
                            <label class="flex items-center gap-2 font-semibold text-red-700 lg:col-span-2">
                                <input type="checkbox" name="clear_microsoft_translator_key" value="1">
                                Xóa Microsoft Translator key hiện tại
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="admin-btn-primary">Lưu cài đặt</button>
                <button type="button" class="admin-btn-secondary" data-deepl-test-url="{{ route('admin.translations.test') }}">Kiểm tra kết nối</button>
                <button type="button" class="admin-btn-secondary" data-deepl-usage-url="{{ route('admin.translations.usage') }}">Kiểm tra quota</button>
            </div>

            <div class="mt-4 hidden rounded-2xl border p-4 text-sm" data-deepl-status></div>
        </form>

        <aside class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Trạng thái quota</p>
            @if ($provider === 'deepl' && $usage && $limit)
                <p class="mt-3 text-3xl font-black text-emerald-950">{{ number_format($count) }}</p>
                <p class="text-sm text-slate-500">/ {{ number_format($limit) }} ký tự</p>
                <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-emerald-600" style="width: {{ $percent }}%"></div>
                </div>
            @else
                <p class="mt-3 text-sm leading-7 text-slate-600">Provider hiện tại chưa có usage hiển thị trong admin. Với Microsoft, kiểm tra quota trong Azure Portal.</p>
            @endif
        </aside>
    </div>
@endsection
