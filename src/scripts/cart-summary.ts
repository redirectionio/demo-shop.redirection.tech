import { readCart, removeFromCart } from './cart';
import type { ClientProduct } from './product-catalog';
import { shippingCostFor } from '../lib/shipping';

export type CartSummaryLine = { product: ClientProduct; quantity: number; totalInCents: number };

export type CartSummary = {
    lines: CartSummaryLine[];
    subtotalInCents: number;
    shippingInCents: number;
    totalInCents: number;
};

export function summarizeCart(products: Map<string, ClientProduct>): CartSummary {
    const lines: CartSummaryLine[] = [];
    for (const { productId, quantity } of readCart()) {
        const product = products.get(productId);
        // The product may have been removed from the catalog since it was added to the cart.
        if (product === undefined || !product.purchasable) {
            console.warn(`The product "${productId}" is no longer available, it has been removed from the cart.`);
            removeFromCart(productId);
            continue;
        }
        lines.push({ product, quantity, totalInCents: product.priceInCents * quantity });
    }

    const subtotalInCents = lines.reduce((sum, line) => sum + line.totalInCents, 0);
    const shippingInCents = lines.length > 0 ? shippingCostFor(subtotalInCents) : 0;

    return { lines, subtotalInCents, shippingInCents, totalInCents: subtotalInCents + shippingInCents };
}
