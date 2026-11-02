<?php

namespace Traffic;

/**
 * Who visits the shop, and how often: about 300 people a day, and the bots that crawl every site.
 */
final readonly class Population
{
    private const float HUMAN_VISITS_PER_DAY = 300;
    private const float SCANNER_VISITS_PER_DAY = 6;

    public function __construct(
        private Browsers $browsers,
        private KnownUrls $knownUrls,
        private UserAgents $userAgents,
        private Journal $journal,
        private Random $random,
    ) {
    }

    /**
     * @return list<Stream>
     */
    public function streams(): array
    {
        $streams = [
            new Stream('human', self::HUMAN_VISITS_PER_DAY, Rhythm::Human, fn (int $number): \Generator => new HumanVisit(
                $number, $this->browsers, $this->knownUrls, $this->userAgents, $this->journal, $this->random,
            )->run()),
            new Stream('scanner', self::SCANNER_VISITS_PER_DAY, Rhythm::Constant, fn (int $number): \Generator => new ScannerVisit(
                $number, $this->browsers, $this->journal, $this->random,
            )->run()),
        ];
        foreach ($this->crawlers() as $crawler) {
            $streams[] = new Stream($crawler->name, $crawler->visitsPerDay, $crawler->rhythm, fn (int $number): \Generator => new CrawlerVisit(
                $crawler, $number, $this->browsers, $this->knownUrls, $this->journal, $this->random,
            )->run());
        }

        return $streams;
    }

    /**
     * The visits that last as long as the simulation.
     *
     * @return list<\Generator<int, float>>
     */
    public function perpetualVisits(): array
    {
        return [new MonitorVisit($this->browsers, $this->journal)->run()];
    }

    /**
     * @return list<Crawler>
     */
    private function crawlers(): array
    {
        $robotsTxt = '/robots.txt';

        return [
            // Search engines
            new Crawler('Googlebot', $this->userAgents->googlebotSmartphone(), 30, Rhythm::Constant, 2, 12, 3, 40, [$robotsTxt => 0.05, '/sitemap.xml' => 0.03], 0.12),
            new Crawler('Googlebot', $this->userAgents->googlebotDesktop(), 8, Rhythm::Constant, 1, 6, 3, 40, [$robotsTxt => 0.05], 0.12),
            new Crawler('bingbot', $this->userAgents->bingbot(), 12, Rhythm::Constant, 1, 8, 2, 30, [$robotsTxt => 0.1, '/sitemap.xml' => 0.05], 0.15),
            new Crawler('Applebot', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.1.1 Safari/605.1.15 (Applebot/0.1; +http://www.apple.com/go/applebot)', 3, Rhythm::Constant, 1, 5, 2, 20, [$robotsTxt => 0.2]),
            // AI crawlers, which also look for llms.txt
            new Crawler('GPTBot', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.2; +https://openai.com/gptbot', 8, Rhythm::Constant, 3, 25, 0.5, 4, [$robotsTxt => 0.3, '/llms.txt' => 0.15]),
            new Crawler('OAI-SearchBot', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot', 4, Rhythm::Constant, 1, 6, 1, 10, [$robotsTxt => 0.2]),
            new Crawler('ClaudeBot', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0; +claudebot@anthropic.com)', 7, Rhythm::Constant, 3, 20, 0.5, 5, [$robotsTxt => 0.3, '/llms.txt' => 0.15]),
            new Crawler('PerplexityBot', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)', 4, Rhythm::Constant, 1, 8, 1, 8, [$robotsTxt => 0.2, '/llms.txt' => 0.1]),
            new Crawler('meta-externalagent', 'meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)', 5, Rhythm::Constant, 2, 15, 0.5, 5, [$robotsTxt => 0.1]),
            new Crawler('Bytespider', 'Mozilla/5.0 (Linux; Android 5.0) AppleWebKit/537.36 (KHTML, like Gecko) Mobile Safari/537.36 (compatible; Bytespider; spider-feedback@bytedance.com)', 3, Rhythm::Constant, 5, 30, 0.2, 2, [], 0.1),
            new Crawler('Amazonbot', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Amazonbot/0.1; +https://developer.amazon.com/support/amazonbot) Chrome/119.0.6045.214 Safari/537.36', 3, Rhythm::Constant, 1, 8, 2, 20, [$robotsTxt => 0.2]),
            new Crawler('CCBot', 'CCBot/2.0 (https://commoncrawl.org/faq/)', 2, Rhythm::Constant, 2, 15, 1, 10, [$robotsTxt => 0.3], 0.1),
            // SEO tools, which crawl old pages from the backlinks they track
            new Crawler('AhrefsBot', 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)', 8, Rhythm::Constant, 3, 25, 1, 10, [$robotsTxt => 0.2, '/sitemap.xml' => 0.05], 0.25),
            new Crawler('SemrushBot', 'Mozilla/5.0 (compatible; SemrushBot/7~bl; +http://www.semrush.com/bot.html)', 6, Rhythm::Constant, 3, 20, 1, 10, [$robotsTxt => 0.2], 0.25),
            new Crawler('MJ12bot', 'Mozilla/5.0 (compatible; MJ12bot/v1.4.8; http://mj12bot.com/)', 2, Rhythm::Constant, 2, 15, 1, 10, [$robotsTxt => 0.3], 0.3),
            new Crawler('DataForSeoBot', 'Mozilla/5.0 (compatible; DataForSeoBot/1.0; +https://dataforseo.com/dataforseo-bot)', 2, Rhythm::Constant, 2, 10, 1, 8, [$robotsTxt => 0.2], 0.2),
            new Crawler('DotBot', 'Mozilla/5.0 (compatible; DotBot/1.2; +https://opensiteexplorer.org/dotbot; help@moz.com)', 1, Rhythm::Constant, 2, 10, 2, 10, [$robotsTxt => 0.3], 0.3),
            // AI assistants fetching a page a human asked about
            new Crawler('ChatGPT-User', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot', 8, Rhythm::Human, 1, 2, 2, 8, [], 0.03),
            new Crawler('Perplexity-User', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user)', 3, Rhythm::Human, 1, 2, 2, 8, [], 0.03),
            new Crawler('Claude-User', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)', 2, Rhythm::Human, 1, 2, 2, 8, [], 0.03),
            // Link previews, when someone shares a page
            new Crawler('facebookexternalhit', 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)', 5, Rhythm::Human, 1, 1, 1, 1),
            new Crawler('WhatsApp', 'WhatsApp/2.25.31.76 A', 4, Rhythm::Human, 1, 1, 1, 1),
            new Crawler('Slackbot', 'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)', 2, Rhythm::Human, 1, 1, 1, 1),
            new Crawler('Twitterbot', 'Twitterbot/1.0', 1, Rhythm::Human, 1, 1, 1, 1),
            new Crawler('LinkedInBot', 'LinkedInBot/1.0 (compatible; Mozilla/5.0; Apache-HttpClient +http://www.linkedin.com)', 1, Rhythm::Human, 1, 1, 1, 1),
            new Crawler('Discordbot', 'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)', 1, Rhythm::Human, 1, 1, 1, 1),
            new Crawler('Pinterestbot', 'Mozilla/5.0 (compatible; Pinterestbot/1.0; +http://www.pinterest.com/bot.html)', 3, Rhythm::Constant, 1, 5, 2, 15, [], 0.1),
        ];
    }
}
