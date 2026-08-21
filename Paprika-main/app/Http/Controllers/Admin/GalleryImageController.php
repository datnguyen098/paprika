<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GalleryImageRequest;
use App\Models\Branch;
use App\Models\GalleryImage;
use App\Services\UploadService;
use App\Support\TranslationPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryImageController extends Controller
{
    public function __construct(private readonly UploadService $uploads) {}

    public function index(Request $request): View
    {
        $galleryImages = GalleryImage::query()
            ->with('branch')
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->q.'%'))
            ->when($request->filled('branch_id'), fn ($query) => $query->where('branch_id', $request->branch_id))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->orderBy('sort_order')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $branches = Branch::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.gallery.index', compact('galleryImages', 'branches'));
    }

    public function create(): View
    {
        return view('admin.gallery.create', [
            'galleryImage' => new GalleryImage([
                'location' => 'space',
                'is_featured' => true,
                'is_active' => true,
            ]),
            'branches' => Branch::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(GalleryImageRequest $request): RedirectResponse
    {
        $data = $this->normalizedData($request);
        $data['image'] = $this->uploads->uploadImage($request->file('image'), 'gallery');

        $galleryImage = GalleryImage::create($data);
        $this->syncTranslations($request, $galleryImage);

        return redirect()->route('admin.gallery.index')->with('success', 'Đã thêm ảnh không gian quán.');
    }

    public function edit(GalleryImage $gallery): View
    {
        return view('admin.gallery.edit', [
            'galleryImage' => $gallery,
            'branches' => Branch::query()->active()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function update(GalleryImageRequest $request, GalleryImage $gallery): RedirectResponse
    {
        $data = $this->normalizedData($request);

        if ($request->hasFile('image')) {
            $oldImage = $gallery->image;
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'gallery');
            $this->uploads->deleteImage($oldImage);
        }

        $gallery->update($data);
        $this->syncTranslations($request, $gallery);

        return redirect()->route('admin.gallery.index')->with('success', 'Đã cập nhật ảnh không gian quán.');
    }

    public function destroy(GalleryImage $gallery): RedirectResponse
    {
        $this->uploads->deleteImage($gallery->image);
        $gallery->delete();

        return back()->with('success', 'Đã xóa ảnh không gian quán.');
    }

    private function normalizedData(GalleryImageRequest $request): array
    {
        return $request->safe()
            ->except(['image', 'translations'])
            + [
                'is_featured' => $request->boolean('is_featured'),
                'is_active' => $request->boolean('is_active'),
            ];
    }

    private function syncTranslations($request, $model): void
    {
        $translations = data_get($request->validated(), 'translations', []);

        foreach ($translations as $locale => $fields) {
            if ($locale === config('locales.default')) {
                continue;
            }
            $values = TranslationPayload::prepare($model, $locale, $fields, ['gallery_image_translations' => 'title']);

            if ($values === null) {
                $model->translations()->where('locale', $locale)->delete();

                continue;
            }

            $model->translations()->updateOrCreate(['locale' => $locale], $values);
        }
    }
}
