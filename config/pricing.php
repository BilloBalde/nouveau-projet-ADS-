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
    | Désactivé par défaut : l'installation d'origine vend au prix
    | affiché, quelle que soit la quantité. Le code des paliers reste en place,
    | il suffit de passer PRICING_TIERS_ENABLED=true pour le réactiver.
    |
    */

    'tiers_enabled' => env('PRICING_TIERS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Marge appliquée au prix de revient
    |--------------------------------------------------------------------------
    |
    | Pourcentage ajouté au prix de revient pour proposer le prix de vente,
    | à la création d'un produit comme à chaque achat qui le réapprovisionne.
    |
    | Cette valeur était écrite en dur à trois endroits (la fiche produit, le
    | calcul du prix de revient d'un achat, et le libellé du formulaire), ce
    | qui rendait tout changement de politique commerciale impossible sans
    | toucher au code. Un prix de vente fixé à la main sur une fiche produit
    | n'est plus recalculé : voir products.price_manuel.
    |
    */

    'sale_margin_percent' => (float) env('PRICING_SALE_MARGIN', 30),

];
