<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\GalleryImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_api_returns_space_images_grouped_by_branch(): void
    {
        $branch = Branch::create([
            'name' => 'Paprika Patras',
            'slug' => 'paprika-patras',
            'address' => 'Patras',
            'phone' => '+30 000',
            'is_active' => true,
        ]);

        GalleryImage::create([
            'branch_id' => $branch->id,
            'title' => 'Góc bàn xanh',
            'slug' => 'goc-ban-xanh',
            'description' => 'Không gian ấm cúng.',
            'image' => 'images/gallery/table.jpg',
            'alt_text' => 'Bàn ăn tại Paprika',
            'location' => 'space',
            'is_active' => true,
            'is_featured' => true,
        ]);

        GalleryImage::create([
            'title' => 'Mặt tiền',
            'slug' => 'mat-tien',
            'image' => 'images/gallery/front.jpg',
            'location' => 'space',
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/gallery')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.images.0.title', 'Góc bàn xanh')
            ->assertJsonPath('data.images.0.branch.name', 'Paprika Patras')
            ->assertJsonPath('data.branches.0.name', 'Paprika Patras')
            ->assertJsonPath('data.branches.0.images.0.title', 'Góc bàn xanh')
            ->assertJsonPath('data.shared_images.0.title', 'Mặt tiền');
    }
}
