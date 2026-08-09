<?php

namespace App\Services;

use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Atomically decrement stock (PCS) if there is enough available.
     */
    public function decrementIfAvailable(int $storeId, int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        $affected = DB::table('store_products')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->where('quantity', '>=', $quantity)
            ->decrement('quantity', $quantity);

        if ($affected > 0) {
            $this->decrementGlobalStock($productId, $quantity);
            return true;
        }

        return false;
    }

    /**
     * Stock total d'un produit, toutes boutiques confondues. Calculé à la volée à partir
     * de store_products (la seule table alimentée de façon fiable par ventes/achats/
     * transferts) plutôt que lu depuis products.pcs, qui n'est pas gardé synchronisé par
     * les achats et dérive donc de la réalité au fil du temps.
     */
    public function totalAvailable(int $productId): int
    {
        return (int) DB::table('store_products')->where('product_id', $productId)->sum('quantity');
    }

    /**
     * Stock d'un produit dans une boutique précise.
     */
    public function availableInStore(int $storeId, int $productId): int
    {
        return (int) DB::table('store_products')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->value('quantity');
    }

    public function increment(int $storeId, int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $row = StoreProduct::firstOrCreate(
            ['store_id' => $storeId, 'product_id' => $productId],
            ['quantity' => 0]
        );

        $row->increment('quantity', $quantity);
        $this->incrementGlobalStock($productId, $quantity);
    }

    private function decrementGlobalStock(int $productId, int $quantity): void
    {
        if (! DB::getSchemaBuilder()->hasColumn('products', 'pcs')) {
            return;
        }

        DB::table('products')
            ->where('id', $productId)
            ->decrement('pcs', $quantity);
    }

    private function incrementGlobalStock(int $productId, int $quantity): void
    {
        if (! DB::getSchemaBuilder()->hasColumn('products', 'pcs')) {
            return;
        }

        DB::table('products')
            ->where('id', $productId)
            ->increment('pcs', $quantity);
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

        $affected = StoreProduct::where('id', $storeProductId)
            ->where('quantity', '>=', $quantity)
            ->decrement('quantity', $quantity);

        return $affected > 0;
    }
}

