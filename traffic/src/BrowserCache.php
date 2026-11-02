<?php

namespace Traffic;

/**
 * The validators (ETag) a browser keeps, to revalidate its cache with conditional requests.
 */
final class BrowserCache
{
    /** @var array<string, string> */
    private array $etagByPath = [];

    public function remember(string $path, string $etag): void
    {
        $this->etagByPath[$path] = $etag;
    }

    public function etagOf(string $path): ?string
    {
        return $this->etagByPath[$path] ?? null;
    }
}
