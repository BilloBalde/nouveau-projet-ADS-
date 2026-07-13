<?php
// app/Observers/StockObserver.php

namespace App\Observers;

use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockObserver
{
    /**
     * Enregistre ou met à jour l'historique pour un ou plusieurs modèles.
     *
     * @param \Illuminate\Database\Eloquent\Model|array $models
     * @param string $action 'create'|'update'|'delete'
     */
    private function recordHistory($models, string $action = 'create')
    {
        $models = is_array($models) ? $models : [$models];

        DB::transaction(function () use ($models, $action) {
            foreach ($models as $model) {
                // --- Vérification du store_id ---
                $storeId = $model->store_id ?? null;
                if (empty($storeId)) {
                    Log::debug('[StockObserver] store_id manquant, modèle ignoré', [
                        'class' => get_class($model),
                        'id'    => $model->id ?? null,
                    ]);
                    continue;
                }

                // --- Détermination du type, signe, montants ---
                $type = null;
                $sign = 1;          // +1 = entrée, -1 = sortie
                $currentAmount = 0;
                $originalAmount = 0;

                $className = get_class($model);

                switch ($className) {
                    // -------------------------------------------------
                    // VENTES
                    // -------------------------------------------------
                    case 'App\Models\Sale':
                        $type = 'sale';
                        $sign = 1;   // une vente augmente la trésorerie
                        $currentAmount = $model->prixTotal ?? 0;
                        $originalAmount = $model->getOriginal('prixTotal') ?? 0;
                        break;

                    // -------------------------------------------------
                    // DÉPENSES
                    // -------------------------------------------------
                    case 'App\Models\Expense':
                        $type = 'expense';
                        $sign = -1;  // une dépense diminue la trésorerie
                        $currentAmount = $model->amount ?? 0;
                        $originalAmount = $model->getOriginal('amount') ?? 0;
                        break;

                    // -------------------------------------------------
                    // ACHATS
                    // -------------------------------------------------
                    case 'App\Models\Achat':   // ou 'App\Models\Purchase' selon votre modèle
                        $type = 'purchase';
                        $sign = -1;  // un achat diminue la trésorerie
                        // Utilisez le champ qui représente le montant réellement payé
                        // Ici on prend 'grand_total' comme montant total de l'achat
                        $currentAmount = $model->grand_total ?? 0;
                        $originalAmount = $model->getOriginal('grand_total') ?? 0;
                        break;

                    // -------------------------------------------------
                    // Autres modèles (à décommenter et adapter si besoin)
                    // -------------------------------------------------
                    /*
                    case 'App\Models\TransBank':
                        $type = 'reception';
                        $sign = -1;
                        $currentAmount = $model->montant_fg ?? 0;
                        $originalAmount = $model->getOriginal('montant_fg') ?? 0;
                        break;

                    case 'App\Models\EmployeePayment':
                        if (!$model->is_paid) {
                            continue 2;
                        }
                        $type = 'employee_payment';
                        $sign = -1;
                        $currentAmount = $model->amount ?? 0;
                        $originalAmount = $model->getOriginal('amount') ?? 0;
                        break;

                    case 'App\Models\Production':
                        if (!($model->isDirty('status') && $model->status === 'delivre')) {
                            continue 2;
                        }
                        $type = 'delivery_fee';
                        $sign = -1;
                        $currentAmount = $model->frais ?? 0;
                        $originalAmount = $model->getOriginal('frais') ?? 0;
                        break;
                    */
                    default:
                        // Modèle non géré → on passe au suivant
                        Log::debug('[StockObserver] Classe non prise en charge', ['class' => $className]);
                        continue 2;
                }

                // --- Génération de la référence unique ---
                $reference = $type . '_' . $model->id;

                // --- Calcul du montant du mouvement selon l'action ---
                $amount = match($action) {
                    'create' => $currentAmount * $sign,
                    'update' => ($currentAmount - $originalAmount) * $sign,
                    'delete' => -($currentAmount * $sign),
                    default  => $currentAmount * $sign,
                };

                // Si le montant est nul, on ignore (pas d'impact financier)
                if ($amount == 0) {
                    Log::debug('[StockObserver] Montant nul, aucune action', [
                        'reference' => $reference,
                        'action'    => $action,
                    ]);
                    continue;
                }

                // --- Insertion ou mise à jour de la ligne d'historique ---
                $history = StockHistory::updateOrCreate(
                    [
                        'store_id'  => $storeId,
                        'type'      => $type,
                        'reference' => $reference,
                    ],
                    [
                        'amount'       => $amount,
                        'dispo_before' => 0, // sera recalculé juste après
                        'dispo_after'  => 0,
                    ]
                );

                // --- Recalcul des disponibilités à partir de ce mouvement ---
                $this->recalculateDispo($storeId, $history->id);
            }
        });
    }

    /**
     * Recalcule dispo_before et dispo_after pour tous les mouvements d'un store
     * à partir d'un ID donné (inclus).
     */
    private function recalculateDispo($storeId, $fromHistoryId)
    {
        $prevDispo = StockHistory::where('store_id', $storeId)
            ->where('id', '<', $fromHistoryId)
            ->orderByDesc('id')
            ->value('dispo_after') ?? 0;

        $histories = StockHistory::where('store_id', $storeId)
            ->where('id', '>=', $fromHistoryId)
            ->orderBy('id')
            ->get();

        $runningDispo = $prevDispo;
        foreach ($histories as $history) {
            $history->update([
                'dispo_before' => $runningDispo,
                'dispo_after'  => $runningDispo + $history->amount,
            ]);
            $runningDispo += $history->amount;
        }

        Log::info('[StockObserver] Disponibilités recalculées', [
            'store_id'    => $storeId,
            'from_id'     => $fromHistoryId,
            'dispo_final' => $runningDispo,
        ]);
    }

    // -------------------------------------------------------------
    // Événements Eloquent
    // -------------------------------------------------------------
    public function created($model)
    {
        $this->recordHistory($model, 'create');
    }

    public function updated($model)
    {
        $this->recordHistory($model, 'update');
    }

    public function deleted($model)
    {
        $this->recordHistory($model, 'delete');
    }
}