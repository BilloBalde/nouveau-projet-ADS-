<?php

namespace App\Services;

use App\Models\InventorySession;
use App\Models\InventorySessionLine;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Chiffre d'affaires déduit du stock.
 *
 * Certaines boutiques ne saisissent pas leurs ventes une par une : elles
 * vendent, puis comptent le stock restant à intervalle régulier. Ce qui a
 * disparu entre deux comptages, achats et transferts déduits, a été vendu.
 *
 * Pour chaque produit d'une boutique, entre la séance précédente et celle-ci :
 *
 *   vendu = compté au début
 *         + achats
 *         + transferts entrants
 *         − transferts sortants
 *         − pertes déclarées
 *         + autres mouvements tracés (signés)
 *         − compté à la fin
 *
 *   chiffre d'affaires = vendu × prix de vente figé
 *
 * Trois règles tiennent ce calcul :
 *
 * 1. Les mouvements produits par une séance d'inventaire (la vente déduite et
 *    la perte déclarée) sont exclus des termes. Ce sont des RÉSULTATS de
 *    comptage, jamais des entrées du comptage suivant. Les exclure par leur
 *    origine et non par leur date évite qu'une séance validée en retard
 *    pollue la période suivante.
 *
 * 2. Tout mouvement qui n'est ni un achat ni un transfert est porté à part,
 *    dans « autres », jamais fondu dans les ventes. Un ajustement manuel ou
 *    la correction d'un achat se verraient sinon comme du chiffre d'affaires,
 *    et l'égalité ne se vérifierait plus sur le rapport.
 *
 * 3. Les pertes déclarées (casse, vol, péremption) sortent du stock mais pas
 *    du chiffre d'affaires. Sans elles, la formule facture les pertes au prix
 *    de vente : le chiffre est gonflé et l'inventaire ne révèle plus rien.
 */
class InventaireService
{
    /** Origine des mouvements écrits par une séance : jamais réinjectés. */
    public const ORIGINE = 'inventory_session';

    public function __construct(private StockService $stock) {}

    /**
     * Ouvre une séance de comptage pour une boutique, bornée par la dernière
     * séance validée de cette même boutique.
     */
    public function preparer(int $storeId, string $date, ?int $userId = null): InventorySession
    {
        $store = Store::findOrFail($storeId);

        // Une date future est un piège sans retour : le comptage suivant serait
        // refusé comme antérieur, et un comptage validé ne se supprime pas. Une
        // faute de frappe sur l'année bloquerait la boutique jusqu'à cette date.
        // Le garde-fou est ici et non seulement dans le formulaire : c'est le
        // service qui porte la règle, pas l'écran.
        if (Carbon::parse($date)->startOfDay()->gt(Carbon::today())) {
            throw new RuntimeException(sprintf(
                'Un comptage ne peut pas être daté du %s : on ne compte pas un stock à venir.',
                Carbon::parse($date)->format('d/m/Y')
            ));
        }

        $precedente = InventorySession::derniereValidee($storeId);

        if ($precedente && $precedente->counted_on->gt($date)) {
            throw new RuntimeException(sprintf(
                'La boutique « %s » a déjà été comptée le %s. Un inventaire ne peut pas porter une date antérieure.',
                $store->store_name,
                $precedente->counted_on->format('d/m/Y')
            ));
        }

        if (InventorySession::where('store_id', $storeId)->where('status', InventorySession::BROUILLON)->exists()) {
            throw new RuntimeException(sprintf(
                'Un comptage est déjà en cours pour « %s ». Terminez-le ou supprimez-le avant d\'en ouvrir un autre.',
                $store->store_name
            ));
        }

        return InventorySession::create([
            'reference' => InventorySession::prochaineReference(),
            'store_id' => $storeId,
            'previous_session_id' => $precedente?->id,
            'counted_on' => $date,
            'status' => InventorySession::BROUILLON,
            'created_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Termes attendus pour chaque produit de la boutique, avant comptage :
     * ce que le stock enregistré dit, et d'où il vient.
     *
     * Sert à préremplir la feuille de comptage — le gérant voit le stock
     * théorique en face de la case à remplir — puis à figer les termes à la
     * validation.
     *
     * @return array<string, array{product_id:int, fund_id:int|null, opening:int, purchased:int, transfer_in:int, transfer_out:int, other:int, theorique:int}>
     */
    public function termesAttendus(InventorySession $session): array
    {
        $debut = $session->debutPeriode();
        $fin = $session->finPeriode();

        $termes = [];
        $cle = fn (int $productId, ?int $fundId) => $productId.'|'.($fundId ?: '');

        // Point de départ : ce qui avait été compté à la séance précédente.
        if ($session->previous_session_id) {
            foreach (InventorySessionLine::where('inventory_session_id', $session->previous_session_id)->get() as $ligne) {
                $termes[$cle((int) $ligne->product_id, $ligne->fund_id)] = $this->termesVides(
                    (int) $ligne->product_id,
                    $ligne->fund_id,
                    (int) $ligne->counted_quantity
                );
            }
        }

        // Mouvements de la période, par produit, propriétaire et type.
        $mouvements = DB::table('stock_movements')
            ->where('store_id', $session->store_id)
            ->when($debut, fn ($q) => $q->where('created_at', '>=', $debut))
            ->where('created_at', '<=', $fin)
            // Les mouvements écrits par une séance sont des résultats de
            // comptage : ils ne nourrissent pas le comptage suivant.
            ->where(fn ($q) => $q->whereNull('source_type')->orWhere('source_type', '!=', self::ORIGINE))
            ->selectRaw('product_id, '.$this->fondsSql().' as fonds, type, SUM(quantity) as qty')
            ->groupBy('product_id', DB::raw($this->fondsSql()), 'type')
            ->get();

        foreach ($mouvements as $ligne) {
            $fundId = ((int) $ligne->fonds) ?: null;
            $k = $cle((int) $ligne->product_id, $fundId);
            $termes[$k] ??= $this->termesVides((int) $ligne->product_id, $fundId, 0);
            $qty = (int) $ligne->qty;

            match ($ligne->type) {
                StockMovement::PURCHASE => $termes[$k]['purchased'] += $qty,
                StockMovement::TRANSFER_IN => $termes[$k]['transfer_in'] += $qty,
                // Le journal porte les sorties en négatif : on garde une magnitude.
                StockMovement::TRANSFER_OUT => $termes[$k]['transfer_out'] += abs($qty),
                default => $termes[$k]['other'] += $qty,
            };
        }

        // Produits présents en stock mais sans aucun mouvement ni comptage
        // précédent : ils doivent figurer sur la feuille, sinon un produit
        // oublié passe pour un produit à zéro.
        //
        // Au premier comptage, le stock enregistré sert aussi de point de
        // départ affiché : sans séance précédente, la colonne « Début » serait
        // à zéro pour tout le monde alors que l'application tient un stock, et
        // l'écran de saisie annoncerait un théorique de 0 partout. Ce chiffre
        // n'entre dans aucun calcul — une première séance ne déduit aucune
        // vente — il sert au gérant à comparer ce qu'il compte avec ce que
        // l'application croyait avoir.
        $premiere = $session->estPremiereSeance();

        foreach (DB::table('store_products')->where('store_id', $session->store_id)->get() as $stock) {
            $fundId = $stock->fund_id ? (int) $stock->fund_id : null;
            $k = $cle((int) $stock->product_id, $fundId);
            $termes[$k] ??= $this->termesVides((int) $stock->product_id, $fundId, $premiere ? (int) $stock->quantity : 0);
        }

        // Premier comptage d'une boutique : tout le catalogue, et pas seulement
        // ce qui a déjà une ligne de stock ici.
        //
        // Sur une installation neuve, aucun produit n'a encore d'historique
        // dans cette boutique : la feuille sortait presque vide, et le gérant
        // n'avait rien à compter — donc aucun moyen de poser son stock de
        // départ. Les comptages suivants, eux, restent limités à ce qui a
        // circulé : inutile de faire chercher un produit que la boutique n'a
        // jamais eu.
        if ($premiere) {
            foreach (DB::table('products')->orderBy('libelle')->pluck('id') as $productId) {
                $termes[$cle((int) $productId, null)] ??= $this->termesVides((int) $productId, null, 0);
            }
        }

        foreach ($termes as $k => $t) {
            $termes[$k]['theorique'] = $t['opening'] + $t['purchased'] + $t['transfer_in'] - $t['transfer_out'] + $t['other'];
        }

        return $termes;
    }

    /**
     * Valide la séance : calcule les quantités vendues, fige tous les termes,
     * aligne le stock enregistré sur le stock compté et trace le tout.
     *
     * @param  array<int, array{product_id:int, fund_id?:int|null, counted:int, loss?:int, loss_reason?:string|null, unit_price?:float|null}>  $comptages
     */
    public function valider(InventorySession $session, array $comptages, ?int $userId = null): InventorySession
    {
        if ($session->estValidee()) {
            throw new RuntimeException('Cette séance d\'inventaire a déjà été validée.');
        }

        if ($comptages === []) {
            throw new RuntimeException('Aucun produit compté : il n\'y a rien à valider.');
        }

        return DB::transaction(function () use ($session, $comptages, $userId) {
            $termes = $this->termesAttendus($session);
            $premiere = $session->estPremiereSeance();

            $session->lines()->delete();

            $totalCa = 0.0;
            $totalPertes = 0.0;
            $totalMarge = 0.0;
            $totalVendu = 0;
            $fonds = app(FundService::class);

            foreach ($comptages as $comptage) {
                $productId = (int) $comptage['product_id'];
                $fundId = ! empty($comptage['fund_id']) ? (int) $comptage['fund_id'] : null;
                $compte = max(0, (int) $comptage['counted']);
                $perte = max(0, (int) ($comptage['loss'] ?? 0));

                $produit = Product::findOrFail($productId);
                $t = $termes[$productId.'|'.($fundId ?: '')] ?? $this->termesVides($productId, $fundId, 0);

                // Prix de vente figé : celui proposé à la saisie, sinon celui de
                // la fiche au moment du comptage. Il ne rebougera plus.
                $prix = isset($comptage['unit_price']) && $comptage['unit_price'] !== null && $comptage['unit_price'] !== ''
                    ? (float) $comptage['unit_price']
                    : (float) ($produit->price ?? 0);

                // Une première séance ne déduit rien : elle constate le stock de
                // départ. Sans séance précédente, il n'existe aucune borne et
                // tout le stock compté passerait pour une vente.
                // Prix de revient figé lui aussi : un achat postérieur change le
                // coût du produit, et la marge d'une période close ne doit pas
                // bouger derrière. Marchandise d'un associé : son coût à lui.
                $cout = $fundId
                    ? (float) $fonds->unitCost($fundId, $productId)
                    : (float) $produit->coutUnitaire();

                // Une première séance ne déduit rien : elle constate le stock de
                // départ. Sans séance précédente, il n'existe aucune borne et
                // tout le stock compté passerait pour une vente.
                $vendu = $premiere ? 0 : max(0, $t['opening'] + $t['purchased'] + $t['transfer_in']
                    - $t['transfer_out'] - $perte + $t['other'] - $compte);

                // Marge inconnue plutôt que fausse : sans coût connu, on ne
                // compte pas tout le prix de vente comme bénéfice.
                $marge = $cout > 0 ? ($prix - $cout) * $vendu : 0.0;

                if ($perte > $t['theorique']) {
                    throw new RuntimeException(sprintf(
                        'Perte déclarée impossible pour « %s » : %s annoncés alors que le stock théorique n\'est que de %s.',
                        $produit->libelle,
                        $produit->quantiteAvecUnite($perte),
                        $produit->quantiteAvecUnite($t['theorique'])
                    ));
                }

                InventorySessionLine::create([
                    'inventory_session_id' => $session->id,
                    'product_id' => $productId,
                    'fund_id' => $fundId,
                    'unite' => $produit->unite,
                    'counted_quantity' => $compte,
                    'loss_quantity' => $perte,
                    'loss_reason' => $comptage['loss_reason'] ?? null,
                    'unit_price_gnf' => $prix,
                    'unit_cost_gnf' => $cout,
                    'opening_quantity' => $t['opening'],
                    'purchased_quantity' => $t['purchased'],
                    'transfer_in_quantity' => $t['transfer_in'],
                    'transfer_out_quantity' => $t['transfer_out'],
                    'other_quantity' => $t['other'],
                    'sold_quantity' => $vendu,
                    'revenue_gnf' => $vendu * $prix,
                    'margin_gnf' => $marge,
                ]);

                $totalVendu += $vendu;
                $totalCa += $vendu * $prix;
                $totalMarge += $marge;
                // Une perte se chiffre à ce qu'elle a coûté, pas à ce qu'elle
                // aurait rapporté : la marchandise perdue n'a jamais été vendue.
                $totalPertes += $perte * ($cout > 0 ? $cout : $prix);

                $this->tracerEtAligner($session, $produit, $fundId, $compte, $vendu, $perte, $prix, $comptage['loss_reason'] ?? null);
            }

            $session->update([
                'status' => InventorySession::VALIDEE,
                'validated_by' => $userId ?? auth()->id(),
                'validated_at' => now(),
                'total_revenue_gnf' => $totalCa,
                'total_margin_gnf' => $totalMarge,
                'total_loss_gnf' => $totalPertes,
                'total_sold_quantity' => $totalVendu,
            ]);

            return $session->fresh('lines');
        });
    }

    /**
     * Écrit au journal ce que le comptage a révélé, puis aligne le stock
     * enregistré sur le stock compté.
     *
     * La vente déduite et la perte déclarée sont deux mouvements distincts :
     * le journal doit dire ce qui a été vendu et ce qui a été perdu, pas un
     * écart global dans lequel les deux se confondent. Un dernier ajustement
     * absorbe ce qui resterait (une première séance, ou un écart que les
     * termes n'expliquent pas) pour que le stock enregistré finisse exactement
     * sur le stock compté.
     */
    private function tracerEtAligner(
        InventorySession $session,
        Product $produit,
        ?int $fundId,
        int $compte,
        int $vendu,
        int $perte,
        float $prix,
        ?string $motifPerte
    ): void {
        $contexte = [
            'source_type' => self::ORIGINE,
            'source_id' => $session->id,
            'document_ref' => $session->reference,
            'fund_id' => $fundId,
        ];

        if ($vendu > 0) {
            $this->stock->forceDecrement((int) $session->store_id, (int) $produit->id, $vendu, $contexte + [
                'type' => StockMovement::SALE,
                'unit_price' => $prix,
                'reason' => 'Vente déduite du comptage '.$session->reference,
            ]);
        }

        if ($perte > 0) {
            $this->stock->forceDecrement((int) $session->store_id, (int) $produit->id, $perte, $contexte + [
                'type' => StockMovement::LOSS,
                'unit_price' => $prix,
                'reason' => $motifPerte ?: 'Perte déclarée au comptage '.$session->reference,
            ]);
        }

        // Reste éventuel : première séance, ou écart inexpliqué par les termes.
        $this->stock->adjustTo((int) $session->store_id, (int) $produit->id, $compte, $contexte + [
            'type' => StockMovement::INVENTORY,
            'reason' => 'Alignement sur le stock compté ('.$session->reference.')',
        ]);
    }

    /** @return array{product_id:int, fund_id:int|null, opening:int, purchased:int, transfer_in:int, transfer_out:int, other:int, theorique:int} */
    private function termesVides(int $productId, ?int $fundId, int $opening): array
    {
        return [
            'product_id' => $productId,
            'fund_id' => $fundId,
            'opening' => $opening,
            'purchased' => 0,
            'transfer_in' => 0,
            'transfer_out' => 0,
            'other' => 0,
            'theorique' => $opening,
        ];
    }

    /** COALESCE portable : le propriétaire absent vaut 0 pour le regroupement. */
    private function fondsSql(): string
    {
        return 'COALESCE(fund_id, 0)';
    }
}
