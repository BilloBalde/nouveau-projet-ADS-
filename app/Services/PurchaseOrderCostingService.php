<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

class PurchaseOrderCostingService
{
    /**
     * Marge appliquée automatiquement sur le prix de revient (landed_unit_cost_gnf)
     * pour obtenir le prix de vente d'un produit, à chaque achat qui le concerne.
     */
    private const SALE_MARGIN = 0.30;

    /**
     * @param  array<int, array{product_id:int, quantity:int, cartons?:int|null, pieces_per_carton?:int|null, unit_price_foreign?:float|null, cbm_per_carton?:float|null, description?:string|null}>  $lines
     */
    public function costOrder(PurchaseOrder $order, array $lines): void
    {
        DB::transaction(function () use ($order, $lines) {

            foreach ($order->items as $existingItem) {
                StoreProduct::where('store_id', $order->store_id)
                    ->where('product_id', $existingItem->product_id)
                    ->decrement('quantity', $existingItem->quantity);
            }
            $order->items()->delete();

            $isChine = $order->origin === 'chine';
            $rate = $isChine ? (float) ($order->exchange_rate_used ?? 1) : 1.0;

            $prepared = [];
            $totalCbm = 0.0;
            $grandTotalPrice = 0;

            foreach ($lines as $line) {
                $quantity = (int) $line['quantity'];
                $cartons = isset($line['cartons']) ? (int) $line['cartons'] : null;
                $piecesPerCarton = isset($line['pieces_per_carton']) ? (int) $line['pieces_per_carton'] : null;
                $unitPriceForeign = (float) ($line['unit_price_foreign'] ?? 0);
                $unitPriceGnf = $isChine ? round($unitPriceForeign * $rate, 2) : $unitPriceForeign;

                // Le CBM se saisit par carton (mesure réelle du colis) ; le CBM par
                // unité — utilisé pour répartir transport/douane/frais divers — s'en
                // déduit automatiquement en divisant par le nombre de pièces du carton.
                $cbmPerCarton = $isChine ? (float) ($line['cbm_per_carton'] ?? 0) : 0.0;
                $cbmPerUnit = ($isChine && $piecesPerCarton > 0)
                    ? round($cbmPerCarton / $piecesPerCarton, 6)
                    : 0.0;
                $lineTotalCbm = round($cbmPerUnit * $quantity, 3);
                $totalCbm += $lineTotalCbm;
                $grandTotalPrice += $unitPriceGnf * $quantity;
                $prepared[] = [
                    'product_id' => $line['product_id'],
                    'quantity' => $quantity,
                    'cartons' => $cartons,
                    'pieces_per_carton' => $piecesPerCarton,
                    'unit_price_foreign' => $isChine ? $unitPriceForeign : null,
                    'unit_price_gnf' => $unitPriceGnf,
                    'cbm_per_carton' => $isChine ? $cbmPerCarton : null,
                    'cbm_per_unit' => $isChine ? $cbmPerUnit : null,
                    'line_total_cbm' => $isChine ? $lineTotalCbm : null,
                    'description' => $line['description'] ?? null,
                ];
            }

                $totalLandedCost =
                    $grandTotalPrice
                    + $order->transport_cost_gnf
                    + $order->customs_cost_gnf
                    + $order->other_fees_gnf;

            // Si aucun CBM n'a été renseigné (ex : oubli, ou achat local sans volume
            // suivi), on répartit les frais à parts égales entre les lignes plutôt que
            // de les laisser à 0 partout : l'argent dépensé (transport, douane, frais
            // divers) doit toujours se retrouver dans le prix de revient de quelque chose.
            $lineCount = count($prepared);

            foreach ($prepared as &$item) {
                $share = $totalCbm > 0
                    ? ($item['line_total_cbm'] ?? 0) / $totalCbm
                    : ($lineCount > 0 ? 1 / $lineCount : 0);

                $freightShare = round($order->transport_cost_gnf * $share, 2);
                $customsShare = round($order->customs_cost_gnf * $share, 2);
                $otherFeesShare = round($order->other_fees_gnf * $share, 2);

                $item['allocated_freight_gnf'] = $freightShare;
                $item['allocated_customs_gnf'] = $customsShare;
                $item['allocated_other_fees_gnf'] = $otherFeesShare;

                $item['landed_unit_cost_gnf'] = round(
                    (
                        ($item['unit_price_gnf'] * $item['quantity'])
                        + $freightShare
                        + $customsShare
                        + $otherFeesShare
                    ) / max(1, $item['quantity']),
                    2
                );
            }
            unset($item);

            $order->update([
                'total_cbm' => $totalCbm > 0 ? $totalCbm : null,
                'total_landed_cost_gnf' => $totalLandedCost,
            ]);

            foreach ($prepared as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cartons' => $item['cartons'],
                    'pieces_per_carton' => $item['pieces_per_carton'],
                    'unit_price_foreign' => $item['unit_price_foreign'],
                    'unit_price_gnf' => $item['unit_price_gnf'],
                    'cbm_per_carton' => $item['cbm_per_carton'],
                    'cbm_per_unit' => $item['cbm_per_unit'],
                    'line_total_cbm' => $item['line_total_cbm'],
                    'allocated_freight_gnf' => $item['allocated_freight_gnf'],
                    'allocated_customs_gnf' => $item['allocated_customs_gnf'],
                    'allocated_other_fees_gnf' => $item['allocated_other_fees_gnf'],
                    'description' => $item['description'],
                    'landed_unit_cost_gnf' => $item['landed_unit_cost_gnf'],
                ]);

                // `price_sale` sert en réalité de base de coût dans ce modèle (cf.
                // SaleController::resolveUnitCost) et `price` est le prix de vente
                // affiché au client — nommage historique trompeur, pas une erreur ici.
                // Le prix de vente se recalcule à chaque achat pour rester à 30% de
                // marge sur le dernier coût de revient connu.
                Product::where('id', $item['product_id'])->update([
                    'price_sale' => $item['landed_unit_cost_gnf'],
                    'price' => round($item['landed_unit_cost_gnf'] * (1 + self::SALE_MARGIN), 2),
                ]);

                StoreProduct::updateOrCreate(
                    ['store_id' => $order->store_id, 'product_id' => $item['product_id']],
                    ['quantity' => DB::raw('quantity + ' . (int) $item['quantity'])]
                );
            }
        });
    }
}