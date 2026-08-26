<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paliers de remise par quantité
    |--------------------------------------------------------------------------
    |
    | Contrôle les tarifs de gros définis dans App\Support\PricingTiers : le
    | tableau « Tarifs de gros » sur les fiches produit ET la baisse réelle du
    | prix au panier et à la commande.
    |
    | Désactivé par défaut : le projet Coleah Bandeya & Fils vend au prix
    | affiché, quelle que soit la quantité. Le code des paliers reste en place,
    | il suffit de passer PRICING_TIERS_ENABLED=true pour le réactiver.
    |
    */

    'tiers_enabled' => env('PRICING_TIERS_ENABLED', false),

];
