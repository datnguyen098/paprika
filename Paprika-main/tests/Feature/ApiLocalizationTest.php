<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_api_uses_accept_language_for_dish_text(): void
    {
        $category = Category::create([
            'name' => 'Món Việt',
            'slug' => 'mon-viet',
            'description' => 'Danh mục tiếng Việt',
            'type' => 'dish',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $dish = Dish::create([
            'category_id' => $category->id,
            'name' => 'Phở bò',
            'slug' => 'pho-bo',
            'description' => 'Mô tả tiếng Việt',
            'price' => 950,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $dish->translations()->create([
            'locale' => 'en',
            'name' => 'Beef pho',
            'slug' => 'beef-pho',
            'description' => 'English description',
        ]);

        $dish->translations()->create([
            'locale' => 'el',
            'name' => 'Φο με μοσχάρι',
            'slug' => 'pho-me-moschari',
            'description' => 'Ελληνική περιγραφή',
        ]);

        $this->withHeader('Accept-Language', 'en')
            ->getJson('/api/v1/menu?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Beef pho')
            ->assertJsonPath('data.0.description', 'English description');

        $this->withHeader('Accept-Language', 'el')
            ->getJson('/api/v1/menu?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Φο με μοσχάρι')
            ->assertJsonPath('data.0.description', 'Ελληνική περιγραφή');
    }

    public function test_dish_detail_api_uses_accept_language_for_dish_text(): void
    {
        $category = Category::create([
            'name' => 'Món Việt',
            'slug' => 'mon-viet',
            'type' => 'dish',
            'is_active' => true,
        ]);

        $dish = Dish::create([
            'category_id' => $category->id,
            'name' => 'Bánh mì',
            'slug' => 'banh-mi',
            'description' => 'Mô tả tiếng Việt',
            'content' => 'Nội dung tiếng Việt',
            'ingredients' => 'Nguyên liệu tiếng Việt',
            'price' => 750,
            'is_active' => true,
        ]);

        $dish->translations()->create([
            'locale' => 'en',
            'name' => 'Banh mi',
            'slug' => 'banh-mi-en',
            'description' => 'English description',
            'content' => 'English content',
            'ingredients' => 'English ingredients',
        ]);

        $this->withHeader('Accept-Language', 'en')
            ->getJson("/api/v1/dishes/{$dish->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Banh mi')
            ->assertJsonPath('data.description', 'English description')
            ->assertJsonPath('data.content', 'English content')
            ->assertJsonPath('data.ingredients', 'English ingredients');
    }
}
