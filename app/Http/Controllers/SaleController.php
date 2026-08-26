<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Partner;
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
use Illuminate\Support\Facades\Schema;

class SaleController extends Controller
{
    private StockService $stock;

    public function __construct(StockService $stock)
    {
        $this->middleware('auth.check');
        // Route::resource('sales', ...) n'appliquait aucune permission : un
        // comptable ou un responsable achats (sans aucun sales.* dans son preset)
        // pouvait créer/modifier des ventes, alors que seuls admin/manager/vendeur
        // le devraient (comptable n'a que sales.view en lecture).
        $this->middleware('permission:sales.view')->only(['index', 'show']);
        $this->middleware('permission:sales.create')->only(['create', 'store']);
        $this->middleware('permission:sales.edit')->only(['edit', 'update']);
        $this->middleware('permission:sales.delete')->only(['destroy']);
        $this->stock = $stock;
    }

    /**
     * Résout la période choisie (jour/semaine/mois/trimestre/semestre/année/
     * personnalisé) en une plage [début, fin] — même logique que
     * PaymentController::getFilteredPayments(), pour un comportement cohérent
     * entre les pages Ventes et Paiements. Retourne [null, null] si aucun
     * filtre de période n'est fourni (pas de plage imposée par défaut).
     */
    private function resolvePeriodRange(Request $request): array
    {
        if (! $request->filled('period')) {
            return [null, null];
        }

        $period = $request->get('period');
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'daily':
                $date = $request->get('date', now()->toDateString());
                $startDate = \Illuminate\Support\Carbon::parse($date)->startOfDay();
                $endDate = \Illuminate\Support\Carbon::parse($date)->endOfDay();
                break;
            case 'weekly':
                $weekStart = $request->get('week_start', now()->startOfWeek()->toDateString());
                $startDate = \Illuminate\Support\Carbon::parse($weekStart)->startOfWeek();
                $endDate = $startDate->copy()->endOfWeek();
                break;
            case 'monthly':
                $month = $request->get('month', now()->format('Y-m'));
                $startDate = \Illuminate\Support\Carbon::parse($month)->startOfMonth();
                $endDate = \Illuminate\Support\Carbon::parse($month)->endOfMonth();
                break;
            case 'quarterly':
                $quarter = (int) $request->get('quarter', ceil(now()->month / 3));
                $year = (int) $request->get('year', now()->year);
                $startMonth = ($quarter - 1) * 3 + 1;
                $startDate = \Illuminate\Support\Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = $startDate->copy()->addMonths(3)->subDay()->endOfDay();
                break;
            case 'semestral':
                $semester = (int) $request->get('semester', now()->month <= 6 ? 1 : 2);
                $year = (int) $request->get('year', now()->year);
                $startMonth = $semester == 1 ? 1 : 7;
                $startDate = \Illuminate\Support\Carbon::create($year, $startMonth, 1)->startOfMonth();
                $endDate = $startMonth == 1
                    ? \Illuminate\Support\Carbon::create($year, 6, 30)->endOfDay()
                    : \Illuminate\Support\Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'yearly':
                $year = (int) $request->get('year', now()->year);
                $startDate = \Illuminate\Support\Carbon::create($year, 1, 1)->startOfDay();
                $endDate = \Illuminate\Support\Carbon::create($year, 12, 31)->endOfDay();
                break;
            case 'custom':
                $startDate = $request->filled('start_date') ? \Illuminate\Support\Carbon::parse($request->start_date)->startOfDay() : null;
                $endDate = $request->filled('end_date') ? \Illuminate\Support\Carbon::parse($request->end_date)->endOfDay() : null;
                break;
        }

        return [$startDate, $endDate];
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

        [$startDate, $endDate] = $this->resolvePeriodRange($request);
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
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
        [$startDate, $endDate] = $this->resolvePeriodRange($request);
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
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
        [$startDate, $endDate] = $this->resolvePeriodRange($request);
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Sans aucun filtre, se limiter au mois en cours : Dompdf ne peut pas
        // rendre de façon fiable un tableau de plusieurs milliers de ventes
        // (testé : ~1.5 Go de RAM pour l'historique complet, quelle que soit
        // la limite mémoire). Pour l'historique complet, utiliser l'export Excel.
        // L'utilisateur peut choisir sa propre période via le filtre en haut de
        // l'index — ce repli ne s'applique que si aucun filtre n'est actif.
        if (!$request->anyFilled(['numeroFacture', 'product_id', 'period'])) {
            $query->whereDate('created_at', '>=', now()->startOfMonth())
                  ->whereDate('created_at', '<=', now()->endOfMonth());
        }

        // Role-based filtering
        if (auth()->user()->role_id == 3) {
            $storeId = Store::where('user_id', auth()->user()->id)->value('id');
            $query->where('store_id', $storeId);
        }

        // Garde-fou : au-delà de ce volume, Dompdf devient trop lent/gourmand en
        // RAM (testé : une année complète peut bloquer le serveur plusieurs
        // minutes). On préfère refuser proprement plutôt que de faire planter
        // la requête — l'Excel n'a pas cette limite.
        $count = (clone $query)->count();
        if ($count > 1500) {
            return back()->with(
                'error',
                "Cette période contient {$count} ventes — trop pour générer un PDF de façon fiable. "
                . 'Réduisez la période (ex : un mois au lieu d\'une année), ou utilisez l\'export Excel qui ne connaît pas cette limite.'
            );
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        // Get products for filter display
        $produits = Product::all();
        $company = \App\Models\Company::current();

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



    /**
     * Prochain numéro de facture libre.
     *
     * `Facture::max('id')` ignore les factures en corbeille, alors que l'index
     * unique sur `factures.numero_facture`, lui, les compte toujours : après la
     * mise à la corbeille d'une facture, son numéro était réattribué et
     * l'insertion échouait sur « Duplicate entry ». On repart donc du maximum
     * corbeille comprise, puis on avance tant que le numéro est déjà pris.
     */
    private function prochainNumeroFacture(): string
    {
        $sequence = (Facture::withTrashed()->max('id') ?? 0) + 1;

        do {
            $numero = date('Ym') . sprintf('%04d', $sequence);
            $sequence++;
        } while (Facture::withTrashed()->where('numero_facture', $numero)->exists());

        return $numero;
    }

    public function store(Request $request)
    {
        if ($storeId = $this->ownStoreIdOverride()) {
            $request->merge(['store_id' => $storeId]);
        }

        // Les lignes elles-mêmes n'étaient pas validées. Une quantité nulle créait
        // une facture à 0 GNF ; une quantité négative passait dès qu'une autre
        // ligne maintenait le total positif, et retranchait du montant sans
        // contrepartie — le stock, lui, restait protégé par StockService.
        // Seul le contrôle « avance > total » les rattrapait parfois, avec un
        // message sans rapport avec la cause.
        $request->validate([
            'numeroFacture' => 'required|string',
            'store_id'      => 'required|exists:stores,id',
            'customer_id'   => 'required|exists:customers,id',
            'avance'        => 'nullable|numeric|min:0',
            'paid_by'       => 'nullable|string',
            'notes'         => 'nullable|string',
            'sales'                              => 'required|array|min:1',
            'sales.*.product_id'                 => 'required|exists:products,id',
            'sales.*.quantity'                   => 'required|integer|min:1',
            'sales.*.prix'                       => 'required|numeric|min:0',
            'sales.*.partner_id'                 => 'nullable|exists:partners,id',
            'sales.*.purchase_price_from_partner'=> 'nullable|numeric|min:0',
        ], [
            'sales.required'            => 'Veuillez ajouter au moins un produit.',
            'sales.*.quantity.min'      => 'La quantité doit être d\'au moins 1.',
            'sales.*.quantity.integer'  => 'La quantité doit être un nombre entier.',
            'sales.*.prix.min'          => 'Le prix ne peut pas être négatif.',
        ]);

        // Le numéro affiché dans le POS est calculé à l'ouverture de la page : deux
        // caisses ouvertes en même temps reçoivent le même, et le premier à valider
        // le consomme. Plutôt que de renvoyer une erreur au vendeur, on reprend
        // simplement le prochain numéro libre au moment de l'enregistrement.
        // (La règle `unique:sales` ne suffisait pas : elle ne regardait pas la table
        // `factures`, où porte la contrainte qui faisait échouer l'insertion.)
        $numeroDemande = (string) $request->numeroFacture;
        $dejaPris = Facture::withTrashed()->where('numero_facture', $numeroDemande)->exists()
            || Sale::where('numeroFacture', $numeroDemande)->exists();

        if ($dejaPris) {
            $request->merge(['numeroFacture' => $this->prochainNumeroFacture()]);
        }

        $salesData = $request->input('sales') ?? [];
        $salesData = array_values(array_filter($salesData, fn($row) => !empty($row['product_id'])));

        if (empty($salesData)) {
            return redirect()->back()->with('error', 'Veuillez ajouter au moins un produit.')->withInput();
        }

        DB::beginTransaction();
        try {
            $total_quantity = 0;
            $total_price = 0;

            foreach ($salesData as $data) {
                $isThirdParty = !empty($data['is_third_party']) && $data['is_third_party'] == "1";

                $data['prixTotal'] = $data['prix'] * $data['quantity'];

                $product = Product::find($data['product_id']);
                $libelle = $product?->libelle ?? 'Produit inconnu';

                if ($isThirdParty) {
                    // ====================== VENTE TIERCE ======================
                    if (empty($data['partner_id']) || empty($data['purchase_price_from_partner'])) {
                        throw new \Exception('Pour une vente tierce, le partenaire et le prix d\'achat sont obligatoires.');
                    }

                    $prixAmi = (float) $data['purchase_price_from_partner'];
                    $this->assertPriceCoversCost($libelle, (float) $data['prix'], $prixAmi);

                    $data['interet'] = ($data['prix'] - $prixAmi) * $data['quantity'];
                    $data['is_third_party'] = true;

                } else {
                    // ====================== VENTE NORMALE ======================
                    // Le contrôle de marge passe avant le décrément de stock : inutile
                    // de consommer du stock pour une ligne qui va être refusée.
                    $prix_achat = $product ? $this->resolveUnitCost($product) : 0.0;
                    $this->assertPriceCoversCost($libelle, (float) $data['prix'], $prix_achat);

                    if (!$this->stock->decrementIfAvailable((int) $request->store_id, (int) $data['product_id'], (int) $data['quantity'])) {
                        throw new \RuntimeException('Stock insuffisant pour le produit : ' . $libelle);
                    }

                    $data['interet'] = ($data['prix'] - $prix_achat) * $data['quantity'];
                    $data['is_third_party'] = false;
                }

                $total_quantity += $data['quantity'];
                $total_price += $data['prixTotal'];

                Sale::create([
                    'numeroFacture'               => $request->numeroFacture,
                    'product_id'                  => $data['product_id'],
                    'prix'                        => $data['prix'],
                    'quantity'                    => $data['quantity'],
                    'prixTotal'                   => $data['prixTotal'],
                    'interet'                     => $data['interet'],
                    'store_id'                    => $request->store_id,
                    'user_id'                     => auth()->id(),
                    'is_third_party'              => $data['is_third_party'],
                    'partner_id'                  => $data['partner_id'] ?? null,
                    'purchase_price_from_partner' => $data['purchase_price_from_partner'] ?? null,
                ]);
            }

            // ====================== FACTURE & PAIEMENT ======================
            if ($request->avance > $total_price) {
                throw new \RuntimeException('Le montant de l\'avance ne peut pas être supérieur au total.');
            }

            $reste = $total_price - ($request->avance ?? 0);

            $facture = Facture::create([
                'numero_facture' => $request->numeroFacture,
                'store_id'       => $request->store_id,
                'customer_id'    => $request->customer_id,
                'montant_total'  => $total_price,
                'quantity'       => $total_quantity,
                'avance'         => $request->avance ?? 0,
                'notes'          => $request->notes,
                'reste'          => $reste,
                'statut'         => $reste == 0 ? 'payé' : 'non payé',
                'livraison'      => $reste == 0 ? 'livré' : 'non livré',
            ]);

            // Payment
            $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);
            Payment::create([
                'facture_id'   => $facture->id,
                'versement'    => $facture->avance,
                'total_paye'   => $facture->avance,
                'paid_by'      => $request->paid_by ?? 'cash',
                'reste'        => $reste,
                'note'         => "Premier versement effectué à l'émission de la facture.",
                'receipt_number' => $receiptNumber,
            ]);

            // Mise à jour Client
            // Convention du projet : balance = total_taken - total_repaid, donc
            // un solde POSITIF signifie que le client doit de l'argent. C'est ce
            // que lisent le tableau de bord (total des crédits), le contrôle qui
            // empêche d'archiver un client endetté, et le recalcul groupé de
            // PaymentController. Cette ligne appliquait le signe inverse : une
            // vente à crédit rendait le solde négatif, et le client disparaissait
            // des créances affichées.
            $customer = Customer::find($request->customer_id);
            $customer->recalculerCompteurs();

            DB::commit();

            return redirect()->route('factures.index')
                            ->with('success', 'Vente enregistrée avec succès !');

        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->back()
                            ->with('error', 'Erreur : ' . $th->getMessage())
                            ->withInput();
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
        $partners = Partner::all();
        $numeroFacture = $this->prochainNumeroFacture();
        return view('sales.pos', compact('produits', 'boutiques', 'customers', 'categories', 'userStoreId', 'numeroFacture', 'partners'));
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

            // Les compteurs du client sont recalcules depuis les factures, une fois
            // la facture et les paiements a jour.
            $customer->recalculerCompteurs();

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
            // Même règle qu'à la création : modifier une vente ne doit pas permettre
            // de repasser sous le prix de revient.
            $this->assertPriceCoversCost($product?->libelle ?? 'Produit inconnu', (float) $request->prix, (float) $prixAchat);

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
    /**
     * Interdit la vente à perte : le prix unitaire saisi doit couvrir le coût
     * d'acquisition — prix de revient du produit pour une vente normale, prix
     * payé au partenaire pour une vente tierce.
     *
     * Un coût nul signifie que le produit n'a jamais été rattaché à un achat :
     * il n'y a alors rien à comparer, la vente passe. Le contrôle ne peut pas
     * être plus strict que ce que l'historique d'achat permet de savoir.
     */
    private function assertPriceCoversCost(string $libelle, float $prixVente, float $cout): void
    {
        if ($cout > 0 && $prixVente < $cout) {
            throw new \RuntimeException(sprintf(
                'Vente à perte refusée pour « %s » : le prix de vente (%s GNF) est inférieur au prix de revient (%s GNF).',
                $libelle,
                number_format($prixVente, 0, ',', ' '),
                number_format($cout, 0, ',', ' ')
            ));
        }
    }

    private function resolveUnitCost(Product $product): float
    {
        // price_sale est désormais synchronisé à chaque achat (ancien flux Purchase
        // et nouveau module PurchaseOrder) — c'est la source la plus fiable et la
        // plus à jour. L'ancienne chaîne LigneCommande/Purchase ne sert plus que de
        // dernier recours pour les produits jamais touchés par un achat synchronisé.
        if ($product->price_sale !== null && (float) $product->price_sale > 0) {
            return (float) $product->price_sale;
        }

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

    /**
     * Store a new partner via AJAX
     */
    public function storePartner(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ]);

        try {
            $partner = Partner::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => true,
                'partner' => $partner,
                'message' => 'Partenaire créé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a quick third-party sale with a new product
     */
    public function storeQuickThirdParty(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'prix' => 'required|numeric|min:0',
            'purchase_price_from_partner' => 'required|numeric|min:0',
            'partner_id' => 'required|exists:partners,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Créer ou récupérer le produit
            $product = Product::where('sku', $request->sku)->first();
            
            if (!$product) {
                // Récupérer tous les champs requis de la table products
                $productData = [
                    'libelle' => $request->libelle,
                    'sku' => $request->sku,
                    'code' => $request->sku, // Si code est requis et différent de sku
                    'price' => $request->prix,
                    'price_sale' => 0,
                    'quantity' => 0,
                    'stock_alert' => 0,
                    'status' => 'active',
                    'user_id' => auth()->id(),
                    'description' => $request->libelle ?? 'Produit tierce', // ✅ Important
                    'mark' => 'TIERCE', // Si le champ mark est requis
                    'category_id' => null, // Si category_id peut être null
                ];

                // Ajouter d'autres champs s'ils sont requis
                if (Schema::hasColumn('products', 'code')) {
                    $productData['code'] = $request->sku;
                }
                if (Schema::hasColumn('products', 'description')) {
                    $productData['description'] = $request->libelle;
                }
                if (Schema::hasColumn('products', 'mark')) {
                    $productData['mark'] = 'TIERCE';
                }
                if (Schema::hasColumn('products', 'barcode')) {
                    $productData['barcode'] = $request->sku;
                }

                $product = Product::create($productData);
            }

            // Générer un numéro de facture
            $numeroFacture = $this->prochainNumeroFacture();

            // Récupérer le magasin
            $storeId = $request->store_id;
            if (auth()->user()->role_id == 3 && empty($storeId)) {
                $storeId = Store::where('user_id', auth()->user()->id)->value('id');
            }
            
            // S'assurer qu'un store_id existe
            if (empty($storeId)) {
                $storeId = Store::first()->id ?? 1;
            }

            // S'assurer qu'un customer_id existe
            $customerId = $request->customer_id;
            if (empty($customerId)) {
                // Utiliser un client par défaut ou le premier client
                $defaultCustomer = Customer::first();
                if ($defaultCustomer) {
                    $customerId = $defaultCustomer->id;
                } else {
                    throw new \Exception('Aucun client trouvé. Veuillez d\'abord créer un client.');
                }
            }

            // Vente tierce rapide : le coût de référence est le prix payé au partenaire.
            $this->assertPriceCoversCost(
                $request->libelle,
                (float) $request->prix,
                (float) $request->purchase_price_from_partner
            );

            $prixTotal = $request->prix * $request->quantity;
            $interet = ($request->prix - $request->purchase_price_from_partner) * $request->quantity;

            // Créer la vente
            Sale::create([
                'numeroFacture' => $numeroFacture,
                'product_id' => $product->id,
                'prix' => $request->prix,
                'quantity' => $request->quantity,
                'prixTotal' => $prixTotal,
                'interet' => $interet,
                'store_id' => $storeId,
                'user_id' => auth()->id(),
                'is_third_party' => true,
                'partner_id' => $request->partner_id,
                'purchase_price_from_partner' => $request->purchase_price_from_partner,
            ]);

            // Créer la facture
            $avance = $request->avance ?? 0;
            $reste = $prixTotal - $avance;
            
            $facture = Facture::create([
                'numero_facture' => $numeroFacture,
                'store_id' => $storeId,
                'customer_id' => $customerId,
                'montant_total' => $prixTotal,
                'quantity' => $request->quantity,
                'avance' => $avance,
                'notes' => $request->notes,
                'reste' => $reste,
                'statut' => $reste == 0 ? 'payé' : 'non payé',
                'livraison' => $reste == 0 ? 'livré' : 'non livré',
            ]);

            // Créer le paiement
            $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);
            Payment::create([
                'facture_id' => $facture->id,
                'versement' => $facture->avance,
                'total_paye' => $facture->avance,
                'paid_by' => $request->paid_by ?? 'cash',
                'reste' => $reste,
                'note' => "Vente tierce rapide - " . ($request->notes ?? ''),
                'receipt_number' => $receiptNumber,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vente tierce enregistrée avec succès',
                'redirect' => route('factures.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur vente tierce rapide: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }
}
