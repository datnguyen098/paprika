<?php

namespace App\Support;

class DeliveryRoute
{
    public function __construct(
        public readonly float $distanceKm,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $placeId = null,
        public readonly ?string $formattedAddress = null,
        public readonly ?int $durationSeconds = null,
    ) {}
}
