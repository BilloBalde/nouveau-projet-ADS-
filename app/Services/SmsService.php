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
        $driver = config('sms.driver', 'log');

        try {
            match ($driver) {
                'twilio' => $this->sendTwilio($phone, $message),
                'http' => $this->sendHttp($phone, $message),
                default => $this->sendLog($phone, $message),
            };
        } catch (\Throwable $e) {
            Log::error('SMS OTP échec', ['phone' => $phone, 'error' => $e->getMessage()]);
        }
    }

    protected function sendLog(string $phone, string $message): void
    {
        Log::channel('single')->info('[SMS OTP — mode log, aucun envoi réel]', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }

    protected function sendHttp(string $phone, string $message): void
    {
        $url = config('sms.http.url');
        if (empty($url)) {
            Log::warning('SMS_HTTP_URL manquant, fallback log.');
            $this->sendLog($phone, $message);

            return;
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
    }

    protected function sendTwilio(string $phone, string $message): void
    {
        $sid = config('sms.twilio.sid');
        $token = config('sms.twilio.token');
        $from = config('sms.twilio.from');

        if (empty($sid) || empty($token) || empty($from)) {
            Log::warning('Twilio non configuré (TWILIO_*), fallback log.');
            $this->sendLog($phone, $message);

            return;
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
}
