<?php

namespace Traffic;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Browsers
{
    public function __construct(
        private HttpClientInterface $client,
        private Site $site,
        private Journal $journal,
        /** What returning visitors got on their previous visits. */
        private BrowserCache $returningVisitorsCache = new BrowserCache(),
    ) {
    }

    public function forFirstVisit(Visitor $visitor): Browser
    {
        return new Browser($this->client, $this->site, $this->journal, $visitor, new BrowserCache());
    }

    /**
     * Returning visitors still have the assets in their cache: they revalidate them, and mostly get 304 responses.
     */
    public function forReturningVisitor(Visitor $visitor): Browser
    {
        return new Browser($this->client, $this->site, $this->journal, $visitor, $this->returningVisitorsCache);
    }
}
