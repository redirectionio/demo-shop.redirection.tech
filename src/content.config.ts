import { defineCollection, reference } from 'astro:content';
import { file, glob } from 'astro/loaders';
import { z } from 'astro/zod';
import { parse } from 'yaml';
import { colorIds, materialIds } from './lib/facets';

const localizedString = z.object({
    en: z.string(),
    fr: z.string(),
});

const categories = defineCollection({
    loader: file('src/catalog/categories.yaml', {
        // Astro doesn't keep the order of the entries: categories.yaml is in display order.
        parser: (text) => (parse(text) as object[]).map((category, position) => ({ ...category, position })),
    }),
    schema: ({ image }) =>
        z.object({
            description: localizedString,
            name: localizedString,
            parent: reference('categories').optional(),
            photo: image().optional(),
            position: z.number(),
        }),
});

const products = defineCollection({
    loader: glob({ base: './src/catalog/products', pattern: '*.yaml' }),
    schema: ({ image }) =>
        z.object({
            category: reference('categories'),
            colors: z.array(z.enum(colorIds())).min(1),
            description: localizedString,
            dimensionsInCm: z
                .object({
                    depth: z.number().positive(),
                    height: z.number().positive(),
                    width: z.number().positive(),
                })
                .optional(),
            featured: z.boolean().default(false),
            materials: z.array(z.enum(materialIds())).min(1),
            name: localizedString,
            photos: z.array(image()).min(1),
            previousPriceInCents: z.number().int().positive().optional(),
            priceInCents: z.number().int().positive(),
            // A discontinued product has no page anymore: its URL returns a 404.
            status: z.enum(['available', 'discontinued', 'out-of-stock']).default('available'),
        }),
});

// One Markdown file per page and locale: `<page>/<locale>.md`.
const pages = defineCollection({
    loader: glob({ base: './src/catalog/pages', pattern: '*/*.md' }),
    schema: z.object({
        description: z.string(),
        title: z.string(),
    }),
});

export const collections = { categories, pages, products };
