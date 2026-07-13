<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Intégration Orange Money Web Payment API — Guinée (GNF).
 *
 * Documentation : https://developer.orange.com/apis/om-webpay
 *
 * Flux :
 *   1. getAccessToken()    → OAuth2 token (mis en cache 50 min)
 *   2. initiatePayment()   → Orange retourne payment_url + pay_token
 *   3. L'utilisateur paie sur la page Orange Money
 *   4. Orange appelle notif_url (webhook)
 *   5. getPaymentStatus()  → vérification du statut via API (plus sûr que faire confiance au webhook seul)
 */
class OrangeMoneyService
{
    private string $clientId;
    private string $clientSecret;
    private string $merchantKey;
    private string $baseUrl;
    private string $country;
    private string $currency;
    private bool $useMock;

    public function __construct()
    {
        $this->clientId     = config('services.orange_money.client_id', '');
        $this->clientSecret = config('services.orange_money.client_secret', '');
        $this->merchantKey  = config('services.orange_money.merchant_key', '');
        $this->baseUrl      = rtrim(config('services.orange_money.base_url', 'https://api.orange.com'), '/');
        $this->country      = config('services.orange_money.country', 'GN');
        $this->currency     = config('services.orange_money.currency', 'GNF');
        $this->useMock      = (bool) config('services.orange_money.use_mock', false);
    }

    // -------------------------------------------------------------------------
    // Authentification OAuth2
    // -------------------------------------------------------------------------

    /**
     * Obtenir un access token OAuth2 (client_credentials).
     * Mis en cache 50 minutes (Orange tokens expirent généralement en 60 min).
     */
    public function getAccessToken(): ?string
    {
        return Cache::remember('orange_money_access_token', 3000, function () {
            $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");

            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => "Basic {$credentials}",
                        'Accept'        => 'application/json',
                    ])
                    ->asForm()
                    ->post("{$this->baseUrl}/oauth/v3/token", [
                        'grant_type' => 'client_credentials',
                    ]);

                if ($response->failed()) {
                    Log::error('[OrangeMoney] Échec récupération token', [
                        'status' => $response->status(),
                        'body'   => $response->body(),
                    ]);
                    return null;
                }

                $token = $response->json('access_token');

                Log::info('[OrangeMoney] Token OAuth2 obtenu avec succès');

                return $token;

            } catch (\Throwable $e) {
                Log::error('[OrangeMoney] Exception token OAuth2', ['message' => $e->getMessage()]);
                return null;
            }
        });
    }

    // -------------------------------------------------------------------------
    // Initiation du paiement
    // -------------------------------------------------------------------------

    /**
     * Initier un paiement Orange Money.
     *
     * @param  int    $amount   Montant en GNF (entier)
     * @param  string $orderId  Référence commande (ex: "SMH-24")
     * @param  object $user     Utilisateur (name, email, phone)
     * @return array  ['success' => bool, 'payment_url' => string, 'pay_token' => string, 'notif_token' => string, 'error' => string]
     */
    public function initiatePayment(int $amount, string $orderId, object $user): array
    {
        if ($this->useMock || empty($this->clientId) || empty($this->merchantKey)) {
            $payToken = 'mock-' . Str::uuid()->toString();

            return [
                'success'     => true,
                'payment_url' => config('services.orange_money.return_url')
                    ? rtrim(config('services.orange_money.return_url'), '/') . '?pay_token=' . $payToken
                    : route('payment.success', ['pay_token' => $payToken]),
                'pay_token'   => $payToken,
                'notif_token' => $payToken,
            ];
        }

        $token = $this->getAccessToken();

        if (!$token) {
            return [
                'success' => false,
                'error'   => 'Impossible d\'obtenir le token d\'authentification Orange Money.',
            ];
        }

        $notifUrl  = config('services.orange_money.notif_url')  ?: route('payment.webhook');
        $returnUrl = config('services.orange_money.return_url') ?: route('payment.success');
        $cancelUrl = config('services.orange_money.cancel_url') ?: route('payment.cancel');

        $payload = [
            'merchant_key' => $this->merchantKey,
            'currency'     => $this->currency,
            'order_id'     => $orderId,
            'amount'       => $amount,
            'return_url'   => $returnUrl,
            'cancel_url'   => $cancelUrl,
            'notif_url'    => $notifUrl,
            'lang'         => 'fr',
            'reference'    => $orderId,
        ];

        Log::info('[OrangeMoney] Initiation paiement', [
            'order_id' => $orderId,
            'amount'   => $amount,
            'currency' => $this->currency,
            'country'  => $this->country,
        ]);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ])
                ->post("{$this->baseUrl}/orange-money-webpay/{$this->country}/v1/webpayment", $payload);

            Log::info('[OrangeMoney] Réponse API webpayment', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error'   => "Erreur Orange Money (HTTP {$response->status()}) : " . $response->body(),
                ];
            }

            $data = $response->json();

            if (empty($data['payment_url'])) {
                return [
                    'success' => false,
                    'error'   => $data['message'] ?? $data['error'] ?? 'Réponse Orange Money invalide (pas de payment_url).',
                ];
            }

            return [
                'success'     => true,
                'payment_url' => $data['payment_url'],
                'pay_token'   => $data['pay_token']   ?? null,
                'notif_token' => $data['notif_token'] ?? null,
            ];

        } catch (\Throwable $e) {
            Log::error('[OrangeMoney] Exception initiatePayment', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'error'   => 'Erreur serveur lors de l\'initiation du paiement. Réessayez.',
            ];
        }
    }

    // -------------------------------------------------------------------------
    // Vérification du statut
    // -------------------------------------------------------------------------

    /**
     * Vérifier le statut d'un paiement via l'API Orange (plus fiable que le webhook seul).
     *
     * @param  string $payToken  Le pay_token reçu lors de l'initiation
     * @return array  ['success' => bool, 'completed' => bool, 'status' => string, 'amount' => int, 'reference' => string, 'txnid' => string]
     */
    public function getPaymentStatus(string $payToken): array
    {
        if ($this->useMock || empty($this->clientId) || empty($this->merchantKey)) {
            return [
                'success' => true,
                'completed' => str_contains($payToken, 'mock-'),
                'status' => str_contains($payToken, 'mock-') ? 'SUCCESS' : 'unknown',
                'amount' => 0,
                'reference' => '',
                'txnid' => $payToken,
            ];
        }

        $token = $this->getAccessToken();

        if (!$token) {
            return ['success' => false, 'completed' => false, 'status' => 'unknown', 'error' => 'Token indisponible.'];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Accept'        => 'application/json',
                ])
                ->get("{$this->baseUrl}/orange-money-webpay/{$this->country}/v1/transactionstatus/{$payToken}");

            Log::info('[OrangeMoney] Vérification statut', [
                'pay_token' => $payToken,
                'status'    => $response->status(),
                'body'      => $response->body(),
            ]);

            if ($response->failed()) {
                return ['success' => false, 'completed' => false, 'status' => 'unknown'];
            }

            $data = $response->json();

            return [
                'success'   => true,
                'completed' => ($data['status'] ?? '') === 'SUCCESS',
                'status'    => $data['status']    ?? 'unknown',
                'amount'    => (int) ($data['amount']    ?? 0),
                'reference' => $data['reference'] ?? '',
                'txnid'     => $data['txnid']     ?? '',
            ];

        } catch (\Throwable $e) {
            Log::error('[OrangeMoney] Exception getPaymentStatus', ['message' => $e->getMessage()]);
            return ['success' => false, 'completed' => false, 'status' => 'unknown'];
        }
    }
}
