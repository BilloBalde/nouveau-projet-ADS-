<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tout se compte désormais à la pièce, et il n'y a plus de promotion.
 *
 * Un produit vendu à la douzaine, au carton ou au paquet est converti en
 * pièces partout où il apparaît : quantités multipliées par le nombre de
 * pièces de l'unité, prix unitaires divisés d'autant. Les montants (quantité ×
 * prix) ne changent donc pas, et le stock, le journal, les documents et les
 * comptages d'inventaire restent cohérents entre eux — un comptage resté en
 * douzaines servirait sinon de stock d'ouverture au prochain, compté en
 * pièces, et le chiffre d'affaires déduit serait faux.
 *
 * Un rouleau, ou un carton dont le contenu n'était pas renseigné, compte pour
 * une pièce : son contenu est inconnu, rien n'est multiplié.
 *
 * Irréversible : les unités d'origine ne sont pas conservées.
 */
return new class extends Migration
{
    /** [table => [colonnes de quantité, colonnes de prix unitaire]] */
    private const LIGNES = [
        'store_products' => [['quantity'], []],
        'stock_movements' => [['quantity', 'stock_before', 'stock_after'], ['unit_price']],
        'sales' => [['quantity'], ['prix', 'unit_cost', 'purchase_price_from_partner']],
        'purchase_order_items' => [['quantity'], ['unit_price_foreign', 'unit_price_gnf', 'landed_unit_cost_gnf', 'cbm_per_unit']],
        'devi_lines' => [['quantity'], ['unit_price']],
        'order_items' => [['quantity', 'quantity_delivered'], ['price']],
        'stock_transfers' => [['quantity'], []],
        'inventory_session_lines' => [
            ['counted_quantity', 'loss_quantity', 'opening_quantity', 'purchased_quantity', 'transfer_in_quantity',
                'transfer_out_quantity', 'other_quantity', 'sold_quantity'],
            ['unit_price_gnf', 'unit_cost_gnf'],
        ],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $produits = DB::table('products')
                ->where('unite', '!=', 'pcs')
                ->get(['id', 'unite', 'pcs_par_carton', 'pcs_par_paquet']);

            foreach ($produits as $produit) {
                $facteur = match ($produit->unite) {
                    'douzaine' => 12,
                    'carton' => (int) $produit->pcs_par_carton,
                    'paquet' => (int) $produit->pcs_par_paquet,
                    default => 1,
                };
                $facteur = max(1, $facteur);

                $fiche = ['unite' => 'pcs', 'pcs_par_carton' => null, 'pcs_par_paquet' => null, 'dimension_rouleau' => null];

                if ($facteur > 1) {
                    foreach (self::LIGNES as $table => [$quantites, $prix]) {
                        $maj = [];
                        foreach ($quantites as $colonne) {
                            $maj[$colonne] = DB::raw("$colonne * $facteur");
                        }
                        foreach ($prix as $colonne) {
                            $maj[$colonne] = DB::raw("ROUND($colonne / $facteur, 2)");
                        }
                        DB::table($table)->where('product_id', $produit->id)->update($maj);
                    }

                    foreach (['price', 'price_sale', 'price_sale_minimum', 'promo_price'] as $colonne) {
                        $fiche[$colonne] = DB::raw("ROUND($colonne / $facteur, 2)");
                    }
                }

                DB::table('products')->where('id', $produit->id)->update($fiche);
            }

            // Unité figée sur les lignes de documents : toutes en pièces.
            foreach (array_diff(array_keys(self::LIGNES), ['store_products']) as $table) {
                DB::table($table)->where(fn ($q) => $q->whereNull('unite')->orWhere('unite', '!=', 'pcs'))->update(['unite' => 'pcs']);
            }

            DB::table('products')->update([
                'is_promo' => false,
                'promo_price' => null,
                'promo_percent' => null,
                'promo_start_date' => null,
                'promo_end_date' => null,
            ]);
        });
    }

    public function down(): void
    {
        // Les unités d'origine ne sont pas conservées : rien à restaurer.
    }
};
