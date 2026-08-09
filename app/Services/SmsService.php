<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Envoie le SMS OTP (ou journalise selon le pilote configuré).
     * Ne lance pas d'exception vers le contrôleur : erreurs loguées uniquement.
     */
    public function sendOtp(string $phone, string $code): void
    {
        $message = sprintf(config('sms.otp_message', 'Code : %s'), $code);
        $this->send($phone, $message);
    }

    /**
     * Envoie un SMS à contenu libre (ex : rappel de dette) via le pilote configuré.
     * Retourne true si l'envoi a réussi, pour permettre un retour immédiat à
     * l'utilisateur qui déclenche l'envoi manuellement (contrairement à l'OTP,
     * envoyé en fire-and-forget).
     */
    public function send(string $phone, string $message): bool
    {
        $driver = config('sms.driver', 'whatsapp');

        try {
            return match ($driver) {
                'twilio' => $this->sendTwilio($phone, $message),
                'http' => $this->sendHttp($phone, $message),
                'whatsapp' => $this->sendWhatsapp($phone, $message),
                'nimba' => $this->sendNimba($phone, $message),
                default => $this->sendLog($phone, $message),
            };
        } catch (\Throwable $e) {
            Log::error('SMS échec', ['phone' => $phone, 'error' => $e->getMessage()]);

            return false;
        }
    }

    protected function sendLog(string $phone, string $message): bool
    {
        Log::channel('single')->info('[SMS — mode log, aucun envoi réel]', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return true;
    }

    protected function sendHttp(string $phone, string $message): bool
    {
        $url = config('sms.http.url');
        if (empty($url)) {
            Log::warning('SMS_HTTP_URL manquant, fallback log.');

            return $this->sendLog($phone, $message);
        }

        $body = config('sms.http.body_template', []);
        $payload = [];
        foreach ($body as $key => $value) {
            $payload[$key] = str_replace(
                ['{{phone}}', '{{message}}'],
                [$phone, $message],
                (string) $value
            );
        }

        $method = strtolower((string) config('sms.http.method', 'post'));
        $headers = array_filter((array) config('sms.http.headers', []));
        $timeout = (int) config('sms.http.timeout', 15);

        $request = Http::timeout($timeout)->withHeaders($headers);

        if ($method === 'get') {
            $response = $request->get($url, $payload);
        } else {
            $response = $request->post($url, $payload);
        }

        if (! $response->successful()) {
            Log::error('SMS HTTP erreur', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response->successful();
    }

    protected function sendTwilio(string $phone, string $message): bool
    {
        $sid = config('sms.twilio.sid');
        $token = config('sms.twilio.token');
        $from = config('sms.twilio.from');

        if (empty($sid) || empty($token) || empty($from)) {
            Log::warning('Twilio non configuré (TWILIO_*), fallback log.');

            return $this->sendLog($phone, $message);
        }

        $to = $this->normalizeE164($phone);

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->timeout(20)
            ->post($url, [
                'To' => $to,
                'From' => $from,
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            Log::error('Twilio SMS erreur', ['body' => $response->body()]);
        }

        return $response->successful();
    }

    /**
     * Préfixe +224 si numéro guinéen sans indicatif.
     */
    protected function normalizeE164(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($phone, '+')) {
            return $phone;
        }
        if (strlen($digits) === 9 && str_starts_with($digits, '6')) {
            return '+224'.$digits;
        }

        return str_starts_with($phone, '+') ? $phone : '+'.$digits;
    }

    /**
     * Nimba SMS (api.nimbasms.com) : Basic Auth (service_id, secret_token),
     * POST /v1/messages en form-urlencoded — même contrat que le SDK officiel.
     */
    protected function sendNimba(string $phone, string $message): bool
    {
        $serviceId = config('sms.nimba.service_id');
        $secretToken = config('sms.nimba.secret_token');
        $senderName = config('sms.nimba.sender_name');

        if (!$serviceId || !$secretToken || !$senderName) {
            Log::warning('Nimba SMS non configuré (NIMBA_SERVICE_ID/NIMBA_SECRET_TOKEN/NIMBA_SENDER_NAME).');

            return $this->sendLog($phone, $message);
        }

        $to = $this->normalizeE164($phone);

        $response = Http::withBasicAuth($serviceId, $secretToken)
            ->asForm()
            ->timeout(20)
            ->post('https://api.nimbasms.com/v1/messages', [
                'to' => [$to],
                'sender_name' => $senderName,
                'message' => $message,
            ]);

        Log::info('Nimba SMS Response', [
            'phone' => $to,
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ]);

        if (! $response->successful()) {
            Log::error('Erreur Nimba SMS', ['body' => $response->body()]);
        }

        return $response->successful();
    }

    protected function sendWhatsapp(string $phone, string $message): bool
    {
        $instance = env('ULTRAMSG_INSTANCE');
        $token = env('ULTRAMSG_TOKEN');

        if (!$instance || !$token) {
            Log::warning('UltraMsg non configuré');

            return $this->sendLog($phone, $message);
        }

        $response = Http::asForm()
            ->timeout(20)
            ->post(
                "https://api.ultramsg.com/{$instance}/messages/chat",
                [
                    'token' => $token,
                    'to' => str_replace('+', '', $phone),
                    'body' => $message,
                ]
            );

        Log::info('WhatsApp OTP Response', [
            'phone' => $phone,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);


        if (!$response->successful()) {
            Log::error('Erreur WhatsApp UltraMsg', [
                'body' => $response->body()
            ]);
        }

        return $response->successful();
    }
}
