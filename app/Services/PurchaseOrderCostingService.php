<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseOrderCostingService
{
    /**
     * Prix de revient à retenir sur la fiche produit quand l'achat en donne un
     * différent : le nouveau (celui de cet achat), l'ancien, ou un prix saisi.
     */
    public const NOUVEAU = 'nouveau';

    public const ANCIEN = 'ancien';

    public const SAISI = 'saisi';

    public const CHOIX = [self::NOUVEAU, self::ANCIEN, self::SAISI];

    public function __construct(private ?StockService $stock = null)
    {
        $this->stock ??= app(StockService::class);
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int, cartons?:int|null, pieces_per_carton?:int|null, unit_price_foreign?:float|null, cbm_per_carton?:float|null, description?:string|null, cost_choice?:string|null, cost_custom?:float|string|null}>  $lines
     * @param  int|null  $previousStoreId  destination avant modification : c'est
     *                                     de là que le stock de l'ancienne version de l'achat doit être retiré.
     *                                     Auparavant on le retirait de la NOUVELLE destination, ce qui faussait
     *                                     deux emplacements quand on changeait la destination d'un achat.
     */
    public function costOrder(PurchaseOrder $order, array $lines, ?int $previousStoreId = null): void
    {
        DB::transaction(function () use ($order, $lines, $previousStoreId) {
            $oldStoreId = $previousStoreId ?? (int) $order->store_id;

            foreach ($order->items as $existingItem) {
                // Retrait tracé de la version précédente de l'achat (modification).
                $this->stock->forceDecrement($oldStoreId, (int) $existingItem->product_id, (int) $existingItem->quantity, [
                    'type' => StockMovement::CORRECTION,
                    'source_type' => 'purchase_order',
                    'source_id' => $order->id,
                    'document_ref' => $order->reference,
                    'supplier_id' => $order->partner_id,
                    'reason' => 'Modification de l\'achat '.$order->reference.' : annulation des lignes précédentes',
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
                    'cost_choice' => in_array($line['cost_choice'] ?? null, self::CHOIX, true) ? $line['cost_choice'] : self::NOUVEAU,
                    'cost_custom' => isset($line['cost_custom']) && $line['cost_custom'] !== '' ? (float) $line['cost_custom'] : null,
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
                // Le coût réel de cet achat reste toujours sur sa ligne
                // (landed_unit_cost_gnf) : c'est un fait. Le prix de revient de la
                // fiche, lui, est un choix quand il change : le gérant garde
                // l'ancien, prend le nouveau ou en saisit un autre. Sans réponse,
                // le nouveau est retenu.
                //
                // Le prix de vente n'est recalculé, sur le prix de revient retenu,
                // que s'il n'a pas été fixé à la main : sinon l'ajustement du
                // gérant serait écrasé au prochain réapprovisionnement.
                $produit = Product::find($item['product_id']);
                $retenu = $produit ? $this->prixDeRevientRetenu($produit, $item) : null;

                if ($produit && $retenu !== null) {
                    $produit->price_sale = $retenu;

                    if (! $produit->prixFixeALaMain()) {
                        $produit->price = Product::prixDeVenteConseille($retenu);
                    }

                    $produit->save();
                }

                // Entrée en stock à l'emplacement exact choisi comme destination.
                $this->stock->increment((int) $order->store_id, (int) $item['product_id'], (int) $item['quantity'], [
                    'type' => StockMovement::PURCHASE,
                    'source_type' => 'purchase_order',
                    'source_id' => $order->id,
                    'document_ref' => $order->reference,
                    'supplier_id' => $order->partner_id,
                    'unit_price' => $item['landed_unit_cost_gnf'],
                ]);
            }
        });
    }

    /** Prix de revient à écrire sur la fiche, ou null pour la laisser telle quelle. */
    private function prixDeRevientRetenu(Product $produit, array $item): ?float
    {
        // Un produit sans prix de revient prend celui de son premier achat :
        // il n'y a rien à garder.
        if ((float) $produit->price_sale <= 0) {
            return (float) $item['landed_unit_cost_gnf'];
        }

        return match ($item['cost_choice']) {
            self::ANCIEN => null,
            self::SAISI => $item['cost_custom'] > 0 ? round($item['cost_custom'], 2) : (float) $item['landed_unit_cost_gnf'],
            default => (float) $item['landed_unit_cost_gnf'],
        };
    }
}
