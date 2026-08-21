<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Dish;
use App\Models\DishTimeSlot;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class DishAvailabilityResult
{
    /**
     * @param \Illuminate\Support\Collection<int, DishTimeSlot> $activeSlots
     * @param \Illuminate\Support\Collection<int, DishTimeSlot> $allSlots
     */
    public function __construct(
        public readonly bool $available,
        public readonly Collection $activeSlots,
        public readonly Collection $allSlots,
    ) {}

    public function label(): ?string
    {
        $slots = $this->activeSlots->isNotEmpty() ? $this->activeSlots : $this->allSlots;
        if ($slots->isEmpty()) {
            return null;
        }

        return $slots
            ->map(fn (DishTimeSlot $slot) => (string) $slot->localized('name', $slot->name))
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->implode(' · ');
    }

    public function windowLabel(?string $locale = null): ?string
    {
        $windows = $this->windowLabels($locale);

        return $windows === [] ? null : implode(', ', $windows);
    }

    public function windowLabels(?string $locale = null): array
    {
        return $this->allSlots
            ->map(fn (DishTimeSlot $slot) => $this->formatTime((string) $slot->start_time, $locale).' - '.$this->formatTime((string) $slot->end_time, $locale))
            ->filter(fn ($v) => $v !== ' - ')
            ->unique()
            ->values()
            ->all();
    }

    private function formatTime(string $time, ?string $locale): string
    {
        $time = substr($time, 0, 5);
        if ($time === '') {
            return '';
        }

        return ($locale ?? app()->getLocale()) === 'vi'
            ? str_replace(':', 'h', $time)
            : $time;
    }
}

class DishAvailabilityService
{
    public function check(Dish $dish, Branch $branch, ?CarbonInterface $now = null): DishAvailabilityResult
    {
        $now ??= business_now($branch);
        $dish->loadMissing(['timeSlots.translations']);

        $allSlots = $dish->timeSlots
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->values();

        if ($allSlots->isEmpty()) {
            return new DishAvailabilityResult(true, collect(), collect());
        }

        $time = $now->format('H:i:s');

        $active = $allSlots->filter(function (DishTimeSlot $slot) use ($now, $time): bool {
            $start = $this->normalizeTime((string) $slot->start_time);
            $end = $this->normalizeTime((string) $slot->end_time);
            $slotDate = $end < $start && $time <= $end
                ? $now->copy()->subDay()->toDateString()
                : $now->toDateString();

            if ($slot->start_date && $slotDate < $slot->start_date->toDateString()) {
                return false;
            }
            if ($slot->end_date && $slotDate > $slot->end_date->toDateString()) {
                return false;
            }

            // Overnight range e.g. 22:00 -> 02:00
            if ($end < $start) {
                return ($time >= $start) || ($time <= $end);
            }

            return $time >= $start && $time <= $end;
        })->values();

        return new DishAvailabilityResult($active->isNotEmpty(), $active, $allSlots);
    }

    public function at(Dish $dish, Branch $branch, ?CarbonInterface $at = null): DishAvailabilityResult
    {
        return $this->check($dish, $branch, $at);
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
