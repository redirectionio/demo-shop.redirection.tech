export function slugify(text: string): string {
    return text
        .toLowerCase()
        .replaceAll('œ', 'oe')
        .replaceAll('æ', 'ae')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}
