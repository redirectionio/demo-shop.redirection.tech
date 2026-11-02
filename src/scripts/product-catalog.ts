// The cart and checkout pages are rendered in the browser, from the products
// embedded in the page by src/components/ProductCatalogData.astro.

export type ClientProduct = {
    id: string;
    name: string;
    url: string;
    priceInCents: number;
    photoUrl: string;
    purchasable: boolean;
};

export function readProductCatalog(): Map<string, ClientProduct> {
    const products: ClientProduct[] = JSON.parse(document.getElementById('product-catalog')!.textContent!);

    return new Map(products.map((product) => [product.id, product]));
}
