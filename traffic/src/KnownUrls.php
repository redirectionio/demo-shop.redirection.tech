<?php

namespace Traffic;

/**
 * The pages the outside world sends visitors to: the ones the site has, and the ones it used to have.
 */
final readonly class KnownUrls
{
    /** How skewed the popularity of the pages is: a few pages get most of the visits. */
    private const float ZIPF_EXPONENT = 0.8;

    /** A page that just left the site is still everywhere: it gets as many visits as several old pages. */
    private const float RECENTLY_GONE_WEIGHT = 20;

    /** How fast the world forgets a page that left the site. */
    private const float FORGETTING_TIME_IN_DAYS = 30;

    /** The world never forgets a page entirely: old backlinks and bookmarks remain. */
    private const float FORGOTTEN_WEIGHT = 0.3;

    /**
     * @param array<string, float> $listedWeights The popularity of the pages of the site
     * @param array<string, float> $staleWeights  The popularity of the pages it used to have
     */
    private function __construct(
        private array $listedWeights,
        private array $staleWeights,
    ) {
    }

    /**
     * @param list<string> $legacyPaths
     */
    public static function from(UrlMemory $memory, array $legacyPaths, \DateTimeImmutable $now): self
    {
        $listedPaths = $memory->listedPaths();
        if ([] === $listedPaths) {
            throw new \RuntimeException('The URL memory lists no page: run "castor traffic:memorize" first.');
        }

        // A stable ranking: the same pages stay popular from one run to the next.
        usort($listedPaths, static fn (string $a, string $b): int => crc32($a) <=> crc32($b));
        $listedWeights = [];
        foreach ($listedPaths as $rank => $path) {
            $listedWeights[$path] = 1 / ($rank + 1) ** self::ZIPF_EXPONENT;
        }

        $staleWeights = array_fill_keys($legacyPaths, self::FORGOTTEN_WEIGHT);
        foreach ($memory->goneSinceByPath() as $path => $goneSince) {
            $staleWeights[$path] = self::FORGOTTEN_WEIGHT + self::RECENTLY_GONE_WEIGHT * exp(-$goneSince->diff($now)->days / self::FORGETTING_TIME_IN_DAYS);
        }

        return new self($listedWeights, $staleWeights);
    }

    /**
     * @return list<string>
     */
    public static function readLegacyPaths(string $file): array
    {
        $lines = file($file, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES);
        if (false === $lines) {
            throw new \RuntimeException(\sprintf('Cannot read "%s".', $file));
        }

        return array_values(array_filter(array_map(trim(...), $lines), static fn (string $line): bool => '' !== $line && !str_starts_with($line, '#')));
    }

    public function popularPath(Random $random): string
    {
        return $random->weighted($this->listedWeights);
    }

    public function popularPathIn(Random $random, string $locale): string
    {
        return $this->pickAmong($random, static fn (string $path): bool => str_starts_with($path, "/{$locale}/"));
    }

    public function popularProductPathIn(Random $random, string $locale): string
    {
        return $this->pickAmong($random, static fn (string $path): bool => str_starts_with($path, "/{$locale}/") && self::isProductPath($path));
    }

    /**
     * A page the site no longer has, or null when the world knows none.
     */
    public function stalePath(Random $random): ?string
    {
        return [] === $this->staleWeights ? null : $random->weighted($this->staleWeights);
    }

    /**
     * @param \Closure(string): bool $filter
     */
    private function pickAmong(Random $random, \Closure $filter): string
    {
        $weights = array_filter($this->listedWeights, $filter, \ARRAY_FILTER_USE_KEY);

        return [] === $weights ? $this->popularPath($random) : $random->weighted($weights);
    }

    /**
     * Products are the only pages three levels below the locale, category pages excepted (see src/lib/routing.ts).
     */
    private static function isProductPath(string $path): bool
    {
        return 1 === preg_match('{^/[^/]+/[^/]+/[^/]+/[^/]+/$}', $path) && !str_contains($path, '/page/');
    }
}
