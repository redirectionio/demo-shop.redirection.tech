<?php

namespace Traffic;

/**
 * Visits arriving independently of each other, like the visitors coming from search engines.
 */
final readonly class Stream
{
    public function __construct(
        public string $name,
        public float $visitsPerDay,
        public Rhythm $rhythm,
        /** @var \Closure(int $number): \Generator<int, float> */
        public \Closure $startVisit,
    ) {
    }
}
