<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\OrderRequest;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\Payment\OrangeMoneyService;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\NewOrderForAdminNotification;
use App\Models\Product;
use App\Models\User;
use App\Support\PricingTiers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentService $paymentService,
        private OrangeMoneyService $orangeMoney,
    ) {}

    public function buyNow($id, Request $request)
    {
        $product = Product::findOrFail($id);
        $quantity = max(1, (int) $request->query('quantity', 1));
        session(['buy_now' => [['product_id' => $product->id, 'quantity' => $quantity]]]);
        return redirect()->route('checkout');
    }

    public function checkout()
    {
        $user = Auth::user();
        $addresses = $user->addresses;

        $isBuyNow  = session()->has('buy_now');
        $cartItems = $isBuyNow ? session('buy_now') : session()->get('cart', []);

        if (empty($cartItems)) {
            return redirect()->route('shop.home')->with('error', 'Votre panier est vide.');
        }

        $products = Product::whereIn('id', array_column($cartItems, 'product_id'))->get();
        $total = 0;
        foreach ($products as $product) {
            foreach ($cartItems as $item) {
                if ($item['product_id'] == $product->id) {
                    $basePrice = $product->effective_promo_price ?? $product->price;
                    $total += PricingTiers::unitPriceFor($basePrice, $item['quantity']) * $item['quantity'];
                }
            }
        }

        return view('ecommerce.checkout', compact('addresses', 'cartItems', 'products', 'total', 'isBuyNow'));
    }

    public function store(OrderRequest $request)
    {
        $order = $this->orderService->createOrder(
            Auth::id(),
            $request->delivery_address_id,
            $request->items,
            $request->payment_method
        );

        // Notification de commande passée
        Auth::user()->notify(new OrderPlacedNotification($order));

        // Alerter les administrateurs/managers qu'une commande attend validation
        $admins = User::whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_MANAGER])->get();
        Notification::send($admins, new NewOrderForAdminNotification($order));

        // Vider le panier ou la session buy_now selon le cas
        $request->boolean('is_buy_now')
            ? session()->forget('buy_now')
            : session()->forget('cart');

        // Orange Money → initier le paiement et rediriger vers la page Orange
        if ($request->payment_method === 'orange_money') {
            $result = $this->orangeMoney->initiatePayment(
                amount:  (int) $order->total_amount,
                orderId: 'FBK-' . $order->id,
                user:    Auth::user(),
            );

            if (!$result['success']) {
                Log::error('[Checkout] Échec initiation Orange Money', [
                    'order_id' => $order->id,
                    'error'    => $result['error'],
                ]);

                return redirect()->route('orders.show', $order->id)
                    ->with('error', 'Commande créée mais le paiement Orange Money a échoué : ' . $result['error'] . ' — Réessayez depuis vos commandes.');
            }

            // Sauvegarder le pay_token pour retrouver la commande dans le webhook
            $order->update(['transaction_id' => $result['pay_token']]);

            // Redirection vers la page de paiement Orange Money
            return redirect()->away($result['payment_url']);
        }

        // Paiement à la livraison → confirmation classique
        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Votre commande a été confirmée. Notre équipe vous contactera bientôt.');
    }

    public function index()
    {
        $orders = Auth::user()->orders()->with('items.product')->latest()->get();
        return view('ecommerce.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Auth::user()->orders()->with('items.product', 'address', 'facture.paiements')->findOrFail($id);
        return view('ecommerce.orders.show', compact('order'));
    }
}
