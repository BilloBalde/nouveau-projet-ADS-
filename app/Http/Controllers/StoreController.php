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
        $dataTable = Store::all();
        return view('stores.index', compact('dataTable'));
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
        return view('stores.create', compact('places', 'users'));
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
        ]);

        if(request()->hasfile('store_picture')){
            $storeName = time().'.'.request()->store_picture->getClientOriginalExtension();
            request()->store_picture->move(public_path('stores'), $storeName);
        }
        try{
            Store::create([
                'store_name' => $request->store_name,
                'place_id' => $request->place_id,
                'user_id' => $request->user_id,
                'status' => $request->status,
                'store_picture' => $storeName ?? NULL,
                'description' => $request->description
            ]);
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
        ], [
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
            ]);

            return response()->json([
                'success' => true,
                'store' => ['id' => $store->id, 'store_name' => $store->store_name],
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $store = Store::find($id);
        $places = Place::all();
        $users = User::all();
        return view('stores.edit', compact('store','places', 'users'));

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
        ]);
        $store = Store::find($id);
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
            $store->save();
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
