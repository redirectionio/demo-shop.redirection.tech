import type { ImageMetadata } from 'astro';
import { type CollectionEntry, getCollection } from 'astro:content';
import type { ColorId, MaterialId } from './facets';
import { type Locale, type Localized, locales } from './i18n';
import { slugify } from './slugify';

export class Category {
    readonly children: Category[] = [];

    constructor(
        readonly id: string,
        readonly name: Localized,
        readonly description: Localized,
        readonly photo: ImageMetadata | null,
        readonly parent: Category | null,
    ) {}

    slug(locale: Locale): string {
        return slugify(this.name[locale]);
    }

    /** From the top-level category down to this one. */
    lineage(): Category[] {
        return this.parent === null ? [this] : [...this.parent.lineage(), this];
    }

    topLevel(): Category {
        return this.lineage()[0];
    }

    contains(category: Category): boolean {
        return category.lineage().includes(this);
    }
}

export type ProductStatus = CollectionEntry<'products'>['data']['status'];

export class Product {
    constructor(
        readonly id: string,
        readonly category: Category,
        readonly name: Localized,
        readonly description: Localized,
        readonly priceInCents: number,
        readonly previousPriceInCents: number | null,
        readonly colors: ColorId[],
        readonly materials: MaterialId[],
        readonly dimensionsInCm: { depth: number; height: number; width: number } | null,
        readonly photos: [ImageMetadata, ...ImageMetadata[]],
        readonly status: ProductStatus,
        readonly featured: boolean,
    ) {}

    slug(locale: Locale): string {
        return slugify(this.name[locale]);
    }

    isListed(): boolean {
        return this.status !== 'discontinued';
    }

    isPurchasable(): boolean {
        return this.status === 'available';
    }

    isOnSale(): boolean {
        return this.previousPriceInCents !== null;
    }
}

export class ContentPage {
    constructor(
        readonly id: string,
        readonly entries: Localized<CollectionEntry<'pages'>>,
    ) {}

    title(locale: Locale): string {
        return this.entries[locale].data.title;
    }

    slug(locale: Locale): string {
        return slugify(this.title(locale));
    }
}

export class Catalog {
    constructor(
        readonly categories: Category[],
        private readonly products: Product[],
        private readonly contentPages: Map<string, ContentPage>,
    ) {}

    topCategories(): Category[] {
        return this.categories.filter((category) => category.parent === null);
    }

    /** Products with a page, discontinued ones excluded. */
    listedProducts(): Product[] {
        return this.products.filter((product) => product.isListed());
    }

    productsIn(category: Category): Product[] {
        return this.listedProducts().filter((product) => category.contains(product.category));
    }

    featuredProducts(): Product[] {
        return this.listedProducts().filter((product) => product.featured);
    }

    relatedProducts(product: Product, limit: number): Product[] {
        return this.productsIn(product.category)
            .filter((other) => other !== product)
            .slice(0, limit);
    }

    allContentPages(): ContentPage[] {
        return [...this.contentPages.values()];
    }

    contentPage(id: string): ContentPage {
        const page = this.contentPages.get(id);
        if (page === undefined) {
            throw new Error(`The content page "${id}" does not exist.`);
        }

        return page;
    }
}

let catalog: Promise<Catalog> | undefined;

export function loadCatalog(): Promise<Catalog> {
    catalog ??= buildCatalog();

    return catalog;
}

async function buildCatalog(): Promise<Catalog> {
    const categoryEntries = (await getCollection('categories')).sort((a, b) => a.data.position - b.data.position);
    const categoriesById = new Map<string, Category>();
    for (const entry of categoryEntries) {
        // Parents are declared before their children in categories.yaml.
        const parent = entry.data.parent === undefined ? null : categoriesById.get(entry.data.parent.id);
        if (parent === undefined) {
            throw new Error(`The category "${entry.id}" must be declared after its parent "${entry.data.parent?.id}".`);
        }

        const category = new Category(entry.id, entry.data.name, entry.data.description, entry.data.photo ?? null, parent);
        parent?.children.push(category);
        categoriesById.set(entry.id, category);
    }
    const categories = [...categoriesById.values()];

    const products = (await getCollection('products')).map((entry) => {
        const [firstPhoto, ...otherPhotos] = entry.data.photos;

        return new Product(
            entry.id,
            // Astro already checked that the referenced category exists.
            categoriesById.get(entry.data.category.id)!,
            entry.data.name,
            entry.data.description,
            entry.data.priceInCents,
            entry.data.previousPriceInCents ?? null,
            entry.data.colors,
            entry.data.materials,
            entry.data.dimensionsInCm ?? null,
            [firstPhoto, ...otherPhotos],
            entry.data.status,
            entry.data.featured,
        );
    });
    // Products are listed by category order, then by name.
    products.sort(
        (a, b) => categories.indexOf(a.category) - categories.indexOf(b.category) || a.name.en.localeCompare(b.name.en),
    );

    return new Catalog(categories, products, groupContentPages(await getCollection('pages')));
}

function groupContentPages(entries: CollectionEntry<'pages'>[]): Map<string, ContentPage> {
    const entriesByPage = new Map<string, Partial<Localized<CollectionEntry<'pages'>>>>();
    for (const entry of entries) {
        const [pageId, locale] = entry.id.split('/');
        if (!(locales as readonly string[]).includes(locale)) {
            throw new Error(`The content page file "${entry.filePath}" is not named after a supported locale.`);
        }
        entriesByPage.set(pageId, { ...entriesByPage.get(pageId), [locale]: entry });
    }

    const pages = new Map<string, ContentPage>();
    for (const [pageId, localizedEntries] of entriesByPage) {
        const missingLocales = locales.filter((locale) => localizedEntries[locale] === undefined);
        if (missingLocales.length > 0) {
            throw new Error(`The content page "${pageId}" has no translation for: ${missingLocales.join(', ')}.`);
        }
        pages.set(pageId, new ContentPage(pageId, localizedEntries as Localized<CollectionEntry<'pages'>>));
    }

    return pages;
}
