<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SyncsTranslations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use SyncsTranslations;

    public function __construct(private readonly UploadService $uploads) {}

    public function index(Request $request): View
    {
        $categories = Category::query()
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->q.'%'))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->paginate(10)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create', ['category' => new Category(['is_active' => true])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $data = collect($request->validated())->except(['image', 'translations'])->all();
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'categories');
        }

        $category = Category::create($data);
        $this->syncTranslations($request, $category);

        return redirect()->route('admin.categories.index')->with('success', 'Đã thêm danh mục.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $data = collect($request->validated())->except(['image', 'translations'])->all();
        $data['is_active'] = $request->boolean('is_active');

        if ($request->hasFile('image')) {
            $oldImage = $category->image;
            $data['image'] = $this->uploads->uploadImage($request->file('image'), 'categories');
            $this->uploads->deleteImage($oldImage);
        }

        $category->update($data);
        $this->syncTranslations($request, $category);

        return redirect()->route('admin.categories.index')->with('success', 'Đã cập nhật danh mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->dishes()->exists() || $category->posts()->exists()) {
            return back()->with('error', 'Không thể xóa danh mục đang có món ăn hoặc bài viết.');
        }

        $this->uploads->deleteImage($category->image);
        $category->delete();

        return back()->with('success', 'Đã xóa danh mục.');
    }
}
