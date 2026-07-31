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
     * @param  array<int, array{product_id:int, quantity:int, cartons?:int|null, pieces_per_carton?:int|null, unit_price_foreign?:float|null, cbm_per_unit?:float|null, description?:string|null}>  $lines
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
                $cbmPerUnit = $isChine ? (float) ($line['cbm_per_unit'] ?? 0) : 0.0;
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


            foreach ($prepared as &$item) {
                $share = $totalCbm > 0
                    ? ($item['line_total_cbm'] ?? 0) / $totalCbm
                    : 0;

                $freightShare = round($order->transport_cost_gnf * $share, 2);
                $customsShare = round($order->customs_cost_gnf * $share, 2);
                $otherFeesShare = round($order->other_fees_gnf * $share, 2);

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
                    'cbm_per_unit' => $item['cbm_per_unit'],
                    'description' => $item['description'],
                    'landed_unit_cost_gnf' => $item['landed_unit_cost_gnf'],
                ]);

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