// @ts-check
import tailwindcss from '@tailwindcss/vite';
import { defineConfig, fontProviders } from 'astro/config';

export default defineConfig({
    // The site is served by redirection.io, which proxies GitHub Pages.
    site: 'https://demo-shop.redirection.tech',
    trailingSlash: 'always',
    build: {
        format: 'directory',
    },
    fonts: [
        {
            cssVariable: '--font-fraunces',
            name: 'Fraunces',
            provider: fontProviders.fontsource(),
            weights: ['300 700'],
        },
        {
            cssVariable: '--font-inter',
            name: 'Inter',
            provider: fontProviders.fontsource(),
            weights: ['300 700'],
        },
    ],
    vite: {
        plugins: [tailwindcss()],
    },
});
