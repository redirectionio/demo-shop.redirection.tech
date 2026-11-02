import type { Localized } from './i18n';

type Color = { hex: string; name: Localized };

export const colors = {
    beige: { hex: '#d8c7a8', name: { en: 'Beige', fr: 'Beige' } },
    black: { hex: '#1f1f1f', name: { en: 'Black', fr: 'Noir' } },
    blue: { hex: '#4a6a8a', name: { en: 'Blue', fr: 'Bleu' } },
    brown: { hex: '#7a5235', name: { en: 'Brown', fr: 'Marron' } },
    gold: { hex: '#c9a54c', name: { en: 'Gold', fr: 'Doré' } },
    green: { hex: '#5f7a5a', name: { en: 'Green', fr: 'Vert' } },
    grey: { hex: '#9a9a96', name: { en: 'Grey', fr: 'Gris' } },
    natural: { hex: '#c8a77c', name: { en: 'Natural wood', fr: 'Bois naturel' } },
    pink: { hex: '#d9a3a0', name: { en: 'Pink', fr: 'Rose' } },
    red: { hex: '#a63a32', name: { en: 'Red', fr: 'Rouge' } },
    white: { hex: '#f4f1ea', name: { en: 'White', fr: 'Blanc' } },
    yellow: { hex: '#d9b54a', name: { en: 'Yellow', fr: 'Jaune' } },
} as const satisfies Record<string, Color>;

export type ColorId = keyof typeof colors;

export const materials = {
    brass: { en: 'Brass', fr: 'Laiton' },
    ceramic: { en: 'Ceramic', fr: 'Céramique' },
    cotton: { en: 'Cotton', fr: 'Coton' },
    glass: { en: 'Glass', fr: 'Verre' },
    leather: { en: 'Leather', fr: 'Cuir' },
    linen: { en: 'Linen', fr: 'Lin' },
    metal: { en: 'Metal', fr: 'Métal' },
    paper: { en: 'Paper', fr: 'Papier' },
    polyester: { en: 'Polyester', fr: 'Polyester' },
    stoneware: { en: 'Stoneware', fr: 'Grès' },
    velvet: { en: 'Velvet', fr: 'Velours' },
    wax: { en: 'Wax', fr: 'Cire' },
    wood: { en: 'Wood', fr: 'Bois' },
    wool: { en: 'Wool', fr: 'Laine' },
} as const satisfies Record<string, Localized>;

export type MaterialId = keyof typeof materials;

export function colorIds(): [ColorId, ...ColorId[]] {
    return Object.keys(colors) as [ColorId, ...ColorId[]];
}

export function materialIds(): [MaterialId, ...MaterialId[]] {
    return Object.keys(materials) as [MaterialId, ...MaterialId[]];
}
