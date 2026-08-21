<?php

namespace App\Support;

use App\Models\Branch;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class OpenDays
{
    public const DEFAULT_DAYS = [1, 2, 3, 4, 5, 6, 0];

    public static function options(): array
    {
        return [
            1 => 'Thứ 2',
            2 => 'Thứ 3',
            3 => 'Thứ 4',
            4 => 'Thứ 5',
            5 => 'Thứ 6',
            6 => 'Thứ 7',
            0 => 'Chủ nhật',
        ];
    }

    public static function configuredDays(?Branch $branch = null): array
    {
        if ($branch && filled($branch->open_days)) {
            return self::normalize($branch->open_days);
        }

        return self::normalize(setting('open_days', implode(',', self::DEFAULT_DAYS)));
    }

    public static function normalize(mixed $value): array
    {
        if ($value === null || $value === '') {
            return self::DEFAULT_DAYS;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/[\s,;|]+/', $value);
        }

        if (! is_array($value)) {
            return self::DEFAULT_DAYS;
        }

        $days = collect($value)
            ->map(fn (mixed $day): ?int => self::parseDay($day))
            ->filter(fn (?int $day): bool => $day !== null)
            ->unique()
            ->values()
            ->all();

        if ($days === []) {
            return self::DEFAULT_DAYS;
        }

        return collect(self::DEFAULT_DAYS)
            ->filter(fn (int $day): bool => in_array($day, $days, true))
            ->values()
            ->all();
    }

    public static function isOpenOn(mixed $date, ?Branch $branch = null): bool
    {
        return in_array(self::dayOfWeek($date, $branch), self::configuredDays($branch), true);
    }

    public static function nextOpenDate(?Branch $branch = null, mixed $from = null): Carbon
    {
        $date = $from
            ? self::carbon($from, $branch)->startOfDay()
            : business_today($branch);

        for ($offset = 0; $offset < 14; $offset++) {
            $candidate = $date->copy()->addDays($offset);

            if (self::isOpenOn($candidate, $branch)) {
                return $candidate;
            }
        }

        return $date;
    }

    private static function parseDay(mixed $day): ?int
    {
        if (is_numeric($day)) {
            $number = (int) $day;

            return $number >= 0 && $number <= 6 ? $number : null;
        }

        $token = str($day)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '')->toString();

        return match ($token) {
            'sun', 'sunday', 'chunhat', 'cn' => 0,
            'mon', 'monday', 'thu2', 't2' => 1,
            'tue', 'tuesday', 'thu3', 't3' => 2,
            'wed', 'wednesday', 'thu4', 't4' => 3,
            'thu', 'thursday', 'thu5', 't5' => 4,
            'fri', 'friday', 'thu6', 't6' => 5,
            'sat', 'saturday', 'thu7', 't7' => 6,
            default => null,
        };
    }

    private static function dayOfWeek(mixed $date, ?Branch $branch = null): int
    {
        return self::carbon($date, $branch)->dayOfWeek;
    }

    private static function carbon(mixed $date, ?Branch $branch = null): Carbon
    {
        if ($date instanceof CarbonInterface) {
            return Carbon::instance($date)->timezone(business_timezone($branch));
        }

        return Carbon::parse((string) $date, business_timezone($branch));
    }
}
