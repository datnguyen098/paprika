<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SyncsTranslations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DishRequest;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishOptionGroup;
use App\Services\UploadService;
use App\Support\DishOptionPresetRepository;
use App\Support\TranslationPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DishController extends Controller
{
    use SyncsTranslations;

    public function __construct(private readonly UploadService $uploads) {}

    public function index(Request $request): View
    {
        $categories = Category::dish()->orderBy('name')->get();
        $dishes = Dish::query()
            ->with(['category', 'timeSlots.branch'])
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->when($request->filled('has_time_slots'), function ($query) use ($request): void {
                if ($request->has_time_slots === 'yes') {
                    $query->whereHas('timeSlots');

                    return;
                }

                if ($request->has_time_slots === 'no') {
                    $query->whereDoesntHave('timeSlots');
                }
            })
            ->orderBy('sort_order')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.dishes.index', compact('dishes', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::dish()->active()->orderBy('name')->get();
        $optionPresets = app(DishOptionPresetRepository::class)->all()->all();

        return view('admin.dishes.create', ['dish' => new Dish(['is_active' => true]), 'categories' => $categories, 'optionPresets' => $optionPresets]);
    }

    public function store(DishRequest $request): RedirectResponse
    {
        $data = $this->normalizedData($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'dishes');
        }

        $data['gallery'] = $this->uploads->uploadMultipleImages($request->file('gallery', []), 'dishes');

        $dish = Dish::create($data);
        $this->syncTranslations($request, $dish);
        $this->syncOptionGroups($request, $dish);

        return redirect()->route('admin.dishes.index')->with('success', 'Đã thêm món ăn.');
    }

    public function edit(Dish $dish): View
    {
        $categories = Category::dish()->orderBy('name')->get();
        $dish->load('optionGroups.translations', 'optionGroups.options.translations');
        $optionPresets = app(DishOptionPresetRepository::class)->all()->all();

        return view('admin.dishes.edit', compact('dish', 'categories', 'optionPresets'));
    }

    public function update(DishRequest $request, Dish $dish): RedirectResponse
    {
        $data = $this->normalizedData($request);
        $gallery = $dish->gallery ?? [];
        $removed = $request->input('remove_gallery', []);

        if ($request->hasFile('image')) {
            $oldImage = $dish->image;
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'dishes');
            $this->uploads->deleteImage($oldImage);
        }

        if ($removed) {
            $this->uploads->deleteImages($removed);
            $gallery = array_values(array_diff($gallery, $removed));
        }

        $gallery = array_merge($gallery, $this->uploads->uploadMultipleImages($request->file('gallery', []), 'dishes'));
        $data['gallery'] = $gallery;

        $dish->update($data);
        $this->syncTranslations($request, $dish);
        $this->syncOptionGroups($request, $dish);

        return redirect()->route('admin.dishes.index')->with('success', 'Đã cập nhật món ăn.');
    }

    public function destroy(Dish $dish): RedirectResponse
    {
        $this->uploads->deleteImage($dish->image);
        $this->uploads->deleteImages($dish->gallery ?? []);
        $dish->delete();

        return back()->with('success', 'Đã xóa món ăn.');
    }

    private function normalizedData(DishRequest $request): array
    {
        return collect($request->validated())
            ->except(['image', 'gallery', 'remove_gallery', 'translations', 'option_groups', 'time_slots'])
            ->merge([
                'is_featured' => $request->boolean('is_featured'),
                'is_active' => $request->boolean('is_active'),
                'price' => $this->moneyToMinorUnits($request->input('price')),
                'sale_price' => $request->filled('sale_price') ? $this->moneyToMinorUnits($request->input('sale_price')) : null,
            ])
            ->all();
    }

    private function moneyToMinorUnits(mixed $value): int
    {
        return (int) round(((float) str_replace(',', '.', (string) $value)) * 100);
    }

    private function syncModelTranslations($model, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            if ($locale === config('locales.default')) {
                continue;
            }
            $values = TranslationPayload::prepare($model, $locale, $fields);

            if ($values === null) {
                $model->translations()->where('locale', $locale)->delete();

                continue;
            }

            $model->translations()->updateOrCreate(['locale' => $locale], $values);
        }
    }

    private function syncOptionGroups(DishRequest $request, Dish $dish): void
    {
        $groups = collect(data_get($request->validated(), 'option_groups', []))
            ->filter(fn (array $group): bool => filled($group['name'] ?? null))
            ->values();

        $keptGroupIds = [];

        foreach ($groups as $groupIndex => $groupData) {
            $groupSlug = Str::slug($groupData['name']);
            $groupId = (int) ($groupData['id'] ?? 0);
            $group = $groupId > 0
                ? $dish->optionGroups()->whereKey($groupId)->first()
                : null;

            if (! $group) {
                $group = $dish->optionGroups()->where('slug', $groupSlug)->first();
            }

            if (! $group) {
                $group = $dish->optionGroups()->make();
            }

            $type = $groupData['type'] ?? DishOptionGroup::TYPE_SINGLE;
            $group->fill([
                'name' => $groupData['name'],
                'slug' => $groupSlug,
                'type' => $type,
                'description' => $groupData['description'] ?? null,
                'is_required' => (bool) ($groupData['is_required'] ?? false),
                'min_select' => $type === DishOptionGroup::TYPE_SINGLE ? 0 : (int) ($groupData['min_select'] ?? 0),
                'max_select' => $type === DishOptionGroup::TYPE_SINGLE ? 1 : (int) ($groupData['max_select'] ?? 0),
                'sort_order' => (int) ($groupData['sort_order'] ?? $groupIndex),
                'is_active' => (bool) ($groupData['is_active'] ?? true),
            ]);
            $group->save();

            $this->syncModelTranslations($group, $groupData['translations'] ?? []);

            $keptGroupIds[] = $group->id;
            $this->syncOptions($group, $groupData['options'] ?? []);
        }

        $staleGroups = $dish->optionGroups();

        if ($keptGroupIds !== []) {
            $staleGroups->whereNotIn('id', $keptGroupIds);
        }

        $staleGroups->delete();
    }

    private function syncOptions(DishOptionGroup $group, array $options): void
    {
        $optionRows = collect($options)
            ->filter(fn (array $option): bool => filled($option['name'] ?? null))
            ->values();

        $keptOptionIds = [];

        foreach ($optionRows as $optionIndex => $optionData) {
            $optionSlug = Str::slug($optionData['name']);
            $optionId = (int) ($optionData['id'] ?? 0);
            $option = $optionId > 0
                ? $group->options()->whereKey($optionId)->first()
                : null;

            if (! $option) {
                $option = $group->options()->where('slug', $optionSlug)->first();
            }

            if (! $option) {
                $option = $group->options()->make();
            }

            $option->fill([
                'name' => $optionData['name'],
                'slug' => $optionSlug,
                'description' => $optionData['description'] ?? null,
                'price_delta' => $this->moneyToMinorUnits($optionData['price_delta'] ?? 0),
                'is_default' => (bool) ($optionData['is_default'] ?? false),
                'is_active' => (bool) ($optionData['is_active'] ?? true),
                'sort_order' => (int) ($optionData['sort_order'] ?? $optionIndex),
            ]);
            $option->save();

            $this->syncModelTranslations($option, $optionData['translations'] ?? []);

            $keptOptionIds[] = $option->id;
        }

        $staleOptions = $group->options();

        if ($keptOptionIds !== []) {
            $staleOptions->whereNotIn('id', $keptOptionIds);
        }

        $staleOptions->delete();
    }

}
