<?php

namespace App\Services;

use App\Models\InventorySession;
use App\Models\InventorySessionLine;
use App\Models\Product;
use App\Models\Store;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Période de rappel de l'inventaire de fin d'année qui contient la date
     * donnée, ou null hors période. Du 10 décembre au 10 janvier par défaut
     * (config/sales.php) : l'échéance est le 31 décembre de l'année comptée.
     *
     * @return array{debut: Carbon, fin: Carbon, echeance: Carbon}|null
     */
    public function periodeDeRappel(?Carbon $jour = null): ?array
    {
        $jour = ($jour ?? Carbon::today())->copy()->startOfDay();
        [$moisDebut, $jourDebut] = array_map('intval', explode('-', (string) config('sales.inventaire_rappel_debut', '12-10')));
        [$moisFin, $jourFin] = array_map('intval', explode('-', (string) config('sales.inventaire_rappel_fin', '01-10')));

        // L'année comptée : celle qui se termine. En janvier, c'est la précédente.
        foreach ([$jour->year, $jour->year - 1] as $annee) {
            $debut = Carbon::create($annee, $moisDebut, $jourDebut)->startOfDay();
            $fin = Carbon::create($moisFin < $moisDebut ? $annee + 1 : $annee, $moisFin, $jourFin)->endOfDay();

            if ($jour->betweenIncluded($debut, $fin)) {
                return ['debut' => $debut, 'fin' => $fin, 'echeance' => Carbon::create($annee, 12, 31)->startOfDay()];
            }
        }

        return null;
    }

    /**
     * Inventaire de fin d'année de chaque boutique, pendant la période de
     * rappel : fait si un comptage y a été validé, à faire sinon — orange
     * avant le 31 décembre, rouge après. Hors période, rien n'est à faire.
     *
     * Seules les boutiques qui ont du stock ou un comptage figurent : un
     * emplacement vide n'a rien à compter. Les plus urgentes d'abord.
     *
     * @param  array<int>|null  $storeIds  null = tous les emplacements
     * @return Collection<int, object{store: Store, derniere: ?Carbon, echeance: Carbon, fin: Carbon, jours: int, etat: string}>
     *                                                                                                                           etat : en_retard, bientot, a_jour
     */
    public function echeances(?array $storeIds = null): Collection
    {
        $aujourdhui = Carbon::today();
        $periode = $this->periodeDeRappel($aujourdhui);

        if ($periode === null) {
            return collect();
        }

        $faites = InventorySession::where('status', InventorySession::VALIDEE)
            ->when($storeIds !== null, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->whereBetween('counted_on', [$periode['debut']->toDateString(), $periode['fin']->toDateString()])
            ->distinct()
            ->pluck('store_id')
            ->all();

        $derniers = InventorySession::where('status', InventorySession::VALIDEE)
            ->when($storeIds !== null, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->selectRaw('store_id, MAX(counted_on) as derniere')
            ->groupBy('store_id')
            ->pluck('derniere', 'store_id');

        $avecStock = DB::table('store_products')
            ->where('quantity', '>', 0)
            ->when($storeIds !== null, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->distinct()
            ->pluck('store_id');

        $ids = $avecStock->merge($derniers->keys())->unique()->values();

        $ordre = ['en_retard' => 0, 'bientot' => 1, 'a_jour' => 2];
        $echeance = $periode['echeance'];
        $fin = $periode['fin']->copy()->startOfDay();
        $jours = (int) $aujourdhui->diffInDays($echeance, false);

        return Store::whereIn('id', $ids)->orderBy('store_name')->get()
            ->map(function (Store $store) use ($derniers, $faites, $echeance, $fin, $jours) {
                $derniere = isset($derniers[$store->id]) ? Carbon::parse($derniers[$store->id])->startOfDay() : null;

                $etat = match (true) {
                    in_array($store->id, $faites) => 'a_jour',
                    $jours < 0 => 'en_retard',
                    default => 'bientot',
                };

                return (object) compact('store', 'derniere', 'echeance', 'fin', 'jours', 'etat');
            })
            ->sortBy(fn ($e) => [$ordre[$e->etat], $e->store->store_name])
            ->values();
    }

    /** Boutiques dont l'inventaire de fin d'année reste à faire, pendant la période de rappel. */
    public function alertes(?array $storeIds = null): Collection
    {
        return $this->echeances($storeIds)->reject(fn ($e) => $e->etat === 'a_jour')->values();
    }
}
