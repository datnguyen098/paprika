<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DishOptionGroup;
use App\Models\SiteSetting;
use App\Support\DishOptionPresetRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DishOptionSettingController extends Controller
{
    public function __construct(private readonly DishOptionPresetRepository $presets) {}

    public function edit(): View
    {
        return view('admin.dish-option-settings.edit', [
            'presets' => $this->presets->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'presets' => ['nullable', 'array'],
            'presets.*.name' => ['nullable', 'string', 'max:120'],
            'presets.*.slug' => ['nullable', 'string', 'max:120'],
            'presets.*.description' => ['nullable', 'string', 'max:500'],
            'presets.*.groups' => ['nullable', 'array'],
            'presets.*.groups.*.name' => ['nullable', 'string', 'max:120'],
            'presets.*.groups.*.type' => ['nullable', 'in:single,multiple,exclude'],
            'presets.*.groups.*.description' => ['nullable', 'string', 'max:500'],
            'presets.*.groups.*.is_required' => ['nullable', 'boolean'],
            'presets.*.groups.*.is_active' => ['nullable', 'boolean'],
            'presets.*.groups.*.min_select' => ['nullable', 'integer', 'min:0', 'max:20'],
            'presets.*.groups.*.max_select' => ['nullable', 'integer', 'min:0', 'max:20'],
            'presets.*.groups.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'presets.*.groups.*.options' => ['nullable', 'array'],
            'presets.*.groups.*.options.*.name' => ['nullable', 'string', 'max:120'],
            'presets.*.groups.*.options.*.description' => ['nullable', 'string', 'max:300'],
            'presets.*.groups.*.options.*.price_delta' => ['nullable', 'numeric', 'min:-999', 'max:999'],
            'presets.*.groups.*.options.*.is_default' => ['nullable', 'boolean'],
            'presets.*.groups.*.options.*.is_active' => ['nullable', 'boolean'],
            'presets.*.groups.*.options.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $presets = collect($data['presets'] ?? [])
            ->filter(fn (array $preset): bool => filled($preset['name'] ?? null))
            ->values()
            ->map(fn (array $preset, int $index): array => $this->normalizePreset($preset, $index))
            ->all();

        SiteSetting::set('dish_option_presets', json_encode($presets, JSON_UNESCAPED_UNICODE), 'json', 'commerce');

        return back()->with('success', 'Đã cập nhật cấu hình biến thể món.');
    }

    private function normalizePreset(array $preset, int $index): array
    {
        return [
            'name' => $preset['name'],
            'slug' => filled($preset['slug'] ?? null) ? Str::slug($preset['slug']) : Str::slug($preset['name']),
            'description' => $preset['description'] ?? null,
            'sort_order' => $index,
            'groups' => collect($preset['groups'] ?? [])
                ->filter(fn (array $group): bool => filled($group['name'] ?? null))
                ->values()
                ->map(fn (array $group, int $groupIndex): array => $this->normalizeGroup($group, $groupIndex))
                ->all(),
        ];
    }

    private function normalizeGroup(array $group, int $groupIndex): array
    {
        $type = $group['type'] ?? DishOptionGroup::TYPE_SINGLE;

        return [
            'name' => $group['name'],
            'slug' => Str::slug($group['name']),
            'type' => $type,
            'description' => $group['description'] ?? null,
            'is_required' => (bool) ($group['is_required'] ?? false),
            'is_active' => (bool) ($group['is_active'] ?? true),
            'min_select' => $type === DishOptionGroup::TYPE_SINGLE ? 0 : (int) ($group['min_select'] ?? 0),
            'max_select' => $type === DishOptionGroup::TYPE_SINGLE ? 1 : (int) ($group['max_select'] ?? 0),
            'sort_order' => (int) ($group['sort_order'] ?? $groupIndex),
            'options' => collect($group['options'] ?? [])
                ->filter(fn (array $option): bool => filled($option['name'] ?? null))
                ->values()
                ->map(fn (array $option, int $optionIndex): array => [
                    'name' => $option['name'],
                    'slug' => Str::slug($option['name']),
                    'description' => $option['description'] ?? null,
                    'price_delta' => number_format((float) ($option['price_delta'] ?? 0), 2, '.', ''),
                    'is_default' => (bool) ($option['is_default'] ?? false),
                    'is_active' => (bool) ($option['is_active'] ?? true),
                    'sort_order' => (int) ($option['sort_order'] ?? $optionIndex),
                ])
                ->all(),
        ];
    }

}
