# demo-shop.redirection.tech

Maison 301 is a fictional home decor shop, used to demo redirection.io. The site is fully static (built with
[Astro](https://astro.build)), deployed on GitHub Pages, and served at https://demo-shop.redirection.tech through
redirection.io.

GitHub Pages can neither redirect nor add headers: everything of the kind is left to redirection.io.

## Tasks

```shell
castor site:dev      # development server (search only works with site:preview)
castor site:check    # type checking and catalog validation
castor site:build    # static site in dist/, with the Pagefind search index
castor site:preview  # build, then serve dist/
```

Every push on `main` is deployed by GitHub Actions (`.github/workflows/deploy.yml`).

## Catalog

Everything lives in `src/catalog/`:

- `categories.yaml`: categories, in display order;
- `products/*.yaml`: one file per product, the file name is its identifier;
- `pages/<page>/<locale>.md`: content pages (about, terms of sale…);
- `photos/`: CC0 or public domain photos, with their source in `photos.yaml`.

URLs are built from the names (`src/lib/routing.ts`): renaming a product or a category changes its URL, which makes
for a good redirection demo. A product with `status: discontinued` has no page anymore, so its URL returns a 404.

Filters and sorting are query string parameters (`?color=green&material=linen&sort=price-asc`), applied in the
browser.

## Traffic

So that the demos show a live site, `castor traffic:simulate` sends it the traffic of a small shop: about 300 people
a day (from search engines, newsletters, social networks, AI assistants…, who browse, search, filter, and sometimes
order), search engine and AI crawlers, SEO tools, link previews, vulnerability scanners and an uptime monitor. Humans
follow the hours of a French shop; bots crawl around the clock. The code lives in `traffic/src/`.

```shell
castor traffic:memorize                                           # record the pages of the live site
castor traffic:simulate --duration=55                             # visit the live site for 55 minutes
castor traffic:simulate --site=http://localhost:4321 --speed=100  # try it out on "castor site:preview"
```

The outside world keeps visiting the pages the site no longer has: renaming a product makes its old URL show up in
redirection.io, with traffic, until a redirection fixes it. `traffic:memorize` records every page the sitemap has
ever listed, with the day it left it, on the `traffic-state` branch: an orphan branch holding a single commit,
replaced on each change. `traffic/legacy-urls.txt` adds the URLs of the previous version of the shop, a PrestaShop.

The `Traffic` workflow runs both tasks every hour.
