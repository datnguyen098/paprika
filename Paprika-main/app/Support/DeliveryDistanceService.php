<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Branch;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DeliveryDistanceService
{
    public function routeFromBranch(Branch $branch, ?string $address, ?float $latitude = null, ?float $longitude = null, ?string $placeId = null): DeliveryRoute
    {
        $this->ensureConfigured($branch);

        $destination = $this->resolveDestination($branch, $address, $latitude, $longitude, $placeId);
        $originLat = (float) $branch->delivery_origin_latitude;
        $originLng = (float) $branch->delivery_origin_longitude;

        $response = Http::acceptJson()
            ->timeout(15)
            ->get('https://api.geoapify.com/v1/routing', [
                'waypoints' => $originLat.','.$originLng.'|'.$destination['lat'].','.$destination['lng'],
                'mode' => config('services.geoapify.routing_mode', 'drive'),
                'format' => 'json',
                'units' => 'metric',
                'apiKey' => config('services.geoapify.key'),
            ])
            ->throw()
            ->json();

        $result = $response['results'][0] ?? null;
        $distanceMeters = $result['distance'] ?? $response['features'][0]['properties']['distance'] ?? null;

        if (! is_numeric($distanceMeters)) {
            throw new RuntimeException('Geoapify did not return a route distance.');
        }

        return new DeliveryRoute(
            distanceKm: round(((float) $distanceMeters) / 1000, 2),
            latitude: $destination['lat'],
            longitude: $destination['lng'],
            placeId: $destination['place_id'],
            formattedAddress: $destination['formatted'],
            durationSeconds: isset($result['time']) && is_numeric($result['time']) ? (int) $result['time'] : null,
        );
    }

    /**
     * @return array<int, array{formatted: string, place_id: string|null, latitude: float, longitude: float}>
     */
    public function suggestAddresses(string $query, ?Branch $branch = null, int $limit = 6): array
    {
        $this->ensureKeyConfigured();

        $limit = max(1, min($limit, 10));

        $response = Http::acceptJson()
            ->timeout(15)
            ->get('https://api.geoapify.com/v1/geocode/autocomplete', [
                'text' => $query,
                'filter' => 'countrycode:'.config('services.geoapify.country_code', 'gr'),
                'bias' => $branch && $branch->delivery_origin_longitude && $branch->delivery_origin_latitude
                    ? 'proximity:'.$branch->delivery_origin_longitude.','.$branch->delivery_origin_latitude
                    : null,
                'limit' => $limit,
                'apiKey' => config('services.geoapify.key'),
            ])
            ->throw()
            ->json();

        $features = $response['features'] ?? [];
        if (! is_array($features)) {
            return [];
        }

        $out = [];
        foreach ($features as $feature) {
            $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
            $coordinates = $feature['geometry']['coordinates'] ?? null;
            if (! is_array($coordinates) || count($coordinates) < 2) {
                continue;
            }

            $formatted = (string) ($properties['formatted'] ?? '');
            if ($formatted === '') {
                continue;
            }

            $out[] = [
                'formatted' => $formatted,
                'place_id' => isset($properties['place_id']) ? Order::normalizeExternalPlaceId($properties['place_id']) : null,
                'latitude' => (float) $coordinates[1],
                'longitude' => (float) $coordinates[0],
            ];
        }

        return $out;
    }

    /**
     * @return array{formatted: string|null, place_id: string|null, latitude: float, longitude: float}
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        $this->ensureKeyConfigured();

        $response = Http::acceptJson()
            ->timeout(15)
            ->get('https://api.geoapify.com/v1/geocode/reverse', [
                'lat' => $latitude,
                'lon' => $longitude,
                'filter' => 'countrycode:'.config('services.geoapify.country_code', 'gr'),
                'limit' => 1,
                'apiKey' => config('services.geoapify.key'),
            ])
            ->throw()
            ->json();

        $feature = $response['features'][0] ?? null;
        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
        $coordinates = $feature['geometry']['coordinates'] ?? null;

        return [
            'formatted' => isset($properties['formatted']) ? (string) $properties['formatted'] : null,
            'place_id' => isset($properties['place_id']) ? Order::normalizeExternalPlaceId($properties['place_id']) : null,
            'latitude' => is_array($coordinates) && count($coordinates) >= 2 ? (float) $coordinates[1] : $latitude,
            'longitude' => is_array($coordinates) && count($coordinates) >= 2 ? (float) $coordinates[0] : $longitude,
        ];
    }

    private function resolveDestination(Branch $branch, ?string $address, ?float $latitude, ?float $longitude, ?string $placeId): array
    {
        if ($latitude !== null && $longitude !== null) {
            return [
                'lat' => $latitude,
                'lng' => $longitude,
                'place_id' => $placeId,
                'formatted' => $address,
            ];
        }

        if (blank($address)) {
            throw new RuntimeException('Delivery address is required to calculate distance.');
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->get('https://api.geoapify.com/v1/geocode/search', [
                'text' => $address,
                'filter' => 'countrycode:'.config('services.geoapify.country_code', 'gr'),
                'bias' => $branch->delivery_origin_longitude && $branch->delivery_origin_latitude
                    ? 'proximity:'.$branch->delivery_origin_longitude.','.$branch->delivery_origin_latitude
                    : null,
                'limit' => 1,
                'apiKey' => config('services.geoapify.key'),
            ])
            ->throw()
            ->json();

        $feature = $response['features'][0] ?? null;
        $properties = $feature['properties'] ?? [];
        $coordinates = $feature['geometry']['coordinates'] ?? null;

        if (! is_array($coordinates) || count($coordinates) < 2) {
            throw new RuntimeException('Geoapify could not find this delivery address.');
        }

        return [
            'lat' => (float) $coordinates[1],
            'lng' => (float) $coordinates[0],
            'place_id' => isset($properties['place_id'])
                ? Order::normalizeExternalPlaceId($properties['place_id'])
                : $placeId,
            'formatted' => $properties['formatted'] ?? $address,
        ];
    }

    private function ensureConfigured(Branch $branch): void
    {
        $this->ensureKeyConfigured();

        if ($branch->delivery_origin_latitude === null || $branch->delivery_origin_longitude === null) {
            throw new RuntimeException('Branch delivery origin coordinates are missing.');
        }
    }

    private function ensureKeyConfigured(): void
    {
        if (blank(config('services.geoapify.key'))) {
            throw new RuntimeException('Missing Geoapify API key.');
        }
    }
}
