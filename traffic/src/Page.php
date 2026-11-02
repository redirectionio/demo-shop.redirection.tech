<?php

namespace Traffic;

/**
 * A page as a visitor sees it: where its links lead, what it lets them do, and what it needs to be displayed.
 *
 * Keep the selectors in sync with the components of src/components/.
 */
final readonly class Page
{
    private function __construct(
        public string $path,
        public int $status,
        private Site $site,
        private ?\Dom\HTMLDocument $document,
    ) {
    }

    public static function parse(Site $site, string $path, int $status, string $html): self
    {
        return new self($path, $status, $site, \Dom\HTMLDocument::createFromString($html, \LIBXML_NOERROR));
    }

    /**
     * A response that is not an HTML page, or a redirection to another site.
     */
    public static function withoutDocument(Site $site, string $path, int $status): self
    {
        return new self($path, $status, $site, null);
    }

    public function url(): string
    {
        return $this->site->url($this->path);
    }

    public function isError(): bool
    {
        return $this->status >= 400;
    }

    public function locale(): ?string
    {
        $locale = $this->document?->documentElement?->getAttribute('lang');

        return '' === $locale ? null : $locale;
    }

    /**
     * @return list<string>
     */
    public function productLinks(): array
    {
        // The parser leaves out the content of <template>, which holds the products of the client-side filters.
        return $this->links('[data-product-card] a[href]');
    }

    /**
     * @return list<string>
     */
    public function navigationLinks(): array
    {
        return $this->links('header nav a[href], main nav:not([data-pagination]) a[href]');
    }

    /**
     * @return list<string>
     */
    public function paginationLinks(): array
    {
        return $this->links('[data-pagination] a[href]');
    }

    /**
     * @return list<string>
     */
    public function footerLinks(): array
    {
        return $this->links('footer a[href]');
    }

    public function otherLanguageLink(): ?string
    {
        return $this->links('header a[hreflang][href]')[0] ?? null;
    }

    public function cartLink(): ?string
    {
        $link = $this->document?->querySelector('[data-cart-count]')?->closest('a[href]');

        return null === $link ? null : $this->resolve($link->getAttribute('href'));
    }

    public function checkoutLink(): ?string
    {
        return $this->links('[data-cart-content] a[href]')[0] ?? null;
    }

    public function searchAction(): ?string
    {
        $action = $this->document?->querySelector('form[role="search"]')?->getAttribute('action');

        return null === $action ? null : $this->resolve($action);
    }

    public function orderConfirmationPath(): ?string
    {
        $url = $this->document?->querySelector('form[data-checkout]')?->getAttribute('data-confirmation-url');

        return null === $url ? null : $this->resolve($url);
    }

    public function canAddToCart(): bool
    {
        return null !== $this->document?->querySelector('form[data-add-to-cart] button:not([disabled])');
    }

    public function filterForm(): ?FilterForm
    {
        $form = $this->document?->querySelector('form[data-filters][action]');
        if (null === $form) {
            return null;
        }

        $choices = [];
        foreach ($form->querySelectorAll('input[type="checkbox"][name][value]') as $checkbox) {
            $choices[$checkbox->getAttribute('name')][] = $checkbox->getAttribute('value');
        }
        $sortOrders = [];
        foreach ($form->querySelectorAll('select[name="sort"] option[value]') as $option) {
            $sortOrders[] = $option->getAttribute('value');
        }

        $action = $this->resolve($form->getAttribute('action'));
        if (null === $action || [] === $choices) {
            return null;
        }

        // The first sort order is the default one, which the page leaves out of the URL.
        return new FilterForm($action, $choices, \array_slice($sortOrders, 1));
    }

    /**
     * What a browser loads to display the page: style sheets, scripts, fonts, icon and the images on screen.
     *
     * @return list<string>
     */
    public function assetPaths(Device $device, int $lazyImagesSeen): array
    {
        if (null === $this->document) {
            return [];
        }

        $urls = [];
        foreach ($this->document->querySelectorAll('link[rel="stylesheet"][href], link[rel="preload"][href], link[rel="modulepreload"][href], link[rel="icon"][href], script[src]') as $element) {
            $urls[] = $element->getAttribute('href') ?? $element->getAttribute('src');
        }
        foreach ($this->document->querySelectorAll('img[src]') as $image) {
            if ('lazy' === $image->getAttribute('loading') && $lazyImagesSeen-- <= 0) {
                continue;
            }
            $urls[] = $this->imageSource($image, $device);
        }

        return array_values(array_unique(array_filter(array_map($this->resolve(...), $urls))));
    }

    /**
     * Browsers pick the image of the "srcset" that suits the screen: phones and tablets have dense screens, and
     * desktops are wide but often less dense.
     */
    private function imageSource(\Dom\Element $image, Device $device): string
    {
        $candidates = [];
        foreach (explode(',', $image->getAttribute('srcset') ?? '') as $candidate) {
            $parts = preg_split('/\s+/', trim($candidate));
            if ('' !== $parts[0]) {
                $candidates[(int) ($parts[1] ?? 0)] = $parts[0];
            }
        }
        if ([] === $candidates) {
            return $image->getAttribute('src');
        }

        ksort($candidates);
        $candidates = array_values($candidates);
        $index = Device::Desktop === $device ? \count($candidates) - 2 : \count($candidates) - 1;

        return $candidates[max(0, $index)];
    }

    /**
     * @return list<string>
     */
    private function links(string $selector): array
    {
        if (null === $this->document) {
            return [];
        }

        $paths = [];
        foreach ($this->document->querySelectorAll($selector) as $link) {
            $paths[] = $this->resolve($link->getAttribute('href'));
        }

        return array_values(array_unique(array_filter($paths)));
    }

    private function resolve(string $href): ?string
    {
        return $this->site->resolve($href, $this->path);
    }
}
