import { type Catalog, type Category, type ContentPage, type Product, loadCatalog } from './catalog';
import { type Locale, type Localized, locales } from './i18n';

// Every URL of the site is built here, and nowhere else.

export const productsPerPage = 12;

const systemPageSlugs = {
    cart: { en: 'cart', fr: 'panier' },
    checkout: { en: 'checkout', fr: 'commande' },
    'order-confirmation': { en: 'checkout/confirmation', fr: 'commande/confirmation' },
    search: { en: 'search', fr: 'recherche' },
} as const satisfies Record<string, Localized>;

export type SystemPage = keyof typeof systemPageSlugs;

export function homeUrl(locale: Locale): string {
    return `/${locale}/`;
}

export function categoryUrl(category: Category, locale: Locale, pageNumber = 1): string {
    const slugs = category.lineage().map((ancestor) => ancestor.slug(locale));
    const pageSegment = pageNumber > 1 ? `page/${pageNumber}/` : '';

    return `/${locale}/${slugs.join('/')}/${pageSegment}`;
}

export function productUrl(product: Product, locale: Locale): string {
    return `${categoryUrl(product.category, locale)}${product.slug(locale)}/`;
}

export function contentPageUrl(page: ContentPage, locale: Locale): string {
    return `/${locale}/${page.slug(locale)}/`;
}

export function systemPageUrl(page: SystemPage, locale: Locale): string {
    return `/${locale}/${systemPageSlugs[page][locale]}/`;
}

type RouteTarget =
    | { kind: 'home' }
    | { kind: 'category'; category: Category; pageNumber: number; pageCount: number; products: Product[] }
    | { kind: 'product'; product: Product }
    | { kind: 'content-page'; page: ContentPage }
    | { kind: 'system-page'; page: SystemPage };

export type Route = RouteTarget & {
    locale: Locale;
    url: string;
    /** The URL of the same page in every locale, for the language switcher and hreflang. */
    alternateUrls: Localized;
    /** Whether search engines should index the page. */
    indexable: boolean;
};

export type RouteOf<Kind extends Route['kind']> = Extract<Route, { kind: Kind }>;

let routes: Promise<Route[]> | undefined;

export function loadRoutes(): Promise<Route[]> {
    routes ??= loadCatalog().then(buildRoutes);

    return routes;
}

function buildRoutes(catalog: Catalog): Route[] {
    const targets: { target: RouteTarget; urlFor: (locale: Locale) => string; indexable: boolean }[] = [
        { target: { kind: 'home' }, urlFor: homeUrl, indexable: true },
        ...Object.keys(systemPageSlugs).map((page) => ({
            target: { kind: 'system-page' as const, page: page as SystemPage },
            urlFor: (locale: Locale) => systemPageUrl(page as SystemPage, locale),
            indexable: false,
        })),
        ...catalog.allContentPages().map((page) => ({
            target: { kind: 'content-page' as const, page },
            urlFor: (locale: Locale) => contentPageUrl(page, locale),
            indexable: true,
        })),
        ...catalog.categories.flatMap((category) => {
            const products = catalog.productsIn(category);
            const pageCount = Math.max(1, Math.ceil(products.length / productsPerPage));

            return Array.from({ length: pageCount }, (_, index) => ({
                target: {
                    kind: 'category' as const,
                    category,
                    pageNumber: index + 1,
                    pageCount,
                    products: products.slice(index * productsPerPage, (index + 1) * productsPerPage),
                },
                urlFor: (locale: Locale) => categoryUrl(category, locale, index + 1),
                indexable: true,
            }));
        }),
        ...catalog.listedProducts().map((product) => ({
            target: { kind: 'product' as const, product },
            urlFor: (locale: Locale) => productUrl(product, locale),
            indexable: true,
        })),
    ];

    const builtRoutes = targets.flatMap(({ target, urlFor, indexable }) => {
        const alternateUrls = Object.fromEntries(locales.map((locale) => [locale, urlFor(locale)])) as Localized;

        return locales.map((locale): Route => ({ ...target, locale, url: alternateUrls[locale], alternateUrls, indexable }));
    });

    const seenUrls = new Set<string>();
    for (const route of builtRoutes) {
        if (seenUrls.has(route.url)) {
            throw new Error(`Two pages share the URL "${route.url}": rename one of them.`);
        }
        seenUrls.add(route.url);
    }

    return builtRoutes;
}
