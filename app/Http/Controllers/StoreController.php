<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        // Route::resource('boutiques', ...) n'appliquait aucune permission : un
        // vendeur ou un comptable pouvait créer/modifier/supprimer des boutiques,
        // alors que le preset PermissionSeeder ne le prévoit que pour admin/manager
        // (et la vue seule pour "achat").
        $this->middleware('permission:stores.view')->only(['index', 'show']);
        $this->middleware('permission:stores.create')->only(['create', 'store']);
        $this->middleware('permission:stores.edit')->only(['edit', 'update']);
        $this->middleware('permission:stores.delete')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dataTable = Store::visibleTo(auth()->user()->isRestrictedToStore() ? auth()->user() : null)
            ->with(['parent:id,store_name', 'manager:id,name,username'])
            ->withCount('users')
            ->withSum('stockRows as stock_total', 'quantity')
            ->orderByDesc('is_main')
            ->orderBy('type')
            ->orderBy('store_name')
            ->get();
        return view('stores.index', compact('dataTable'));
    }

    /**
     * Règles communes aux champs « emplacement » (type, rattachement,
     * magasin principal) pour la création complète et la création rapide.
     */
    private function locationRules(?int $storeId = null): array
    {
        return [
            'type'      => 'nullable|in:' . implode(',', array_keys(Store::TYPES)),
            'parent_id' => 'nullable|exists:stores,id' . ($storeId ? '|not_in:' . $storeId : ''),
            'is_main'   => 'nullable|boolean',
            'address'   => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:255',
        ];
    }

    /**
     * Champs d'emplacement validés. Refuse un rattachement qui créerait une
     * boucle (A rattaché à B rattaché à A) ; un stock interne doit avoir un
     * magasin de rattachement.
     */
    private function locationData(Request $request, ?Store $store = null): array
    {
        $type = $request->input('type') ?: ($store->type ?? Store::TYPE_MAGASIN);
        $parentId = $request->filled('parent_id') ? (int) $request->input('parent_id') : null;

        if ($store && $parentId && in_array($parentId, Store::withDescendantIds($store->id), true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => 'Un emplacement ne peut pas être rattaché à lui-même ni à l\'un de ses sous-emplacements.',
            ]);
        }

        if ($type === Store::TYPE_STOCK && ! $parentId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'parent_id' => 'Un stock interne doit être rattaché à un magasin.',
            ]);
        }

        return array_filter([
            'type'      => $type,
            'parent_id' => $parentId,
            'is_main'   => $request->boolean('is_main'),
            'address'   => $request->input('address', $store->address ?? null),
            'phone'     => $request->input('phone', $store->phone ?? null),
        ], fn ($v) => $v !== null) + ['parent_id' => $parentId];
    }

    /** Un seul magasin principal : le désigner retire l'attribut aux autres. */
    private function syncMainStore(Store $store): void
    {
        if ($store->is_main) {
            Store::where('id', '!=', $store->id)->where('is_main', true)->update(['is_main' => false]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $places = Place::all();
        $users = User::where('id', '>', 2)->get();
        $parents = Store::optionsFor(null, false);
        return view('stores.create', compact('places', 'users', 'parents'));
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
           'store_name' =>'required|string|max:255',
            'place_id' =>'required|integer',
            'user_id' =>'required|integer',
           'status' =>'required|integer',
           'store_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'description' =>'required|string|max:255'
        ] + $this->locationRules());

        $location = $this->locationData($request);

        if(request()->hasfile('store_picture')){
            $storeName = time().'.'.request()->store_picture->getClientOriginalExtension();
            request()->store_picture->move(public_path('stores'), $storeName);
        }
        try{
            $store = Store::create([
                'store_name' => $request->store_name,
                'place_id' => $request->place_id,
                'user_id' => $request->user_id,
                'status' => $request->status,
                'store_picture' => $storeName ?? '',
                'description' => $request->description
            ] + $location);
            $this->syncMainStore($store);
            return redirect()->route('boutiques.index')->with('success', 'Stock crée avec succès.');
        }
        catch(\Exception $e) {
            return back()->with('fall', 'une erreur lors de lajout, voici le message : '.$e->getMessage());
        }
    }

    /**
     * Création rapide d'une boutique en AJAX (ex : depuis le formulaire Achat, pour ne
     * pas obliger l'utilisateur à quitter la page en cours). Retourne du JSON, pas de
     * upload de photo ni de champs annexes — la fiche complète reste modifiable ensuite
     * depuis Boutique List.
     */
    public function quickStore(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'place_id' => 'required|exists:places,id',
            'user_id' => 'required|exists:users,id',
        ] + $this->locationRules(), [
            'store_name.required' => 'Le nom de la boutique est requis.',
            'place_id.required' => 'La ville est requise.',
            'user_id.required' => 'Le responsable est requis.',
        ]);

        try {
            $store = Store::create([
                'store_name' => $request->store_name,
                'place_id' => $request->place_id,
                'user_id' => $request->user_id,
                'status' => 1,
                'store_picture' => '',
                'description' => $request->store_name,
            ] + $this->locationData($request));
            $this->syncMainStore($store);

            return response()->json([
                'success' => true,
                'store' => [
                    'id' => $store->id,
                    'store_name' => $store->full_name . ' (' . $store->type_label . ')',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return redirect()->route('stock.show', $id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $store = Store::findOrFail($id);
        $places = Place::all();
        $users = User::all();
        $parents = Store::optionsFor(null, false)->whereNotIn('id', Store::withDescendantIds($store->id));
        return view('stores.edit', compact('store','places', 'users', 'parents'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //dd($id);
        $request->validate([
            'place_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
            'status' => ['required','string'],
            'description' => ['required','string'],
        ] + $this->locationRules((int) $id));
        $store = Store::findOrFail($id);
        $location = $this->locationData($request, $store);
        if(request()->hasfile('store_picture')){
            $storeName = time().'.'.request()->store_picture->getClientOriginalExtension();
            request()->store_picture->move(public_path('stores'), $storeName);
            $store->store_picture = $storeName;
        }

        try{
            $store->store_name = $request->store_name;
            $store->place_id = $request->place_id;
            $store->user_id = $request->user_id;
            $store->status = $request->status;
            $store->description = $request->description;
            $store->fill($location);
            $store->save();
            $this->syncMainStore($store);
            return redirect()->route('boutiques.index')->with('success', 'Stock modifié '.$request->store_name.' avec succès.');
        }
        catch(\Exception $e) {
            return back()->with('fall', 'une erreur lors de la modification, voici le message : '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $store = Store::findOrFail($id);
            if ($store->children()->exists()) {
                return back()->with('error', 'Impossible de supprimer cet emplacement : des entrepôts ou stocks y sont rattachés. Rattachez-les ailleurs d\'abord.');
            }
            if ($store->is_main) {
                return back()->with('error', 'Impossible de supprimer le magasin principal. Désignez d\'abord un autre magasin principal.');
            }
            $firstStore = Store::orderBy('id')->first()?->id;
            if ($id == $firstStore) {
                return back()->with('error', 'Impossible de supprimer cette boutique, car elle est la seule');
            }else{
                $store->delete();
            }
            return redirect()->route('boutiques.index')->with('success', 'Boutique supprimée avec succès.');
        } catch (QueryException $e) {
            // Erreur 1451 = violation de clé étrangère (ventes, achats, factures, devis...
            // liés à cette boutique) — on ne peut pas supprimer sans perdre l'historique.
            if ((int) $e->getCode() === 23000) {
                return back()->with('error', 'Impossible de supprimer cette boutique : elle a des ventes, achats, factures ou d\'autres données associées. Désactivez-la plutôt avec le bouton Statut.');
            }
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        } catch (\Throwable $th) {
            return back()->with('error', 'Erreur lors de la suppression : ' . $th->getMessage());
        }
    }
}
