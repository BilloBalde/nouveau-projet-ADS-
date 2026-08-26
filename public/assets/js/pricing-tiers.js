/* Paliers de remise automatique — doit rester en phase avec App\Support\PricingTiers */
const PRICING_TIERS = [
    { qty: 1, percent: 0 },
    { qty: 3, percent: 5 },
    { qty: 5, percent: 10 },
    { qty: 10, percent: 15 },
];

/* Posé par Blade avant le chargement de ce script, depuis config('pricing.tiers_enabled').
   Absent (ancienne page en cache, script chargé seul) : on considère les paliers inactifs,
   c'est le comportement attendu sur cette installation. */
function pricingTiersEnabled() {
    return window.PRICING_TIERS_ENABLED === true;
}

function pricingTiersFor(basePrice) {
    return PRICING_TIERS.map(t => ({
        qty: t.qty,
        percent: t.percent,
        price: Math.round(basePrice * (1 - t.percent / 100)),
    }));
}

function displayTiersFor(basePrice) {
    if (!pricingTiersEnabled()) return [];
    return pricingTiersFor(basePrice).filter(t => t.percent > 0);
}

function unitPriceForQuantity(basePrice, quantity) {
    if (!pricingTiersEnabled()) return Math.round(basePrice);

    let applicable = PRICING_TIERS[0];
    for (const tier of PRICING_TIERS) {
        if (quantity >= tier.qty) applicable = tier;
    }
    return Math.round(basePrice * (1 - applicable.percent / 100));
}
