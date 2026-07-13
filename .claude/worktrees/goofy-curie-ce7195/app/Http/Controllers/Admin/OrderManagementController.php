<?php
// app/Http/Controllers/Admin/OrderManagementController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Facture;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Notifications\OrderStatusNotification;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderManagementController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    // Affiche la liste des commandes en attente

    public function index()
    {
        $orders = Order::with(['user', 'items.product'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }

    public function stockCheck(Order $order, Request $request)
    {
        $order->load('items.product');

        $items = [];
        $allOk = true;

        foreach ($order->items as $item) {
            $product   = $item->product;
            $available = $this->stock->totalAvailable($item->product_id);
            $ok        = $available >= $item->quantity;

            $items[] = [
                'name'      => $product->libelle ?? 'Produit #' . $product->id,
                'requested' => $item->quantity,
                'available' => $available,
                'ok'        => $ok,
            ];
            if (!$ok) $allOk = false;
        }

        return response()->json(['all_ok' => $allOk, 'items' => $items]);
    }
    // Affiche toutes les ventes confirmées par le manager
    public function confirmed(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'facture', 'sales'])
            ->whereIn('status', ['approved', 'delivering', 'delivered'])
            ->latest();

        if ($request->filled('order_id')) {
            $query->where('id', $request->order_id);
        }
        if ($request->filled('client')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%'.$request->client.'%'));
        }
        if ($request->filled('payment')) {
            $query->whereHas('facture', fn($q) => $q->where('statut', $request->payment));
        }

        $orders = $query->paginate(15);
        return view('admin.orders.confirmed', compact('orders'));
    }

  

    // Rejette une commande
    public function reject(Order $order)
    {
        if ($order->status !== 'pending') {

            if (request()->ajax()) {
                return response()->json(['error' => 'Déjà traitée'], 400);
            }

            return redirect()->back()->with('error', 'Cette commande a déjà été traitée.');
        }

        $order->update(['status' => 'rejected']);

        $order->user?->notify(new OrderStatusNotification($order, 'rejected'));

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.orders.index')->with('success', 'Commande rejetée.');
    }

    public function approve(Order $order, Request $request)
    {
        if ($order->status !== 'pending') {
            return $this->approveFailure($request, 'Commande déjà traitée.');
        }

        $storeId = $request->input('store_id');
        if (!$storeId) {
            return $this->approveFailure($request, 'Veuillez sélectionner une boutique.');
        }

        $orderItems = $order->items;
        if ($orderItems->isEmpty()) {
            return $this->approveFailure($request, 'Cette commande ne contient aucun article.');
        }

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT);

        DB::beginTransaction();

        try {
            // Décrémentation du stock (boutique + global) et création des lignes de vente
            $totalQuantity = 0;
            $totalInteret  = 0;

            foreach ($orderItems as $item) {
                $qty = $item->quantity;
                $product = $item->product ?? \App\Models\Product::find($item->product_id);

                if (! $this->stock->decrementProductStockIfAvailable($item->product_id, $qty)) {
                    $available = $this->stock->totalAvailable($item->product_id);
                    throw new \Exception(
                        "Stock insuffisant pour « {$product?->libelle} » (dispo: {$available}, demandé: {$qty})"
                    );
                }

                // Intérêt basé sur le dernier prix d'achat
                $lastPurchase = Purchase::where('product_id', $item->product_id)->latest()->first();
                $prixAchat    = $lastPurchase ? $lastPurchase->price : 0;
                $interet      = ($item->price - $prixAchat) * $qty;
                $totalInteret += $interet;

                Sale::create([
                    'numeroFacture' => $invoiceNumber,
                    'product_id'    => $item->product_id,
                    'store_id'      => $storeId,
                    'quantity'      => $qty,
                    'prix'          => $item->price,
                    'prixTotal'     => $qty * $item->price,
                    'interet'       => $interet,
                    'user_id'       => auth()->id(),
                ]);
                $totalQuantity += $qty;
            }

            // Mettre à jour le solde de la boutique (intérêts e-commerce)
            if ($totalInteret != 0) {
                \App\Models\Store::where('id', $storeId)->increment('balance', $totalInteret);
            }

            // Création client, facture, paiement... (gardez votre code existant)
            $user = $order->user;
            $customer = Customer::firstOrCreate(
                ['email' => $user->email],
                [
                    'mark'         => strtoupper(substr($user->name, 0, 3)) . $user->id,
                    'customerName' => $user->name,
                    'tel'          => $user->phone ?? 'N/A',
                    'address'      => $order->address?->full_address ?? 'N/A',
                    'email'        => $user->email,
                ]
            );

            $isPaid = $order->payment_status === 'paid';
            $avance = $isPaid ? $order->total_amount : 0;
            $reste = $order->total_amount - $avance;
            $statut = $isPaid ? 'payé' : 'non payé';
            $paidBy = $order->payment_method === 'orange_money' ? 'orange money' : 'cash';

            $facture = Facture::create([
                'numero_facture' => $invoiceNumber,
                'customer_id'    => $customer->id,
                'store_id'       => $storeId,
                'quantity'       => $totalQuantity,
                'montant_total'  => $order->total_amount,
                'avance'         => $avance,
                'reste'          => $reste,
                'statut'         => $statut,
                'livraison'      => 'non livré',
                'notes'          => 'Commande e-commerce #' . $order->id,
            ]);

            if ($avance > 0) {
                Payment::create([
                    'facture_id' => $facture->id,
                    'versement'  => $avance,
                    'total_paye' => $avance,
                    'reste'      => 0,
                    'paid_by'    => $paidBy,
                    'note'       => 'Paiement ' . $order->payment_method . ' – Transaction: ' . ($order->transaction_id ?? 'N/A'),
                ]);
            }

            $order->update([
                'status' => 'approved',
                'invoice_number' => $invoiceNumber,
            ]);

            DB::commit();

            $order->user?->notify(new OrderStatusNotification($order, 'approved'));

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true]);
            }
            return redirect()->route('admin.orders.index')->with('success', 'Commande approuvée, vente enregistrée.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->approveFailure($request, 'Erreur : ' . $e->getMessage());
        }
    }

    private function approveFailure(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }
        return redirect()->back()->with('error', $message);
    }

    /**
     * Récupère les articles d'une facture (pour livraison partielle)
     */
    public function getFactureItems(Facture $facture)
    {
        $order = $facture->order;

        if (!$order) {
            return response()->json(['error' => 'Commande introuvable pour cette facture.'], 404);
        }

        $order->load('items.product');

        $items = $order->items->map(fn($item) => [
            'id'                 => $item->id,
            'product_name'       => $item->product->libelle ?? 'Produit #' . $item->product_id,
            'quantity'           => $item->quantity,
            'quantity_delivered' => $item->quantity_delivered ?? 0,
            'quantity_remaining' => max(0, $item->quantity - ($item->quantity_delivered ?? 0)),
        ]);

        return response()->json($items);
    }

    /**
     * Enregistre une livraison partielle
     */
    public function partialDeliver(Request $request, Facture $facture)
    {
        $order = $facture->order;

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Commande introuvable.'], 404);
        }

        try {
            $quantities = $request->input('quantities', []);

            foreach ($quantities as $itemId => $newQty) {
                $item = OrderItem::where('id', $itemId)
                    ->where('order_id', $order->id)
                    ->first();

                if (!$item) continue;

                // Additionner la nouvelle livraison à ce qui était déjà livré, sans dépasser le total commandé
                $item->quantity_delivered = min(
                    $item->quantity_delivered + (int) $newQty,
                    $item->quantity
                );
                $item->save();
            }

            // Requête fraîche après les mises à jour (évite le cache Eloquent)
            $totalDelivered = $order->items()->sum('quantity_delivered');
            $totalOrdered   = $order->items()->sum('quantity');

            if ($totalOrdered > 0 && $totalDelivered >= $totalOrdered) {
                $facture->update(['livraison' => 'livré']);

                if ($order->status !== 'delivered') {
                    $order->update(['status' => 'delivered']);
                    $order->user?->notify(new OrderStatusNotification($order, 'delivered'));
                }
            } elseif ($totalDelivered > 0) {
                $facture->update(['livraison' => 'partiellement livré']);

                // Notifier "en cours de livraison" une seule fois (le statut sert de garde)
                if (! in_array($order->status, ['delivering', 'delivered'])) {
                    $order->update(['status' => 'delivering']);
                    $order->user?->notify(new OrderStatusNotification($order, 'delivering'));
                }
            } else {
                $facture->update(['livraison' => 'non livré']);
            }

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Liste des factures générées par approbation de commandes e-commerce
     */
    public function factures(Request $request)
    {
        $query = Facture::with(['customer', 'store', 'paiements', 'order.user', 'order.items.product'])
            ->where('numero_facture', 'like', 'INV-%')
            ->latest();

        if ($request->filled('numero_facture')) {
            $query->where('numero_facture', 'like', '%' . $request->numero_facture . '%');
        }
        if ($request->filled('client')) {
            $query->whereHas('order.user', fn($q) => $q->where('name', 'like', '%' . $request->client . '%'));
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('livraison')) {
            $query->where('livraison', $request->livraison);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $factures = $query->paginate(15);
        return view('admin.orders.factures', compact('factures'));
    }

    /**
     * Afficher les détails d'une commande spécifique
     */
    public function show(Order $order)
    {
        // Charge les relations nécessaires pour la vue détail
        $order->load(['user', 'items.product', 'address', 'facture']);

        return view('admin.orders.show', compact('order'));
    }

}