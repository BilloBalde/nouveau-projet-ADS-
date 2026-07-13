<?php
// app/Http/Controllers/DevisController.php

namespace App\Http\Controllers;

use App\Models\Devi;
use App\Models\DeviLine;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DeviController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $query = Devi::with(['store', 'customer', 'createdBy']);

        if ($request->filled('numero_devis')) {
            $query->where('numero_devis', 'like', '%' . $request->numero_devis . '%');
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by store for staff
        if (auth()->user()->role_id == 3) {
            $storeId = Store::where('user_id', auth()->id())->value('id');
            $query->where('store_id', $storeId);
        }

        $devis = $query->orderBy('created_at', 'desc')->paginate(15);
        $customers = Customer::all();

        return view('devis.index', compact('devis', 'customers'));
    }

    public function create()
    {
        $stores = Store::all();
        $products = Product::with('stores')->get();
        $customers = Customer::all();
        
        // For staff, preselect their store
        $userStoreId = null;
        if (auth()->user()->role_id == 3) {
            $userStoreId = Store::where('user_id', auth()->id())->value('id');
        }

        return view('devis.create', compact('stores', 'products', 'customers', 'userStoreId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_id' => 'nullable|exists:customers,id',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Create customer if new
            $customerId = $request->customer_id;
            if (!$customerId && $request->customer_name) {
                $customer = Customer::create([
                    'customerName' => $request->customer_name,
                    'tel' => $request->customer_phone ?? 'N/A',
                    'address' => $request->customer_address ?? 'N/A',
                    'mark' => 'DEV',
                ]);
                $customerId = $customer->id;
            }

            // Calculate total
            $totalAmount = 0;
            foreach ($request->products as $product) {
                $totalAmount += $product['quantity'] * $product['unit_price'];
            }

            // Create devis
            $devis = Devi::create([
                'store_id' => $request->store_id,
                'customer_id' => $customerId,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'valid_until' => $request->valid_until,
                'status' => 'draft',
            ]);

            // Create devi lines
            foreach ($request->products as $productData) {
                $totalPrice = $productData['quantity'] * $productData['unit_price'];
                
                DeviLine::create([
                    'devis_id' => $devis->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => $productData['unit_price'],
                    'total_price' => $totalPrice,
                    'notes' => $productData['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('devis.show', $devis->id)
                ->with('success', 'Devis créé avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $devis = Devi::with(['lines.product', 'store', 'customer', 'createdBy'])->findOrFail($id);
        
        // Get company info for PDF
        $company = \App\Models\Company::latest()->first();

        return view('devis.show', compact('devis', 'company'));
    }

    public function edit($id)
    {
        $devis = Devi::with('lines')->findOrFail($id);
        
        // Can't edit if already accepted or validated
        if ($devis->status !== 'draft') {
            return redirect()->route('devis.show', $id)
                ->with('error', 'Seuls les devis en brouillon peuvent être modifiés.');
        }

        $stores = Store::all();
        $products = Product::with('stores')->get();
        $customers = Customer::all();

        return view('devis.edit', compact('devis', 'stores', 'products', 'customers'));
    }

    public function update(Request $request, $id)
    {
        $devis = Devi::findOrFail($id);

        if ($devis->status !== 'draft') {
            return back()->with('error', 'Seuls les devis en brouillon peuvent être modifiés.');
        }

        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'customer_id' => 'nullable|exists:customers,id',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Calculate total
            $totalAmount = 0;
            foreach ($request->products as $product) {
                $totalAmount += $product['quantity'] * $product['unit_price'];
            }

            // Update devis
            $devis->update([
                'store_id' => $request->store_id,
                'customer_id' => $request->customer_id,
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
                'valid_until' => $request->valid_until,
            ]);

            // Delete old lines
            $devis->lines()->delete();

            // Create new lines
            foreach ($request->products as $productData) {
                $totalPrice = $productData['quantity'] * $productData['unit_price'];
                
                DeviLine::create([
                    'devis_id' => $devis->id,
                    'product_id' => $productData['product_id'],
                    'quantity' => $productData['quantity'],
                    'unit_price' => $productData['unit_price'],
                    'total_price' => $totalPrice,
                    'notes' => $productData['notes'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('devis.show', $devis->id)
                ->with('success', 'Devis mis à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $devis = Devi::findOrFail($id);
        
        // Can only delete draft devis
        if ($devis->status !== 'draft') {
            return back()->with('error', 'Seuls les devis en brouillon peuvent être supprimés.');
        }

        $devis->delete();

        return redirect()->route('devis.index')
            ->with('success', 'Devis supprimé avec succès.');
    }

    /**
     * Change status of devis
     */
    public function changeStatus(Request $request, $id)
    {
        $devis = Devi::findOrFail($id);

        $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
        ]);

        $devis->update(['status' => $request->status]);

        $statusMessages = [
            'draft' => 'Devis remis en brouillon.',
            'sent' => 'Devis marqué comme envoyé.',
            'accepted' => 'Devis accepté par le client.',
            'rejected' => 'Devis rejeté.',
            'expired' => 'Devis expiré.',
        ];

        return back()->with('success', $statusMessages[$request->status]);
    }

    /**
     * Validate devis - create invoice and affect stock
     */
    public function validateDevis($id)
    {
        $devis = Devi::with('lines')->findOrFail($id);

        if ($devis->status !== 'accepted') {
            return back()->with('error', 'Seuls les devis acceptés peuvent être validés.');
        }

        if ($devis->validated_at) {
            return back()->with('error', 'Ce devis a déjà été validé.');
        }

        try {
            $facture = $devis->validate();

            return redirect()->route('factures.show', $facture->numero_facture)
                ->with('success', 'Devi validé avec succès. Facture #' . $facture->numero_facture . ' créée.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    public function showPdf($id)
    {
        $devis = Devi::with(['lines.product', 'store', 'customer', 'createdBy'])->findOrFail($id);
        $company = \App\Models\Company::latest()->first();
        
        return view('devis.pdf', compact('devis', 'company'));
    }

    public function downloadPdf($id)
    {
        $devis = Devi::with(['lines.product', 'store', 'customer', 'createdBy'])->findOrFail($id);
        $company = \App\Models\Company::latest()->first();
        
        $html = view('devis.pdf', compact('devis', 'company'))->render();
        
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download('Devis_' . $devis->numero_devis . '.pdf');
    }

    /**
     * Generate PDF for devis
     */
    public function generatePdf($id)
    {
        $devis = Devi::with(['lines.product', 'store', 'customer', 'createdBy'])->findOrFail($id);
        $company = \App\Models\Company::latest()->first();

        $pdf = Pdf::loadView('devis.pdf', compact('devis', 'company'));
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
        ]);

        return $pdf->download('Devis_' . $devis->numero_devis . '.pdf');
    }
}