<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    public function index(Request $request)
    {
        $categories = Category::all();

        $query = Product::query();

        if ($request->filled('libelle')) {
            $query->where('libelle', 'like', '%' . $request->input('libelle') . '%');
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->input('category_id'));
            });
        }

        $connectedUser = Auth::user()->role_id;

        if ($connectedUser == 3) {
            $userStoreId = Store::where('user_id', Auth::user()->id)->value('id');
            $query->whereHas('stores', function ($q) use ($userStoreId) {
                $q->where('store_id', $userStoreId);
            });
        }
        $allProducts = $query->with(['categories', 'stores'])->get();

        $userStoreId = $connectedUser == 3
            ? Store::where('user_id', Auth::user()->id)->value('id')
            : null;

        return view('products.index', compact('allProducts', 'categories', 'userStoreId'))
            ->with('libelle', $request->input('libelle'))
            ->with('category_id', $request->input('category_id'));
    }

    public function fetchProductDetails(Request $request)
    {
        $productName = $request->query('productName');
        $product = Product::with('category')->where('libelle', 'like', '%' . $productName . '%')->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'libelle' => $product->libelle,
                    'qtityCtn' => $product->qtityCtn,
                    'image' => $product->image,
                    'category' => [
                        'id' => $product->category?->id,
                        'name' => $product->category?->slug
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }
    
    public function fetchProductDetailsSuggestion(Request $request)
    {
        $productName = $request->query('productName');

        if ($request->boolean('suggestions')) {
            $suggestions = Product::where('libelle', 'like', '%' . $productName . '%')
                ->limit(10)
                ->get(['id', 'libelle']);

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
        }

        $product = Product::where('libelle', $productName)->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => $product
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found'
        ]);
    }

    public function create()
    {
        $categories = Category::all();
        $stores = Store::all();
        return view('products.create', compact('categories', 'stores'));
    }

    public function validation(Request $request)
    {
        return $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'libelle' => 'required|string|max:225',
            'sku' => 'required|string|max:225',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'price_sale' => 'nullable|numeric',
            'stock_initial' => 'required|integer|min:0',
            'store_id' => 'required|exists:stores,id',
            'image' => 'image',
            'is_promo' => 'nullable|boolean',
            'promo_percent' => 'nullable|numeric|min:0|max:100',
            'promo_start_date' => 'nullable|date',
            'promo_end_date' => 'nullable|date|after_or_equal:promo_start_date',
        ], [
            'categories.required' => 'veuillez selectionner la categorie',
            'categories.array' => 'Selectionner plusieurs categories',
            'libelle.required' => 'champ libelle doit être rempli',
            'libelle.string' => 'champ libelle prend au maximum 225 caractères',
            'sku.required' => 'champ sku doit être rempli',
            'sku.string' => 'champ sku prend au maximum 225 caractères',
            'description.required' => 'champ description doit être rempli avec une chaine de caractere',
            'price.required' => 'champ Prix doit être rempli avec une valeure numérique',
            'price_sale.numeric' => 'champ Prix Vente doit être rempli avec une valeure numérique',
            'stock_initial.required' => 'champ Stock initial doit être rempli',
            'stock_initial.integer' => 'champ Stock initial doit être un nombre entier',
            'stock_initial.min' => 'champ Stock initial doit être positif',
            'store_id.required' => 'champ Magasin doit être rempli',
            'store_id.exists' => 'Magasin sélectionné invalide',
            'image.image' => 'champ image ne prend que des images',
            'promo_percent.max' => 'Le pourcentage de promotion ne peut pas dépasser 100.',
            'promo_end_date.after_or_equal' => 'La date de fin de promo doit être après la date de début.',
        ]);
    }

    /**
     * Calcule le prix promo à partir du prix de vente et du pourcentage saisi.
     */
    private function computePromoPrice(Request $request, float $price): ?float
    {
        if (!$request->boolean('is_promo') || !$request->filled('promo_percent')) {
            return null;
        }

        return round($price * (1 - ((float) $request->promo_percent / 100)), 2);
    }

    public function store(Request $request)
    {
        $this->validation($request);
        if (request()->hasfile('image')) {
            $productName = time() . '.' . request()->image->getClientOriginalExtension();
            request()->image->move(public_path('products'), $productName);
        }
        try {
            $product = Product::create([
                'libelle' => $request->libelle,
                'sku' => $request->sku,
                'description' => $request->description,
                'pcs' => $request->stock_initial, // stock global e-commerce, initialisé au stock du premier magasin
                'price' => $request->price,
                'price_sale' => $request->price_sale ?? NULL,
                'image' => $productName ?? 'ib profile.jpg',
                'is_promo' => $request->boolean('is_promo'),
                'promo_percent' => $request->promo_percent ?? null,
                'promo_price' => $this->computePromoPrice($request, (float) $request->price),
                'promo_start_date' => $request->promo_start_date ?? null,
                'promo_end_date' => $request->promo_end_date ?? null,
            ]);

            $product->categories()->attach($request->categories);

            // Ajout du stock dans le magasin sélectionné
            $storeId = $request->store_id;
            $product->stores()->attach($storeId, ['quantity' => $request->stock_initial]);

            return redirect()->route('produits.index')->with('success', 'Produit crée avec succès.');
        } catch (\Exception $e) {
            return back()->with('fall', 'une erreur lors de lajout, voici le message : ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $product = Product::with('categories')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'libelle' => $product->libelle,
                'sku' => $product->sku,
                'description' => $product->description,
                'price' => $product->price,
                'price_sale' => $product->price_sale,
                'image' => $product->image,
                'category_name' => $product->categories->pluck('slug')->implode(', ')
            ]
        ]);
    }

    public function edit($id)
    {
        $product = Product::with(['categories', 'stores', 'latestLigneCommande'])->findOrFail($id);
        // ✅ SUPPRIMÉ: Category::where('tenant_id', Auth::user()->tenant_id)
        $categories = Category::all();
        $stores = Store::select('id', 'store_name')->get();

        $user = Auth::user();
        $userStoreId = ($user && $user->role_id == 3)
            ? Store::where('user_id', $user->id)->value('id')
            : null;

        $perStoreQuantities = $product->stores
            ->mapWithKeys(fn ($s) => [$s->id => (int) ($s->pivot->quantity ?? 0)])
            ->toArray();

        $totalQty = array_sum($perStoreQuantities);

        $quantityForUser = $userStoreId
            ? (int) ($perStoreQuantities[$userStoreId] ?? 0)
            : 0;

        return view('products.edit', compact(
            'product',
            'categories',
            'stores',
            'userStoreId',
            'perStoreQuantities',
            'totalQty',
            'quantityForUser'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'libelle'      => 'required|string|max:255',
            'sku'          => 'required|string|max:255|unique:products,sku,' . $id,
            'categories'   => 'required|array',
            'categories.*' => 'exists:categories,id',
            'description'  => 'required|string',
            'price'        => 'required|numeric',
            'stock_restant' => 'required|integer|min:0',
            'price_sale'   => 'nullable|numeric',
            'image'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'store_id' => 'required|exists:stores,id',
            'is_promo' => 'nullable|boolean',
            'promo_percent' => 'nullable|numeric|min:0|max:100',
            'promo_start_date' => 'nullable|date',
            'promo_end_date' => 'nullable|date|after_or_equal:promo_start_date',
        ], [
            'promo_percent.max' => 'Le pourcentage de promotion ne peut pas dépasser 100.',
            'promo_end_date.after_or_equal' => 'La date de fin de promo doit être après la date de début.',
        ]);

        try {
            // ✅ SUPPRIMÉ: withoutGlobalScope('tenant') - plus besoin
            $product = Product::findOrFail($id);
            $storeId = $request->input('store_id');

            $product->fill($request->only([
                'libelle', 'sku', 'description', 'price', 'price_sale',
            ]));

            $product->is_promo = $request->boolean('is_promo');
            $product->promo_percent = $request->promo_percent ?? null;
            $product->promo_price = $this->computePromoPrice($request, (float) $request->price);
            $product->promo_start_date = $request->promo_start_date ?? null;
            $product->promo_end_date = $request->promo_end_date ?? null;

            $product->save();

            if ($request->hasFile('image')) {
                $productName = time() . '.' . $request->image->getClientOriginalExtension();
                $request->image->move(public_path('products'), $productName);
                $product->update(['image' => $productName]);
            }

            // ✅ SUPPRIMÉ: tenant_id dans sync
            $product->categories()->sync($request->categories);

            $storeProduct = StoreProduct::updateOrCreate(
                ['product_id' => $id, 'store_id' => $storeId],
                ['quantity' => $request->input('stock_restant')]
            );

            // Garde products.pcs (stock global e-commerce) en phase avec la somme des stocks par magasin
            $product->pcs = $product->stores()->sum('store_products.quantity');
            $product->save();

            return redirect()
                ->route('produits.index')
                ->with('success', 'Produit mis à jour avec succès!');

        } catch (\Exception $e) {
            return back()->with('fall', 'Erreur: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            Product::find($id)->delete();
            return redirect()->back()->with('success', 'Produit supprimé avec succès.');
        } catch (\Illuminate\Database\QueryException $th) {
            if ($th->getCode() == "23000") {
                return back()->with(
                    'error',
                    "Impossible de supprimer ce produit : il est déjà utilisé dans d'autres enregistrements."
                );
            }
            return redirect()->back()->with('error', 'Produit pas supprimé. Voici l\'erreur' . $th->getMessage());
        }
    }
}
