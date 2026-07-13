/* Paliers de remise automatique — doit rester en phase avec App\Support\PricingTiers */
const PRICING_TIERS = [
    { qty: 1, percent: 0 },
    { qty: 3, percent: 5 },
    { qty: 5, percent: 10 },
    { qty: 10, percent: 15 },
];

function pricingTiersFor(basePrice) {
    return PRICING_TIERS.map(t => ({
        qty: t.qty,
        percent: t.percent,
        price: Math.round(basePrice * (1 - t.percent / 100)),
    }));
}

function displayTiersFor(basePrice) {
    return pricingTiersFor(basePrice).filter(t => t.percent > 0);
}

function unitPriceForQuantity(basePrice, quantity) {
    let applicable = PRICING_TIERS[0];
    for (const tier of PRICING_TIERS) {
        if (quantity >= tier.qty) applicable = tier;
    }
    return Math.round(basePrice * (1 - applicable.percent / 100));
}
