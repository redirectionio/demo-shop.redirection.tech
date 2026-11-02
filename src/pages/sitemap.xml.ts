import type { APIRoute } from 'astro';
import { locales } from '../lib/i18n';
import { loadRoutes } from '../lib/routing';

export const GET: APIRoute = async ({ site }) => {
    const absolute = (url: string) => new URL(url, site).href;
    const urls = (await loadRoutes())
        .filter((route) => route.indexable)
        .map((route) => {
            const alternates = locales.map(
                (locale) => `<xhtml:link rel="alternate" hreflang="${locale}" href="${absolute(route.alternateUrls[locale])}"/>`,
            );

            return `<url><loc>${absolute(route.url)}</loc>${alternates.join('')}</url>`;
        });

    return new Response(
        `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">\n${urls.join('\n')}\n</urlset>\n`,
        { headers: { 'Content-Type': 'application/xml' } },
    );
};
