<?php

namespace App\Services;

use App\Models\Dish;
use App\Models\DishOptionGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'cart.items';

    public function items(): Collection
    {
        if (! request()->hasSession()) {
            return collect();
        }

        $rawItems = collect(session(self::SESSION_KEY, []))->filter();

        if ($rawItems->isEmpty()) {
            return collect();
        }

        $dishIds = $rawItems
            ->map(fn ($line, int|string $key): int => is_array($line) ? (int) ($line['dish_id'] ?? 0) : (int) $key)
            ->filter()
            ->unique()
            ->values();

        $dishes = Dish::query()
            ->with(['category', 'activeOptionGroups.activeOptions'])
            ->active()
            ->whereIn('id', $dishIds)
            ->get()
            ->keyBy('id');

        return $rawItems
            ->map(function (mixed $line, int|string $lineKey) use ($dishes): ?array {
                $branch = active_branch();
                $isLegacyLine = ! is_array($line);
                $dishId = $isLegacyLine ? (int) $lineKey : (int) ($line['dish_id'] ?? 0);
                $dish = $dishes->get($dishId);

                if (! $dish) {
                    return null;
                }

                $quantity = $isLegacyLine ? (int) $line : (int) ($line['quantity'] ?? 1);
                $quantity = max(1, min(99, $quantity));
                $configuration = $this->configuration(
                    $dish,
                    $isLegacyLine ? [] : ($line['selected_option_ids'] ?? []),
                    $isLegacyLine ? null : ($line['customization_note'] ?? null),
                    false
                );

                    $availability = $branch ? dish_availability($dish, $branch) : null;

                    return [
                        'line_key' => (string) $lineKey,
                        'dish' => $dish,
                        'quantity' => $quantity,
                        'available' => $availability?->available ?? true,
                        'availability_label' => $availability?->label(),
                        'base_unit_price' => $configuration['base_unit_price'],
                    'options_total' => $configuration['options_total'],
                    'unit_price' => $configuration['unit_price'],
                    'line_total' => $configuration['unit_price'] * $quantity,
                    'selected_option_ids' => $configuration['selected_option_ids'],
                    'selected_options' => $configuration['selected_options'],
                    'options_snapshot' => $configuration['options_snapshot'],
                    'customization_note' => $configuration['customization_note'],
                    'summary' => $configuration['summary'],
                ];
            })
            ->filter()
            ->values();
    }

    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    public function subtotal(): int
    {
        return $this->items()->sum('line_total');
    }

    public function add(Dish $dish, int $quantity = 1, array $selectedOptionIds = [], ?string $customizationNote = null): void
    {
        $dish->loadMissing(['activeOptionGroups.activeOptions']);
        $configuration = $this->configuration($dish, $selectedOptionIds, $customizationNote);
        $lineKey = $this->lineKey($dish, $configuration['selected_option_ids'], $configuration['customization_note']);
        $quantity = max(1, min(99, $quantity));

        $items = session(self::SESSION_KEY, []);
        $existingQuantity = is_array($items[$lineKey] ?? null) ? (int) ($items[$lineKey]['quantity'] ?? 0) : 0;
        $items[$lineKey] = [
            'dish_id' => $dish->id,
            'quantity' => min(99, $existingQuantity + $quantity),
            'selected_option_ids' => $configuration['selected_option_ids'],
            'customization_note' => $configuration['customization_note'],
        ];

        session([self::SESSION_KEY => $items]);
    }

    public function update(array $quantities): void
    {
        $currentItems = session(self::SESSION_KEY, []);

        foreach ($quantities as $lineKey => $quantity) {
            $quantity = (int) $quantity;
            $lineKey = (string) $lineKey;

            if (! array_key_exists($lineKey, $currentItems)) {
                continue;
            }

            if ($quantity <= 0) {
                unset($currentItems[$lineKey]);
                continue;
            }

            if (is_array($currentItems[$lineKey])) {
                $currentItems[$lineKey]['quantity'] = min(99, $quantity);
            } else {
                $currentItems[$lineKey] = min(99, $quantity);
            }
        }

        session([self::SESSION_KEY => $currentItems]);
    }

    public function remove(string $lineKey): void
    {
        $items = session(self::SESSION_KEY, []);
        unset($items[$lineKey]);

        session([self::SESSION_KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @param array<int|string> $selectedOptionIds
     * @return array{
     *     base_unit_price: int,
     *     options_total: int,
     *     unit_price: int,
     *     selected_option_ids: array<int>,
     *     selected_options: \Illuminate\Support\Collection,
     *     options_snapshot: array<int, array<string, mixed>>,
     *     customization_note: ?string,
     *     summary: string
     * }
     */
    public function configuration(Dish $dish, array $selectedOptionIds = [], ?string $customizationNote = null, bool $strict = true): array
    {
        $groups = $dish->relationLoaded('activeOptionGroups')
            ? $dish->activeOptionGroups
            : $dish->activeOptionGroups()->with('activeOptions')->get();

        $selectedOptionIds = collect($selectedOptionIds)
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $availableOptions = $groups
            ->flatMap(fn (DishOptionGroup $group) => $group->activeOptions->map(fn ($option) => [$option, $group]))
            ->mapWithKeys(fn (array $pair): array => [$pair[0]->id => ['option' => $pair[0], 'group' => $pair[1]]]);

        $unknownIds = $selectedOptionIds->reject(fn (int $id): bool => $availableOptions->has($id));
        if ($strict && $unknownIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'options' => 'Một số tùy chọn không còn khả dụng.',
            ]);
        }

        $selectedOptionIds = $selectedOptionIds->filter(fn (int $id): bool => $availableOptions->has($id));

        foreach ($groups as $group) {
            $groupSelectedCount = $selectedOptionIds
                ->filter(fn (int $id): bool => (int) $availableOptions->get($id)['group']->id === (int) $group->id)
                ->count();

            if ($groupSelectedCount > 0) {
                continue;
            }

            $defaults = $group->activeOptions
                ->where('is_default', true)
                ->take($group->type === DishOptionGroup::TYPE_SINGLE ? 1 : ($group->max_select ?: $group->activeOptions->count()))
                ->pluck('id');

            $selectedOptionIds = $selectedOptionIds->merge($defaults)->unique()->values();
        }

        $errors = [];
        foreach ($groups as $group) {
            $count = $selectedOptionIds
                ->filter(fn (int $id): bool => (int) $availableOptions->get($id)['group']->id === (int) $group->id)
                ->count();
            $minimum = max((int) $group->min_select, $group->is_required ? 1 : 0);

            if ($count < $minimum) {
                $errors['options'][] = "Vui lòng chọn {$group->name}.";
            }

            if ($group->type === DishOptionGroup::TYPE_SINGLE && $count > 1) {
                $errors['options'][] = "{$group->name} chỉ được chọn một lựa chọn.";
            }

            if ($group->max_select !== null && $count > (int) $group->max_select) {
                $errors['options'][] = "{$group->name} chỉ được chọn tối đa {$group->max_select} lựa chọn.";
            }
        }

        if ($strict && $errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $selectedOptions = $selectedOptionIds
            ->map(fn (int $id) => $availableOptions->get($id))
            ->filter()
            ->map(function (array $pair): array {
                return [
                    'id' => $pair['option']->id,
                    'name' => $pair['option']->localized('name'),
                    'price_delta' => (int) $pair['option']->price_delta,
                    'group_id' => $pair['group']->id,
                    'group_name' => $pair['group']->localized('name'),
                    'group_type' => $pair['group']->type,
                ];
            })
            ->values();

        $baseUnitPrice = (int) ($dish->sale_price ?: $dish->price);
        $optionsTotal = (int) $selectedOptions->sum('price_delta');
        $customizationNote = Str::limit(trim((string) $customizationNote), 500, '');
        $customizationNote = $customizationNote === '' ? null : $customizationNote;
        $summary = $this->summary($selectedOptions, $customizationNote);

        return [
            'base_unit_price' => $baseUnitPrice,
            'options_total' => $optionsTotal,
            'unit_price' => max(0, $baseUnitPrice + $optionsTotal),
            'selected_option_ids' => $selectedOptions->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'selected_options' => $selectedOptions,
            'options_snapshot' => $selectedOptions->all(),
            'customization_note' => $customizationNote,
            'summary' => $summary,
        ];
    }

    private function lineKey(Dish $dish, array $selectedOptionIds, ?string $customizationNote): string
    {
        return sha1(json_encode([
            'dish_id' => $dish->id,
            'options' => array_values($selectedOptionIds),
            'note' => $customizationNote,
        ]));
    }

    private function summary(Collection $selectedOptions, ?string $customizationNote): string
    {
        $parts = $selectedOptions
            ->groupBy('group_name')
            ->map(fn (Collection $options, string $group): string => $group.': '.$options->pluck('name')->implode(', '))
            ->values()
            ->all();

        if ($customizationNote) {
            $parts[] = $this->localeString('note_prefix').$customizationNote;
        }

        return $parts === [] ? $this->localeString('standard') : implode(' | ', $parts);
    }

    private function localeString(string $key): string
    {
        return match (current_locale()) {
            'en' => match ($key) {
                'note_prefix' => 'Note: ',
                'standard' => 'Standard recipe',
                default => $key,
            },
            'el' => match ($key) {
                'note_prefix' => 'Σημείωση: ',
                'standard' => 'Τυπική συνταγή',
                default => $key,
            },
            default => match ($key) {
                'note_prefix' => 'Ghi chú: ',
                'standard' => 'Công thức tiêu chuẩn',
                default => $key,
            },
        };
    }
}
