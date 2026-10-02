<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_api_lists_active_pages(): void
    {
        Page::create([
            'title' => 'Chính sách giao hàng',
            'slug' => 'chinh-sach-giao-hang',
            'content' => '<p>Nội dung chính sách.</p>',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/pages')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.title', 'Chính sách giao hàng')
            ->assertJsonPath('data.0.slug', 'chinh-sach-giao-hang');
    }

    public function test_page_detail_api_resolves_localized_slug(): void
    {
        $page = Page::create([
            'title' => 'Trang tiếng Việt',
            'slug' => 'trang-tieng-viet',
            'content' => '<p>Nội dung tiếng Việt.</p>',
            'is_active' => true,
        ]);

        $page->translations()->create([
            'locale' => 'en',
            'title' => 'English page',
            'slug' => 'english-page',
            'content' => '<p>English content.</p>',
        ]);

        $this->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/pages/english-page')
            ->assertOk()
            ->assertJsonPath('data.title', 'English page')
            ->assertJsonPath('data.slug', 'english-page')
            ->assertJsonPath('data.content', '<p>English content.</p>');
    }
}
