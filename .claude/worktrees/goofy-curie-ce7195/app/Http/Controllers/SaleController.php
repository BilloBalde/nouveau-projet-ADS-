<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Facture;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StoreProduct;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Purchase;
use App\Models\Achat;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Exports\SalesExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class SaleController extends Controller
{
    private StockService $stock;

    public function __construct(StockService $stock)
    {
        $this->middleware('auth.check');
        $this->stock = $stock;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $produits = Product::all();
        if (auth()->user()->role_id == 3) {
            $boutiques = Store::where('user_id', auth()->user()->id)->get();
            $employees = User::where('store_id', Store::where('user_id', auth()->user()->id)->value('id'))->get();
        } else {
            $boutiques = Store::all();
            $employees = User::whereNotNull('store_id')->get();
        }

        $query = Sale::query();

        if ($request->filled('numeroFacture')) {
            $query->where('numeroFacture', 'like', '%' . $request->input('numeroFacture') . '%');
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', 'like', '%' . $request->input('product_id') . '%');
        }

        if ($request->filled('created_at')) {
            $query->where('created_at', $request->input('created_at'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Pagination serveur + eager loading : on ne charge plus (ni ne réécrit)
        // toute la table à chaque affichage — l'intérêt stocké fait foi.
        $dataTable = $query->with(['product.latestLigneCommande', 'user', 'facture'])
            ->latest()
            ->paginate(50)
            ->appends($request->query());

        // Pass the necessary data to the view, including options for filters
        $customers = Customer::all();
        return view('sales.index', compact('dataTable', 'produits','customers','boutiques','employees'));
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'customerName' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            $customer = Customer::create([
                'customerName' => $request->customerName,
                'mark' => $request->mark ?? 'AMD',
                'tel' => $request->tel ?? '17288399',
                'email' => $request->email ?? 'test@email.com',
                'address' => $request->address ?? 'kobaya',
                'fidelite' => 1
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Customer creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('sales.create');
    }

    public function ajout($numero_facture, $avance, $store_id)
    {
        $produits = Product::all();
        //dd($numero_facture);
        return view('sales.create', compact('numero_facture', 'produits', 'avance', 'store_id'));
    }

    public function voirSales($numero_facture){
        //dd('Here i am');
        $ligneVentes = Sale::where('numeroFacture', $numero_facture)->get();
        return view('sales.voirSales', compact('ligneVentes', 'numero_facture'));
    }

    /**
     * Export sales to Excel
     */
    public function exportExcel(Request $request)
    {
        $query = Sale::with('product');

        // Apply filters
        if ($request->filled('numeroFacture')) {
            $query->where('numeroFacture', 'like', '%' . $request->numeroFacture . '%');
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        // Role-based filtering
        if (auth()->user()->role_id == 3) {
            $storeId = Store::where('user_id', auth()->user()->id)->value('id');
            $query->where('store_id', $storeId);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        $filename = 'ventes_' . now()->format('Y-m-d_His') . '.xlsx';
        
        return Excel::download(new SalesExport($request, $sales), $filename);
    }

    /**
     * Export sales to PDF
     */
    public function exportPDF(Request $request)
    {
        // Garde-fou mémoire : ce rapport peut couvrir des milliers de ventes
        // ("Toutes les ventes") et Dompdf est gourmand en RAM sur les gros tableaux.
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 300);

        $query = Sale::with('product');

        // Apply filters
        if ($request->filled('numeroFacture')) {
            $query->where('numeroFacture', 'like', '%' . $request->numeroFacture . '%');
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        // Sans aucun filtre, se limiter au mois en cours : Dompdf ne peut pas
        // rendre de façon fiable un tableau de plusieurs milliers de ventes
        // (testé : ~1.5 Go de RAM pour l'historique complet, quelle que soit
        // la limite mémoire). Pour l'historique complet, utiliser l'export Excel.
        if (!$request->anyFilled(['numeroFacture', 'product_id', 'created_at'])) {
            $query->whereDate('created_at', '>=', now()->startOfMonth())
                  ->whereDate('created_at', '<=', now()->endOfMonth());
        }

        // Role-based filtering
        if (auth()->user()->role_id == 3) {
            $storeId = Store::where('user_id', auth()->user()->id)->value('id');
            $query->where('store_id', $storeId);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        // Get products for filter display
        $produits = Product::all();
        $company = \App\Models\Company::latest()->first();

        $pdf = Pdf::loadView('exports.sales-pdf', compact('sales', 'produits', 'company'));

        // Configure PDF
        $pdf->setPaper('A4', 'landscape'); // Use landscape for more columns
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            // Le logo est chargé depuis le disque (chemin local, pas asset()) donc pas besoin
            // de requêtes HTTP sortantes : ça évite un aller-retour qui, sur certains hébergements,
            // pouvait faire prendre plusieurs dizaines de secondes à cet export.
            'isRemoteEnabled' => false,
            'chroot' => public_path(),
        ]);

        $filename = 'ventes_' . now()->format('Y-m-d_His') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Export filtered sales to Excel (from index page with current filters)
     */
    public function exportCurrentExcel(Request $request)
    {
        return $this->exportExcel($request);
    }

    /**
     * Export filtered sales to PDF (from index page with current filters)
     */
    public function exportCurrentPDF(Request $request)
    {
        return $this->exportPDF($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        if (auth()->user()->role_id == 3 && empty($request->store_id)) {
            $request->merge([
                'store_id' => Store::where('user_id', auth()->user()->id)->value('id'),
            ]);
        }
        $request->validate([
            'numeroFacture' => 'required|string',
            'store_id' => 'required|exists:stores,id',
            'customer_id' => 'required|exists:customers,id',
            'avance' => 'nullable|numeric|min:0',
        ]);
        $salesData = $request->input('sales') ?? [];
        $salesData = array_values(array_filter($salesData, function ($row) {
            return is_array($row)
                && isset($row['product_id'], $row['prix'], $row['quantity'])
                && $row['product_id'] !== null
                && $row['product_id'] !== '';
        }));
        $rules = [
            'sales.*.numeroFacture' => 'required|string',
            'sales.*.product_id' => 'required|exists:products,id',
            'sales.*.prix' => 'required|numeric|min:0',
            'sales.*.quantity' => 'required|integer|min:1',
        ];

        $validator = Validator::make(['sales' => $salesData], $rules);
        if ($validator->fails()) {
            // Handle validation failure
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if (empty($salesData)) {
            return redirect()->back()->with('error', 'Veuillez ajouter au moins un produit.')->withInput();
        }
        DB::beginTransaction();
        try {
            // Prepare the data for insertion
            $total_quantity = 0;
            $total_price = 0;
            $i = 0;
            //dd($salesData);
            // Insert multiple rows
            foreach ($salesData as $data) {
                //dd($request->store_id);
                $data["prixTotal"]=$data['prix']*$data['quantity'];
                // Atomic decrement to avoid overselling under concurrency
                if (!$this->stock->decrementIfAvailable((int) $request->store_id, (int) $data['product_id'], (int) $data['quantity'])) {
                    throw new \RuntimeException('Stock insuffisant pour ce produit.');
                }

                //$prix_achat = Purchase::where('product_id', $data['product_id'])->first()->price;
                //$dataItem->latestLigneCommande?->unit_price_sale
                $product = Product::find($data['product_id']);
                $prix_achat = $this->resolveUnitCost($product);
                //dd($prix_achat);
                $data["interet"] = ($data['prix'] - $prix_achat) * $data['quantity'];
                $total_quantity += $data['quantity'];
                $total_price += $data['prixTotal'];
                Sale::create(
                    [
                        'numeroFacture' => $request->numeroFacture,
                        'product_id' => $data['product_id'],
                        'prix' => $data['prix'],
                        'quantity' => $data['quantity'],
                        'prixTotal' => $data['prixTotal'],
                        'interet' => $data['interet'],
                        'store_id' => $request->store_id,
                        'user_id' => auth()->id(),
                    ]
                );
                $i++;
            }

            if ($request->avance > $total_price) {
                throw new \RuntimeException('Le montant de la commande est inférieur à l\'avance.');
            }
            $reste= $total_price - $request->avance;
            if ($reste == 0) {
                $facture = Facture::create([
                    'numero_facture' => $request->numeroFacture,
                    'store_id' => $request->store_id,
                    'customer_id' => $request->customer_id,
                    'montant_total' => $total_price,
                    'quantity' => $total_quantity,
                    'avance' => $request->avance,
                    'notes' => $request->notes,
                    'reste' => $reste,
                    'statut' => 'payé',
                    'livraison' => 'livré',
                ]);
            } elseif ($reste > 0) {
                //dd($salesData);
                $facture = Facture::create([
                    'numero_facture' => $request->numeroFacture,
                    'store_id' => $request->store_id,
                    'customer_id' => $request->customer_id,
                    'montant_total' => $total_price,
                    'quantity' => $total_quantity,
                    'avance' => $request->avance,
                    'notes' => $request->notes,
                    'reste' => $reste,
                    'statut' => 'non payé',
                    'livraison' => 'non livré',
                ]);
            }

            $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);
            Payment::create([
                'facture_id' => $facture->id,
                'versement' => $facture->avance,
                'total_paye' => $facture->avance,
                'paid_by' => $request->paid_by,
                'reste' => $reste,
                'note' => "un premier versement de ".$facture->avance." effectué lors de l'emission de la facture comme avance.",
                'receipt_number' => $receiptNumber,
            ]);

            $customer = Customer::find($request->customer_id);
            $customer->total_taken += $total_price;
            $customer->total_repaid += $request->avance;
            $customer->balance += $request->avance - $total_price;
            $customer->save();

            DB::commit();

            // Redirect back with a success message
            return redirect()->route('factures.index')->with('success', 'Vente créee, et stock mis à jour avec succès');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $sale)
    {
        return view('sales.edit', compact('sale'));
    }

    public function pos(){
        $categories = Category::all();
        $produits = Product::with(['categories', 'latestLigneCommande', 'stores'])->get();
        $userStoreId = Auth::user()->role_id == 3
            ? Store::where('user_id', Auth::user()->id)->value('id')
            : null;
        $boutiques = Store::all();
        $customers = Customer::all();
        $countFactures = (Facture::max('id') ?? 0) + 1;
        $numeroFacture = date('Ym').''.sprintf("%04d", $countFactures);
        return view('sales.pos', compact('produits', 'boutiques', 'customers', 'categories', 'userStoreId', 'numeroFacture'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $sale)
    {
        $sale = Sale::find($sale); // Use a meaningful variable name like $saleId instead of $sale
        if (!$sale) {
            return redirect()->route('sales.index')->with('error', 'Vente non trouvée.');
        }

        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
                'prix' => 'required|numeric|min:0',
            ]);

            DB::beginTransaction();

            // Fetch the invoice associated with the sale
            $invoice = Facture::where('numero_facture', $sale->numeroFacture)->first();
            if (!$invoice) {
                DB::rollBack();
                return redirect()->route('sales.index')->with('error', 'Facture non trouvée.');
            }

            // Fetch the customer
            $customer = Customer::find($invoice->customer_id);
            if (!$customer) {
                DB::rollBack();
                return redirect()->route('sales.index')->with('error', 'Client non trouvé.');
            }

            // Fetch payments associated with the invoice
            $paiements = Payment::where('facture_id', $invoice->id)->get();

            // Calculate the remaining amount after the update
            $resteMontant = ($sale->quantity * $sale->prix) - ($request->quantity * $request->prix);
            $resteQuantity = ($sale->quantity) - ((int) $request->quantity);

            $customer->total_taken -= $sale->quantity * $sale->prix;
            $customer->total_taken += $request->quantity * $request->prix;

            $totalPaid = $paiements->sum('versement');
            // Update the invoice
            $invoice->quantity -= $resteQuantity;
            $invoice->montant_total -= $resteMontant;
            $invoice->reste -= $resteMontant;
            $invoice->save();

            // Update payments
            foreach ($paiements as $pay) {
                $pay->reste -= $resteMontant;
                $pay->save();
            }

            // Update customer's total_repaid and balance based on new invoice state
            $customer->total_repaid = ($customer->total_repaid - $totalPaid) + $paiements->sum('versement');
            $customer->balance = $customer->total_taken - $customer->total_repaid;
            $customer->save();
            // Update store product stock based on quantity delta
            $deltaQuantity = $request->quantity - $sale->quantity;
            if ($deltaQuantity > 0) {
                if (!$this->stock->decrementIfAvailable((int) $sale->store_id, (int) $sale->product_id, (int) $deltaQuantity)) {
                    DB::rollBack();
                    return redirect()->route('sales.index')->with('error', 'Stock insuffisant pour modifier cette vente.');
                }
            } elseif ($deltaQuantity < 0) {
                $this->stock->increment((int) $sale->store_id, (int) $sale->product_id, abs((int) $deltaQuantity));
            }

            // Update the sale record
            $sale->prix = $request->prix;
            $sale->quantity = $request->quantity;
            $sale->prixTotal = $request->quantity * $request->prix;

            $product = Product::find($sale->product_id);
            $prixAchat = $product ? $this->resolveUnitCost($product) : 0;
            $sale->interet = ($request->prix - $prixAchat) * $request->quantity;
            $sale->save();

            DB::commit();
            return redirect()->route('sales.index')->with('success', 'Vente modifiée avec succès.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('sales.index')->with('error', 'La vente n\'a pas été modifiée. Raison : ' . $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */


    public function exitSale($numero_facture)
    {
        try {
            // Delete the Purchase and Logistic record
            Facture::where('numero_facture', $numero_facture)->delete();

            return redirect()->route('sales.index')->with('success', "Vous êtes parti sans valider, la vente a été annulé.");
        } catch (\Throwable $th) {
            // Log the error and return with an error message
            return redirect()->back()->with('error', 'Impossible de quitter cette page, une erreur est survenue: ' . $th->getMessage());
        }
    }

    /**
     * Coût d'achat unitaire : ligne de commande fournisseur la plus récente,
     * sinon dernier prix d'achat (table purchases), sinon price_sale.
     * Ne jamais retomber silencieusement sur 0 quand un achat existe —
     * c'est ce qui gonflait l'intérêt à 100 % du prix de vente.
     */
    private function resolveUnitCost(Product $product): float
    {
        $latest = $product->latestLigneCommande;
        if ($latest && $latest->unit_price_purchase !== null) {
            return (float) $latest->unit_price_purchase;
        }
        if ($latest && $latest->quantity > 0 && $latest->total_price_purchase !== null) {
            return (float) ($latest->total_price_purchase / $latest->quantity);
        }

        $purchasePrice = \App\Models\Purchase::where('product_id', $product->id)->latest()->value('price');
        if ($purchasePrice !== null) {
            return (float) $purchasePrice;
        }

        return (float) ($product->price_sale ?? 0.0);
    }
}
