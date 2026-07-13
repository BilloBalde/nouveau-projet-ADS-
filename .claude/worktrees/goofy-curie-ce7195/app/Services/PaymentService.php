<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class PaymentService
{
    public function initializeOrangeMoney(Order $order)
    {
        // Mocking Orange Money API Call
        // En prod: appel API Orange avec token, auth_header, etc.
        
        $transactionId = "OM-" . strtoupper(bin2hex(random_bytes(6)));
        
        $order->update([
            'transaction_id' => $transactionId
        ]);

        return [
            'status' => 'success',
            'payment_url' => "https://api.orange.com/pay-mock?id=$transactionId",
            'transaction_id' => $transactionId
        ];
    }

    public function handleWebhook(array $payload)
    {
        $transactionId = $payload['transaction_id'];
        $status = $payload['status']; // SUCCESS, FAILED

        $order = Order::where('transaction_id', $transactionId)->first();

        if ($order && $status === 'SUCCESS') {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing'
            ]);
            return true;
        }

        return false;
    }
}
