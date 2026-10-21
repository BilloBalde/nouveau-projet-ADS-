<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Circuit d'un transfert entre deux emplacements (magasin ↔ entrepôt, dans
 * tous les sens) :
 *
 *   request()  → pending      demandé / préparé           (transfers.create)
 *   approve()  → approved     autorisé                    (transfers.approve)
 *   ship()     → in_transit   sortie de la source         (transfers.execute)
 *   receive()  → completed    entrée à la destination     (transfers.execute)
 *   reject() / cancel()       refus, ou annulation ; un transfert en transit
 *                             annulé est remis en stock à la source.
 *
 * Chaque étape conserve son auteur et sa date ; chaque variation de stock
 * passe par StockService et laisse une trace dans le journal.
 */
class TransferService
{
    public function __construct(private StockService $stock) {}

    public function request(User $user, array $data): StockTransfer
    {
        $from = (int) $data['from_store_id'];
        $to = (int) $data['to_store_id'];

        // Un employé rattaché à un magasin peut demander un transfert qui part
        // de son périmètre ou qui y arrive (réapprovisionnement), pas entre
        // deux emplacements qui lui sont étrangers.
        if (! $user->canAccessStore($from) && ! $user->canAccessStore($to)) {
            throw new RuntimeException('Ni la source ni la destination ne font partie de votre périmètre.');
        }

        return DB::transaction(function () use ($user, $data, $from, $to) {
            $transfer = StockTransfer::create([
                'product_id' => (int) $data['product_id'],
                'from_store_id' => $from,
                'to_store_id' => $to,
                'quantity' => (int) $data['quantity'],
                // Le propriétaire de la marchandise ne change pas en changeant de lieu.
                'fund_id' => ! empty($data['fund_id']) ? (int) $data['fund_id'] : null,
                'status' => StockTransfer::PENDING,
                'requested_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            $transfer->update(['reference' => 'TR-'.str_pad((string) $transfer->id, 6, '0', STR_PAD_LEFT)]);

            return $transfer;
        });
    }

    public function approve(User $user, StockTransfer $transfer): void
    {
        $this->expectStatus($transfer, [StockTransfer::PENDING]);
        $this->expectScope($user, $transfer->from_store_id, $transfer->to_store_id);

        $transfer->update([
            'status' => StockTransfer::APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $user, StockTransfer $transfer, ?string $reason = null): void
    {
        $this->expectStatus($transfer, [StockTransfer::PENDING, StockTransfer::APPROVED]);
        $this->expectScope($user, $transfer->from_store_id, $transfer->to_store_id);

        $transfer->update([
            'status' => StockTransfer::REJECTED,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'notes' => trim(($transfer->notes ? $transfer->notes."\n" : '').'Refus : '.($reason ?: 'sans motif')),
        ]);
    }

    /** Sortie physique : le stock quitte la source. */
    public function ship(User $user, StockTransfer $transfer): void
    {
        $this->expectStatus($transfer, [StockTransfer::APPROVED]);
        $this->expectScope($user, $transfer->from_store_id);

        DB::transaction(function () use ($user, $transfer) {
            $this->stock->decrementOrFail($transfer->from_store_id, $transfer->product_id, $transfer->quantity, $this->context($transfer, StockMovement::TRANSFER_OUT, $transfer->to_store_id));

            $transfer->update([
                'status' => StockTransfer::IN_TRANSIT,
                'shipped_by' => $user->id,
                'shipped_at' => now(),
            ]);
        });
    }

    /** Réception : le stock entre à la destination. */
    public function receive(User $user, StockTransfer $transfer): void
    {
        $this->expectStatus($transfer, [StockTransfer::IN_TRANSIT]);
        $this->expectScope($user, $transfer->to_store_id);

        DB::transaction(function () use ($user, $transfer) {
            $this->stock->increment($transfer->to_store_id, $transfer->product_id, $transfer->quantity, $this->context($transfer, StockMovement::TRANSFER_IN, $transfer->from_store_id));

            $transfer->update([
                'status' => StockTransfer::COMPLETED,
                'received_by' => $user->id,
                'received_at' => now(),
            ]);
        });
    }

    /**
     * Enchaîne autorisation, expédition et réception quand l'utilisateur a
     * tous les droits nécessaires (transfert immédiat, comme avant la mise en
     * place du circuit). Toutes les étapes restent tracées.
     */
    public function executeImmediately(User $user, StockTransfer $transfer): void
    {
        DB::transaction(function () use ($user, $transfer) {
            $this->approve($user, $transfer);
            $this->ship($user, $transfer->refresh());
            $this->receive($user, $transfer->refresh());
        });
    }

    public function cancel(User $user, StockTransfer $transfer): void
    {
        $this->expectStatus($transfer, [StockTransfer::PENDING, StockTransfer::APPROVED, StockTransfer::IN_TRANSIT]);
        $this->expectScope($user, $transfer->from_store_id, $transfer->to_store_id);

        DB::transaction(function () use ($transfer) {
            if ($transfer->status === StockTransfer::IN_TRANSIT) {
                $context = $this->context($transfer, StockMovement::CORRECTION, $transfer->to_store_id);
                $context['reason'] = 'Annulation du transfert '.$transfer->reference.' en transit : retour à la source';
                $this->stock->increment($transfer->from_store_id, $transfer->product_id, $transfer->quantity, $context);
            }

            $transfer->update(['status' => StockTransfer::CANCELLED]);
        });
    }

    /**
     * Correction d'un transfert terminé (quantité erronée…). Le transfert
     * initial et ses mouvements restent dans le journal ; seul l'écart est
     * appliqué, par des mouvements de CORRECTION.
     */
    public function correctQuantity(User $user, StockTransfer $transfer, int $newQuantity, ?string $reason = null): void
    {
        $this->expectStatus($transfer, [StockTransfer::COMPLETED]);
        $this->expectScope($user, $transfer->from_store_id, $transfer->to_store_id);

        $delta = $newQuantity - (int) $transfer->quantity;
        if ($delta === 0) {
            return;
        }

        DB::transaction(function () use ($transfer, $newQuantity, $delta, $reason) {
            $out = $this->context($transfer, StockMovement::CORRECTION, $transfer->to_store_id);
            $in = $this->context($transfer, StockMovement::CORRECTION, $transfer->from_store_id);
            $out['reason'] = $in['reason'] = sprintf(
                'Correction du transfert %s : %d → %d%s',
                $transfer->reference,
                $transfer->quantity,
                $newQuantity,
                $reason ? ' ('.$reason.')' : ''
            );

            if ($delta > 0) {
                $this->stock->decrementOrFail($transfer->from_store_id, $transfer->product_id, $delta, $out);
                $this->stock->increment($transfer->to_store_id, $transfer->product_id, $delta, $in);
            } else {
                $this->stock->decrementOrFail($transfer->to_store_id, $transfer->product_id, -$delta, $in);
                $this->stock->increment($transfer->from_store_id, $transfer->product_id, -$delta, $out);
            }

            $transfer->update(['quantity' => $newQuantity]);
        });
    }

    private function context(StockTransfer $transfer, string $type, int $counterpart): array
    {
        return [
            'type' => $type,
            'source_type' => 'stock_transfer',
            'source_id' => $transfer->id,
            'document_ref' => $transfer->reference,
            'counterpart_store_id' => $counterpart,
            'fund_id' => $transfer->fund_id,
        ];
    }

    private function expectStatus(StockTransfer $transfer, array $allowed): void
    {
        if (! in_array($transfer->status, $allowed, true)) {
            throw new RuntimeException(sprintf(
                'Action impossible : le transfert %s est « %s ».',
                $transfer->reference,
                $transfer->status_label
            ));
        }
    }

    private function expectScope(User $user, int ...$storeIds): void
    {
        foreach ($storeIds as $storeId) {
            if ($user->canAccessStore($storeId)) {
                return;
            }
        }

        throw new RuntimeException('Ce transfert ne concerne aucun emplacement de votre périmètre.');
    }
}
