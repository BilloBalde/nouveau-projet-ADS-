<?php

namespace App\Http\Controllers;

use App\Models\Achat;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Company;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use App\Models\InvoiceClient;
use App\Models\LigneCommande;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProformaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Achat::query();
        if ($request->filled('identifier')) {
            $query->where('identifier', 'like', '%' . $request->input('identifier') . '%');
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }
        if ($request->filled('date_achat')) {
            $query->where('date_achat', 'like', '%' . $request->input('date_achat') . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        $customers = Customer::all();
        $dataTable = $query->where('status', '!=', 'canceled')->get();
        $enumValues = ['commanded', 'canceled', 'delivered', 'shipped', 'paid'];
        return view('proformas.index', compact('dataTable', 'customers', 'enumValues'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $customers = Customer::all();
        $categories = Category::all();
        $products = Product::all();
        $shops = Store::all();
        $userStoreId = Auth::user()->role_id == 3
            ? Store::where('user_id', Auth::user()->id)->value('id')
            : null;
        return view('proformas.create', compact('customers', 'products', 'shops', 'userStoreId', 'categories'));
    }

    public function selection()
    {
        $proformas = Achat::where('is_selected', false)->get();
        return view('proformas.selection', compact('proformas'));
    }

    /* public function creation(Request $request)
    {
        if (isset($request->customer_id)) {
            $customer_id = $request->customer_id;
            //dd($customer_id);
            $invoices = Invoice::where('customer_id', $customer_id)->where('printed', 0)->get();
            return view('proformas.create', compact('customer_id', 'invoices'))->with('success', 'Veuillez selectionner les invoices pour le proforma');
        } else {
            return redirect()->route('proformas.index')->with('error', 'Veuillez selectionner un client');
        }

    } */

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'customerName' => 'required|string|max:255',
            'mark' => 'required|string|max:255',
            'email' => 'required|email',
            'tel' => 'required|string',
            'address' => 'required|string',
        ]);

        try {
            $customer = Customer::create([
                'customerName' => $request->customerName,
                'mark' => $request->mark,
                'email' => $request->email,
                'tel' => $request->tel,
                'address' => $request->address,
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

    public function addInvoice($id)
    {
        $proforma = Achat::find($id);
        return view('proformas.add', compact('proforma'))->with('success', 'Veuillez ajouter les produits');
    }

    public function editInvoice($id)
    {
        $proforma = Achat::find($id);
        $ligneCommandes = LigneCommande::where('achat_id', $proforma->id)->get();
        return view('proformas.modifier', compact('proforma', 'ligneCommandes'))->with('success', 'Veuillez ajouter les produits');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /* public function store(Request $request)
    {
        //dd($request->additional_value);
        try {
            $request->validate([
                'proformas' =>'required',
                'additional_value' =>'required',
            ]);
        } catch (\Throwable $th) {
            return redirect()->route('proformas.index')->with('error', $th->getMessage());
        }
        $proformas = $request->proformas;
        $total_cbm = 0;
        $total_weight = 0;
        $total_ctns = 0;
        $total_pcs = 0;
        $total_amount = 0;
        $interest = 0;
        $invoices = '';
        $identifier = 'ACHAT'.Carbon::now()->format('Ym').'-'.str_pad(Achat::count() + 1, 4, '0', STR_PAD_LEFT);
        $i = 0;
        foreach ($proformas as $proforma) {
            if (isset($proforma['selection'])) {
                $i++;
            }
        }
        foreach ($proformas as $proforma) {
            if (isset($proforma['selection'])) {
                $customer_id = $proforma['customer_id'];
                $invoice = Invoice::find($proforma['invoice_id']);
                $ligneCommande = LigneCommande::where('invoice_id', $invoice->id)->get();
                //dd($ligneCommande);
                $total_cbm += $ligneCommande->sum('total_cbm');
                $total_weight += $ligneCommande->sum('total_weight');
                $total_ctns += $ligneCommande->sum('cartons');
                $total_pcs += $ligneCommande->sum('quantity');
                $total_amount += $invoice->amount_to_be_paid;
                $interest += $proforma['interest'];
                $invoices .= $invoice->id. '-';
                $invoice_date = $invoice->invoice_date;
                $invoice->printed = 1;
                $invoice->divers += $request->additional_value / $i;
                //dd($invoice->divers);
                $invoice->save();
                $delivery_date = Carbon::now()->format('Y-m-d');
            }
        }
        try {
            if (isset($request->proforma_id)) {
                //dd("je tombe icI");
                $leProforma = Achat::find($request->proforma_id);
                $leProforma->total_amount += $total_amount;
                $leProforma->total_cbm += $total_cbm;
                $leProforma->total_weight += $total_weight;
                $leProforma->total_pcs += $total_pcs;
                $leProforma->total_ctns += $total_ctns;
                $leProforma->interest += $interest;
                $leProforma->invoices .= $invoices;
                $leProforma->save();
            }else{
                Achat::create([
                    'identifier' => $identifier,
                    'invoices' => $invoices,
                    'customer_id' => $customer_id,
                    'invoice_date' => $invoice_date,
                    'delivery_date' => $delivery_date,
                    'total_cbm' => $total_cbm,
                    'total_weight' => $total_weight,
                    'total_ctns' => $total_ctns,
                    'total_pcs' => $total_pcs,
                    'total_amount' => $total_amount,
                    'interest' => $interest,
                ]);
            }
            return redirect()->route('proformas.index')->with('success', 'Proforma créée avec succès');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Une erreur est survenue lors de la création de la proforma');
        }
        //dd($identifier, $invoices, $invoice_date, $customer_id, $delivery_date,  $total_amount, $total_cbm, $total_ctns, $total_pcs, $total_weight, $interest);
    } */

    public function liste(Request $request) {}

    public function validateSelected(Request $request)
    {
        $cbmInputs = $request->input('cbm', []);

        // Get the proforma IDs based on cbm input keys
        $proformaIds = array_keys($cbmInputs);

        // Fetch those proformas
        $proformas = Achat::whereIn('id', $proformaIds)->get();

        $totalCbm = 0;
        $totalWeight = 0;

        foreach ($proformas as $proforma) {
            $totalCbm += (float) ($cbmInputs[$proforma->id] ?? 0);
            $totalWeight += (float) $proforma->total_weight;
        }

        $isCbmValid = ($totalCbm > 28 && $totalCbm < 29) || ($totalCbm > 68 && $totalCbm < 69);
        $isWeightValid = $totalWeight < 29;

        if (!($isCbmValid && $isWeightValid)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: CBM or Weight out of range.',
                'total_cbm' => $totalCbm,
                'total_weight' => $totalWeight,
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Proforma  $proforma
     * @return \Illuminate\Http\Response
     */
    /* public function show($id)
    {
        $user = auth()->user();
        //dd($user);
        $proforma = Achat::where('identifier', $id)->first();
        if ($proforma->status === 'delivered') {
            $invoice = InvoiceClient::where('proforma_id', $proforma->id)->first();
            $identifiant = $invoice->invoice_no;
            $dateInvoice = $invoice->invoice_date;
        } else {
            $identifiant = $proforma->identifier;
            $dateInvoice = $proforma->date_proforma;
        }
        $compagnie = Company::latest()->first();
        $ligneCommandes = LigneCommande::where('proforma_id', $proforma->id)->get();
        $customer = $proforma->customer;
        return view('proformas.show', compact('proforma', 'user', 'customer', 'ligneCommandes', 'identifiant', 'dateInvoice', 'compagnie'));
    } */

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Proforma  $proforma
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $proforma = Achat::find($id);
        return view('proformas.edit', compact('proforma'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Proforma  $proforma
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $proforma = Achat::find($id);
            $proforma->status = $request->status;
            $proforma->delivery_date = $request->delivery_date;
            $proforma->save();
            return redirect()->back()->with('success', "Proforma mis a jour avec sucess");
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', "erreur lors de la modification " . $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Proforma  $proforma
     * @return \Illuminate\Http\Response
     */
    public function destroy($proforma)
    {

        try {
            $proforma = Achat::findOrFail($proforma);

            // Supprimer les commandes liées si elles existent
            if ($proforma->ligneCommandes()->exists()) {
                $proforma->ligneCommandes()->delete();
            }

            // Supprimer la proforma
            $proforma->delete();

            return redirect()->route('proformas.index')->with('success', 'Proforma supprimée avec succès');
        } catch (\Throwable $th) {

            return redirect()->back()->with('error', 'Une erreur est survenue lors de la suppression de la proforma : ' . $th->getMessage());
        }
    }
}
