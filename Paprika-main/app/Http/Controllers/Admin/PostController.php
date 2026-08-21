<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SyncsTranslations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Services\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    use SyncsTranslations;

    public function __construct(private readonly UploadService $uploads) {}

    public function index(Request $request): View
    {
        $categories = Category::post()->orderBy('name')->get();
        $posts = Post::query()
            ->with('category')
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->q.'%'))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->status === 'active'))
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.posts.index', compact('posts', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::post()->active()->orderBy('name')->get();

        return view('admin.posts.create', ['post' => new Post(['is_active' => true]), 'categories' => $categories]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $data = $this->normalizedData($request);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->uploads->uploadImage($request->file('thumbnail'), 'posts');
        }

        $post = Post::create($data);
        $this->syncTranslations($request, $post);

        return redirect()->route('admin.posts.index')->with('success', 'Đã thêm bài viết.');
    }

    public function edit(Post $post): View
    {
        $categories = Category::post()->orderBy('name')->get();

        return view('admin.posts.edit', compact('post', 'categories'));
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        $data = $this->normalizedData($request);

        if ($request->hasFile('thumbnail')) {
            $oldThumbnail = $post->thumbnail;
            $data['thumbnail'] = $this->uploads->uploadImage($request->file('thumbnail'), 'posts');
            $this->uploads->deleteImage($oldThumbnail);
        }

        $post->update($data);
        $this->syncTranslations($request, $post);

        return redirect()->route('admin.posts.index')->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->uploads->deleteImage($post->thumbnail);
        $post->delete();

        return back()->with('success', 'Đã xóa bài viết.');
    }

    private function normalizedData(PostRequest $request): array
    {
        return collect($request->validated())
            ->except(['thumbnail', 'translations'])
            ->merge([
                'is_featured' => $request->boolean('is_featured'),
                'is_active' => $request->boolean('is_active'),
            ])
            ->all();
    }
}
