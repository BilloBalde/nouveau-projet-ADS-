<?php

namespace App\Support;

/**
 * Mode de vente de l'installation : la caisse, ou l'inventaire.
 *
 * Un seul réglage (config/sales.php) décide si les ventes se saisissent une
 * par une au point de vente, ou si le chiffre d'affaires se déduit du stock
 * compté.
 *
 * Les deux ne cohabitent pas sur une même installation, pour qu'il n'y ait
 * qu'une seule source de chiffre d'affaires. Il n'y a pas de risque de double
 * comptage : une vente saisie sort du stock enregistré, elle ne se retrouve
 * donc pas dans l'écart de comptage. Le problème est ailleurs — le chiffre
 * d'affaires d'un mois serait réparti entre deux écrans que rien ne totalise,
 * et le gérant qui lit le rapport d'inventaire croirait avoir vendu moins
 * qu'en réalité.
 */
class ModeDeVente
{
    public const CAISSE = 'caisse';

    public const INVENTAIRE = 'inventaire';

    public static function actuel(): string
    {
        return config('sales.mode') === self::INVENTAIRE ? self::INVENTAIRE : self::CAISSE;
    }

    /** Les ventes se saisissent une par une (caisse, facturation, devis). */
    public static function parCaisse(): bool
    {
        return self::actuel() === self::CAISSE;
    }

    /** Le chiffre d'affaires se déduit des comptages de stock. */
    public static function parInventaire(): bool
    {
        return self::actuel() === self::INVENTAIRE;
    }
}
