<?php

namespace App\Support;

use App\Models\Branch;

class DeliveryQuoteCalculator
{
    public function quote(Branch $branch, string $method, int $subtotal, ?float $distanceKm = null): DeliveryQuote
    {
        if (! $branch->accepts_online_orders) {
            return new DeliveryQuote(
                false,
                message: 'Cơ sở này đang tạm ngừng nhận đơn online.',
                messageKey: 'site.delivery_quote.online_orders_paused'
            );
        }

        if ($method === 'pickup') {
            return $branch->accepts_pickup_orders
                ? new DeliveryQuote(true)
                : new DeliveryQuote(
                    false,
                    message: 'Cơ sở này đang tạm ngừng nhận đơn tự đến lấy.',
                    messageKey: 'site.delivery_quote.pickup_paused'
                );
        }

        if ($method !== 'delivery') {
            return new DeliveryQuote(
                false,
                message: 'Hình thức nhận món không hợp lệ.',
                messageKey: 'site.delivery_quote.invalid_method'
            );
        }

        if (! $branch->accepts_delivery_orders) {
            return new DeliveryQuote(
                false,
                message: 'Cơ sở này đang tạm ngừng giao hàng.',
                messageKey: 'site.delivery_quote.delivery_paused'
            );
        }

        if ($subtotal < (int) $branch->delivery_min_order_amount) {
            return new DeliveryQuote(
                false,
                message: 'Đơn giao hàng tối thiểu '.format_money((int) $branch->delivery_min_order_amount).'.',
                messageKey: 'site.delivery_quote.min_order_amount'
            );
        }

        if (! $branch->auto_delivery_quote_enabled) {
            return new DeliveryQuote(
                true,
                distanceKm: $distanceKm,
                zoneLabel: 'Shipper xác nhận phí ship',
                message: 'Phí ship sẽ do shipper xác nhận và thu trực tiếp.',
                manualFee: true,
                source: 'manual',
                messageKey: 'site.delivery_quote.fee_shipper_confirm'
            );
        }

        if ($distanceKm === null) {
            return new DeliveryQuote(
                false,
                message: 'Vui lòng nhập địa chỉ giao hàng để hệ thống tính phí ship.',
                messageKey: 'site.delivery_quote.enter_address'
            );
        }

        if ($distanceKm < 0) {
            return new DeliveryQuote(
                false,
                message: 'Khoảng cách giao hàng không hợp lệ.',
                messageKey: 'site.delivery_quote.invalid_distance'
            );
        }

        if ($branch->delivery_max_distance_km !== null && $distanceKm > (float) $branch->delivery_max_distance_km) {
            // Do not set messageKey here — the hard-coded message already contains
            // the correct max distance from DB, so avoid letting the translation
            // (which would replace :distance with the actual distance) override it.
            return new DeliveryQuote(
                false,
                distanceKm: $distanceKm,
                message: 'Cơ sở này chỉ giao trong phạm vi: '.number_format((float) $branch->delivery_max_distance_km, 1, ',', '.').' km.',
            );
        }

        $branch->loadMissing('deliveryZones');

        $zone = $branch->deliveryZones
            ->where('is_active', true)
            ->first(function ($zone) use ($distanceKm): bool {
                $min = (float) $zone->min_distance_km;
                $max = $zone->max_distance_km !== null ? (float) $zone->max_distance_km : null;

                return $distanceKm >= $min && ($max === null || $distanceKm <= $max);
            });

        if (! $zone) {
            return new DeliveryQuote(
                false,
                distanceKm: $distanceKm,
                message: 'Chưa có bảng phí ship phù hợp cho khoảng cách này.',
                messageKey: 'site.delivery_quote.no_zone_found'
            );
        }

        $fee = (int) $zone->fee;
        // If zone.label starts with "site." it is treated as a translation key.
        // Otherwise it is used as-is (for plain text labels or dynamic km-based labels).
        $label = $zone->label ?: $this->defaultZoneLabel((float) $zone->min_distance_km, $zone->max_distance_km !== null ? (float) $zone->max_distance_km : null);

        if ($branch->delivery_free_order_amount !== null && $subtotal >= (int) $branch->delivery_free_order_amount) {
            $fee = 0;
            // Do not use the long rule text as a transactional label in the quote summary.
            $label = 'site.delivery_quote.free_shipping';
        }

        return new DeliveryQuote(
            available: true,
            fee: $fee,
            distanceKm: $distanceKm,
            zoneLabel: $label,
            source: 'geoapify',
        );
    }

    private function defaultZoneLabel(float $min, ?float $max): string
    {
        if ($max === null) {
            return 'Từ '.number_format($min, 1, ',', '.').' km';
        }

        return number_format($min, 1, ',', '.').' - '.number_format($max, 1, ',', '.').' km';
    }
}
