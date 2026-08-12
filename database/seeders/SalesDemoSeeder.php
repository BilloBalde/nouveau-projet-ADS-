<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SalesDemoSeeder extends Seeder
{
    /**
     * Une "sale" est une ligne vendue rattachée à une facture (numeroFacture).
     * On recrée les deux ensemble pour rester cohérent avec SaleController::store().
     */
    public function run()
    {
        $storeId = DB::table('stores')->where('store_name', 'Quincaillerie Madina')->value('id');
        $userId = DB::table('users')->value('id');
        $customerIds = DB::table('customers')->pluck('id')->all();
        $products = DB::table('products')->select('id', 'sku', 'price', 'price_sale')->get()->keyBy('sku');

        if (!$storeId || !$userId || empty($customerIds) || $products->isEmpty()) {
            return;
        }

        $skus = $products->keys()->all();

        // [sku, quantité, jours avant aujourd'hui, statut paiement]
        $transactions = [
            ['CBF-PNT-001', 3, 12, 'payé'],
            ['CBF-MRT-001', 2, 11, 'payé'],
            ['CBF-CAD-001', 5, 9, 'payé'],
            ['CBF-TUY-001', 4, 8, 'partiel'],
            ['CBF-FIL-001', 1, 7, 'payé'],
            ['CBF-CLO-001', 6, 5, 'payé'],
            ['CBF-SER-001', 1, 4, 'non payé'],
            ['CBF-AMP-001', 10, 3, 'payé'],
            ['CBF-PEL-001', 2, 2, 'partiel'],
            ['CBF-VIS-001', 8, 1, 'payé'],
        ];

        foreach ($transactions as $i => [$sku, $quantity, $daysAgo, $statut]) {
            $product = $products[$sku] ?? null;
            if (!$product) {
                continue;
            }

            $date = Carbon::now()->subDays($daysAgo);
            $numeroFacture = $date->format('Ym') . sprintf('%04d', $i + 1);
            $customerId = $customerIds[$i % count($customerIds)];

            $prix = (float) $product->price;
            $prixTotal = round($prix * $quantity, 2);
            $interet = round(($product->price - $product->price_sale) * $quantity, 2);

            DB::table('sales')->updateOrInsert(
                ['numeroFacture' => $numeroFacture],
                [
                    'product_id' => $product->id,
                    'store_id' => $storeId,
                    'user_id' => $userId,
                    'quantity' => $quantity,
                    'prix' => $prix,
                    'prixTotal' => $prixTotal,
                    'interet' => $interet,
                    'is_third_party' => 0,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );

            $avance = match ($statut) {
                'payé' => $prixTotal,
                'partiel' => round($prixTotal / 2, 2),
                default => 0,
            };

            DB::table('factures')->updateOrInsert(
                ['numero_facture' => $numeroFacture],
                [
                    'customer_id' => $customerId,
                    'store_id' => $storeId,
                    'quantity' => $quantity,
                    'montant_total' => $prixTotal,
                    'avance' => $avance,
                    'reste' => round($prixTotal - $avance, 2),
                    'statut' => $statut,
                    'livraison' => 'livré',
                    'notes' => null,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
        }
    }
}
