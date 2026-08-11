<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CurrencySetting;
use App\Models\Place;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\User;
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

    /**
     * "PurchaseOrder::count() + 1" se déraille dès qu'une commande est supprimée : le
     * compte total baisse, le prochain numéro généré retombe sur une référence déjà
     * prise par une commande existante, et la validation "unique" la rejette ("cette
     * référence existe déjà"). On se base à la place sur le plus grand numéro déjà
     * utilisé aujourd'hui, immunisé contre les trous laissés par des suppressions.
     */
    private function nextReference(): string
    {
        $prefix = 'PO-' . Carbon::now()->format('Ymd') . '-';

        $lastNumber = PurchaseOrder::where('reference', 'like', $prefix . '%')
            ->pluck('reference')
            ->map(fn ($reference) => (int) substr($reference, strlen($prefix)))
            ->max() ?? 0;

        return $prefix . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * "reçu" seulement si la date de réception est passée (ou aujourd'hui) — avant,
     * le statut était figé à "received" dès la création, même quand la marchandise
     * n'était pas encore arrivée. Recalculé aussi à la modification, pour rester
     * cohérent quand la date de réception change.
     */
    private function resolveStatus(?string $dateRecu): string
    {
        if (! $dateRecu) {
            return 'pending';
        }

        return Carbon::parse($dateRecu)->lte(Carbon::today()) ? 'received' : 'pending';
    }

    public function create()
    {
        $stores = Store::all();
        $products = Product::orderBy('libelle')->get(['id', 'libelle', 'sku']);
        $currencies = CurrencySetting::whereNotNull('rate_to_gnf')->where('currencyCode', '!=', 'GNF')->get();
        $reference = $this->nextReference();
        // Pour les modales "Nouvelle boutique" / "Nouveau produit" du formulaire.
        $places = Place::all();
        $storeManagers = User::where('id', '>', 2)->get();
        $categories = Category::orderBy('category_type')->get();

        return view('purchase_orders.create', compact('stores', 'products', 'currencies', 'reference', 'places', 'storeManagers', 'categories'));
    }

    public function store(Request $request, PurchaseOrderCostingService $costingService)
    {
        $validated = $request->validate([
            'reference' => 'required|string|unique:purchase_orders,reference',
            'store_id' => 'required|exists:stores,id',
            'origin' => 'required|in:chine,guinee',
            // La table s'appelle `currency_settings` (pas `currencies` : ce nom n'existe
            // pas en base et faisait planter la validation dès qu'une devise était
            // choisie, avec une SQLSTATE "table doesn't exist").
            'currency_code' => 'nullable|string|exists:currency_settings,currencyCode',
            // Obligatoire dès qu'une devise est choisie : le taux n'est plus pré-rempli
            // automatiquement côté formulaire, l'utilisateur doit le saisir lui-même.
            'exchange_rate_used' => 'nullable|required_with:currency_code|numeric|min:0',
            'transport_cost_gnf' => 'nullable|numeric|min:0',
            'customs_cost_gnf' => 'nullable|numeric|min:0',
            'other_fees_gnf' => 'nullable|numeric|min:0',
            'date_emis' => 'required|date',
            'date_recu' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.unit_price_foreign' => 'nullable|numeric|min:0',
            'lines.*.cbm_per_carton' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
            'lines.*.cartons' => 'nullable|integer|min:0',
            'lines.*.pieces_per_carton' => 'nullable|integer|min:0',
            'lines.*.quantity' => 'required|integer|min:1',
        ], [
            'reference.unique' => 'Cette référence existe déjà.',
            'exchange_rate_used.required_with' => 'Le taux du jour est obligatoire quand une devise est sélectionnée.',
        ]);

        $order = PurchaseOrder::create([
            'reference' => $validated['reference'],
            'store_id' => $validated['store_id'],
            'origin' => $validated['origin'],
            'currency_code' => $validated['origin'] === 'chine' ? $validated['currency_code'] : null,
            // 'exchange_rate_used' => $validated['origin'] === 'chine' ? $validated['exchange_rate_used'] : null,
            'exchange_rate_used' => $validated['exchange_rate_used'] ?? 1,   // taux par défaut 1
            'transport_cost_gnf' => $validated['transport_cost_gnf'] ?? 0,
            'customs_cost_gnf' => $validated['customs_cost_gnf'] ?? 0,
            'other_fees_gnf' => $validated['other_fees_gnf'] ?? 0,
            'status' => $this->resolveStatus($validated['date_recu'] ?? null),
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
        // Pour les modales "Nouvelle boutique" / "Nouveau produit" du formulaire.
        $places = Place::all();
        $storeManagers = User::where('id', '>', 2)->get();
        $categories = Category::orderBy('category_type')->get();

        return view('purchase_orders.edit', compact('order', 'stores', 'products', 'currencies', 'places', 'storeManagers', 'categories'));
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
            'currency_code' => 'nullable|string|exists:currency_settings,currencyCode',
            // Obligatoire dès qu'une devise est choisie : le taux n'est plus pré-rempli
            // automatiquement côté formulaire, l'utilisateur doit le saisir lui-même.
            'exchange_rate_used' => 'nullable|required_with:currency_code|numeric|min:0',
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
            'lines.*.cbm_per_carton' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string',
            'lines.*.cartons' => 'nullable|integer|min:0',
            'lines.*.pieces_per_carton' => 'nullable|integer|min:0',
        ], [
            'exchange_rate_used.required_with' => 'Le taux du jour est obligatoire quand une devise est sélectionnée.',
        ]);

        $order->update([
            'store_id' => $validated['store_id'],
            'origin' => $validated['origin'],
            'currency_code' => $validated['origin'] === 'chine' ? $validated['currency_code'] : null,
            ///'exchange_rate_used' => $validated['origin'] === 'chine' ? $validated['exchange_rate_used'] : null,
            'exchange_rate_used' => $validated['exchange_rate_used'] ?? 1,
            'transport_cost_gnf' => $validated['transport_cost_gnf'] ?? 0,
            'customs_cost_gnf' => $validated['customs_cost_gnf'] ?? 0,
            'other_fees_gnf' => $validated['other_fees_gnf'] ?? 0,
            'date_emis' => $validated['date_emis'],
            'date_recu' => $validated['date_recu'] ?? null,
            'status' => $this->resolveStatus($validated['date_recu'] ?? null),
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
