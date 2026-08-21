<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Promotion;
use App\Services\UploadService;
use App\Support\TranslationPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function __construct(private readonly UploadService $uploads) {}

    public function index(Request $request): View
    {
        $promotions = Promotion::query()
            ->when($request->filled('placement'), fn ($query) => $query->where('placement', $request->placement))
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->q.'%'))
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.promotions.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('admin.promotions.create', ['promotion' => new Promotion([
            'placement' => 'home',
            'template' => 'split',
            'accent_color' => '#047857',
            'show_once' => true,
            'is_active' => true,
        ])]);
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        $data = $this->normalizedData($request);

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'promotions');
        }

        $promotion = Promotion::create($data);
        $this->syncTranslations($request, $promotion);

        return redirect()->route('admin.promotions.index')->with('success', 'Đã thêm khuyến mãi/quảng cáo.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('admin.promotions.edit', compact('promotion'));
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->normalizedData($request);

        if ($request->hasFile('image')) {
            $oldImage = $promotion->image;
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'promotions');
            $this->uploads->deleteImage($oldImage);
        }

        $promotion->update($data);
        $this->syncTranslations($request, $promotion);

        return redirect()->route('admin.promotions.index')->with('success', 'Đã cập nhật khuyến mãi/quảng cáo.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->uploads->deleteImage($promotion->image);
        $promotion->delete();

        return back()->with('success', 'Đã xóa khuyến mãi/quảng cáo.');
    }

    private function normalizedData(PromotionRequest $request): array
    {
        return collect($request->validated())
            ->except(['image', 'translations'])
            ->merge([
                'accent_color' => $request->input('accent_color') ?: '#047857',
                'show_once' => $request->boolean('show_once'),
                'is_active' => $request->boolean('is_active'),
            ])
            ->all();
    }

    private function syncTranslations($request, $model): void
    {
        $translations = data_get($request->validated(), "translations", []);

        foreach ($translations as $locale => $fields) {
            if ($locale === config("locales.default")) {
                continue;
            }
            $values = TranslationPayload::prepare($model, $locale, $fields);

            if ($values === null) {
                $model->translations()->where('locale', $locale)->delete();

                continue;
            }

            $model->translations()->updateOrCreate(["locale" => $locale], $values);
        }
    }
}
