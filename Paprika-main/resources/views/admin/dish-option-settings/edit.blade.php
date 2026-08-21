@extends('admin.layouts.app')

@section('title', 'Cấu hình biến thể món')

@section('content')
    <form method="POST" action="{{ route('admin.dish-option-settings.update') }}" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200" data-variant-presets-builder>
        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-emerald-700">Paprika</p>
                <h1 class="mt-2 text-2xl font-black text-slate-950">Cấu hình biến thể món</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Tạo các bộ lựa chọn dùng lại như đồ ăn, đồ uống, size, độ cay, topping. Khi sửa món, bạn có thể chọn một bộ ở đây để áp dụng nhanh rồi tinh chỉnh riêng cho món đó.
                </p>
            </div>
            <button type="button" class="admin-btn-secondary" data-add-preset>Thêm bộ cấu hình</button>
        </div>

        <div class="mt-6 space-y-5" data-presets>
            @foreach ($presets as $presetIndex => $preset)
                @include('admin.dish-option-settings.partials.preset-card', ['preset' => $preset, 'presetIndex' => $presetIndex])
            @endforeach
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="admin-btn-primary">Lưu cấu hình</button>
            <a href="{{ route('admin.dishes.index') }}" class="admin-btn-secondary">Quay lại món ăn</a>
        </div>

        @include('admin.dish-option-settings.partials.templates')
    </form>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-variant-presets-builder]').forEach((builder) => {
            const presets = builder.querySelector('[data-presets]');
            const presetTemplate = builder.querySelector('[data-preset-template]');
            const groupTemplate = builder.querySelector('[data-preset-group-template]');
            const optionTemplate = builder.querySelector('[data-preset-option-template]');
            let presetCounter = presets.querySelectorAll('[data-preset]').length;

            const presetKeyOf = (preset) => preset.querySelector('input[name^="presets["]')?.name.match(/presets\[([^\]]+)]/)?.[1] || '0';
            const groupKeyOf = (group) => group.querySelector('input[name*="[groups]"]')?.name.match(/\[groups]\[([^\]]+)]/)?.[1] || '0';

            const addOption = (group) => {
                const preset = group.closest('[data-preset]');
                const options = group.querySelector('[data-preset-options]');
                const html = optionTemplate.innerHTML
                    .replaceAll('__PRESET__', presetKeyOf(preset))
                    .replaceAll('__GROUP__', groupKeyOf(group))
                    .replaceAll('__OPTION__', `${Date.now()}${options.querySelectorAll('[data-preset-option]').length}`);

                options.insertAdjacentHTML('beforeend', html);
                options.lastElementChild?.querySelector('input[name$="[name]"]')?.focus();
            };

            const addGroup = (preset) => {
                const groups = preset.querySelector('[data-preset-groups]');
                const html = groupTemplate.innerHTML
                    .replaceAll('__PRESET__', presetKeyOf(preset))
                    .replaceAll('__GROUP__', `${Date.now()}${groups.querySelectorAll('[data-preset-group]').length}`);

                groups.insertAdjacentHTML('beforeend', html);
                const group = groups.lastElementChild;
                group.querySelector('input[name$="[name]"]')?.focus();
                addOption(group);
            };

            builder.querySelector('[data-add-preset]')?.addEventListener('click', () => {
                const key = presetCounter++;
                presets.insertAdjacentHTML('beforeend', presetTemplate.innerHTML.replaceAll('__PRESET__', String(key)));
                const preset = presets.lastElementChild;
                preset.querySelector('input[name$="[name]"]')?.focus();
                addGroup(preset);
            });

            builder.addEventListener('click', (event) => {
                const addGroupButton = event.target.closest('[data-add-preset-group]');
                const addOptionButton = event.target.closest('[data-add-preset-option]');
                const removePreset = event.target.closest('[data-remove-preset]');
                const removeGroup = event.target.closest('[data-remove-preset-group]');
                const removeOption = event.target.closest('[data-remove-preset-option]');

                if (addGroupButton) addGroup(addGroupButton.closest('[data-preset]'));
                if (addOptionButton) addOption(addOptionButton.closest('[data-preset-group]'));
                if (removePreset) removePreset.closest('[data-preset]')?.remove();
                if (removeGroup) removeGroup.closest('[data-preset-group]')?.remove();
                if (removeOption) removeOption.closest('[data-preset-option]')?.remove();
            });
        });
    </script>
@endpush
