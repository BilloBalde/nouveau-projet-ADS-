<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

/**
 * Seul point d'écriture du stock par emplacement (store_products).
 *
 * Chaque variation écrit une ligne dans le journal stock_movements. Le
 * paramètre $context décrit le mouvement :
 *   type                 StockMovement::SALE, PURCHASE, TRANSFER_OUT…
 *   source_type/source_id document métier (sale, purchase_order, stock_transfer…)
 *   document_ref         référence lisible (n° de facture, d'achat, de transfert)
 *   customer_id, supplier_id, unit_price, counterpart_store_id, reason
 *   user_id              par défaut l'utilisateur connecté
 *
 * Les appelants travaillent dans une transaction : si une ligne échoue, tout
 * est annulé, mouvements du journal compris.
 *
 * Tout le stock appartient à la boutique : une seule ligne par emplacement
 * et par produit.
 */
class StockService
{
    /**
     * Décrémente le stock d'un emplacement s'il y en a assez (opération
     * atomique : jamais de stock négatif, même en accès concurrent).
     */
    public function decrementIfAvailable(int $storeId, int $productId, int $quantity, array $context = []): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        $rowId = $this->rows($storeId, $productId)
            ->where('quantity', '>=', $quantity)
            ->orderBy('id')
            ->lockForUpdate()
            ->value('id');

        if (! $rowId) {
            return false;
        }

        $affected = DB::table('store_products')
            ->where('id', $rowId)
            ->where('quantity', '>=', $quantity)
            ->decrement('quantity', $quantity);

        if ($affected === 0) {
            return false;
        }

        $this->record($storeId, $productId, -$quantity, $context + ['type' => StockMovement::ADJUSTMENT]);

        return true;
    }

    /**
     * Comme decrementIfAvailable(), mais lève une exception au message
     * explicite (produit, emplacement, disponible, demandé).
     */
    public function decrementOrFail(int $storeId, int $productId, int $quantity, array $context = []): void
    {
        if (! $this->decrementIfAvailable($storeId, $productId, $quantity, $context)) {
            throw new InsufficientStockException([
                $this->shortageMessage($storeId, $productId, $this->availableInStore($storeId, $productId), $quantity),
            ]);
        }
    }

    /**
     * Vérifie en une passe que toutes les lignes sont servies, AVANT la
     * moindre écriture. Les lignes portant sur le même produit au même
     * emplacement sont cumulées. Lève une exception listant toutes les lignes
     * en défaut (pas seulement la première).
     *
     * @param  array<int, array{store_id:int, product_id:int, quantity:int}>  $lines
     */
    public function assertAvailable(array $lines): void
    {
        $needed = [];
        foreach ($lines as $line) {
            $key = (int) $line['store_id'].':'.(int) $line['product_id'];
            $needed[$key] = ($needed[$key] ?? 0) + (int) $line['quantity'];
        }

        if (! $needed) {
            return;
        }

        $storeIds = array_unique(array_map(fn ($k) => (int) explode(':', $k)[0], array_keys($needed)));
        $productIds = array_unique(array_map(fn ($k) => (int) explode(':', $k)[1], array_keys($needed)));

        $available = DB::table('store_products')
            ->whereIn('store_id', $storeIds)
            ->whereIn('product_id', $productIds)
            ->selectRaw('store_id, product_id, SUM(quantity) as qty')
            ->groupBy('store_id', 'product_id')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->store_id.':'.$r->product_id => (int) $r->qty]);

        $failures = [];
        foreach ($needed as $key => $qty) {
            $have = $available[$key] ?? 0;
            if ($have < $qty) {
                [$storeId, $productId] = array_map('intval', explode(':', $key));
                $failures[] = $this->shortageMessage($storeId, $productId, $have, $qty);
            }
        }

        if ($failures) {
            throw new InsufficientStockException($failures);
        }
    }

    /**
     * Stock total d'un produit, tous emplacements confondus.
     *
     * store_products est la seule source de vérité du stock. Une colonne
     * products.pcs tenait en parallèle un total global, que les achats ne
     * réalimentaient pas : elle dérivait de la réalité, aucune lecture ne s'y
     * fiait plus, et elle a été retirée.
     */
    public function totalAvailable(int $productId): int
    {
        return (int) DB::table('store_products')->where('product_id', $productId)->sum('quantity');
    }

    /** Lignes de stock d'un emplacement pour un produit. */
    private function rows(int $storeId, int $productId)
    {
        return DB::table('store_products')
            ->where('store_id', $storeId)
            ->where('product_id', $productId);
    }

    /** Stock d'un produit dans un emplacement précis. */
    public function availableInStore(int $storeId, int $productId): int
    {
        return (int) $this->rows($storeId, $productId)->sum('quantity');
    }

    public function increment(int $storeId, int $productId, int $quantity, array $context = []): void
    {
        if ($quantity <= 0) {
            return;
        }

        $row = StoreProduct::firstOrCreate(
            ['store_id' => $storeId, 'product_id' => $productId],
            ['quantity' => 0]
        );

        $row->increment('quantity', $quantity);
        $this->record($storeId, $productId, $quantity, $context + ['type' => StockMovement::ADJUSTMENT]);
    }

    /**
     * Retire du stock sans contrôle de disponibilité (annulation d'un achat
     * dont une partie a déjà été vendue, par exemple) : le stock peut alors
     * devenir négatif, ce qui signale une incohérence à régulariser plutôt
     * que de la masquer. Toujours tracé.
     */
    public function forceDecrement(int $storeId, int $productId, int $quantity, array $context = []): void
    {
        if ($quantity <= 0) {
            return;
        }

        $row = StoreProduct::firstOrCreate(
            ['store_id' => $storeId, 'product_id' => $productId],
            ['quantity' => 0]
        );

        $row->decrement('quantity', $quantity);
        $this->record($storeId, $productId, -$quantity, $context + ['type' => StockMovement::CORRECTION]);
    }

    /**
     * Inventaire / ajustement : fixe le stock compté. Le mouvement enregistré
     * est l'écart (compté − théorique). Retourne null si aucun écart.
     */
    public function adjustTo(int $storeId, int $productId, int $counted, array $context = []): ?StockMovement
    {
        $counted = max(0, $counted);
        $current = $this->availableInStore($storeId, $productId);
        $delta = $counted - $current;

        if ($delta === 0) {
            return null;
        }

        $context += ['type' => StockMovement::INVENTORY];

        if ($delta > 0) {
            $this->increment($storeId, $productId, $delta, $context);
        } else {
            $this->forceDecrement($storeId, $productId, -$delta, $context);
        }

        return StockMovement::where('store_id', $storeId)->where('product_id', $productId)->latest('id')->first();
    }

    private function record(int $storeId, int $productId, int $signedQuantity, array $context): void
    {
        $after = $this->availableInStore($storeId, $productId);

        StockMovement::create([
            'product_id' => $productId,
            'store_id' => $storeId,
            'type' => $context['type'],
            'quantity' => $signedQuantity,
            'stock_before' => $after - $signedQuantity,
            'stock_after' => $after,
            'counterpart_store_id' => $context['counterpart_store_id'] ?? null,
            'source_type' => $context['source_type'] ?? null,
            'source_id' => $context['source_id'] ?? null,
            'document_ref' => $context['document_ref'] ?? null,
            'customer_id' => $context['customer_id'] ?? null,
            'supplier_id' => $context['supplier_id'] ?? null,
            'unit_price' => $context['unit_price'] ?? null,
            'user_id' => $context['user_id'] ?? auth()->id(),
            'reason' => $context['reason'] ?? null,
        ]);
    }

    private function shortageMessage(int $storeId, int $productId, int $available, int $requested): string
    {
        $fiche = DB::table('products')->where('id', $productId)->first(['libelle', 'unite']);
        $product = $fiche->libelle ?? ('Produit #'.$productId);
        $unite = $fiche->unite ?? 'pcs';
        $store = DB::table('stores')->where('id', $storeId)->value('store_name') ?? ('Emplacement #'.$storeId);

        return sprintf(
            'Stock insuffisant pour %s à %s. Disponible : %s. Demandé : %s.',
            $product,
            $store,
            Product::quantiteEnUnite($available, $unite),
            Product::quantiteEnUnite($requested, $unite)
        );
    }

    /**
     * Decrement cartons count if available, by StoreProduct row id.
     */
    public function decrementCtnsByRowIdIfAvailable(int $storeProductId, int $ctns): bool
    {
        if ($ctns <= 0) {
            return true;
        }

        $affected = StoreProduct::where('id', $storeProductId)
            ->where('ctns', '>=', $ctns)
            ->decrement('ctns', $ctns);

        return $affected > 0;
    }

    /**
     * Decrement quantity (PCS) if available, by StoreProduct row id.
     */
    public function decrementQtyByRowIdIfAvailable(int $storeProductId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        $row = StoreProduct::find($storeProductId);

        $affected = StoreProduct::where('id', $storeProductId)
            ->where('quantity', '>=', $quantity)
            ->decrement('quantity', $quantity);

        if ($affected > 0 && $row) {
            $this->record((int) $row->store_id, (int) $row->product_id, -$quantity, ['type' => StockMovement::ADJUSTMENT]);
        }

        return $affected > 0;
    }
}
