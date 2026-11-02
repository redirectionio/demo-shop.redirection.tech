<?php

namespace Traffic;

final readonly class CrawlerVisit
{
    public function __construct(
        private Crawler $crawler,
        private int $number,
        private Browsers $browsers,
        private KnownUrls $knownUrls,
        private Journal $journal,
        private Random $random,
    ) {
    }

    /**
     * @return \Generator<int, float, mixed, void> The seconds to wait before each next step
     */
    public function run(): \Generator
    {
        $visitor = new Visitor($this->crawler->name, $this->number, $this->crawler->userAgent);
        $browser = $this->browsers->forFirstVisit($visitor);
        $this->journal->visitStarted($visitor);

        $paths = [];
        foreach ($this->crawler->openingRequests as $path => $probability) {
            if ($this->random->chance($probability)) {
                $paths[] = $path;
            }
        }
        for ($pageCount = $this->random->int($this->crawler->minPages, $this->crawler->maxPages); $pageCount > 0; --$pageCount) {
            $paths[] = ($this->random->chance($this->crawler->staleShare) ? $this->knownUrls->stalePath($this->random) : null)
                ?? $this->knownUrls->popularPath($this->random);
        }

        // A crawler fetches each page once per visit.
        foreach (array_values(array_unique($paths)) as $index => $path) {
            if ($index > 0) {
                yield $this->random->float($this->crawler->minDelay, $this->crawler->maxDelay);
            }
            $browser->navigate($path, null);
        }
    }
}
