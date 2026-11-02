<?php

namespace Traffic;

/**
 * Someone shopping: they come from a search engine, a newsletter, a social network…, browse the catalog, and sometimes
 * place an order.
 */
final readonly class HumanVisit
{
    private const array LOCALES = ['fr' => 75, 'en' => 25];

    private const array ACCEPT_LANGUAGES = [
        'fr' => [
            'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7' => 50,
            'fr-FR,fr;q=0.9' => 25,
            'fr' => 8,
            'fr-BE,fr;q=0.9,nl;q=0.8,en;q=0.7' => 7,
            'fr-CH,fr;q=0.9,de;q=0.8,en;q=0.7' => 5,
            'fr-CA,fr;q=0.9,en-CA;q=0.8,en;q=0.7' => 5,
        ],
        // The English pages also serve the visitors whose language the site does not have.
        'en' => [
            'en-GB,en;q=0.9' => 30,
            'en-US,en;q=0.9' => 30,
            'de-DE,de;q=0.9,en;q=0.8' => 12,
            'nl-NL,nl;q=0.9,en;q=0.8' => 8,
            'es-ES,es;q=0.9,en;q=0.8' => 8,
            'it-IT,it;q=0.9,en;q=0.8' => 7,
            'en' => 5,
        ],
    ];

    private const array DEVICES = ['mobile' => 62, 'desktop' => 33, 'tablet' => 5];

    private const array SOURCES = [
        'google' => 40,
        'direct' => 16,
        'social' => 10,
        'search-engine' => 7,
        'old-link' => 7,
        'google-ads' => 6,
        'newsletter' => 6,
        'ai-assistant' => 4,
    ];

    /** How many visitors leave right after the first page, depending on where they come from. */
    private const array BOUNCE_RATES = [
        'google' => 0.45,
        'direct' => 0.3,
        'social' => 0.6,
        'search-engine' => 0.45,
        'old-link' => 0.55,
        'google-ads' => 0.5,
        'newsletter' => 0.35,
        'ai-assistant' => 0.4,
    ];

    private const array GOOGLE = [
        'fr' => ['https://www.google.com/', 'https://www.google.fr/'],
        'en' => ['https://www.google.com/', 'https://www.google.co.uk/', 'https://www.google.de/'],
    ];

    private const array SEARCH_ENGINES = ['https://www.bing.com/', 'https://duckduckgo.com/', 'https://www.ecosia.org/', 'https://www.qwant.com/', 'https://search.yahoo.com/'];

    private const array SEARCH_TERMS = [
        'fr' => [
            'canapé', 'canape cuir', 'canapé 3 places', 'fauteuil', 'fauteuil velours', 'table basse', 'table basse bois',
            'lampe', 'lampe de chevet', 'lampe laiton', 'suspension', 'lampadaire', 'miroir', 'miroir rond', 'vase',
            'bougie', 'bougie parfumée', 'coussin', 'coussin lin', 'plaid', 'plaid laine', 'tapis', 'lin', 'velours',
            'cadeau', 'promo', 'canaper', 'fauteil', 'lampadère', 'mirroir', 'cousin velour',
        ],
        'en' => [
            'sofa', 'leather sofa', 'couch', 'armchair', 'coffee table', 'lamp', 'table lamp', 'pendant light',
            'floor lamp', 'mirror', 'round mirror', 'vase', 'candle', 'scented candle', 'cushion', 'throw', 'wool throw',
            'rug', 'linen', 'velvet', 'brass', 'gift', 'sale', 'sofaa', 'armchiar', 'mirrow', 'cusion',
        ],
    ];

    private const float RETURNING_VISITOR_SHARE = 0.3;

    /** Among the visitors who stay, the ones who came to buy. */
    private const float BUYER_SHARE = 0.12;

    private const float MEAN_PAGES_AFTER_LANDING = 4;
    private const int MAX_PAGES_AFTER_LANDING = 30;

    public function __construct(
        private int $number,
        private Browsers $browsers,
        private KnownUrls $knownUrls,
        private UserAgents $userAgents,
        private Journal $journal,
        private Random $random,
    ) {
    }

    /**
     * @return \Generator<int, float, mixed, void> The seconds to wait before each next step
     */
    public function run(): \Generator
    {
        $locale = $this->random->weighted(self::LOCALES);
        $device = Device::from($this->random->weighted(self::DEVICES));
        $agent = $this->userAgents->forHuman($device);
        $visitor = new Visitor('human', $this->number, $agent->userAgent, $this->random->weighted(self::ACCEPT_LANGUAGES[$locale]));
        $isReturning = $this->random->chance(self::RETURNING_VISITOR_SHARE);
        $browser = $isReturning ? $this->browsers->forReturningVisitor($visitor) : $this->browsers->forFirstVisit($visitor);
        $source = $this->random->weighted(self::SOURCES);
        [$path, $referer] = $this->landing($source, $locale);

        $this->journal->visitStarted($visitor, implode(', ', [$source, $locale, $device->value, $isReturning ? 'returning' : 'first visit']));
        $page = $this->open($browser, $device, $path, $referer);
        if ($agent->requestsAppleTouchIcons && $this->random->chance(0.3)) {
            $browser->load(['/apple-touch-icon-precomposed.png', '/apple-touch-icon.png', '/favicon.ico'], null);
        }

        // The language chooser, when nothing redirected the visitor to their language.
        if ('/' === $page->path && !$page->isError()) {
            yield $this->random->float(2, 6);
            $page = $this->open($browser, $device, "/{$locale}/", $page->url());
        }

        if ($page->isError()) {
            if ($this->random->chance(0.5)) {
                return;
            }
            yield $this->random->float(4, 15);
            if ($this->random->chance(0.7)) {
                $page = $this->open($browser, $device, "/{$locale}/", $page->url());
            } else {
                $page = yield from $this->search($browser, $device, $page, $locale);
            }
        } elseif ($this->random->chance(self::BOUNCE_RATES[$source])) {
            return;
        }

        $wantsToBuy = $this->random->chance(self::BUYER_SHARE);
        $cartSize = 0;
        $pagesLeft = min(self::MAX_PAGES_AFTER_LANDING, (int) ceil($this->random->exponential(self::MEAN_PAGES_AFTER_LANDING)));
        for (; null !== $page && $pagesLeft > 0; --$pagesLeft) {
            yield $this->readingTime($page);

            if ($page->canAddToCart() && $this->random->chance($wantsToBuy ? 0.5 : 0.04)) {
                // The cart lives in the browser: adding a product sends no request.
                ++$cartSize;
                yield $this->random->float(2, 10);
            }

            $checkoutLink = $page->checkoutLink();
            $cartLink = $page->cartLink();
            if ($cartSize > 0 && null !== $checkoutLink) {
                if ($wantsToBuy && $this->random->chance(0.7)) {
                    yield from $this->checkout($browser, $device, $visitor, $page, $checkoutLink);

                    return;
                }
            } elseif ($cartSize > 0 && null !== $cartLink && $this->random->chance($wantsToBuy ? 0.6 : 0.15)) {
                $page = $this->open($browser, $device, $cartLink, $page->url());

                continue;
            }

            $page = yield from $this->browse($browser, $device, $page, $locale);
        }
    }

    /**
     * @return array{string, ?string} The path of the first page, and the referer
     */
    private function landing(string $source, string $locale): array
    {
        return match ($source) {
            'google' => [$this->knownUrls->popularPathIn($this->random, $locale), $this->random->pick(self::GOOGLE[$locale])],
            'direct' => [$this->directPath($locale), null],
            'social' => $this->socialLanding($locale),
            'search-engine' => [$this->knownUrls->popularPathIn($this->random, $locale), $this->random->pick(self::SEARCH_ENGINES)],
            'old-link' => [
                $this->knownUrls->stalePath($this->random) ?? $this->knownUrls->popularPathIn($this->random, $locale),
                $this->random->pick([null, null, self::GOOGLE[$locale][0], 'https://www.pinterest.fr/']),
            ],
            'google-ads' => [
                $this->withQuery($this->knownUrls->popularProductPathIn($this->random, $locale), ['gclid' => 'Cj0KCQiA' . $this->random->token(80) . 'BwE']),
                self::GOOGLE[$locale][0],
            ],
            'newsletter' => [
                $this->withQuery(
                    $this->random->chance(0.7) ? $this->knownUrls->popularProductPathIn($this->random, $locale) : "/{$locale}/",
                    ['utm_source' => 'newsletter', 'utm_medium' => 'email', 'utm_campaign' => 'newsletter-' . date('Y-m')],
                ),
                // Webmail sends a referer, mail applications do not.
                $this->random->chance(0.15) ? 'https://mail.google.com/' : null,
            ],
            'ai-assistant' => $this->assistantLanding($locale),
        };
    }

    private function directPath(string $locale): string
    {
        return match ($this->random->weighted(['root' => 45, 'home' => 30, 'bookmark' => 25])) {
            // redirection.io can redirect visitors to their language.
            'root' => '/',
            'home' => "/{$locale}/",
            'bookmark' => $this->knownUrls->popularPathIn($this->random, $locale),
        };
    }

    /**
     * @return array{string, string}
     */
    private function socialLanding(string $locale): array
    {
        $path = $this->knownUrls->popularProductPathIn($this->random, $locale);

        return match ($this->random->weighted(['instagram' => 40, 'pinterest' => 35, 'facebook' => 25])) {
            'instagram' => [
                $this->withQuery($path, ['utm_source' => 'instagram', 'utm_medium' => 'social', 'utm_campaign' => 'link-in-bio']),
                'https://l.instagram.com/',
            ],
            'pinterest' => [$path, 'fr' === $locale ? 'https://www.pinterest.fr/' : 'https://www.pinterest.com/'],
            'facebook' => [
                $this->withQuery($path, ['fbclid' => 'IwZXh0bgNhZW0CMTEAAR' . $this->random->token(40) . '_aem_' . $this->random->token(22)]),
                $this->random->pick(['https://l.facebook.com/', 'https://m.facebook.com/']),
            ],
        };
    }

    /**
     * @return array{string, string}
     */
    private function assistantLanding(string $locale): array
    {
        $path = $this->knownUrls->popularProductPathIn($this->random, $locale);

        return match ($this->random->weighted(['chatgpt' => 60, 'perplexity' => 20, 'gemini' => 12, 'claude' => 8])) {
            // ChatGPT tags the links it gives.
            'chatgpt' => [$this->withQuery($path, ['utm_source' => 'chatgpt.com']), 'https://chatgpt.com/'],
            'perplexity' => [$path, 'https://www.perplexity.ai/'],
            'gemini' => [$path, 'https://gemini.google.com/'],
            'claude' => [$path, 'https://claude.ai/'],
        };
    }

    /**
     * Follows one of the links of the page, or leaves.
     *
     * @return \Generator<int, float, mixed, ?Page>
     */
    private function browse(Browser $browser, Device $device, Page $page, string $locale): \Generator
    {
        $locale = $page->locale() ?? $locale;
        $products = $page->productLinks();
        $navigation = $page->navigationLinks();
        $pagination = $page->paginationLinks();
        $footer = $page->footerLinks();
        $filters = $page->filterForm();
        $otherLanguage = $page->otherLanguageLink();
        $home = "/{$locale}/";

        $action = $this->random->weighted(array_filter([
            'product' => [] === $products ? 0 : 50,
            'navigation' => [] === $navigation ? 0 : 20,
            'filters' => null === $filters ? 0 : 8,
            'pagination' => [] === $pagination ? 0 : 6,
            'search' => null === $page->searchAction() ? 0 : 5,
            'home' => $home === $page->path ? 0 : 4,
            'footer' => [] === $footer ? 0 : 3,
            'other-language' => null === $otherLanguage ? 0 : 2,
            'leave' => 10,
        ]));
        if ('search' === $action) {
            return yield from $this->search($browser, $device, $page, $locale);
        }

        $path = match ($action) {
            'product' => $this->pickByPosition($products),
            'navigation' => $this->random->pick($navigation),
            'filters' => $filters->pathOfRandomFilters($this->random),
            'pagination' => $this->random->pick($pagination),
            'home' => $home,
            'footer' => $this->random->pick($footer),
            'other-language' => $otherLanguage,
            'leave' => null,
        };

        return null === $path ? null : $this->open($browser, $device, $path, $page->url());
    }

    /**
     * Searches the catalog, then opens one of the results, or leaves when none suits.
     *
     * @return \Generator<int, float, mixed, ?Page>
     */
    private function search(Browser $browser, Device $device, Page $page, string $locale): \Generator
    {
        $action = $page->searchAction();
        if (null === $action) {
            return null;
        }

        // Typing.
        yield $this->random->float(3, 12);
        $results = $this->open($browser, $device, $action . '?' . http_build_query(['q' => $this->random->pick(self::SEARCH_TERMS[$locale])]), $page->url());
        $this->loadSearchIndex($browser, $results, $locale);

        yield $this->random->logNormal(12, 0.6);
        if ($this->random->chance(0.3)) {
            return null;
        }

        // The results are displayed by a script: the visitor opens one of the products the index returned.
        return $this->open($browser, $device, $this->knownUrls->popularProductPathIn($this->random, $locale), $results->url());
    }

    /**
     * Pagefind loads its index from the results page (see src/components/pages/SearchPage.astro).
     */
    private function loadSearchIndex(Browser $browser, Page $results, string $locale): void
    {
        $browser->load(['/pagefind/pagefind.js'], $results->url());
        $entry = $browser->fetch('/pagefind/pagefind-entry.json', $results->url());
        $language = null === $entry ? null : (json_decode($entry, true)['languages'][$locale] ?? null);
        if (null === $language) {
            return;
        }

        $browser->load(["/pagefind/pagefind.{$language['hash']}.pf_meta", "/pagefind/wasm.{$language['wasm']}.pagefind"], $results->url());
    }

    /**
     * @return \Generator<int, float, mixed, void>
     */
    private function checkout(Browser $browser, Device $device, Visitor $visitor, Page $cart, string $checkoutLink): \Generator
    {
        $checkout = $this->open($browser, $device, $checkoutLink, $cart->url());

        // Filling in the delivery and payment details.
        yield $this->random->logNormal(90, 0.5);

        $confirmationPath = $checkout->orderConfirmationPath();
        if (null === $confirmationPath || !$this->random->chance(0.65)) {
            return;
        }

        // The order number is drawn by the browser (see src/components/pages/CheckoutPage.astro).
        $this->open($browser, $device, $confirmationPath . '?order=' . $this->random->int(100000, 999999), $checkout->url());
        $this->journal->order($visitor);
    }

    private function open(Browser $browser, Device $device, string $path, ?string $referer): Page
    {
        $page = $browser->navigate($path, $referer);
        $browser->load($page->assetPaths($device, $device->lazyImagesSeen($this->random)), $page->url());

        return $page;
    }

    private function readingTime(Page $page): float
    {
        $median = match (true) {
            $page->isError() => 6,
            $page->canAddToCart() => 35,
            default => 20,
        };

        return min(600, $this->random->logNormal($median, 0.8));
    }

    /**
     * Visitors click the first products more than the next ones.
     *
     * @param non-empty-list<string> $paths
     */
    private function pickByPosition(array $paths): string
    {
        $weights = array_map(static fn (int $position): float => 1 / sqrt($position + 1), array_keys($paths));

        return $paths[$this->random->weighted($weights)];
    }

    /**
     * @param array<string, string> $parameters
     */
    private function withQuery(string $path, array $parameters): string
    {
        return $path . (str_contains($path, '?') ? '&' : '?') . http_build_query($parameters);
    }
}
