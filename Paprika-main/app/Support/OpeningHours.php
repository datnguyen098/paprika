<?php

namespace App\Support;

use App\Models\Branch;
use Carbon\Carbon;

class OpeningHours
{
    /**
     * @param array<int, array{start: string, end: string}> $slots
     */
    public function __construct(
        public readonly array $slots,
        public readonly string $opensAt,
        public readonly string $closesAt,
        public readonly ?string $lastBookingTime,
        public readonly int $bufferMinutes,
        public readonly string $label,
    ) {}

    public static function fromSetting(?string $value = null): self
    {
        $openingLabel = trim((string) ($value ?: setting('opening_hours', '09:00 - 21:30 hằng ngày')));
        $slotSetting = trim((string) setting('reservation_time_slots', ''));
        $lastBookingTime = self::extractTime((string) setting('reservation_last_booking_time', ''));
        $bufferMinutes = max(0, (int) setting('reservation_last_order_buffer_minutes', 30));

        return self::make($openingLabel, $slotSetting, $lastBookingTime, $bufferMinutes);
    }

    public static function fromBranch(?Branch $branch = null): self
    {
        if (! $branch) {
            return self::fromSetting();
        }

        $openingLabel = trim((string) ($branch->opening_hours ?: setting('opening_hours', '09:00 - 21:30 hằng ngày')));
        $hasBranchSlots = filled($branch->reservation_time_slots);
        $slotSetting = trim((string) ($hasBranchSlots ? $branch->reservation_time_slots : setting('reservation_time_slots', '')));
        $lastBookingSource = filled($branch->reservation_last_booking_time)
            ? $branch->reservation_last_booking_time
            : ($hasBranchSlots ? '' : setting('reservation_last_booking_time', ''));
        $lastBookingTime = self::extractTime((string) $lastBookingSource);
        $bufferMinutes = max(0, (int) ($branch->reservation_last_order_buffer_minutes ?? setting('reservation_last_order_buffer_minutes', 30)));

        return self::make($openingLabel, $slotSetting, $lastBookingTime, $bufferMinutes);
    }

    public function isWithin(string $time): bool
    {
        $normalizedTime = self::normalizeTime($time);

        foreach ($this->slots as $slot) {
            if ($this->bookableMinuteForTime($normalizedTime, $slot) !== null) {
                return true;
            }
        }

        return false;
    }

    public function isPastToday(string $date, string $time, ?Branch $branch = null): bool
    {
        if ($date !== $this->operatingDateFor(business_now($branch), $branch)->toDateString()) {
            return false;
        }

        return $this->scheduledAt($date, $time, $branch)->lte(business_now($branch));
    }

    public function scheduledAt(string $date, string $time, ?Branch $branch = null): Carbon
    {
        $normalizedTime = self::normalizeTime($time);
        $scheduledAt = Carbon::parse($date, business_timezone($branch))->startOfDay()
            ->setTimeFromTimeString($normalizedTime);

        foreach ($this->slots as $slot) {
            if ($this->bookableMinuteForTime($normalizedTime, $slot) === null) {
                continue;
            }

            if ($this->isOvernightSlot($slot) && self::minuteOfDay($normalizedTime) <= self::minuteOfDay($slot['end'])) {
                return $scheduledAt->addDay();
            }

            return $scheduledAt;
        }

        return $scheduledAt;
    }

    public function operatingDateFor(Carbon $dateTime, ?Branch $branch = null): Carbon
    {
        $local = $dateTime->copy()->timezone(business_timezone($branch));
        $time = $local->format('H:i');

        foreach ($this->slots as $slot) {
            if ($this->isOvernightSlot($slot)
                && self::minuteOfDay($time) <= self::minuteOfDay($slot['end'])) {
                return $local->copy()->subDay()->startOfDay();
            }
        }

        return $local->startOfDay();
    }

    public function message(): string
    {
        return "Quán nhận đặt bàn theo khung giờ: {$this->label}.";
    }

    /**
     * @return array<int, array{start: string, end: string}>
     */
    public function bookableSlots(): array
    {
        $slots = [];

        foreach ($this->slots as $slot) {
            $bookableEnd = $this->latestBookableMinuteInSlot($slot);
            if ($bookableEnd >= self::minuteOfDay($slot['start'])) {
                $bookableEnd = self::minuteToTime($bookableEnd);
                $slots[] = ['start' => $slot['start'], 'end' => $bookableEnd];
            }
        }

        return $slots;
    }

    public function firstBookableTime(): ?string
    {
        return $this->bookableSlots()[0]['start'] ?? null;
    }

    public function lastBookableTime(): ?string
    {
        $slots = $this->bookableSlots();

        return $slots === [] ? null : $slots[array_key_last($slots)]['end'];
    }

    private static function make(string $openingLabel, string $slotSetting, ?string $lastBookingTime, int $bufferMinutes): self
    {
        $slots = self::parseSlots($slotSetting);
        if ($slots === []) {
            $slots = self::parseSlotsFromFreeText($openingLabel);
        }
        if ($slots === []) {
            $slots = [['start' => '09:00', 'end' => '21:30']];
        }

        $opensAt = $slots[0]['start'];
        $closesAt = $slots[array_key_last($slots)]['end'];

        return new self(
            slots: $slots,
            opensAt: $opensAt,
            closesAt: $closesAt,
            lastBookingTime: $lastBookingTime,
            bufferMinutes: $bufferMinutes,
            label: self::buildLabel($slots, $lastBookingTime, $bufferMinutes),
        );
    }

    private static function normalizeTime(string $time): string
    {
        return Carbon::createFromFormat('H:i', str_pad($time, 5, '0', STR_PAD_LEFT))->format('H:i');
    }

    /**
     * @return array<int, array{start: string, end: string}>
     */
    private static function parseSlots(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $items = preg_split('/[\r\n,;|]+/', $value) ?: [];
        $slots = [];

        foreach ($items as $item) {
            if (! preg_match('/([01]?\d|2[0-3]):([0-5]\d)\s*[-–]\s*([01]?\d|2[0-3]):([0-5]\d)/u', $item, $matches)) {
                continue;
            }

            $start = self::normalizeTime($matches[1].':'.$matches[2]);
            $end = self::normalizeTime($matches[3].':'.$matches[4]);
            if ($start === $end) {
                continue;
            }

            $slots[] = compact('start', 'end');
        }

        usort($slots, fn (array $a, array $b): int => strcmp($a['start'], $b['start']));

        return $slots;
    }

    /**
     * @return array<int, array{start: string, end: string}>
     */
    private static function parseSlotsFromFreeText(string $text): array
    {
        preg_match_all('/([01]?\d|2[0-3]):([0-5]\d)\s*[-–]\s*([01]?\d|2[0-3]):([0-5]\d)/u', $text, $matches, PREG_SET_ORDER);

        $slots = [];
        foreach ($matches as $match) {
            $start = self::normalizeTime($match[1].':'.$match[2]);
            $end = self::normalizeTime($match[3].':'.$match[4]);
            if ($start !== $end) {
                $slots[] = compact('start', 'end');
            }
        }

        if ($slots !== []) {
            usort($slots, fn (array $a, array $b): int => strcmp($a['start'], $b['start']));
        }

        return $slots;
    }

    private static function extractTime(string $value): ?string
    {
        if (! preg_match('/([01]?\d|2[0-3]):([0-5]\d)/', $value, $matches)) {
            return null;
        }

        return self::normalizeTime($matches[1].':'.$matches[2]);
    }

    private function latestBookableInSlot(string $slotStart, string $slotEnd): string
    {
        return self::minuteToTime($this->latestBookableMinuteInSlot([
            'start' => $slotStart,
            'end' => $slotEnd,
        ]));
    }

    /**
     * @param array{start: string, end: string} $slot
     */
    private function bookableMinuteForTime(string $time, array $slot): ?int
    {
        $minute = $this->slotMinuteForTime($time, $slot, false);
        if ($minute === null) {
            return null;
        }

        $start = self::minuteOfDay($slot['start']);
        $latest = $this->latestBookableMinuteInSlot($slot);

        return $minute >= $start && $minute <= $latest ? $minute : null;
    }

    /**
     * @param array{start: string, end: string} $slot
     */
    private function latestBookableMinuteInSlot(array $slot): int
    {
        $start = self::minuteOfDay($slot['start']);
        $latest = $this->slotEndMinute($slot) - $this->bufferMinutes;

        if ($this->lastBookingTime) {
            $lastBooking = $this->slotMinuteForTime($this->lastBookingTime, $slot, false);
            if ($lastBooking !== null) {
                $latest = min($latest, $lastBooking);
            }
        }

        return max($start, $latest);
    }

    /**
     * @param array{start: string, end: string} $slot
     */
    private function slotMinuteForTime(string $time, array $slot, bool $allowPreOpen): ?int
    {
        $minute = self::minuteOfDay($time);
        $start = self::minuteOfDay($slot['start']);
        $end = self::minuteOfDay($slot['end']);

        if ($this->isOvernightSlot($slot)) {
            if ($minute < $start) {
                if ($minute <= $end) {
                    return $minute + 1440;
                }

                return $allowPreOpen ? $minute : null;
            }

            return $minute;
        }

        if ($minute < $start || $minute > $end) {
            return $allowPreOpen ? $minute : null;
        }

        return $minute;
    }

    /**
     * @param array{start: string, end: string} $slot
     */
    private function slotEndMinute(array $slot): int
    {
        $end = self::minuteOfDay($slot['end']);

        return $this->isOvernightSlot($slot) ? $end + 1440 : $end;
    }

    /**
     * @param array{start: string, end: string} $slot
     */
    private function isOvernightSlot(array $slot): bool
    {
        return self::minuteOfDay($slot['end']) < self::minuteOfDay($slot['start']);
    }

    private static function minuteOfDay(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', self::normalizeTime($time)));

        return ($hour * 60) + $minute;
    }

    private static function minuteToTime(int $minute): string
    {
        $minute %= 1440;
        if ($minute < 0) {
            $minute += 1440;
        }

        return sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
    }

    /**
     * @param array<int, array{start: string, end: string}> $slots
     */
    private static function buildLabel(array $slots, ?string $lastBookingTime, int $bufferMinutes): string
    {
        $base = collect($slots)->map(fn (array $slot): string => "{$slot['start']} - {$slot['end']}")->implode(', ');
        $parts = [$base];

        if ($lastBookingTime) {
            $parts[] = "nhận đặt bàn đến {$lastBookingTime}";
        } elseif ($bufferMinutes > 0) {
            $parts[] = "ngừng nhận trước giờ đóng bếp {$bufferMinutes} phút";
        }

        return implode(' | ', $parts);
    }
}
