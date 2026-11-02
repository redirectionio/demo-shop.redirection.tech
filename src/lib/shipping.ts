// Keep in sync with the "reassurance.delivery" messages in src/lib/i18n.ts.
const freeShippingThresholdInCents = 100_00;
const shippingCostInCents = 9_90;

export function shippingCostFor(subtotalInCents: number): number {
    return subtotalInCents >= freeShippingThresholdInCents ? 0 : shippingCostInCents;
}
