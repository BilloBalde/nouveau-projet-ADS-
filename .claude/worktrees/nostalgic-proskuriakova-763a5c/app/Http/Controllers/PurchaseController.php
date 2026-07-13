<?php

namespace App\Http\Controllers;

use App\Models\Logistic;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PurchaseController extends Controller
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
        $produits = Product::all();
        $boutiques = Store::all();
        $query = Purchase::query();

        if ($request->filled('numeroPurchase')) {
            $query->where('numeroPurchase', 'like', '%' . $request->input('numeroPurchase') . '%');
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', 'like', '%' . $request->input('product_id') . '%');
        }

        if ($request->filled('created_at')) {
            $query->where('created_at', $request->input('created_at'));
        }

        // Eager loading + pagination serveur
        $dataTable = $query->with('product.categories')
            ->latest()
            ->paginate(50)
            ->appends($request->query());

        // Pass the necessary data to the view, including options for filters
        return view('purchases.index', compact('dataTable', 'produits', 'boutiques'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ajout($numeroPurchase, $quantity, $store_id)
    {
        $products = Product::all();
        return view('purchases.create', compact('numeroPurchase', 'products', 'quantity', 'store_id'));
    }

    public function create()
    {
        return view('purchases.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $purchasesData = $request->input('purchases');
        $request->validate([
            'purchases.*.product_id' => 'required|exists:products,id',
            'purchases.*.price' => 'required|numeric|min:0',
            'purchases.*.quantity' => 'required|integer|min:1',
            'purchases.*.description' => 'nullable|string',
        ], [
            'purchases.*.product_id.required' => 'Le produit est requis.',
            'purchases.*.price.required' => 'Le prix est requis.',
            'purchases.*.quantity.required' => 'La quantité est requise.',
            'purchases.*.quantity.min' => 'La quantité doit être supérieure à zéro.',
        ]);

        //dd($purchasesData);
        // Insert multiple rows
        foreach ($purchasesData as $data) {
            Purchase::create($data);
            StoreProduct::updateOrCreate(
                [
                    'store_id' => $data['store_id'], // Assume store_id is part of the request data
                    'product_id' => $data['product_id'],
                ],
                [
                    'quantity' => \DB::raw("quantity + {$data['quantity']}"), // Increment the stock
                ]
            );
        }

        // Redirect back with a success message
        return redirect()->route('purchases.index')->with('success', 'Achat crée, et stock mis à jour avec succès');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function show(Purchase $purchase)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function edit(Purchase $purchase)
    {
        return view('purchases.edit', compact('purchase'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $purchase = Purchase::find($id);
        try {
            $logistic = Logistic::where('numeroPurchase', $purchase->numeroPurchase)->first();
            $reste = $purchase->quantity - $request->quantity;
            $logistic->quantity = $logistic->quantity - $reste;
            $logistic->save();
            StoreProduct::updateOrCreate(
                [
                    'store_id' => $purchase->store_id, // Assume store_id is part of the request data
                    'product_id' => $purchase->product_id,
                ],
                [
                    'quantity' => \DB::raw("quantity - {$reste}"), // Increment the stock
                ]
            );
            $produit = Product::find($purchase->product_id);
            $oldReste = $produit->stock - $reste;
            $purchase->price = $request->price;
            $purchase->quantity = $request->quantity;
            $produit->stock = $oldReste;
            $produit->save();
            $purchase->save();
            return redirect()->route('purchases.index')->with('success', 'Achats modifié avec succès');
        } catch (\Throwable $th) {
            return redirect()->route('purchases.index')->with('error', 'Achats pas modifié parceque'. $th->getMessage());
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Purchase  $purchase
     * @return \Illuminate\Http\Response
     */
    public function destroy(Purchase $purchase)
    {
        //
    }

    public function exitAchat($numeroPurchase)
    {
        try {
            // Delete the Purchase and Logistic record
            Logistic::where('numeroPurchase', $numeroPurchase)->delete();

            return redirect()->route('purchases.index')->with('success', "Vous êtes parti sans valider, l'achat a été annulé.");
        } catch (\Throwable $th) {
            // Log the error and return with an error message
            return redirect()->back()->with('error', 'Impossible de quitter cette page, une erreur est survenue: ' . $th->getMessage());
        }
    }

}
