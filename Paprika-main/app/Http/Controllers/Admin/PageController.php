<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SyncsTranslations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    use SyncsTranslations;

    public function __construct(private readonly UploadService $uploads) {}

    public function index(Request $request): View
    {
        $pages = Page::query()
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.pages.create', ['page' => new Page(['is_active' => true])]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $data = collect($request->validated())->except(['image', 'translations'])->all();
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'pages');
        }

        $page = Page::create($data);
        $this->syncTranslations($request, $page);

        return redirect()->route('admin.pages.index')->with('success', 'Đã thêm trang tĩnh.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $data = collect($request->validated())->except(['image', 'translations'])->all();
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $oldImage = $page->image;
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'pages');
            $this->uploads->deleteImage($oldImage);
        }

        $page->update($data);
        $this->syncTranslations($request, $page);

        return redirect()->route('admin.pages.index')->with('success', 'Đã cập nhật trang tĩnh.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->uploads->deleteImage($page->image);
        $page->delete();

        return back()->with('success', 'Đã xóa trang tĩnh.');
    }
}
