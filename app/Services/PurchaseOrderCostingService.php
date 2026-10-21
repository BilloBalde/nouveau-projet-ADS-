<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\StoreProduct;
use Illuminate\Support\Facades\DB;

class PurchaseOrderCostingService
{
    /**
     * @param  array<int, array{product_id:int, quantity:int, cartons?:int|null, pieces_per_carton?:int|null, unit_price_foreign?:float|null, cbm_per_carton?:float|null, description?:string|null}>  $lines
     */
    public function __construct(private ?StockService $stock = null)
    {
        $this->stock ??= app(StockService::class);
    }

    /**
     * @param  int|null  $previousStoreId  destination avant modification : c'est
     *   de là que le stock de l'ancienne version de l'achat doit être retiré.
     *   Auparavant on le retirait de la NOUVELLE destination, ce qui faussait
     *   deux emplacements quand on changeait la destination d'un achat.
     */
    public function costOrder(PurchaseOrder $order, array $lines, ?int $previousStoreId = null, int|false|null $previousFundId = false): void
    {
        DB::transaction(function () use ($order, $lines, $previousStoreId, $previousFundId) {
            $oldStoreId = $previousStoreId ?? (int) $order->store_id;
            // Fonds d'origine des lignes précédentes (modification d'un achat qui
            // change de financeur) : false = inchangé.
            $oldFundId = $previousFundId === false ? $order->fund_id : $previousFundId;

            foreach ($order->items as $existingItem) {
                // Retrait tracé de la version précédente de l'achat (modification).
                $this->stock->forceDecrement($oldStoreId, (int) $existingItem->product_id, (int) $existingItem->quantity, [
                    'type'         => StockMovement::CORRECTION,
                    'source_type'  => 'purchase_order',
                    'source_id'    => $order->id,
                    'document_ref' => $order->reference,
                    'supplier_id'  => $order->partner_id,
                    'fund_id'      => $oldFundId,
                    'reason'       => 'Modification de l\'achat ' . $order->reference . ' : annulation des lignes précédentes',
                ]);
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
                //
                // Le prix de revient suit toujours le dernier achat : c'est un fait,
                // pas une décision. Le prix de vente, lui, n'est recalculé que s'il
                // n'a pas été fixé à la main : sinon l'ajustement du gérant était
                // écrasé au prochain réapprovisionnement, sans avertissement.
                //
                // Un achat financé par un associé ne touche pas au prix de revient
                // des fonds propres : son coût est suivi à part (FundService).
                if (! $order->fund_id) {
                    $produit = Product::find($item['product_id']);

                    if ($produit) {
                        $produit->price_sale = $item['landed_unit_cost_gnf'];

                        if (! $produit->prixFixeALaMain()) {
                            $produit->price = Product::prixDeVenteConseille((float) $item['landed_unit_cost_gnf']);
                        }

                        $produit->save();
                    }
                }

                // Entrée en stock à l'emplacement exact choisi comme destination.
                $this->stock->increment((int) $order->store_id, (int) $item['product_id'], (int) $item['quantity'], [
                    'type'         => StockMovement::PURCHASE,
                    'source_type'  => 'purchase_order',
                    'source_id'    => $order->id,
                    'document_ref' => $order->reference,
                    'supplier_id'  => $order->partner_id,
                    'fund_id'      => $order->fund_id,
                    'unit_price'   => $item['landed_unit_cost_gnf'],
                ]);
            }
        });
    }
}