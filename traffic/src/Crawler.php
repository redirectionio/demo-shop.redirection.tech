<?php

namespace Traffic;

/**
 * A bot, and how it crawls.
 */
final readonly class Crawler
{
    public function __construct(
        public string $name,
        public string $userAgent,
        public float $visitsPerDay,
        public Rhythm $rhythm,
        public int $minPages,
        public int $maxPages,
        /** The seconds between two pages, at least. */
        public float $minDelay,
        public float $maxDelay,
        /** @var array<string, float> The files the bot may ask for first, and how often it does */
        public array $openingRequests = [],
        /** The share of its requests on pages the site no longer has. */
        public float $staleShare = 0.05,
    ) {
    }
}
