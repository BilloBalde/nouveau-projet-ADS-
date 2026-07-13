<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Facture;
use App\Models\CatalogueCustomer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerOrderController extends Controller
{
    private StockService $stock;

    public function __construct(StockService $stock)
    {
        $this->middleware('auth.check')->except(['create', 'store']);
        $this->stock = $stock;
    }

    public function create()
    {
        $categories = Category::all();
        $stores = Store::all();
        $selectedStoreId = request()->query('store_id') ?? $stores->first()?->id;

        $productsQuery = Product::with(['categories', 'latestLigneCommande']);
        if (!empty($selectedStoreId)) {
            $productsQuery->whereHas('stores', function ($q) use ($selectedStoreId) {
                $q->where('stores.id', $selectedStoreId)
                    ->where('store_products.quantity', '>', 0);
            });
        }
        $products = $productsQuery->get();

        $selectedStore = $selectedStoreId ? Store::find($selectedStoreId) : null;

        return view('visitor.storefront', [
            'categories' => $categories,
            'products' => $products,
            'stores' => $stores,
            'selectedStore' => $selectedStore,
            'pageName' => 'storefront-page',
        ]);
    }

    public function store(Request $request)
    {
        $isCatalogueAuthed = Auth::guard('catalogue')->check();
        $rules = [
            'store_id' => 'required|exists:stores,id',
            'customer_name' => $isCatalogueAuthed ? 'nullable|string|max:255' : 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            // quantity is canonical (PCS). We accept big numbers; cartons mode is handled client-side + verified here.
            'items.*.quantity' => 'required|integer|min:1',
        ];
        $validated = $request->validate($rules);

        $items = array_values(array_filter($request->items ?? [], function ($item) {
            return isset($item['product_id'], $item['quantity']) && (int) $item['quantity'] > 0;
        }));

        if (empty($items)) {
            return redirect()->back()->with('error', 'Veuillez ajouter au moins un produit.')->withInput();
        }

        DB::beginTransaction();
        try {
            $catalogueCustomerId = null;
            if ($isCatalogueAuthed) {
                $catalogueCustomerId = Auth::guard('catalogue')->id();
            }

            $customerName = $request->customer_name;
            $phone = $request->phone;
            $address = $request->address;
            if ($isCatalogueAuthed) {
                /** @var CatalogueCustomer $cc */
                $cc = Auth::guard('catalogue')->user();
                $customerName = $customerName ?: $cc->name;
                $phone = $phone ?: $cc->phone;
                $address = $address ?: $cc->address;
            }

            $order = CustomerOrder::create([
                'store_id' => $request->store_id,
                'catalogue_customer_id' => $catalogueCustomerId,
                'customer_name' => $customerName,
                'phone' => $phone,
                'address' => $address,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            $totalAmount = 0;
            foreach ($items as $item) {
                $product = Product::with('latestLigneCommande')->find($item['product_id']);
                $unitPrice = (float) ($product?->latestLigneCommande?->unit_price_sale ?? 0);
                // Keep server authoritative: ensure quantity >= 1
                $quantity = max(1, (int) $item['quantity']);
                $lineTotal = $unitPrice * $quantity;

                CustomerOrderItem::create([
                    'customer_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                ]);
                $totalAmount += $lineTotal;
            }

            $order->update(['total_amount' => $totalAmount]);

            DB::commit();

            return redirect()->back()->with('success', 'Votre commande a été enregistrée. Le vendeur vous contactera pour confirmer.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }
    }

    public function index()
    {
        $orders = CustomerOrder::with(['items.product', 'store'])->latest()->get();
        $userStoreId = Auth::check() && Auth::user()->role_id == 3
            ? Store::where('user_id', Auth::user()->id)->value('id')
            : null;
        $stores = Store::all();

        return view('orders.index', compact('orders', 'stores', 'userStoreId'));
    }

    public function show(CustomerOrder $order)
    {
        $order->load(['items.product', 'store']);
        $userStoreId = Auth::check() && Auth::user()->role_id == 3
            ? Store::where('user_id', Auth::user()->id)->value('id')
            : null;
        $stores = Store::all();

        return view('orders.show', compact('order', 'stores', 'userStoreId'));
    }

    public function updateStatus(Request $request, CustomerOrder $order)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,paid,cancelled',
        ]);

        $newStatus = $request->input('status');

        // If confirming: convert customer order into a real POS sale (Sale + Facture + Payment + stock decrement)
        if ($newStatus === 'confirmed' && $order->status === 'pending') {
            $storeId = null;
            // Prefer the store selected by the customer on the catalogue order
            if (!empty($order->store_id)) {
                $storeId = $order->store_id;
            } elseif (Auth::check() && Auth::user()->role_id == 3) {
                $storeId = Store::where('user_id', Auth::user()->id)->value('id');
            } else {
                $storeId = $request->input('store_id');
            }

            if (empty($storeId) || !Store::whereKey($storeId)->exists()) {
                return redirect()->back()->with('error', 'Veuillez sélectionner un magasin pour confirmer cette commande.');
            }

            // basic anti-double conversion guard
            if (Facture::where('notes', 'like', '%Commande web #' . $order->id . '%')->exists()) {
                $order->update(['status' => 'confirmed']);
                return redirect()->back()->with('success', 'Commande déjà convertie en vente.');
            }

            DB::beginTransaction();
            try {
                $order->load('items.product.latestLigneCommande');

                // Find or create a Customer record for POS sale
                $customer = null;
                if (!empty($order->phone)) {
                    $customer = Customer::where('tel', $order->phone)->first();
                }
                if (!$customer) {
                    $baseMark = 'WEB' . date('ymd') . str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);
                    $mark = $baseMark;
                    $i = 0;
                    while (Customer::where('mark', $mark)->exists() && $i < 50) {
                        $i++;
                        $mark = $baseMark . '-' . $i;
                    }

                    $customer = Customer::create([
                        'customerName' => $order->customer_name,
                        'mark' => $mark,
                        'tel' => $order->phone ?? 'N/A',
                        'address' => $order->address ?? 'N/A',
                        'email' => null,
                        'fidelite' => 1,
                    ]);
                }

                $countFactures = (Facture::max('id') ?? 0) + 1;
                $numeroFacture = date('Ym') . '' . sprintf("%04d", $countFactures);

                $totalQuantity = 0;
                $totalPrice = 0;

                foreach ($order->items as $item) {
                    $product = $item->product;
                    if (!$product) {
                        throw new \RuntimeException('Produit introuvable pour un article de commande.');
                    }

                    $qty = (int) $item->quantity;
                    if ($qty < 1) $qty = 1;

                    $unitPrice = (float) $item->unit_price;
                    if ($unitPrice <= 0) {
                        // fallback to current sale price
                        $unitPrice = (float) ($product->latestLigneCommande?->unit_price_sale ?? 0);
                    }

                    // Atomic decrement to avoid overselling under concurrency
                    if (!$this->stock->decrementIfAvailable((int) $storeId, (int) $product->id, (int) $qty)) {
                        throw new \RuntimeException('Stock insuffisant pour le produit: ' . ($product->libelle ?? $product->id));
                    }

                    $unitCost = $this->resolveUnitCost($product);
                    $lineTotal = $unitPrice * $qty;
                    $interet = ($unitPrice - $unitCost) * $qty;

                    Sale::create([
                        'numeroFacture' => $numeroFacture,
                        'product_id' => $product->id,
                        'prix' => $unitPrice,
                        'quantity' => $qty,
                        'prixTotal' => $lineTotal,
                        'interet' => $interet,
                        'store_id' => $storeId,
                        'user_id' => auth()->id(),
                    ]);

                    $totalQuantity += $qty;
                    $totalPrice += $lineTotal;
                }

                $notes = 'Commande web #' . $order->id;
                if (!empty($order->notes)) {
                    $notes .= ' - ' . Str::limit($order->notes, 180);
                }

                $facture = Facture::create([
                    'numero_facture' => $numeroFacture,
                    'store_id' => $storeId,
                    'customer_id' => $customer->id,
                    'montant_total' => $totalPrice,
                    'quantity' => $totalQuantity,
                    'avance' => 0,
                    'notes' => $notes,
                    'reste' => $totalPrice,
                    'statut' => 'non payé',
                    'livraison' => 'non livré',
                ]);

                $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);
                Payment::create([
                    'facture_id' => $facture->id,
                    'receipt_number' => $receiptNumber,
                    'versement' => 0,
                    'total_paye' => 0,
                    'reste' => $totalPrice,
                    'paid_by' => 'cash',
                    'note' => 'Commande web confirmée et convertie en vente (facture #' . $numeroFacture . ').',
                ]);

                $order->update(['status' => 'confirmed']);
                DB::commit();

                return redirect()->route('sales.index')->with('success', 'Commande confirmée: vente créée (facture #' . $numeroFacture . ').');
            } catch (\Throwable $th) {
                DB::rollBack();
                return redirect()->back()->with('error', $th->getMessage());
            }
        }

        $order->update(['status' => $newStatus]);

        return redirect()->back()->with('success', 'Statut mis à jour.');
    }

    private function resolveUnitCost(Product $product): float
    {
        $latest = $product->latestLigneCommande;
        if ($latest && $latest->unit_price_purchase !== null) {
            return (float) $latest->unit_price_purchase;
        }
        if ($latest && $latest->quantity > 0 && $latest->total_price_purchase !== null) {
            return (float) ($latest->total_price_purchase / $latest->quantity);
        }
        $purchasePrice = Purchase::where('product_id', $product->id)->latest()->value('price');
        return $purchasePrice !== null ? (float) $purchasePrice : 0.0;
    }
}
