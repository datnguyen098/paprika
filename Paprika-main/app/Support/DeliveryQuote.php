<?php

namespace App\Support;

class DeliveryQuote
{
    public function __construct(
        public readonly bool $available,
        public readonly int $fee = 0,
        public readonly ?float $distanceKm = null,
        public readonly ?string $zoneLabel = null,
        public readonly ?string $message = null,
        public readonly bool $manualFee = false,
        public readonly ?string $source = null,
        public readonly ?string $messageKey = null,
    ) {}

    /**
     * Create a "manual" unavailable quote — used when the customer proceeded
     * without an automatic shipping calculation (e.g. Geoapify couldn't geocode
     * the address). The order will be created with fee=0 and pending shipping
     * confirmation from the restaurant.
     */
    public static function manualUnavailable(): self
    {
        return new self(
            available: true,
            fee: 0,
            distanceKm: null,
            zoneLabel: null,
            message: null,
            manualFee: true,
            source: 'manual',
            messageKey: null,
        );
    }

    public function total(int $subtotal, int $discount = 0): int
    {
        return max(0, $subtotal + $this->fee - $discount);
    }

    public function localizedMessage(?array $params = null): ?string
    {
        if ($this->messageKey) {
            return __($this->messageKey, $params ?? []);
        }

        return $this->message;
    }

    /**
     * Resolve zoneLabel.
     * If the label looks like a translation key (starts with "site."),
     * it is resolved through the lang files.
     * Otherwise the raw label is returned as-is.
     */
    public function localizedZoneLabel(): ?string
    {
        if ($this->zoneLabel === null || $this->zoneLabel === '') {
            return null;
        }

        if (str_starts_with($this->zoneLabel, 'site.')) {
            $translated = __($this->zoneLabel);
            // If translation equals the key, the key wasn't found — return null
            return $translated === $this->zoneLabel ? null : $translated;
        }

        return $this->zoneLabel;
    }
}
