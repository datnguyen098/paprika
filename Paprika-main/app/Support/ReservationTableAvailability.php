<?php

namespace App\Support;

use App\Models\Reservation;
use App\Models\RestaurantTable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReservationTableAvailability
{
    public const DEFAULT_DURATION_MINUTES = 90;
    public const HOLD_MINUTES = 15;

    public static function availableTables(
        int $branchId,
        string $date,
        string $time,
        int $guests,
        ?int $excludeReservationId = null,
        ?int $durationMinutes = null,
    ): Collection {
        $conflictingTableIds = self::conflictingTableIds($branchId, $date, $time, $durationMinutes ?: self::DEFAULT_DURATION_MINUTES, $excludeReservationId);

        return RestaurantTable::query()
            ->where('branch_id', $branchId)
            ->active()
            ->where('seats', '>=', $guests)
            ->whereNotIn('id', $conflictingTableIds)
            ->orderBy('seats')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();
    }

    public static function bestAvailableTable(
        int $branchId,
        string $date,
        string $time,
        int $guests,
        ?int $excludeReservationId = null,
        ?int $durationMinutes = null,
    ): ?RestaurantTable {
        return self::availableTables($branchId, $date, $time, $guests, $excludeReservationId, $durationMinutes)->first();
    }

    public static function isTableAvailable(
        RestaurantTable $table,
        string $date,
        string $time,
        int $guests,
        ?int $excludeReservationId = null,
        ?int $durationMinutes = null,
    ): bool {
        if ($table->status !== 'active' || $table->seats < $guests) {
            return false;
        }

        return ! self::conflictingTableIds($table->branch_id, $date, $time, $durationMinutes ?: self::DEFAULT_DURATION_MINUTES, $excludeReservationId)
            ->contains($table->id);
    }

    public static function conflictingTableIds(
        int $branchId,
        string $date,
        string $time,
        int $durationMinutes = self::DEFAULT_DURATION_MINUTES,
        ?int $excludeReservationId = null,
    ): Collection {
        $start = self::scheduledAt($date, $time);
        $end = $start->copy()->addMinutes($durationMinutes);

        return Reservation::query()
            ->where('branch_id', $branchId)
            ->whereDate('reservation_date', $date)
            ->whereNotNull('table_id')
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->when($excludeReservationId, fn ($query) => $query->whereKeyNot($excludeReservationId))
            ->get(['id', 'table_id', 'reservation_date', 'reservation_time', 'duration_minutes'])
            ->filter(function (Reservation $reservation) use ($start, $end): bool {
                $reservationStart = $reservation->scheduledAt();
                $reservationEnd = $reservationStart->copy()->addMinutes($reservation->duration_minutes ?: self::DEFAULT_DURATION_MINUTES);

                return $reservationStart->lt($end) && $reservationEnd->gt($start);
            })
            ->pluck('table_id')
            ->unique()
            ->values();
    }

    public static function holdExpiresAt(string $date, string $time): Carbon
    {
        return self::scheduledAt($date, $time)->addMinutes(self::HOLD_MINUTES);
    }

    public static function scheduledAt(string $date, string $time): Carbon
    {
        return Carbon::parse($date.' '.substr($time, 0, 5));
    }
}
