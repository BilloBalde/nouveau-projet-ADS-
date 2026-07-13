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
     * Total stock of a product, as tracked on the product record itself (products.pcs),
     * independent of any specific store's inventory.
     */
    public function totalAvailable(int $productId): int
    {
        return (int) DB::table('products')->where('id', $productId)->value('pcs');
    }

    /**
     * Atomically decrement the product's total stock (products.pcs) if available.
     * Does not touch any store's per-store inventory.
     */
    public function decrementProductStockIfAvailable(int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        $affected = DB::table('products')
            ->where('id', $productId)
            ->where('pcs', '>=', $quantity)
            ->decrement('pcs', $quantity);

        return $affected > 0;
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

