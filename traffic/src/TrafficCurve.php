<?php

namespace Traffic;

use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * When humans shop: quiet nights, a lunch break, busy evenings, and Sunday evenings above all.
 */
final readonly class TrafficCurve
{
    private const string TIMEZONE = 'Europe/Paris';

    /** Relative traffic of each hour of the day, in Paris. */
    private const array HOURLY = [
        35, 20, 12, 8, 7, 10, 25, 50, 75, 90, 100, 105,
        125, 130, 110, 100, 100, 105, 115, 130, 160, 170, 130, 75,
    ];

    /** Indexed by ISO day of the week, from 1 for Monday. Their mean is 1. */
    private const array DAILY = [1 => 1.05, 2 => 1.0, 3 => 1.0, 4 => 0.95, 5 => 0.9, 6 => 0.9, 7 => 1.2];

    /** Some days are busier than others, within this range. */
    private const float DAY_VARIATION = 0.2;

    /**
     * How busy humans are at this time, 1 being the average.
     */
    public function humanActivity(\DateTimeImmutable $at): float
    {
        $local = $at->setTimezone(new \DateTimeZone(self::TIMEZONE));
        $hour = (int) $local->format('G');
        $nextHour = ($hour + 1) % 24;
        $hourly = self::HOURLY[$hour] + (self::HOURLY[$nextHour] - self::HOURLY[$hour]) * (int) $local->format('i') / 60;

        return $hourly / $this->hourlyMean() * self::DAILY[(int) $local->format('N')] * $this->dayVariation($local);
    }

    /**
     * An upper bound of humanActivity().
     */
    public function maxHumanActivity(): float
    {
        return max(self::HOURLY) / $this->hourlyMean() * max(self::DAILY) * (1 + self::DAY_VARIATION);
    }

    private function hourlyMean(): float
    {
        return array_sum(self::HOURLY) / \count(self::HOURLY);
    }

    /**
     * Derived from the date, so that every run of a day agrees.
     */
    private function dayVariation(\DateTimeImmutable $day): float
    {
        $randomizer = new Randomizer(new Xoshiro256StarStar(hash('sha256', $day->format('Y-m-d'), true)));

        return $randomizer->getFloat(1 - self::DAY_VARIATION, 1 + self::DAY_VARIATION);
    }
}
