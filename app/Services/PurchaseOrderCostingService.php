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
     * Calcule le coût de revient réel de chaque ligne d'un achat (conversion devise,
     * répartition du transport/douane au prorata du CBM), met à jour le stock et
     * le prix de revient (price_sale) de chaque produit concerné.
     *
     * @param  array<int, array{product_id:int, quantity:int, unit_price_foreign?:float|null, cbm_per_unit?:float|null, description?:string|null}>  $lines
     */
    public function costOrder(PurchaseOrder $order, array $lines): void
    {
        DB::transaction(function () use ($order, $lines) {
            // Si l'achat a déjà des lignes (modification), on annule d'abord leur effet
            // sur le stock avant de recalculer, pour ne pas compter la quantité en double.
            foreach ($order->items as $existingItem) {
                StoreProduct::where('store_id', $order->store_id)
                    ->where('product_id', $existingItem->product_id)
                    ->decrement('quantity', $existingItem->quantity);
            }
            $order->items()->delete();

            $isChine = $order->origin === 'chine';
            $rate = $isChine ? (float) ($order->exchange_rate_used ?? 1) : 1.0;

            // Étape 1 : prix en GNF + CBM total par ligne, avant répartition des frais.
            $prepared = [];
            $totalCbm = 0.0;

            foreach ($lines as $line) {
                $quantity = (int) $line['quantity'];
                $unitPriceForeign = $line['unit_price_foreign'] ?? null;
                $unitPriceGnf = $isChine
                    ? round(((float) $unitPriceForeign) * $rate, 2)
                    : (float) $unitPriceForeign;

                $cbmPerUnit = $isChine ? (float) ($line['cbm_per_unit'] ?? 0) : 0.0;
                $lineTotalCbm = round($cbmPerUnit * $quantity, 3);
                $totalCbm += $lineTotalCbm;

                $prepared[] = [
                    'product_id' => $line['product_id'],
                    'quantity' => $quantity,
                    'unit_price_foreign' => $isChine ? $unitPriceForeign : null,
                    'unit_price_gnf' => $unitPriceGnf,
                    'cbm_per_unit' => $isChine ? $cbmPerUnit : null,
                    'line_total_cbm' => $isChine ? $lineTotalCbm : null,
                    'description' => $line['description'] ?? null,
                ];
            }

            // Étape 2 : répartition du transport et de la douane au prorata du CBM.
            // Si aucun CBM (achat Guinée typique), rien à répartir — les frais restent à 0 par ligne.
            foreach ($prepared as &$item) {
                $share = $totalCbm > 0 ? ($item['line_total_cbm'] ?? 0) / $totalCbm : 0;
                $item['allocated_freight_gnf'] = round($order->transport_cost_gnf * $share, 2);
                $item['allocated_customs_gnf'] = round($order->customs_cost_gnf * $share, 2);

                $item['landed_unit_cost_gnf'] = round(
                    (
                        $item['unit_price_gnf'] * $item['quantity']
                        + $item['allocated_freight_gnf']
                        + $item['allocated_customs_gnf']
                    ) / max(1, $item['quantity']),
                    2
                );
            }
            unset($item);

            $order->total_cbm = $totalCbm > 0 ? $totalCbm : null;
            $order->save();

            // Étape 3 : persiste les lignes, met à jour le prix de revient produit et le stock.
            foreach ($prepared as $item) {
                PurchaseOrderItem::create(array_merge($item, [
                    'purchase_order_id' => $order->id,
                ]));

                Product::where('id', $item['product_id'])->update([
                    'price_sale' => $item['landed_unit_cost_gnf'],
                ]);

                StoreProduct::updateOrCreate(
                    ['store_id' => $order->store_id, 'product_id' => $item['product_id']],
                    ['quantity' => DB::raw('quantity + ' . (int) $item['quantity'])]
                );
            }
        });
    }
}
