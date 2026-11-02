<?php

namespace Traffic;

final readonly class Site
{
    public function __construct(
        private string $baseUrl,
    ) {
    }

    public function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . $path;
    }

    /**
     * The path a link of the page at $currentPath leads to, or null when it leaves the site.
     */
    public function resolve(string $href, string $currentPath): ?string
    {
        $href = explode('#', $href, 2)[0];
        if ('' === $href) {
            return $currentPath;
        }

        $parts = parse_url($href);
        if (false === $parts) {
            return null;
        }
        // Absolute URLs, and other schemes like "mailto:".
        if (isset($parts['scheme']) || str_starts_with($href, '//')) {
            if (!$this->isSameOrigin($parts)) {
                return null;
            }

            return ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }
        if (str_starts_with($href, '/')) {
            return $href;
        }

        // The site only links with absolute paths: "../" is not worth supporting.
        return preg_replace('{[^/]*$}', '', strtok($currentPath, '?')) . $href;
    }

    /**
     * @param array<string, int|string> $parts
     */
    private function isSameOrigin(array $parts): bool
    {
        $base = parse_url($this->baseUrl);

        return isset($parts['host'])
            && strtolower($parts['host']) === strtolower($base['host'])
            && ($parts['port'] ?? null) === ($base['port'] ?? null);
    }
}
