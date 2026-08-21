<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

class TimezoneOptions
{
    /**
     * @return array<string, string>
     */
    public static function business(): array
    {
        $zones = array_values(array_unique(array_merge(
            [
                'UTC',
                'Asia/Ho_Chi_Minh',
                'Asia/Bangkok',
                'Asia/Hong_Kong',
                'Asia/Shanghai',
                'Asia/Tokyo',
                'America/New_York',
                'America/Chicago',
                'America/Los_Angeles',
            ],
            DateTimeZone::listIdentifiers(DateTimeZone::EUROPE)
        )));

        $zones = array_values(array_filter($zones, [self::class, 'isValid']));

        usort($zones, function (string $a, string $b): int {
            $aRank = self::rank($a);
            $bRank = self::rank($b);

            return $aRank === $bRank ? strcmp($a, $b) : $aRank <=> $bRank;
        });

        return collect($zones)
            ->mapWithKeys(fn (string $timezone): array => [$timezone => self::label($timezone)])
            ->all();
    }

    private static function isValid(string $timezone): bool
    {
        try {
            new DateTimeZone($timezone);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function label(string $timezone): string
    {
        return "{$timezone} (".self::offsetLabel($timezone).")";
    }

    private static function offsetLabel(string $timezone): string
    {
        $zone = new DateTimeZone($timezone);
        $offset = $zone->getOffset(new DateTimeImmutable('now', $zone));
        $sign = $offset >= 0 ? '+' : '-';
        $offset = abs($offset);

        return sprintf('UTC%s%02d:%02d', $sign, intdiv($offset, 3600), intdiv($offset % 3600, 60));
    }

    private static function rank(string $timezone): int
    {
        return match (true) {
            $timezone === 'Europe/Athens' => 0,
            str_starts_with($timezone, 'Europe/') => 1,
            $timezone === 'UTC' => 2,
            default => 3,
        };
    }
}
