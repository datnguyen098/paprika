<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_flutter_branch_api_returns_delivery_origin_as_map_coordinates(): void
    {
        $this->getJson('/api/v1/branches')
            ->assertOk()
            ->assertJsonPath('data.0.latitude', 38.2430666)
            ->assertJsonPath('data.0.longitude', 21.7296262);
    }
}
