<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Dette;
use App\Models\PaymentDette;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DetteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $customers = Customer::all();
        $query = Dette::query()->with(['customer', 'store']);

        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->input('reference') . '%');
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('endettement_date')) {
            $query->whereDate('endettement_date', $request->input('endettement_date'));
        }

        if (auth()->user()->role_id == 3) {
            $store = Store::where('user_id', auth()->user()->id)->first();
            if ($store) {
                $query->where('store_id', $store->id);
            }
        }

        $dataTable = $query->latest()->paginate(50)->appends($request->query());

        return view('dettes.index', compact('dataTable', 'customers'));
    }

    public function create()
    {
        $customers = Customer::all();
        $stores = Store::all();
        $reference = 'DETTE' . Carbon::now()->format('Ym') . '-' . str_pad(Dette::count() + 1, 4, '0', STR_PAD_LEFT);

        return view('dettes.create', compact('customers', 'stores', 'reference'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'reference'         => 'required|unique:dettes,reference',
            'customer_id'       => 'required|exists:customers,id',
            'store_id'          => 'required|exists:stores,id',
            'montant_total'     => 'required|numeric|min:0',
            'montant_paid'      => 'required|numeric|min:0|lte:montant_total',
            'endettement_date'  => 'required|date',
            'notes'             => 'nullable|string',
        ], [
            'reference.required'     => 'Le numéro de référence est obligatoire.',
            'reference.unique'       => 'Cette référence existe déjà.',
            'customer_id.required'   => 'Veuillez sélectionner le client.',
            'store_id.required'      => 'Veuillez sélectionner la boutique.',
            'montant_total.required' => 'Veuillez entrer un montant.',
            'montant_paid.lte'       => 'Le montant payé ne peut pas dépasser le montant total.',
        ]);

        try {
            $reste = $request->montant_total - $request->montant_paid;

            Dette::create([
                'reference'        => $request->reference,
                'store_id'         => $request->store_id,
                'customer_id'      => $request->customer_id,
                'montant_total'    => $request->montant_total,
                'montant_paid'     => $request->montant_paid,
                'reste'            => $reste,
                'notes'            => $request->notes,
                'status'           => $reste == 0 ? 'paid' : 'pending',
                'endettement_date' => $request->endettement_date,
            ]);

            return redirect()->route('dettes.index')->with('success', 'Dette enregistrée avec succès.');
        } catch (\Throwable $th) {
            return back()->withInput()->with('error', 'Erreur lors de l\'enregistrement : ' . $th->getMessage());
        }
    }

    public function edit(Dette $dette)
    {
        $customers = Customer::all();
        $stores = Store::all();

        return view('dettes.edit', compact('dette', 'customers', 'stores'));
    }

    public function update(Request $request, Dette $dette)
    {
        $request->validate([
            'reference'         => 'required|unique:dettes,reference,' . $dette->id,
            'customer_id'       => 'required|exists:customers,id',
            'store_id'          => 'required|exists:stores,id',
            'montant_total'     => 'required|numeric|min:0',
            'montant_paid'      => 'required|numeric|min:0|lte:montant_total',
            'endettement_date'  => 'required|date',
            'notes'             => 'nullable|string',
        ], [
            'reference.required'     => 'Le numéro de référence est obligatoire.',
            'reference.unique'       => 'Cette référence existe déjà.',
            'customer_id.required'   => 'Veuillez sélectionner le client.',
            'store_id.required'      => 'Veuillez sélectionner la boutique.',
            'montant_total.required' => 'Veuillez entrer un montant.',
            'montant_paid.lte'       => 'Le montant payé ne peut pas dépasser le montant total.',
        ]);

        try {
            $reste = $request->montant_total - $request->montant_paid;

            $dette->update([
                'reference'        => $request->reference,
                'store_id'         => $request->store_id,
                'customer_id'      => $request->customer_id,
                'montant_total'    => $request->montant_total,
                'montant_paid'     => $request->montant_paid,
                'reste'            => $reste,
                'notes'            => $request->notes,
                'status'           => $reste == 0 ? 'paid' : 'pending',
                'endettement_date' => $request->endettement_date,
            ]);

            return redirect()->route('dettes.index')->with('success', 'Dette modifiée avec succès.');
        } catch (\Throwable $th) {
            return back()->withInput()->with('error', 'Erreur lors de la modification : ' . $th->getMessage());
        }
    }

    /**
     * Encaisser un versement sur une dette.
     */
    public function pay(Request $request, Dette $dette)
    {
        $request->validate([
            'versement' => 'required|numeric|min:1|lte:' . $dette->reste,
            'paid_by'   => 'required|in:cash,check,orange money',
        ], [
            'versement.lte' => 'Le versement ne peut pas dépasser le reste dû (' . number_format((float) $dette->reste, 0, ',', ' ') . ' GNF).',
        ]);

        PaymentDette::create([
            'reference' => 'PDETTE' . Carbon::now()->format('Ymd') . '-' . str_pad(PaymentDette::count() + 1, 4, '0', STR_PAD_LEFT),
            'dette_id'  => $dette->id,
            'versement' => $request->versement,
            'paid_by'   => $request->paid_by,
            'notes'     => $request->notes ?? 'Versement sur dette ' . $dette->reference,
        ]);

        $dette->montant_paid += $request->versement;
        $dette->reste        -= $request->versement;
        $dette->status        = $dette->reste <= 0 ? 'paid' : 'pending';
        $dette->save();

        return back()->with('success', 'Versement enregistré. Reste dû : ' . number_format((float) $dette->reste, 0, ',', ' ') . ' GNF.');
    }
}
