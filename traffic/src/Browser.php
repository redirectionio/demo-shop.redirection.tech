<?php

namespace Traffic;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Sends the requests of a visitor: the pages it opens, and the assets they need.
 */
final class Browser
{
    private const int MAX_REDIRECTIONS = 5;
    private const string ACCEPT_HTML = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
    private const string ACCEPT_IMAGE = 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8';

    /** @var array<string, true> The assets loaded during this visit: the browser does not ask for them again. */
    private array $loadedAssets = [];

    /** @var array<string, ?string> The bodies of the resources fetched by scripts during this visit. */
    private array $fetchedBodies = [];

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly Site $site,
        private readonly Journal $journal,
        private readonly Visitor $visitor,
        private readonly BrowserCache $cache,
    ) {
    }

    /**
     * Opens a page, following its redirections.
     */
    public function navigate(string $path, ?string $referer, string $method = 'GET'): Page
    {
        // Browsers keep the original referer through redirections.
        $headers = $this->headers(self::ACCEPT_HTML, $referer);
        for ($redirections = 0;; ++$redirections) {
            [$status, $responseHeaders, $body] = $this->send($method, $path, $headers);
            $this->journal->page($this->visitor, $method, $path, $status);

            $location = $responseHeaders['location'][0] ?? null;
            if ($status >= 300 && $status < 400 && null !== $location) {
                if (self::MAX_REDIRECTIONS === $redirections) {
                    throw new VisitAborted(\sprintf('Too many redirections, the last one to "%s".', $location));
                }
                $nextPath = $this->site->resolve($location, $path);
                if (null === $nextPath) {
                    // The redirection leaves the site.
                    return Page::withoutDocument($this->site, $path, $status);
                }
                $path = $nextPath;

                continue;
            }

            $isHtml = str_starts_with($responseHeaders['content-type'][0] ?? '', 'text/html');

            return $isHtml && '' !== $body ? Page::parse($this->site, $path, $status, $body) : Page::withoutDocument($this->site, $path, $status);
        }
    }

    /**
     * Loads assets in parallel, like a browser does, except the ones it already has.
     *
     * @param list<string> $paths
     */
    public function load(array $paths, ?string $referer): void
    {
        $responses = [];
        foreach ($paths as $path) {
            if (isset($this->loadedAssets[$path])) {
                continue;
            }
            $this->loadedAssets[$path] = true;

            $headers = $this->headers(self::acceptFor($path), $referer);
            $etag = $this->cache->etagOf($path);
            if (null !== $etag) {
                $headers['If-None-Match'] = $etag;
            }
            $responses[$path] = $this->client->request('GET', $this->site->url($path), ['headers' => $headers]);
        }

        $failure = null;
        foreach ($responses as $path => $response) {
            try {
                $status = $response->getStatusCode();
                $etag = $response->getHeaders(false)['etag'][0] ?? null;
                $response->getContent(false);
            } catch (TransportExceptionInterface $exception) {
                // The other requests are still running: let them complete first.
                $failure ??= $exception;

                continue;
            }

            $this->journal->asset($status);
            if (null !== $etag) {
                $this->cache->remember($path, $etag);
            }
        }

        if (null !== $failure) {
            throw new VisitAborted('An asset could not be loaded.', previous: $failure);
        }
    }

    /**
     * Fetches a resource a script of the page needs, and returns its body, or null on an error status.
     */
    public function fetch(string $path, string $referer): ?string
    {
        if (!\array_key_exists($path, $this->fetchedBodies)) {
            [$status, , $body] = $this->send('GET', $path, $this->headers('*/*', $referer));
            $this->journal->asset($status);
            $this->fetchedBodies[$path] = 200 === $status ? $body : null;
        }

        return $this->fetchedBodies[$path];
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array{int, array<string, list<string>>, string}
     */
    private function send(string $method, string $path, array $headers): array
    {
        $response = $this->client->request($method, $this->site->url($path), ['headers' => $headers]);

        try {
            return [$response->getStatusCode(), $response->getHeaders(false), $response->getContent(false)];
        } catch (TransportExceptionInterface $exception) {
            throw new VisitAborted(\sprintf('%s %s failed.', $method, $path), previous: $exception);
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $accept, ?string $referer): array
    {
        return array_filter([
            'User-Agent' => $this->visitor->userAgent,
            'Accept' => $accept,
            'Accept-Language' => $this->visitor->acceptLanguage,
            'Referer' => $referer,
        ], static fn (?string $value): bool => null !== $value);
    }

    private static function acceptFor(string $path): string
    {
        return match (strtolower(pathinfo((string) parse_url($path, \PHP_URL_PATH), \PATHINFO_EXTENSION))) {
            'css' => 'text/css,*/*;q=0.1',
            'avif', 'gif', 'ico', 'jpeg', 'jpg', 'png', 'svg', 'webp' => self::ACCEPT_IMAGE,
            default => '*/*',
        };
    }
}
