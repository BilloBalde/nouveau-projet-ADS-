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

    /*
    |--------------------------------------------------------------------------
    | Arrondi du prix de vente conseillé
    |--------------------------------------------------------------------------
    |
    | Le franc guinéen n'a pas de centimes et les pièces ne circulent plus : un
    | prix conseillé de 86 450,67 FG ne peut pas être affiché en boutique, et
    | personne ne peut le rendre. Le prix est donc arrondi au palier suivant —
    | 100 FG par défaut, le plus petit billet en circulation — et toujours vers
    | le haut : arrondir vers le bas rognerait la marge demandée.
    |
    | Mettre 0 pour ne garder qu'un arrondi à l'unité.
    |
    */

    'rounding_step' => (float) env('PRICING_ROUNDING_STEP', 100),

];
