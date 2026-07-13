<?php

namespace App\Http\Controllers;

use App\Models\CurrencySetting;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Services\PurchaseOrderCostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PurchaseOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['store', 'items']);

        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->input('reference') . '%');
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->filled('origin')) {
            $query->where('origin', $request->input('origin'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $dataTable = $query->latest()->paginate(20)->appends($request->query());
        $stores = Store::all();

        return view('purchase_orders.index', compact('dataTable', 'stores'));
    }

    public function create()
    {
        $stores = Store::all();
        $products = Product::orderBy('libelle')->get(['id', 'libelle', 'sku']);
        $currencies = CurrencySetting::whereNotNull('rate_to_gnf')->where('currencyCode', '!=', 'GNF')->get();
        $reference = 'PO-' . Carbon::now()->format('Ymd') . '-' . str_pad((string) (PurchaseOrder::count() + 1), 4, '0', STR_PAD_LEFT);

        return view('purchase_orders.create', compact('stores', 'products', 'currencies', 'reference'));
    }

    public function store(Request $request, PurchaseOrderCostingService $costingService)
    {
        $validated = $request->validate([
            'reference' => 'required|string|unique:purchase_orders,reference',
            'store_id' => 'required|exists:stores,id',
            'origin' => 'required|in:chine,guinee',
            'currency_code' => 'nullable|required_if:origin,chine|string',
            'exchange_rate_used' => 'nullable|required_if:origin,chine|numeric|min:0',
            'transport_cost_gnf' => 'nullable|numeric|min:0',
            'customs_cost_gnf' => 'nullable|numeric|min:0',
            'other_fees_gnf' => 'nullable|numeric|min:0',
            'date_emis' => 'required|date',
            'date_recu' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price_foreign' => 'nullable|numeric|min:0',
            'lines.*.cbm_per_unit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ], [
            'reference.unique' => 'Cette référence existe déjà.',
            'currency_code.required_if' => 'La devise est obligatoire pour un achat en Chine.',
            'exchange_rate_used.required_if' => 'Le taux de change est obligatoire pour un achat en Chine.',
        ]);

        $order = PurchaseOrder::create([
            'reference' => $validated['reference'],
            'store_id' => $validated['store_id'],
            'origin' => $validated['origin'],
            'currency_code' => $validated['origin'] === 'chine' ? $validated['currency_code'] : null,
            'exchange_rate_used' => $validated['origin'] === 'chine' ? $validated['exchange_rate_used'] : null,
            'transport_cost_gnf' => $validated['transport_cost_gnf'] ?? 0,
            'customs_cost_gnf' => $validated['customs_cost_gnf'] ?? 0,
            'other_fees_gnf' => $validated['other_fees_gnf'] ?? 0,
            'status' => 'received',
            'date_emis' => $validated['date_emis'],
            'date_recu' => $validated['date_recu'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $costingService->costOrder($order, $validated['lines']);

        return redirect()->route('purchase-orders.show', $order->id)
            ->with('success', 'Achat enregistré, coût de revient et stock mis à jour avec succès.');
    }

    public function show($id)
    {
        $order = PurchaseOrder::with(['store', 'items.product'])->findOrFail($id);

        return view('purchase_orders.show', compact('order'));
    }

    public function edit($id)
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);
        $stores = Store::all();
        $products = Product::orderBy('libelle')->get(['id', 'libelle', 'sku']);
        $currencies = CurrencySetting::whereNotNull('rate_to_gnf')->where('currencyCode', '!=', 'GNF')->get();

        return view('purchase_orders.edit', compact('order', 'stores', 'products', 'currencies'));
    }

    public function update(Request $request, $id, PurchaseOrderCostingService $costingService)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cet achat est annulé, il ne peut plus être modifié.');
        }

        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'origin' => 'required|in:chine,guinee',
            'currency_code' => 'nullable|required_if:origin,chine|string',
            'exchange_rate_used' => 'nullable|required_if:origin,chine|numeric|min:0',
            'transport_cost_gnf' => 'nullable|numeric|min:0',
            'customs_cost_gnf' => 'nullable|numeric|min:0',
            'other_fees_gnf' => 'nullable|numeric|min:0',
            'date_emis' => 'required|date',
            'date_recu' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|integer|min:1',
            'lines.*.unit_price_foreign' => 'nullable|numeric|min:0',
            'lines.*.cbm_per_unit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ]);

        $order->update([
            'store_id' => $validated['store_id'],
            'origin' => $validated['origin'],
            'currency_code' => $validated['origin'] === 'chine' ? $validated['currency_code'] : null,
            'exchange_rate_used' => $validated['origin'] === 'chine' ? $validated['exchange_rate_used'] : null,
            'transport_cost_gnf' => $validated['transport_cost_gnf'] ?? 0,
            'customs_cost_gnf' => $validated['customs_cost_gnf'] ?? 0,
            'other_fees_gnf' => $validated['other_fees_gnf'] ?? 0,
            'date_emis' => $validated['date_emis'],
            'date_recu' => $validated['date_recu'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $costingService->costOrder($order, $validated['lines']);

        return redirect()->route('purchase-orders.show', $order->id)
            ->with('success', 'Achat mis à jour avec succès.');
    }

    public function destroy($id)
    {
        $order = PurchaseOrder::with('items')->findOrFail($id);

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Cet achat est déjà annulé.');
        }

        \DB::transaction(function () use ($order) {
            // Retire du stock exactement ce que cet achat y avait ajouté.
            // (Le prix de revient du produit n'est pas "annulé" — s'il a été mis à jour
            // par un achat plus récent depuis, revenir en arrière serait incorrect.)
            foreach ($order->items as $item) {
                \App\Models\StoreProduct::where('store_id', $order->store_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('quantity', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
        });

        return redirect()->route('purchase-orders.index')->with('success', 'Achat annulé, stock ajusté en conséquence.');
    }
}
