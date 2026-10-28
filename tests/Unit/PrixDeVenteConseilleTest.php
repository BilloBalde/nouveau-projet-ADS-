<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

/**
 * Le prix de vente conseillé s'écrit en francs entiers.
 *
 * Il portait deux décimales : un prix de revient de 66 500 FG donnait
 * 86 450,00 FG, et le moindre frais de transport réparti au prorata du volume
 * en donnait 86 450,67. Le franc guinéen n'a pas de centimes et les pièces ne
 * circulent plus — ce prix ne s'affiche pas sur une étiquette et ne se rend
 * pas en caisse.
 */
class PrixDeVenteConseilleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['pricing.sale_margin_percent' => 30, 'pricing.rounding_step' => 100]);
    }

    public function test_le_prix_conseille_na_pas_de_decimales(): void
    {
        foreach ([1_234, 7_600, 66_500, 86_385.42, 3_333.33] as $cout) {
            $prix = Product::prixDeVenteConseille((float) $cout);

            $this->assertSame(
                $prix,
                floor($prix),
                "Prix de revient $cout : le prix conseillé doit être un nombre entier de francs."
            );
        }
    }

    public function test_le_prix_monte_au_palier_suivant(): void
    {
        // 66 500 × 1,30 = 86 450 → le palier au-dessus.
        $this->assertSame(86_500.0, Product::prixDeVenteConseille(66_500));

        // 1 234 × 1,30 = 1 604,20.
        $this->assertSame(1_700.0, Product::prixDeVenteConseille(1_234));
    }

    /**
     * Arrondir vers le bas rognerait la marge demandée : le prix conseillé
     * doit toujours couvrir le prix de revient plus la marge.
     */
    public function test_le_prix_ne_descend_jamais_sous_la_marge_demandee(): void
    {
        foreach ([1, 999, 7_600, 66_500, 86_385.42] as $cout) {
            $this->assertGreaterThanOrEqual(
                $cout * 1.30,
                Product::prixDeVenteConseille((float) $cout),
                "Prix de revient $cout : la marge de 30 % a été rognée par l'arrondi."
            );
        }
    }

    /**
     * Le bruit du calcul flottant ajoutait un palier entier : 10 000 × 1,30
     * vaut 13 000,000000000002, dont le palier supérieur est 13 100.
     */
    public function test_un_prix_deja_rond_ne_monte_pas_dun_palier(): void
    {
        $this->assertSame(13_000.0, Product::prixDeVenteConseille(10_000));
        $this->assertSame(26_000.0, Product::prixDeVenteConseille(20_000));
        $this->assertSame(0.0, Product::prixDeVenteConseille(0));
    }

    /**
     * Sur un article conseillé à 130 FG, monter de 100 FG le doublerait :
     * l'arrondi ne doit jamais peser plus qu'un dixième du prix.
     */
    public function test_larrondi_ne_pese_jamais_plus_quun_dixieme_du_prix(): void
    {
        $this->assertSame(130.0, Product::prixDeVenteConseille(100));
        $this->assertSame(260.0, Product::prixDeVenteConseille(200));

        foreach ([1, 50, 100, 777, 1_234, 9_999, 66_500] as $cout) {
            $brut = $cout * 1.30;

            // Le franc est l'unité la plus fine : un prix de 1,30 FG monte à 2,
            // et aucun dixième ne peut l'en empêcher.
            $this->assertLessThanOrEqual(
                max($brut + 1, $brut * 1.1),
                Product::prixDeVenteConseille((float) $cout),
                "Prix de revient $cout : l'arrondi a ajouté plus d'un dixième du prix."
            );
        }
    }

    /** Le palier est réglable par installation ; à 0, l'arrondi est à l'unité. */
    public function test_le_palier_est_configurable(): void
    {
        // 66 000 × 1,30 = 85 800 : déjà rond au palier de 100.
        $this->assertSame(85_800.0, Product::prixDeVenteConseille(66_000));

        config(['pricing.rounding_step' => 500]);
        $this->assertSame(86_000.0, Product::prixDeVenteConseille(66_000));

        config(['pricing.rounding_step' => 0]);
        $this->assertSame(85_800.0, Product::prixDeVenteConseille(66_000));
    }
}
