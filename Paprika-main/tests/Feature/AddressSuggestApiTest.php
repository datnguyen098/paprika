<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AddressSuggestApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_suggest_api_returns_geoapify_suggestions(): void
    {
        config(['services.geoapify.key' => 'test-key']);

        $branch = Branch::create([
            'name' => 'Paprika Patras',
            'slug' => 'paprika-patras',
            'address' => 'Patras',
            'phone' => '+30 000',
            'delivery_origin_latitude' => 38.2466,
            'delivery_origin_longitude' => 21.7346,
            'is_active' => true,
        ]);

        Http::fake([
            'api.geoapify.com/v1/geocode/autocomplete*' => Http::response([
                'features' => [
                    [
                        'properties' => [
                            'formatted' => 'Agiou Nikolaou 1, Patras, Greece',
                            'place_id' => 'abc123',
                        ],
                        'geometry' => [
                            'coordinates' => [21.735, 38.247],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->getJson('/api/v1/orders/address-suggest?q=Agiou&branch_id='.$branch->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('suggestions.0.formatted', 'Agiou Nikolaou 1, Patras, Greece')
            ->assertJsonPath('suggestions.0.latitude', 38.247)
            ->assertJsonPath('suggestions.0.longitude', 21.735);
    }

    public function test_address_reverse_api_returns_formatted_address_for_coordinates(): void
    {
        config(['services.geoapify.key' => 'test-key']);

        Http::fake([
            'api.geoapify.com/v1/geocode/reverse*' => Http::response([
                'features' => [
                    [
                        'properties' => [
                            'formatted' => 'Riga Fereou 12, Patras, Greece',
                            'place_id' => 'reverse-123',
                        ],
                        'geometry' => [
                            'coordinates' => [21.7354, 38.2468],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->postJson('/api/v1/orders/address-reverse', [
            'latitude' => 38.2467,
            'longitude' => 21.7353,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('formatted_address', 'Riga Fereou 12, Patras, Greece')
            ->assertJsonPath('place_id', 'reverse-123')
            ->assertJsonPath('latitude', 38.2468)
            ->assertJsonPath('longitude', 21.7354);
    }
}
