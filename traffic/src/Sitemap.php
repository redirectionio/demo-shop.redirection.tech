<?php

namespace Traffic;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Sitemap
{
    private const string NAMESPACE = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    public function __construct(
        private HttpClientInterface $client,
        private Site $site,
        private string $userAgent,
    ) {
    }

    /**
     * @return non-empty-list<string>
     */
    public function paths(): array
    {
        // Throws on an error status: a broken deployment must not mark every page as gone.
        $xml = $this->client->request('GET', $this->site->url('/sitemap.xml'), [
            'headers' => ['User-Agent' => $this->userAgent],
        ])->getContent();

        $paths = [];
        foreach (\Dom\XMLDocument::createFromString($xml)->getElementsByTagNameNS(self::NAMESPACE, 'loc') as $location) {
            $paths[] = parse_url(trim($location->textContent), \PHP_URL_PATH);
        }
        if ([] === $paths) {
            throw new \RuntimeException('The sitemap lists no page.');
        }

        return $paths;
    }
}
