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
