<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_flutter_home_api_loads_gallery_branch_without_branch_translations_relation(): void
    {
        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'gallery_images',
                ],
            ]);
    }
}
