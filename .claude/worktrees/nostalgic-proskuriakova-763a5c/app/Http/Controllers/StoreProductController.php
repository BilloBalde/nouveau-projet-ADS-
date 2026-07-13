<?php

namespace App\Http\Controllers;

use App\Models\StoreProduct;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreProductController extends Controller
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
        $boutiques = Store::all();
        $query = StockTransfer::query();

        if ($request->filled('from_store_id')) {
            $query->where('from_store_id', 'like', '%' . $request->input('from_store_id') . '%');
        }

        if ($request->filled('to_store_id')) {
            $query->where('to_store_id', 'like', '%' . $request->input('to_store_id') . '%');
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', 'like', '%' . $request->input('product_id') . '%');
        }

        if ($request->filled('created_at')) {
            $query->where('created_at', $request->input('created_at'));
        }

        // Eager loading + pagination serveur (évite ~1100 requêtes N+1)
        $dataTable = $query->with(['product.categories', 'fromStore', 'toStore'])
            ->latest()
            ->paginate(50)
            ->appends($request->query());

        // Pass the necessary data to the view, including options for filters
        return view('transfers.index', compact('dataTable', 'produits', 'boutiques'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $productId = (int) $request->product_id;
            $fromStoreId = (int) $request->from_store_id;
            $toStoreId = (int) $request->to_store_id;
            $quantity = (int) $request->quantity;

            DB::beginTransaction();

            // Atomic decrement on source to avoid negative stock / concurrency issues
            if (!$this->stock->decrementIfAvailable($fromStoreId, $productId, $quantity)) {
                DB::rollBack();
                return redirect()->route('transfers.index')->with('error', 'Stock insuffisant dans le magasin source.');
            }

            // Ensure destination row exists then increment
            $this->stock->increment($toStoreId, $productId, $quantity);

            // Record the stock transfer
            StockTransfer::create([
                'product_id' => $productId,
                'from_store_id' => $fromStoreId,
                'to_store_id' => $toStoreId,
                'quantity' => $quantity,
            ]);

            DB::commit();
            return redirect()->route('transfers.index')->with('success', 'Le transfert a été effectué avec succès.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('transfers.index')->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StoreProduct  $storeProduct
     * @return \Illuminate\Http\Response
     */
    public function show(StoreProduct $storeProduct)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StoreProduct  $storeProduct
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $storeProduct = StockTransfer::find($id);
        $produits = Product::all();
        $boutiques = Store::all();
        return view('transfers.edit', compact('storeProduct', 'produits', 'boutiques'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StoreProduct  $storeProduct
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'quantity' => 'required|integer|min:1',
        ]);
        try {
            // Fetch the existing transfer record
            $transfer = StockTransfer::findOrFail($id);

            $productId = (int) $request->product_id;
            $fromStoreId = (int) $request->from_store_id;
            $toStoreId = (int) $request->to_store_id;
            $newQuantity = (int) $request->quantity;
            $oldQuantity = (int) $transfer->quantity;

            DB::beginTransaction();

            // 1) Reverse original transfer safely:
            // source += oldQuantity
            $this->stock->increment($fromStoreId, $productId, $oldQuantity);

            // destination -= oldQuantity (must have enough)
            if (!$this->stock->decrementIfAvailable($toStoreId, $productId, $oldQuantity)) {
                DB::rollBack();
                return redirect()->route('transfers.index')->with('error', 'Stock insuffisant dans le magasin destination pour annuler l\'ancien transfert.');
            }

            // 2) Apply new transfer safely:
            if (!$this->stock->decrementIfAvailable($fromStoreId, $productId, $newQuantity)) {
                DB::rollBack();
                return redirect()->route('transfers.index')->with('error', 'Stock insuffisant dans le magasin source pour appliquer le nouveau transfert.');
            }

            $this->stock->increment($toStoreId, $productId, $newQuantity);

            // 3) Update transfer record
            $transfer->update([
                'product_id' => $productId,
                'from_store_id' => $fromStoreId,
                'to_store_id' => $toStoreId,
                'quantity' => $newQuantity,
            ]);

            DB::commit();
            return redirect()->route('transfers.index')->with('success', 'Le transfert a été mis à jour avec succès.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return redirect()->route('transfers.index')->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StoreProduct  $storeProduct
     * @return \Illuminate\Http\Response
     */
    public function destroy(StoreProduct $storeProduct)
    {
        //
    }
}
