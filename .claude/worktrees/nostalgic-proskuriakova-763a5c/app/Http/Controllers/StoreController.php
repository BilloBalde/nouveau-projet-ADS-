<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

class StoreController extends Controller
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
            $store = Store::find($id);
            $firstStore = Store::orderBy('id')->first()?->id;
            if ($id == $firstStore) {
                return back()->with('error', 'Impossible de supprimer cette boutique, car elle est la seule');
            }else{
                $store->delete();
            }
            return redirect()->route('boutiques.index')->with('success', 'Stock supprimé avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('boutiques.index')->with('error', 'Error lors de la création : '.$th->getMessage());
        }
    }
}
