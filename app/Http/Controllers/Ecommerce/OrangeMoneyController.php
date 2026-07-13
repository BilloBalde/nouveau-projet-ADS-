<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payment\OrangeMoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Gère le flux de paiement Orange Money (Guinée — GNF) via l'API Orange Web Pay.
 *
 * pay()     → initie le paiement → redirige vers la page Orange Money
 * success() → page de confirmation affichée après paiement
 * cancel()  → page d'abandon si l'utilisateur annule
 * webhook() → reçoit l'IPN Orange Money et met à jour la commande
 */
class OrangeMoneyController extends Controller
{
    public function __construct(private OrangeMoneyService $orangeMoney) {}

    // -------------------------------------------------------------------------
    // Initier le paiement (depuis la page "Mes commandes")
    // -------------------------------------------------------------------------

    /**
     * Relancer le paiement Orange Money pour une commande existante.
     * GET /shop/payment/pay/{order}
     */
    public function pay(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->payment_status === 'paid') {
            return redirect()->route('orders.show', $order->id)
                ->with('info', 'Cette commande est déjà payée.');
        }

        if (!in_array($order->status, ['pending', 'processing'])) {
            return redirect()->route('orders.show', $order->id)
                ->with('error', 'Cette commande ne peut plus être payée.');
        }

        $result = $this->orangeMoney->initiatePayment(
            amount:  (int) $order->total_amount,
            orderId: 'SMH-' . $order->id,
            user:    Auth::user(),
        );

        if (!$result['success']) {
            Log::error('[OrangeMoney] Échec initiation depuis pay()', [
                'order_id' => $order->id,
                'error'    => $result['error'],
            ]);

            return redirect()->route('orders.show', $order->id)
                ->with('error', 'Impossible d\'initier le paiement : ' . $result['error']);
        }

        $order->update(['transaction_id' => $result['pay_token']]);

        return redirect()->away($result['payment_url']);
    }

    // -------------------------------------------------------------------------
    // Pages de retour
    // -------------------------------------------------------------------------

    /**
     * Page de succès affichée après que l'utilisateur a payé.
     * NOTE : c'est le webhook qui marque la commande comme payée, pas cette méthode.
     * GET /shop/payment/success
     */
    public function success(Request $request)
    {
        // Orange redirige avec ?pay_token=xxx dans l'URL
        $payToken = $request->query('pay_token') ?? $request->query('token');
        $order    = null;

        if ($payToken) {
            $order = Order::where('transaction_id', $payToken)
                ->where(function ($query) {
                    $query->whereNull('user_id')->orWhere('user_id', Auth::id());
                })
                ->with('items.product')
                ->first();
        }

        Log::info('[OrangeMoney] Utilisateur redirigé vers succès', [
            'user_id'   => Auth::id(),
            'pay_token' => $payToken,
            'order_id'  => $order?->id,
        ]);

        return view('ecommerce.payment.success', compact('order'));
    }

    /**
     * Page d'annulation si l'utilisateur abandonne le paiement.
     * GET /shop/payment/cancel
     */
    public function cancel(Request $request)
    {
        $payToken = $request->query('pay_token') ?? $request->query('token');
        $order    = null;

        if ($payToken) {
            $order = Order::where('transaction_id', $payToken)
                ->where(function ($query) {
                    $query->whereNull('user_id')->orWhere('user_id', Auth::id());
                })
                ->first();
        }

        Log::info('[OrangeMoney] Paiement annulé', [
            'user_id'   => Auth::id(),
            'pay_token' => $payToken,
            'order_id'  => $order?->id,
        ]);

        return view('ecommerce.payment.cancel', compact('order'));
    }

    // -------------------------------------------------------------------------
    // Webhook IPN
    // -------------------------------------------------------------------------

    /**
     * Webhook IPN — Orange Money appelle cette URL après chaque transaction.
     * La route doit être exclue du middleware CSRF.
     * POST /payment/webhook
     */
    public function webhook(Request $request)
    {
        $data = $request->all();

        Log::info('[OrangeMoney] Webhook reçu', [
            'ip'   => $request->ip(),
            'data' => $data,
        ]);

        $payToken  = $data['pay_token']  ?? $data['token']    ?? null;
        $reference = $data['reference']  ?? $data['order_id'] ?? null;
        $paidAmount = null;
        $status = ['success' => false, 'completed' => false, 'status' => 'unknown'];

        if (empty($payToken) && !empty($reference)) {
            $payToken = $this->resolvePayTokenFromReference($reference);
        }

        // ── Vérification via API (plus sûr que de faire confiance aux données brutes) ──
        if ($payToken) {
            $status = $this->orangeMoney->getPaymentStatus($payToken);

            if (!$status['success']) {
                Log::error('[OrangeMoney] Impossible de vérifier le statut via API', [
                    'pay_token' => $payToken,
                ]);
                // On accepte quand même le webhook pour éviter les retries Orange
                // On utilisera le status du webhook en fallback
            }

            if ($status['success'] && !$status['completed']) {
                Log::info('[OrangeMoney] Paiement non complété selon API', ['status' => $status['status']]);
                return response()->json(['status' => 'not_completed'], 200);
            }

            if ($status['success']) {
                $reference  = $status['reference'] ?: $reference;
                $paidAmount = $status['amount'];
            }
        }

        // ── Fallback : vérifier le champ status du webhook ──
        if (!$payToken || empty($status['success'])) {
            $webhookStatus = $data['status'] ?? '';
            if ($webhookStatus !== 'SUCCESS') {
                Log::info('[OrangeMoney] Webhook ignoré (status non SUCCESS)', ['status' => $webhookStatus]);
                return response()->json(['status' => 'ignored'], 200);
            }
            $paidAmount = (int) ($data['amount'] ?? 0);
        }

        if (!$reference) {
            Log::warning('[OrangeMoney] Webhook sans référence commande', ['data' => $data]);
            return response()->json(['error' => 'Référence manquante.'], 400);
        }

        if (!Str::startsWith($reference, ['SMH-', 'FBK-'])) {
            $reference = 'SMH-' . $reference;
        }

        // ── Extraire l'ID de commande depuis "SMH-{id}" ──
        // (l'ancien préfixe FBK- reste accepté pour les paiements initiés avant le rebranding)
        $orderId = (int) preg_replace('/^(?:SMH-|FBK-)/', '', $reference);

        DB::beginTransaction();
        try {
            $order = Order::lockForUpdate()->find($orderId);

            if (!$order) {
                Log::error('[OrangeMoney] Commande introuvable', ['reference' => $reference, 'orderId' => $orderId]);
                DB::rollBack();
                return response()->json(['error' => 'Commande introuvable.'], 404);
            }

            // Éviter le double traitement
            if ($order->payment_status === 'paid') {
                DB::rollBack();
                Log::info('[OrangeMoney] Commande déjà payée — webhook ignoré', ['order_id' => $orderId]);
                return response()->json(['status' => 'already_processed'], 200);
            }

            // Vérifier la cohérence du montant
            if ($paidAmount && $paidAmount !== (int) $order->total_amount) {
                Log::error('[OrangeMoney] Montant incohérent', [
                    'order_id'  => $orderId,
                    'attendu'   => (int) $order->total_amount,
                    'reçu'      => $paidAmount,
                ]);
                DB::rollBack();
                return response()->json(['error' => 'Montant incohérent.'], 422);
            }

            $order->update([
                'payment_status' => 'paid',
                'status'         => 'processing',
            ]);

            DB::commit();

            Log::info('[OrangeMoney] Commande marquée payée', [
                'order_id' => $orderId,
                'montant'  => $paidAmount,
            ]);

            return response()->json(['status' => 'success'], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[OrangeMoney] Erreur traitement webhook', [
                'order_id' => $orderId ?? null,
                'message'  => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Erreur interne.'], 500);
        }
    }

    private function resolvePayTokenFromReference(string $reference): ?string
    {
        // L'ancien préfixe FBK- reste accepté pour les paiements initiés avant le rebranding.
        $orderId = (int) preg_replace('/^(?:SMH-|FBK-)/', '', $reference);

        return Order::whereKey($orderId)->value('transaction_id');
    }
}
