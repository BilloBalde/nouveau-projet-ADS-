<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Facture;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FactureController extends Controller
{
    private StockService $stock;

    public function __construct(StockService $stock)
    {
        $this->middleware('auth.check');
        $this->stock = $stock;
    }

    public function index(Request $request){
        $customers = Customer::all();
        $query = Facture::query();

        // Appliquer les filtres
        if ($request->filled('numero_facture')) {
            $query->where('numero_facture', 'like', '%' . $request->input('numero_facture') . '%');
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', 'like', '%' . $request->input('statut') . '%');
        }

        if ($request->filled('livraison')) {
            $query->where('livraison', 'like', '%' . $request->input('livraison') . '%');
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->input('created_at')); // utiliser whereDate pour ignorer l'heure
        }

        // Ajout du tri : du plus récent au plus ancien
        $query->orderBy('created_at', 'desc');

        // Eager loading (client, boutique, paiements) + somme des intérêts en SQL
        $query->with(['customer', 'store', 'paiements' => fn ($q) => $q->latest()])
            ->withSum('sales as interet_total', 'interet');

        // Appliquer la restriction selon le rôle
        if (auth()->user()->role_id == 3) {
            $storeId = Store::where('user_id', auth()->user()->id)->first()->id;
            $query->where('store_id', $storeId);
        }

        $dataTable = $query->paginate(50)->appends($request->query());

        return view('factures.index', compact('dataTable', 'customers'));
    }

    public function show($facture){
        $user = User::where('role_id', 2)->first();
        $invoice = Sale::where('numeroFacture', $facture)->get();
        $laFacture = Facture::where('numero_facture', $facture)->first();
        if (!$laFacture) {
            abort(404, "Facture introuvable.");
        }
        $customer = Customer::where('id', $laFacture->customer_id)->first();
        $paiements = Payment::where('facture_id', $laFacture->id)->get();
        return view('factures.show', compact('invoice', 'facture', 'laFacture', 'customer', 'user', 'paiements'));
    }



    /*public function printPdf($facture)
    {
        $laFacture   = Facture::where('numero_facture', $facture)->firstOrFail();
        $customer    = Customer::find($laFacture->customer_id);
        $invoice     = Sale::where('numeroFacture', $facture)->get();
        $company     = Company::latest()->first();
        $managerName = auth()->user()->name ?? 'Gérant';
    
        $pdf = Pdf::loadView('factures.pdf', compact(
                'facture', 'laFacture', 'customer', 'invoice', 'company', 'managerName'
            ))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'dpi'                  => 150,
            ]);
    
        return $pdf->stream("Facture_{$facture}.pdf");
        // Pour forcer le téléchargement à la place : $pdf->download("Facture_{$facture}.pdf")
    }*/


    public function printPdf($facture)
    {
        $laFacture = Facture::where('numero_facture', $facture)->firstOrFail();
        $customer  = Customer::find($laFacture->customer_id);
        $invoice   = Sale::where('numeroFacture', $facture)->get();
        $company   = Company::latest()->first();
        $managerName = auth()->user()->name ?? 'Gérant';

        $pdf = Pdf::loadView('factures.pdf', compact(
                'facture', 'laFacture', 'customer', 'invoice', 'company', 'managerName'
            ))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'dpi' => 150,
                'enable_javascript' => true,
            ]);

        return $pdf->stream("Facture_{$facture}.pdf");
    }


    public function bonDeCommande($facture)
    {
        $user = User::where('role_id', 2)->first();
        $invoice = Sale::where('numeroFacture', $facture)->get();
        $laFacture = Facture::where('numero_facture', $facture)->first();
        $customer = Customer::where('id', $laFacture->customer_id)->first();
        $paiements = Payment::where('facture_id', $laFacture->id)->get();
        
        return view('factures.bon_de_commande', compact('invoice', 'facture', 'laFacture', 'customer', 'user', 'paiements'));
    }

    // In app/Http/Controllers/FactureController.php

    public function bonDeSortie($facture)
    {
        $user = User::where('role_id', 2)->first();
        $invoice = Sale::where('numeroFacture', $facture)->get();
        $laFacture = Facture::where('numero_facture', $facture)->first();
        $customer = Customer::where('id', $laFacture->customer_id)->first();
        $paiements = Payment::where('facture_id', $laFacture->id)->get();
        
        return view('factures.bon_de_sortie', compact('invoice', 'facture', 'laFacture', 'customer', 'user', 'paiements'));
    }
    
    public function storeCustomer(Request $request)
    {
        $request->validate([
            'customerName' => 'required|string|max:255',
        ]);

        try {
            $customer = Customer::create([
                'customerName' => $request->customerName,
                'mark' => $request->mark ?? 'AMD',
                'tel' => $request->tel ?? '17288399',
                'address' => $request->address ?? 'kobaya',
                'fidelite' => 1
            ]);

            return response()->json([
                'success' => true,
                'customer' => $customer
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function facturationForm(){
        $userStoreId = Auth::user()->role_id == 3
            ? Store::where('user_id', Auth::user()->id)->value('id')
            : null;
        $boutiques = Store::all();
        $customers = Customer::all();
        $countFactures = (Facture::max('id') ?? 0) + 1;
        $numeroFacture = date('Ym').''.sprintf("%04d", $countFactures);
        return view('factures.create', compact('boutiques', 'customers', 'userStoreId', 'numeroFacture'));
    }
        
    public function facturationStore(Request $request){
        $validatedData = $request->validate([
            'numeroFacture' => 'required',
            'avance' => 'required|numeric',
            'customer_id' => 'required',
            'store_id' => 'required',
            'products.*.productName' => 'required|string|max:255',
            'products.*.cartons' => 'required|integer|min:1',
            'products.*.qty_per_ctn' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.subtotal' => 'required|numeric|min:0',
            'products.*.image' => 'nullable|file|mimes:jpg,png,jpeg',
        ]);

        try {
            DB::beginTransaction();
            $total_pcs = 0;
            $total_amount = 0;
            foreach ($validatedData['products'] as $product) {
                // 1) Reset per-iteration image state
                $uploadedImagePath = null;

                // 2) Upload if present with a UUID filename
                if (!empty($product['image'])) {
                    $ext = $product['image']->getClientOriginalExtension();
                    $filename = Str::uuid()->toString().'.'.$ext; // unique
                    $product['image']->move(public_path('products'), $filename);
                    $uploadedImagePath = $filename;
                }
                
                $productName = trim($product['productName']);
                $productName = str_replace('`', '', $productName); // Remove backticks that can cause SQL issues
                $produit = Product::firstOrNew(['libelle' => $productName]);
                $produit->sku         = $productName; // consider making this unique if needed
                $produit->qtityCtn    = $product['qty_per_ctn'];
                $produit->description = $productName;

                if ($uploadedImagePath) {
                    // Only set/replace image if a new one was uploaded
                    $produit->image = $uploadedImagePath;
                } elseif (!$produit->exists && !$produit->image) {
                    // New product without an uploaded image: use default
                    $produit->image = 'default.png';
                }
                $produit->save();
                
                $product['quantity'] = $product['cartons'] * $product['qty_per_ctn'];
                $product["prixTotal"] = $product['subtotal'];
                $total_pcs += $product['quantity'];
                $total_amount += $product['subtotal'];
                //dd($product['cartons'], $product['qty_per_ctn'], $product['subtotal']);
                // Atomic decrement to avoid overselling under concurrency
                if (!$this->stock->decrementIfAvailable((int) $request->store_id, (int) $produit->id, (int) $product['quantity'])) {
                    throw new \RuntimeException('Stock insuffisant pour ce produit.');
                }

                //$prix_achat = Purchase::where('product_id', $data['product_id'])->first()->price;
                //$dataItem->latestLigneCommande?->unit_price_sale
                //$product = Product::find($data['product_id']);
                $latest = $produit->latestLigneCommande;
                if ($latest && $latest->unit_price_purchase !== null) {
                    $prix_achat = (float) $latest->unit_price_purchase;
                } elseif ($latest && $latest->quantity > 0 && $latest->total_price_purchase !== null) {
                    $prix_achat = (float) ($latest->total_price_purchase / $latest->quantity);
                } else {
                    $prix_achat = (float) (Purchase::where('product_id', $produit->id)->latest()->value('price') ?? 0);
                }
                //dd($prix_achat);
                $product["interet"] = ($product['price'] - $prix_achat) * $product['quantity'];
                Sale::create(
                    [
                        'numeroFacture' => $request->numeroFacture,
                        'product_id' => $produit->id,
                        'prix' => $product['price'],
                        'quantity' => $product['quantity'],
                        'prixTotal' => $product['prixTotal'],
                        'interet' => $product['interet'],
                        'store_id' => $request->store_id,
                        'user_id' => auth()->id(),
                    ]
                );
            }
            
            if ($request->avance > $total_amount) {
                throw new \RuntimeException('Le montant de la commande est inférieur à l\'avance.');
            }
            $reste = $total_amount - $request->avance;
            
            $facture = Facture::create([
                'numero_facture' => $request->numeroFacture,
                'store_id' => $request->store_id,
                'customer_id' => $request->customer_id,
                'quantity' => $total_pcs,
                'montant_total' => $total_amount,
                'avance' => $request->avance,
                'reste' => $reste,
                'notes' => 'Facture '.$request->numero_facture.' enregistree',
                'statut' => 'non payé',
                'livraison' => 'non livré',
            ]);
            
            $receiptNumber = generateReceiptNumber('RCF', (Payment::max('id') ?? 0) + 1);
            Payment::create([
                'facture_id' => $facture->id,
                'versement' => $facture->avance,
                'total_paye' => $facture->avance,
                'paid_by' => "Cash",
                'reste' => $reste,
                'note' => "un premier versement de ".$facture->avance." effectué lors de l'emission de la facture comme avance.",
                'receipt_number' => $receiptNumber,
            ]);
            
            $sms = "Facture cree avec success";
            $customer = Customer::find($request->customer_id);
            $customer->total_taken += $total_amount;
            $customer->total_repaid += $request->avance;
            $customer->balance += $reste;
            $customer->save();
            $countCustomerInvoices = Facture::where('customer_id', $request->customer_id)->count();
            if ($countCustomerInvoices >= 5){
                $customer->fidelite = 1;
                $customer->save();
                $sms = "Facture cree avec success. Merci au client ".$customer->mark." pour sa fidelity";
            }
            DB::commit();
            // Redirect back with a success message
            if ($request->ajax()) {
                return response()->json(['message' => $sms]);
            } else {
                return redirect()->route('factures.index')->with('success', $sms);
            }
            //return redirect()->route('proformas.index')->with('success', 'Proforma créée avec succès.');
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['error' => 'Erreur : ' . $th->getMessage()], 500);
            } else {
                return redirect()->back()->with('error', 'Erreur lors de l\'enregistrement de ce proforma : ' . $th->getMessage());
            }
        }
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'numero_facture' => 'required|unique:factures',
            'customer_id' => 'required',
            'avance' => 'required|numeric',
            'notes' => 'nullable|string',
        ],[
            'numero_facture.required' => 'Le champ numéro de la facture est obligatoire',
            'numero_facture.unique' => 'Le champ numéro de la facture doit être unique',
            'customer_id.required' => 'Veuillez selectionner le client',
            'avance.required' => 'Veuillez entrer un montant, à la rigueure 0',
        ]);

        if (isset($request->customerName) && isset($request->mark) && isset($request->tel) && isset($request->email) && isset($request->address)) {
            $customer = Customer::create([
                'customerName' => $request->customerName,
                'mark' => $request->mark,
                'tel' => $request->tel,
                'email' => $request->email,
                'address' => $request->address,
            ]);

            $customer_id = $customer->id;
        }
        try {
            $facture = Facture::create([
                'numero_facture' => $request->numero_facture,
                'store_id' => $request->store_id,
                'customer_id' => $customer_id ?? $request->customer_id,
                'avance' => $request->avance,
                'notes' => $request->notes,
                'statut' => 'non payé',
                'livraison' => 'non livré',
            ]);
            
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
            
            $sms = "Facture cree avec success";
            $countCustomerInvoices = Facture::where('customer_id', $request->customer_id)->count();
            if ($countCustomerInvoices >= 5){
                $customer = Customer::find($request->customer_id);
                $customer->fidelite = 1;
                $customer->save();
                $sms = "Facture cree avec success. Merci au client ".$customer->mark." pour sa fidelity";
            }
            // Redirect back with a success message
            $products = Product::all();
            return redirect()->route('sales.ajout',[$request->numero_facture, $request->avance, $request->store_id])->with([
                'success'=>$sms,
                'numeroFacture'=> $request->numero_facture,
                'products'=> $products,
                'avance' => $request->avance
            ]);
        } catch (\Throwable $th) {
            return back()->with('fall', 'une erreur lors de lajout, voici le message : '.$th->getMessage());
        }
    }

    public function edit($id){
        $facture = Facture::find($id);
        return view('factures.edit', compact('facture'));
    }

    public function update(Facture $facture){
        try {
            $facture->livraison = 'livré';
            $facture->save();
            return redirect()->back()->with('success', 'Votre facture a été mis à jour: livré');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'erreur lors de la modification'.$th->getMessage());
        }
    }
    
    public function destroy(Request $request, Facture $facture)
    {
        try {
            DB::beginTransaction();

            // Load related sales & payments once
            $facture->load('paiements');

            $sales = Sale::where('numeroFacture', $facture->numero_facture)->get();
            //dd($sales);

            // 1) Restore stock for each sale, then delete the sale
            foreach ($sales as $sale) {
                // increment quantity back to store stock
                DB::table('store_products')
                    ->where('store_id', $sale->store_id)
                    ->where('product_id', $sale->product_id)
                    ->increment('quantity', (int) $sale->quantity);

                // delete sale row
                $sale->delete();
            }

            // 2) Delete related payments
            foreach ($facture->paiements as $payment) {
                $payment->delete();
            }

            // 3) Finally delete the facture
            $numero = $facture->numero_facture ?? $facture->numeroFacture ?? $facture->id;
            $facture->delete();

            DB::commit();

            $msg = "Facture #{$numero} deleted. Related sales/payments removed and stock restored.";

            if ($request->ajax()) {
                return response()->json(['message' => $msg]);
            }

            return redirect()->route('factures.index')->with('success', $msg);

        } catch (\Throwable $th) {
            DB::rollBack();
            $err = "Erreur lors de la suppression: ".$th->getMessage();

            if ($request->ajax()) {
                return response()->json(['error' => $err], 500);
            }

            return redirect()->back()->with('error', $err);
        }
    }
}
