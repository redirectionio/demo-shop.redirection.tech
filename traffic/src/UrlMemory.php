<?php

namespace Traffic;

/**
 * Every page the site has ever listed in its sitemap: search engines, backlinks and bookmarks remember them long after
 * they are gone.
 */
final readonly class UrlMemory
{
    private const string DATE_FORMAT = 'Y-m-d';

    /**
     * @param array<string, ?string> $goneSinceByPath The day each page left the sitemap, null while it is listed
     */
    private function __construct(
        private array $goneSinceByPath,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromJson(string $json): self
    {
        $goneSinceByPath = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
        // The file can be edited by hand on GitHub.
        if (!\is_array($goneSinceByPath)) {
            throw new \RuntimeException('The URL memory must be a JSON object.');
        }
        foreach ($goneSinceByPath as $path => $goneSince) {
            $isDate = \is_string($goneSince) && false !== \DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $goneSince);
            if (!str_starts_with((string) $path, '/') || (null !== $goneSince && !$isDate)) {
                throw new \RuntimeException(\sprintf('The URL memory is corrupted at "%s": each path must start with "/" and be mapped to null or to a date like "2026-11-02".', $path));
            }
        }

        return new self($goneSinceByPath);
    }

    public function toJson(): string
    {
        return json_encode($this->goneSinceByPath, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR) . "\n";
    }

    /**
     * @param list<string> $listedPaths
     */
    public function withSitemap(array $listedPaths, \DateTimeImmutable $today): self
    {
        $goneSinceByPath = [];
        foreach ($this->goneSinceByPath as $path => $goneSince) {
            $goneSinceByPath[$path] = $goneSince ?? $today->format(self::DATE_FORMAT);
        }
        foreach ($listedPaths as $path) {
            $goneSinceByPath[$path] = null;
        }
        ksort($goneSinceByPath, \SORT_STRING);

        return new self($goneSinceByPath);
    }

    public function equals(self $other): bool
    {
        return $this->goneSinceByPath === $other->goneSinceByPath;
    }

    /**
     * @return list<string>
     */
    public function listedPaths(): array
    {
        return array_keys(array_filter($this->goneSinceByPath, static fn (?string $goneSince): bool => null === $goneSince));
    }

    /**
     * @return array<string, \DateTimeImmutable>
     */
    public function goneSinceByPath(): array
    {
        return array_map(
            static fn (string $goneSince): \DateTimeImmutable => \DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $goneSince),
            array_filter($this->goneSinceByPath, static fn (?string $goneSince): bool => null !== $goneSince),
        );
    }
}
