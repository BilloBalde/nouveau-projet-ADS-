<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\StockService;
use App\Support\ModeDeVente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        // Route::resource('produits', ...) n'appliquait aucune permission : tout
        // rôle connecté pouvait créer/modifier/supprimer des produits, alors que
        // le preset PermissionSeeder réserve products.create/.edit/.delete à
        // admin/manager (vendeur et achats n'ont que products.view).
        $this->middleware('permission:products.view')->only(['index', 'show', 'exportExcel', 'exportPdf', 'exportCsv']);
        $this->middleware('permission:products.create')->only(['create', 'store']);
        $this->middleware('permission:products.edit')->only(['edit', 'update']);
        $this->middleware('permission:products.delete')->only(['destroy']);
    }

    /**
     * Emplacement auquel l'utilisateur est limité, ou null s'il a une vue
     * globale. Même règle que la liste et la fiche produit : tout ce qui
     * affiche du stock passe par ici.
     */
    private function emplacementImpose(): ?int
    {
        $user = Auth::user();

        return ($user && $user->isStoreScoped()) ? $user->assignedStoreId() : null;
    }

    public function index(Request $request)
    {
        $categories = Category::all();

        $query = Product::query();

        if ($request->filled('libelle')) {
            $query->where('libelle', 'like', '%'.$request->input('libelle').'%');
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->input('category_id'));
            });
        }

        $connectedUser = Auth::user()->role_id;

        if (Auth::user()->isStoreScoped()) {
            $userStoreId = Auth::user()->assignedStoreId();
            $query->whereHas('stores', function ($q) use ($userStoreId) {
                $q->where('store_id', $userStoreId);
            });
        }
        $allProducts = $query->with(['categories', 'stores'])->get();

        $userStoreId = Auth::user()->isStoreScoped()
            ? Auth::user()->assignedStoreId()
            : null;

        return view('products.index', compact('allProducts', 'categories', 'userStoreId'))
            ->with('libelle', $request->input('libelle'))
            ->with('category_id', $request->input('category_id'));
    }

    public function fetchProductDetails(Request $request)
    {
        $productName = $request->query('productName');
        // Libellé exact d'abord : c'est ce produit-là que la facture utilisera.
        $product = Product::with('category')->where('libelle', $productName)->first()
            ?? ($request->boolean('exact') ? null : Product::with('category')->where('libelle', 'like', '%'.$productName.'%')->first());

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'libelle' => $product->libelle,
                    'unite' => $product->unite_libelle,
                    'price' => (float) $product->price,
                    // Plancher de vente (prix minimum ou prix de revient), pour le signaler à la saisie.
                    'plancher' => $product->plancherDeVente()[0],
                    'plancher_origine' => $product->plancherDeVente()[1] === 'minimum' ? 'prix de vente minimum' : 'prix de revient',
                    'image' => $product->image,
                    'category' => [
                        'id' => $product->category?->id,
                        'name' => $product->category?->slug,
                    ],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found',
        ]);
    }

    public function fetchProductDetailsSuggestion(Request $request)
    {
        $productName = $request->query('productName');

        if ($request->boolean('suggestions')) {
            $suggestions = Product::where('libelle', 'like', '%'.$productName.'%')
                ->limit(10)
                ->get(['id', 'libelle', 'unite', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau'])
                ->map(fn ($p) => ['id' => $p->id, 'libelle' => $p->libelle, 'unite' => $p->unite_libelle]);

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
            ]);
        }

        $product = Product::where('libelle', $productName)->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'product' => $product,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Product not found',
        ]);
    }

    public function create()
    {
        $categories = Category::all();
        $stores = Store::all();

        return view('products.create', compact('categories', 'stores'));
    }

    /**
     * Tout produit se compte à la pièce : le stock, les prix, les achats, les
     * ventes et les factures. Il n'y a plus d'unité à choisir.
     */
    private function valeursUnites(): array
    {
        return ['unite' => 'pcs', 'pcs_par_carton' => null, 'pcs_par_paquet' => null, 'dimension_rouleau' => null];
    }

    /**
     * Prix de vente minimum (facultatif, par unité du produit) : strictement
     * au-dessus du prix de revient et au plus égal au prix de vente, sinon le
     * prix proposé à la caisse serait lui-même refusé.
     */
    private function regleMinimum(Request $request, float $prixVente): array
    {
        return ['nullable', 'numeric', 'min:0', function ($attr, $valeur, $echec) use ($request, $prixVente) {
            if ($valeur === null || $valeur === '') {
                return;
            }
            $fg = fn ($n) => number_format((float) $n, 0, ',', ' ');
            $revient = (float) $request->input('price_sale');
            if ($revient > 0 && (float) $valeur <= $revient) {
                $echec('Le prix de vente minimum doit être strictement supérieur au prix de revient ('.$fg($revient).' FG).');
            } elseif ($prixVente > 0 && (float) $valeur > $prixVente) {
                $echec('Le prix de vente minimum ne peut pas dépasser le prix de vente ('.$fg($prixVente).' FG).');
            }
        }];
    }

    private function valeurMinimum(Request $request): ?float
    {
        return $request->filled('price_sale_minimum') ? round((float) $request->input('price_sale_minimum'), 2) : null;
    }

    /**
     * Prix de vente d'un produit à sa création.
     *
     * En mode caisse, il se calcule à partir du prix de revient et de la marge
     * configurée : le champ du formulaire est en lecture seule, on ne lui fait
     * pas confiance (JS désactivé ou contourné).
     *
     * En mode inventaire, c'est le gérant qui le saisit — il est la seule
     * source du chiffre d'affaires, le déduire d'une marge le rendrait faux.
     */
    private function prixDeVenteALaCreation(Request $request): float
    {
        return ModeDeVente::parInventaire()
            ? (float) $request->input('price', 0)
            : Product::prixDeVenteConseille((float) $request->input('price_sale', 0));
    }

    public function validation(Request $request)
    {
        return $request->validate([
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'libelle' => 'required|string|max:225',
            'sku' => 'required|string|max:225',
            'description' => 'required|string',
            'price_sale' => 'required|numeric|min:0',
            // En mode inventaire, le prix de vente est saisi par le gérant : il
            // est la seule source du chiffre d'affaires, on ne le devine pas.
            'price' => ModeDeVente::parInventaire() ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'price_sale_minimum' => $this->regleMinimum($request, $this->prixDeVenteALaCreation($request)),
            'stock_initial' => 'required|integer|min:0',
            'store_id' => 'required|exists:stores,id',
            'image' => 'image',
        ], [
            'categories.required' => 'veuillez selectionner la categorie',
            'categories.array' => 'Selectionner plusieurs categories',
            'libelle.required' => 'champ libelle doit être rempli',
            'libelle.string' => 'champ libelle prend au maximum 225 caractères',
            'sku.required' => 'champ sku doit être rempli',
            'sku.string' => 'champ sku prend au maximum 225 caractères',
            'description.required' => 'champ description doit être rempli avec une chaine de caractere',
            'price_sale.required' => 'champ Prix de Revient doit être rempli avec une valeure numérique',
            'price_sale.numeric' => 'champ Prix de Revient doit être rempli avec une valeure numérique',
            'stock_initial.required' => 'champ Stock initial doit être rempli',
            'stock_initial.integer' => 'champ Stock initial doit être un nombre entier',
            'stock_initial.min' => 'champ Stock initial doit être positif',
            'store_id.required' => 'champ Magasin doit être rempli',
            'store_id.exists' => 'Magasin sélectionné invalide',
            'image.image' => 'champ image ne prend que des images',
        ]);
    }

    /**
     * Création rapide d'un produit en AJAX (ex : depuis le formulaire Achat, pour ne pas
     * obliger l'utilisateur à quitter la page en cours). Pas de prix ici : le prix de
     * revient n'est connu qu'une fois l'achat complet soumis (transport/douane/frais
     * divers répartis) — c'est PurchaseOrderCostingService qui le renseignera, avec le
     * prix de vente calculé automatiquement à ce moment-là (cf. costOrder()). Pas
     * d'image ni de stock initial non plus — le stock est ajouté par la ligne d'achat
     * qui suit la création. La fiche complète reste modifiable ensuite depuis Produit
     * List.
     */
    public function quickStore(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|max:225',
            'sku' => 'required|string|max:225',
            'category_id' => 'required|exists:categories,id',
        ], [
            'libelle.required' => 'Le nom du produit est requis.',
            'sku.required' => 'Le SKU est requis.',
            'category_id.required' => 'La catégorie est requise.',
        ]);

        try {
            $product = Product::create([
                'libelle' => $request->libelle,
                'sku' => $request->sku,
                'description' => $request->libelle,
                ...$this->valeursUnites(),
            ]);

            $product->categories()->attach($request->category_id);

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id, 'libelle' => $product->libelle, 'sku' => $product->sku,
                    'unite' => $product->unite, 'unite_libelle' => $product->unite_libelle,
                    'unite_pluriel' => Product::motUnite($product->unite, 2),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $this->validation($request);
        if (request()->hasfile('image')) {
            $productName = time().'-'.bin2hex(random_bytes(4)).'.'.request()->image->getClientOriginalExtension();
            request()->image->move(public_path('products'), $productName);
        }
        try {
            $priceSale = (float) $request->price_sale;
            $price = $this->prixDeVenteALaCreation($request);

            $product = Product::create([
                'libelle' => $request->libelle,
                'sku' => $request->sku,
                'description' => $request->description,
                'price' => $price,
                'price_sale' => $priceSale,
                'price_manuel' => ModeDeVente::parInventaire(),
                'price_sale_minimum' => $this->valeurMinimum($request),
                // Sans upload, on stocke une valeur vide : les vues basculent alors
                // sur leur visuel de repli. Pointer vers un "ib profile.jpg" qui
                // n'existe pas produisait une image cassée sur chaque fiche.
                'image' => $productName ?? '',
                ...$this->valeursUnites(),
            ]);

            $product->categories()->attach($request->categories);

            // Ajout du stock dans le magasin sélectionné
            $storeId = $request->store_id;
            // Stock initial, tracé dans le journal comme premier mouvement.
            app(StockService::class)->increment((int) $storeId, (int) $product->id, (int) $request->stock_initial, [
                'type' => StockMovement::INITIAL,
                'source_type' => 'product',
                'source_id' => $product->id,
                'reason' => 'Stock initial saisi à la création du produit',
            ]);
            // Stock initial nul : la ligne existe quand même, le produit reste
            // rattaché au magasin choisi (comme avant).
            StoreProduct::firstOrCreate(['store_id' => $storeId, 'product_id' => $product->id], ['quantity' => 0]);

            return redirect()->route('produits.index')->with('success', 'Produit crée avec succès.');
        } catch (\Exception $e) {
            return back()->with('fall', 'une erreur lors de lajout, voici le message : '.$e->getMessage());
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
                'category_name' => $product->categories->pluck('slug')->implode(', '),
            ],
        ]);
    }

    public function edit($id)
    {
        $product = Product::with(['categories', 'stores', 'latestLigneCommande'])->findOrFail($id);
        // ✅ SUPPRIMÉ: Category::where('tenant_id', Auth::user()->tenant_id)
        $categories = Category::all();
        $stores = Store::select('id', 'store_name')->get();

        $user = Auth::user();
        $userStoreId = ($user && $user->isStoreScoped())
            ? $user->assignedStoreId()
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
            'libelle' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku,'.$id,
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock_restant' => 'required|integer|min:0',
            'price_sale' => 'nullable|numeric',
            'price_sale_minimum' => $this->regleMinimum($request, (float) $request->input('price')),
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'store_id' => 'required|exists:stores,id',
        ]);

        try {
            // ✅ SUPPRIMÉ: withoutGlobalScope('tenant') - plus besoin
            $product = Product::findOrFail($id);
            $storeId = $request->input('store_id');

            $product->fill($request->only([
                'libelle', 'sku', 'description', 'price', 'price_sale',
            ]));
            $product->price_sale_minimum = $this->valeurMinimum($request);

            // Le prix de vente saisi s'écarte-t-il du prix conseillé (prix de
            // revient + marge) ? Alors c'est une décision du gérant, et les
            // achats suivants ne la recalculeront plus. Resaisir exactement le
            // prix conseillé remet le produit en automatique : pas de case à
            // cocher à comprendre, le chiffre saisi suffit à exprimer l'intention.
            $product->price_manuel = (float) $product->price
                !== Product::prixDeVenteConseille((float) $product->price_sale);

            $product->fill($this->valeursUnites());

            $product->save();

            if ($request->hasFile('image')) {
                $productName = time().'-'.bin2hex(random_bytes(4)).'.'.$request->image->getClientOriginalExtension();
                $request->image->move(public_path('products'), $productName);
                $product->update(['image' => $productName]);
            }

            // ✅ SUPPRIMÉ: tenant_id dans sync
            $product->categories()->sync($request->categories);

            // Le stock saisi sur la fiche produit n'est plus écrasé en silence :
            // l'écart avec le stock enregistré devient un mouvement d'ajustement
            // tracé (auteur, date, avant/après), dans le périmètre de l'utilisateur.
            $this->authorizeStore($storeId);
            app(StockService::class)->adjustTo((int) $storeId, (int) $id, (int) $request->input('stock_restant'), [
                'type' => StockMovement::ADJUSTMENT,
                'source_type' => 'product',
                'source_id' => (int) $id,
                'reason' => 'Stock modifié depuis la fiche produit',
            ]);

            return redirect()
                ->route('produits.index')
                ->with('success', 'Produit mis à jour avec succès!');

        } catch (\Exception $e) {
            return back()->with('fall', 'Erreur: '.$e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // Exports
    //
    // Ces trois exports vivaient dans des closures de routes/web.php, où ils
    // avaient gardé l'ancien couplage « role_id == 3 + stores.user_id » alors
    // que le reste du projet est passé à isStoreScoped()/assignedStoreId().
    // Conséquence : un vendeur affecté par le mécanisme actuel obtenait un
    // périmètre nul, donc le stock de TOUS les emplacements cumulés — alors
    // que la liste à l'écran ne lui montrait que le sien — et les rôles créés
    // ensuite (magasinier, responsable) n'étaient pas limités du tout.
    // Le même export existait en trois formats avec deux règles différentes.
    // -----------------------------------------------------------------

    public function exportExcel()
    {
        try {
            return Excel::download(new ProductsExport, 'products.xlsx');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de l\'export : '.$e->getMessage());
        }
    }

    public function exportPdf()
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        try {
            $dataTable = Product::with('categories', 'stores')->get();
            $userStoreId = $this->emplacementImpose();

            $pdf = Pdf::loadView('products.export', compact('dataTable', 'userStoreId'));
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'chroot' => public_path(),
            ]);

            return $pdf->download('products_'.date('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur: '.$e->getMessage());
        }
    }

    public function exportCsv()
    {
        $filename = 'products_'.date('Y-m-d').'.csv';
        $userStoreId = $this->emplacementImpose();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($userStoreId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nom', 'SKU', 'Catégories', 'Stock', 'Unité', 'Prix']);

            Product::with('categories', 'stores')->chunk(100, function ($products) use ($handle, $userStoreId) {
                foreach ($products as $product) {
                    $stock = $userStoreId
                        ? (int) ($product->stores->firstWhere('id', $userStoreId)?->pivot->quantity ?? 0)
                        : (int) $product->stores->sum('pivot.quantity');

                    fputcsv($handle, [
                        $product->libelle,
                        $product->sku,
                        $product->categories->pluck('slug')->implode('|'),
                        $stock,
                        Product::motUnite($product->unite, $stock),
                        $product->price ?? 0,
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function destroy($id)
    {
        try {
            Product::findOrFail($id)->delete();

            return redirect()->back()->with('success', 'Produit supprimé avec succès.');
        } catch (\RuntimeException $th) {
            // Le modèle refuse un produit encore en stock ou cité par des
            // mouvements, et dit lequel des deux : son message vaut mieux
            // qu'un texte générique.
            return back()->with('error', $th->getMessage());
        } catch (QueryException $th) {
            if ($th->getCode() == '23000') {
                return back()->with(
                    'error',
                    "Impossible de supprimer ce produit : il est déjà utilisé dans d'autres enregistrements."
                );
            }

            return redirect()->back()->with('error', 'Produit pas supprimé. Voici l\'erreur'.$th->getMessage());
        }
    }
}
