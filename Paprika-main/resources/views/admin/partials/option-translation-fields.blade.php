@php
    $localeName = match($locale) { 'en' => 'English', 'el' => 'Ελληνικά', default => $locale };
@endphp

@if (count($optionGroups) > 0)
    <div class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
        <p class="text-sm font-semibold text-emerald-900">Biến thể — {{ $localeName }}</p>
        <p class="mt-1 text-xs text-emerald-800/70">Dịch tên và mô tả của từng nhóm tùy chọn và lựa chọn.</p>
    </div>

    @foreach ($optionGroups as $groupIndex => $group)
        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
            <h4 class="text-sm font-black text-slate-700">{{ $group['name'] ?? 'Nhóm' }}</h4>
            <div class="mt-3 grid gap-3 lg:grid-cols-2">
                <div>
                    <label class="text-xs font-bold text-slate-500">Tên nhóm ({{ $localeName }})</label>
                    <input name="option_groups[{{ $groupIndex }}][translations][{{ $locale }}][name]" value="{{ $group['translations'][$locale]['name'] ?? '' }}" class="admin-input text-sm" placeholder="{{ $group['name'] ?? '' }}" data-copy-field="option_groups[{{ $groupIndex }}][name]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500">Mô tả ({{ $localeName }})</label>
                    <input name="option_groups[{{ $groupIndex }}][translations][{{ $locale }}][description]" value="{{ $group['translations'][$locale]['description'] ?? '' }}" class="admin-input text-sm" placeholder="{{ $group['description'] ?? '' }}" data-copy-field="option_groups[{{ $groupIndex }}][description]">
                </div>
            </div>

            @if (!empty($group['options']))
                <div class="mt-4 space-y-3">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-400">Lựa chọn</p>
                    @foreach ($group['options'] as $optionIndex => $option)
                        <div class="grid gap-2 rounded-lg border border-slate-100 bg-slate-50 p-3 lg:grid-cols-2">
                            <div>
                                <label class="text-xs font-bold text-slate-500">{{ $option['name'] ?? 'Lựa chọn' }} ({{ $localeName }})</label>
                                <input name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][translations][{{ $locale }}][name]" value="{{ $option['translations'][$locale]['name'] ?? '' }}" class="admin-input text-sm" placeholder="{{ $option['name'] ?? '' }}" data-copy-field="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][name]">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-500">Mô tả ({{ $localeName }})</label>
                                <input name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][translations][{{ $locale }}][description]" value="{{ $option['translations'][$locale]['description'] ?? '' }}" class="admin-input text-sm" placeholder="{{ $option['description'] ?? '' }}" data-copy-field="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][description]">
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
@endif
