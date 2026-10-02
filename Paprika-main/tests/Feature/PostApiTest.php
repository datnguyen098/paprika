<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_api_lists_published_posts(): void
    {
        $category = Category::create([
            'name' => 'Tin tức',
            'slug' => 'tin-tuc',
            'type' => 'post',
            'is_active' => true,
        ]);

        Post::create([
            'category_id' => $category->id,
            'title' => 'Câu chuyện Paprika',
            'slug' => 'cau-chuyen-paprika',
            'excerpt' => 'Một bài viết ngắn.',
            'content' => '<p>Nội dung bài viết.</p>',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.title', 'Câu chuyện Paprika')
            ->assertJsonPath('data.0.slug', 'cau-chuyen-paprika')
            ->assertJsonPath('data.0.category.name', 'Tin tức');
    }

    public function test_post_detail_api_resolves_localized_slug(): void
    {
        $category = Category::create([
            'name' => 'Tin tức',
            'slug' => 'tin-tuc',
            'type' => 'post',
            'is_active' => true,
        ]);

        $post = Post::create([
            'category_id' => $category->id,
            'title' => 'Bài viết tiếng Việt',
            'slug' => 'bai-viet-tieng-viet',
            'excerpt' => 'Tóm tắt tiếng Việt.',
            'content' => '<p>Nội dung tiếng Việt.</p>',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $post->translations()->create([
            'locale' => 'en',
            'title' => 'English post',
            'slug' => 'english-post',
            'excerpt' => 'English excerpt.',
            'content' => '<p>English content.</p>',
        ]);

        $this->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/posts/english-post')
            ->assertOk()
            ->assertJsonPath('data.title', 'English post')
            ->assertJsonPath('data.slug', 'english-post')
            ->assertJsonPath('data.content', '<p>English content.</p>');
    }
}
