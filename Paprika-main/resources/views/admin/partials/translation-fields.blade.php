@php
    $translation = $model->translation($locale ?? 'en');
    $inputPrefix = "translations[{$locale}]";
    $errorPrefix = "translations.{$locale}";
    $localeLabel = $localeLabel ?? $locale;
@endphp

<div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 text-sm text-emerald-900">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="font-semibold">Bản dịch {{ $localeLabel }}</p>
            <p class="mt-1 text-emerald-800/80">Điền nội dung {{ $localeLabel }} để frontend hiển thị chuẩn SEO. Có thể copy bản tiếng Việt rồi chỉnh lại.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="admin-btn-mini" data-deepl-translate data-deepl-url="{{ route('admin.translations.translate') }}">Dịch tự động</button>
            <button type="button" class="admin-btn-mini" data-copy-translation>Copy từ tiếng Việt</button>
        </div>
    </div>
    <div class="mt-3 hidden rounded-xl border bg-white p-3 text-sm" data-deepl-inline-status></div>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-2">
    @foreach ($fields as $field)
        @php
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $rows = $field['rows'] ?? 3;
            $copyFrom = $field['copy_from'] ?? $name;
            $list = $field['list'] ?? null;
            $placeholder = $field['placeholder'] ?? null;
            $value = old("translations.{$locale}.{$name}", $translation?->{$name});
        @endphp

        <div class="{{ ($field['wide'] ?? false) ? 'lg:col-span-2' : '' }}">
            <label class="admin-label" for="{{ $locale }}_{{ $name }}">{{ $field['label'] }}</label>

            @if ($type === 'textarea')
                <textarea
                    id="{{ $locale }}_{{ $name }}"
                    name="{{ $inputPrefix }}[{{ $name }}]"
                    rows="{{ $rows }}"
                    class="admin-input"
                    data-copy-field="{{ $copyFrom }}"
                >{{ $value }}</textarea>
            @else
                <input
                    id="{{ $locale }}_{{ $name }}"
                    name="{{ $inputPrefix }}[{{ $name }}]"
                    value="{{ $value }}"
                    class="admin-input"
                    data-copy-field="{{ $copyFrom }}"
                    @if ($list) list="{{ $list }}" @endif
                    @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                >
            @endif

            @error("{$errorPrefix}.{$name}") <p class="form-error">{{ $message }}</p> @enderror
        </div>
    @endforeach
</div>
