<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode de vente de l'installation
    |--------------------------------------------------------------------------
    |
    | « caisse » (par défaut) : chaque vente est saisie au point de vente, et
    | le chiffre d'affaires est la somme des ventes. C'est le fonctionnement
    | historique, celui des installations déjà livrées.
    |
    | « inventaire » : la boutique vend sans rien saisir, puis compte son stock
    | à intervalle régulier. Ce qui a disparu entre deux comptages, achats et
    | transferts déduits, a été vendu ; le chiffre d'affaires est cette
    | quantité multipliée par le prix de vente (voir InventaireService).
    |
    | La caisse et la saisie des ventes sont alors fermées. Non par risque de
    | double comptage — une vente saisie sort du stock enregistré, elle ne se
    | retrouve donc pas dans l'écart de comptage — mais pour qu'il n'y ait
    | qu'une seule source de chiffre d'affaires. Avec les deux ouvertes, le
    | chiffre d'affaires d'un mois serait réparti entre deux écrans que rien
    | ne totalise, et le gérant qui lit le rapport d'inventaire croirait avoir
    | vendu moins qu'en réalité.
    |
    | Attention en mode « inventaire » : le prix de vente est la SEULE source
    | du chiffre d'affaires. Les prix doivent être fixés à la main sur les
    | fiches produit, sinon un achat les recalcule (prix de revient + marge)
    | et le chiffre d'affaires de la période suit ce nouveau prix.
    | Voir config/pricing.php et products.price_manuel.
    |
    */

    'mode' => env('SALES_MODE', 'caisse'),

];
