<?php

namespace App\Services;

use App\Models\InventorySession;
use App\Models\InventorySessionLine;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lecture des comptages : ce que le tableau de bord et les rapports affichent
 * quand le chiffre d'affaires se déduit du stock.
 *
 * Toutes les valeurs viennent des séances VALIDÉES, et des totaux figés à leur
 * validation. Rien n'est recalculé ici : un chiffre d'affaires déjà établi ne
 * doit pas changer parce qu'un prix a bougé depuis.
 *
 * Un comptage en cours n'entre dans aucun total : tant qu'il n'est pas validé,
 * il ne dit rien de ce qui a été vendu.
 */
class InventaireRapportService
{
    /**
     * Séances validées visibles, éventuellement limitées à des emplacements et
     * à une plage de dates (sur la date de comptage, qui clôt la période).
     *
     * @param  array<int>|null  $storeIds  null = tous les emplacements
     */
    public function seances(?array $storeIds = null, ?CarbonInterface $debut = null, ?CarbonInterface $fin = null): Collection
    {
        return InventorySession::with('store:id,store_name')
            ->where('status', InventorySession::VALIDEE)
            ->when($storeIds !== null, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->when($debut, fn ($q) => $q->whereDate('counted_on', '>=', $debut))
            ->when($fin, fn ($q) => $q->whereDate('counted_on', '<=', $fin))
            ->orderByDesc('counted_on')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Chiffres du tableau de bord.
     *
     * @param  array<int>|null  $storeIds
     * @return array{ca_annee:float, marge_annee:float, pertes_annee:float, quantite_annee:int, ca_par_mois:array<int,float>, derniere:?InventorySession, seances:Collection, comptages_en_cours:int, annee:int}
     */
    public function tableauDeBord(?array $storeIds = null, ?int $annee = null): array
    {
        $annee ??= Carbon::now()->year;

        $seances = $this->seances(
            $storeIds,
            Carbon::create($annee, 1, 1)->startOfDay(),
            Carbon::create($annee, 12, 31)->endOfDay()
        );

        // Le chiffre d'affaires d'une période est rattaché au mois où le
        // comptage l'a close : c'est la date à laquelle le gérant constate.
        $parMois = array_fill(1, 12, 0.0);
        foreach ($seances as $seance) {
            $parMois[(int) $seance->counted_on->month] += (float) $seance->total_revenue_gnf;
        }

        return [
            'annee' => $annee,
            'ca_annee' => (float) $seances->sum('total_revenue_gnf'),
            'marge_annee' => (float) $seances->sum('total_margin_gnf'),
            'pertes_annee' => (float) $seances->sum('total_loss_gnf'),
            'quantite_annee' => (int) $seances->sum('total_sold_quantity'),
            'ca_par_mois' => $parMois,
            'derniere' => $seances->first(),
            'seances' => $seances,
            'comptages_en_cours' => InventorySession::where('status', InventorySession::BROUILLON)
                ->when($storeIds !== null, fn ($q) => $q->whereIn('store_id', $storeIds))
                ->count(),
        ];
    }

    /**
     * Produits les plus vendus sur une plage, toutes séances confondues.
     *
     * @param  array<int>|null  $storeIds
     */
    public function meilleuresVentes(?array $storeIds = null, ?CarbonInterface $debut = null, ?CarbonInterface $fin = null, int $limite = 10): Collection
    {
        $ids = $this->seances($storeIds, $debut, $fin)->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return InventorySessionLine::whereIn('inventory_session_id', $ids)
            ->where('sold_quantity', '>', 0)
            ->selectRaw('product_id, SUM(sold_quantity) as quantite, SUM(revenue_gnf) as chiffre, SUM(margin_gnf) as marge')
            ->groupBy('product_id')
            ->orderByDesc('chiffre')
            ->take($limite)
            ->get()
            ->map(function ($ligne) {
                $ligne->produit = Product::find($ligne->product_id);

                return $ligne;
            });
    }

    /**
     * Détail d'une plage pour le rapport : une ligne par produit, cumulée sur
     * toutes les séances de la plage — c'est ce qui permet de lire « mon
     * chiffre de janvier », toutes boutiques confondues, au lieu de devoir
     * additionner les comptages à la main.
     *
     * @param  array<int>|null  $storeIds
     */
    public function detailParProduit(?array $storeIds = null, ?CarbonInterface $debut = null, ?CarbonInterface $fin = null): Collection
    {
        $ids = $this->seances($storeIds, $debut, $fin)->pluck('id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return InventorySessionLine::whereIn('inventory_session_id', $ids)
            ->selectRaw('product_id, unite, SUM(sold_quantity) as quantite, SUM(loss_quantity) as pertes,'
                .' SUM(revenue_gnf) as chiffre, SUM(margin_gnf) as marge')
            ->groupBy('product_id', 'unite')
            ->get()
            ->map(function ($ligne) {
                $ligne->produit = Product::find($ligne->product_id);

                return $ligne;
            })
            ->sortByDesc('chiffre')
            ->values();
    }
}
