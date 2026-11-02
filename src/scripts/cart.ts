// The cart only lives in the browser: the site has no backend.

export type CartLine = { productId: string; quantity: number };

const storageKey = 'maison301.cart';
const changeEvent = 'cart:change';

export function readCart(): CartLine[] {
    const raw = localStorage.getItem(storageKey);
    if (raw === null) {
        return [];
    }

    try {
        const lines: unknown = JSON.parse(raw);
        if (Array.isArray(lines) && lines.every(isCartLine)) {
            return lines;
        }
    } catch (error) {
        if (!(error instanceof SyntaxError)) {
            throw error;
        }
    }

    console.warn('The stored cart is corrupted, it has been emptied.', raw);
    localStorage.removeItem(storageKey);

    return [];
}

export function addToCart(productId: string, quantity: number): void {
    const lines = readCart();
    const line = lines.find((candidate) => candidate.productId === productId);
    if (line === undefined) {
        lines.push({ productId, quantity });
    } else {
        line.quantity += quantity;
    }
    writeCart(lines);
}

export function changeQuantity(productId: string, quantity: number): void {
    writeCart(
        readCart()
            .map((line) => (line.productId === productId ? { productId, quantity } : line))
            .filter((line) => line.quantity > 0),
    );
}

export function removeFromCart(productId: string): void {
    writeCart(readCart().filter((line) => line.productId !== productId));
}

export function emptyCart(): void {
    writeCart([]);
}

export function cartItemCount(): number {
    return readCart().reduce((count, line) => count + line.quantity, 0);
}

export function onCartChange(listener: () => void): void {
    window.addEventListener(changeEvent, listener);
    // Keeps the other tabs in sync.
    window.addEventListener('storage', (event) => {
        if (event.key === storageKey) {
            listener();
        }
    });
}

function writeCart(lines: CartLine[]): void {
    localStorage.setItem(storageKey, JSON.stringify(lines));
    window.dispatchEvent(new Event(changeEvent));
}

function isCartLine(value: unknown): value is CartLine {
    return (
        typeof value === 'object' &&
        value !== null &&
        typeof (value as CartLine).productId === 'string' &&
        Number.isInteger((value as CartLine).quantity) &&
        (value as CartLine).quantity > 0
    );
}
