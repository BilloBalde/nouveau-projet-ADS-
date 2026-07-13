<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\PricingTiers;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder($userId, $addressId, array $items, $paymentMethod)
    {
        return DB::transaction(function () use ($userId, $addressId, $items, $paymentMethod) {
            $totalAmount = 0;

            // Calculer le total et vérifier le stock (optionnel selon besoin)
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $basePrice = $product->effective_promo_price ?? $product->price;
                $totalAmount += PricingTiers::unitPriceFor($basePrice, $item['quantity']) * $item['quantity'];
            }

            $order = Order::create([
                'user_id' => $userId,
                'delivery_address_id' => $addressId,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
            ]);

            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                $basePrice = $product->effective_promo_price ?? $product->price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => PricingTiers::unitPriceFor($basePrice, $item['quantity']),
                ]);
            }

            return $order->load('items.product');
        });
    }
}
