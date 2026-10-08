<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcule l'intérêt des lignes de vente issues des commandes en ligne
 * (factures INV-…) avec la règle de coût commune (Product::coutUnitaire).
 *
 * Jusqu'ici, l'approbation d'une commande prenait comme coût le dernier
 * « achat simple » (table purchases) : un ancien prix donnait des intérêts
 * négatifs, et un produit sans achat simple voyait tout son prix de vente
 * compté comme intérêt.
 *
 * Limite assumée : le coût utilisé est le prix de revient ACTUEL du produit
 * (celui du jour de la vente n'a pas été conservé). Sans --apply, la commande
 * n'affiche que l'aperçu.
 */
class RecalculateEcommerceInterest extends Command
{
    protected $signature = 'sales:recalculer-interets-ecommerce {--apply : Enregistrer les nouveaux intérêts (sinon simple aperçu)}';

    protected $description = 'Recalcule l\'intérêt des ventes e-commerce (factures INV-) avec le prix de revient du produit';

    public function handle(): int
    {
        $lignes = Sale::where('numeroFacture', 'like', 'INV-%')
            ->where('is_third_party', false)
            ->orderBy('id')
            ->get();

        $produits = Product::with('latestLigneCommande')->whereIn('id', $lignes->pluck('product_id')->unique())->get()->keyBy('id');

        $rows = [];
        $changes = [];
        foreach ($lignes as $ligne) {
            $produit = $produits->get($ligne->product_id);
            $nouveau = $produit ? round($produit->interetPour((float) $ligne->prix, (int) $ligne->quantity), 2) : 0.0;

            if (abs($nouveau - (float) $ligne->interet) < 0.01) {
                continue;
            }

            $changes[$ligne->id] = $nouveau;
            $rows[] = [
                $ligne->numeroFacture,
                $produit?->libelle ?? '#' . $ligne->product_id,
                $ligne->quantity,
                number_format((float) $ligne->prix, 0, ',', ' '),
                number_format($produit?->coutUnitaire() ?? 0, 0, ',', ' '),
                number_format((float) $ligne->interet, 0, ',', ' '),
                number_format($nouveau, 0, ',', ' '),
            ];
        }

        if (! $rows) {
            $this->info('Aucune ligne à corriger.');

            return self::SUCCESS;
        }

        $this->table(['Facture', 'Produit', 'Qté', 'Prix', 'Coût', 'Intérêt actuel', 'Nouvel intérêt'], $rows);

        if (! $this->option('apply')) {
            $this->warn(count($rows) . ' ligne(s) à corriger. Relancez avec --apply pour enregistrer.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $id => $interet) {
                Sale::whereKey($id)->update(['interet' => $interet]);
            }
        });

        $this->info(count($changes) . ' ligne(s) corrigée(s).');

        return self::SUCCESS;
    }
}
