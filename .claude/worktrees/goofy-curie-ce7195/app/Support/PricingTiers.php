<?php

namespace App\Support;

class PricingTiers
{
    /**
     * Paliers de remise automatique par quantité.
     * qty = seuil minimum, percent = remise appliquée au prix unitaire.
     */
    public const TIERS = [
        ['qty' => 1, 'percent' => 0],
        ['qty' => 3, 'percent' => 5],
        ['qty' => 5, 'percent' => 10],
        ['qty' => 10, 'percent' => 15],
    ];

    /**
     * Retourne les paliers avec le prix unitaire calculé pour un prix de base donné.
     *
     * @return array<int, array{qty: int, percent: int, price: float}>
     */
    public static function forPrice(float $basePrice): array
    {
        return array_map(
            fn (array $tier) => [
                'qty' => $tier['qty'],
                'percent' => $tier['percent'],
                'price' => round($basePrice * (1 - $tier['percent'] / 100)),
            ],
            self::TIERS
        );
    }

    /**
     * Paliers à afficher (sans le palier de base à 0% de remise).
     *
     * @return array<int, array{qty: int, percent: int, price: float}>
     */
    public static function displayTiers(float $basePrice): array
    {
        return array_values(array_filter(
            self::forPrice($basePrice),
            fn (array $tier) => $tier['percent'] > 0
        ));
    }

    /**
     * Prix unitaire applicable pour une quantité donnée (palier le plus élevé atteint).
     */
    public static function unitPriceFor(float $basePrice, int $quantity): float
    {
        $applicable = self::TIERS[0];
        foreach (self::TIERS as $tier) {
            if ($quantity >= $tier['qty']) {
                $applicable = $tier;
            }
        }

        return round($basePrice * (1 - $applicable['percent'] / 100));
    }
}
