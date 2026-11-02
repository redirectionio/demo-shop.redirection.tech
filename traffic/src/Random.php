<?php

namespace Traffic;

use Random\Randomizer;

final readonly class Random
{
    private const string TOKEN_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

    public function __construct(
        private Randomizer $randomizer = new Randomizer(),
    ) {
    }

    public function chance(float $probability): bool
    {
        return $this->randomizer->nextFloat() < $probability;
    }

    public function int(int $min, int $max): int
    {
        return $this->randomizer->getInt($min, $max);
    }

    public function float(float $min, float $max): float
    {
        return $this->randomizer->getFloat($min, $max);
    }

    /**
     * @template T
     *
     * @param non-empty-list<T> $items
     *
     * @return T
     */
    public function pick(array $items): mixed
    {
        return $items[$this->randomizer->getInt(0, \count($items) - 1)];
    }

    /**
     * @template T
     *
     * @param list<T> $items
     *
     * @return list<T>
     */
    public function sample(array $items, int $count): array
    {
        return \array_slice($this->randomizer->shuffleArray($items), 0, $count);
    }

    /**
     * @template K of array-key
     *
     * @param non-empty-array<K, float|int> $weights
     *
     * @return K
     */
    public function weighted(array $weights): int|string
    {
        $threshold = $this->randomizer->nextFloat() * array_sum($weights);
        foreach ($weights as $key => $weight) {
            $threshold -= $weight;
            if ($threshold < 0) {
                return $key;
            }
        }

        // Rounding errors can leave a tiny remainder.
        return array_key_last($weights);
    }

    /**
     * The time between independent events, like the arrivals of visitors.
     */
    public function exponential(float $mean): float
    {
        return -$mean * log(1 - $this->randomizer->nextFloat());
    }

    /**
     * Durations with a long tail, like the time spent reading a page.
     */
    public function logNormal(float $median, float $sigma): float
    {
        // Box-Muller transform.
        $normal = sqrt(-2 * log(1 - $this->randomizer->nextFloat())) * cos(2 * \M_PI * $this->randomizer->nextFloat());

        return $median * exp($sigma * $normal);
    }

    /**
     * Opaque identifiers, like the click identifiers added by ad networks.
     */
    public function token(int $length): string
    {
        return $this->randomizer->getBytesFromString(self::TOKEN_ALPHABET, $length);
    }
}
