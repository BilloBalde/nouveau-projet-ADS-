<?php

namespace App\Http\Controllers;

use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PlaceController extends Controller
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
        $dataTable = Place::all();
        return view('places.index', compact('dataTable'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('places.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function validateform($request){
        $validate = Validator::make($request->all(), [
            'placeName'=>'required|max:25',
            'countryName'=>'required',
            'description'=>'required',
        ],[
            'placeName.required' => 'champ placeName doit etre rempli.',
            'placeName.max' => 'champ placeName prend maximum 25 charactere.',
            'countryName.required' => 'champ countryName est obligatoire.',
            'description.required' => 'champ description doit etre rempli.',
        ]);
        return $validate;
    }

    public function store(Request $request)
    {
        //dd('I got the error message here');
        Log::info('Store method called');
        Log::info($request->all());
        $request->validate([
            'placeName' => 'required|string|max:255',
            'countryName' => 'required|string|max:255',
            'description' => 'nullable|string',
        ],[
            'placeName.required' => 'champ placeName doit etre rempli',
            'placeName.max' => 'champ placeName prend maximum 25 characteres.',
            'placeName.string' => 'champ placeName prend que de chaine de caractères.',
            'countryName.required' => 'veuillez selectionner le pays'
        ]);
        try {
            Place::updateOrCreate(['id' => $request->id], [
                'placeName' => $request->placeName,
                'countryName' => $request->countryName,
                'description' => $request->description,
            ]);
            if(!empty($request->id)) {
                session(['error' => 'Place '. $request->id.' modifié avec succès.']);
            } else {
                session(['success' => 'Nouveau Place enregistré']);
            }
            return redirect()->route('places.index')->with('success','Place added successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('places.index')->with(['error' => $th->getMessage()], 500);
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
        $place = Place::find($id);
        return view('places.edit', compact('place'));
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
        Log::info('Update method called');
        Log::info($request->all());
        $request->validate([
            'placeName' => 'required|string|max:255',
            'countryName' => 'required|string|max:255',
            'description' => 'nullable|string',
        ],[
            'placeName.required' => 'champ placeName doit etre rempli',
            'placeName.max' => 'champ placeName prend maximum 25 characteres.',
            'placeName.string' => 'champ placeName prend que de chaine de caractères.',
            'countryName.required' => 'veuillez selectionner le pays'
        ]);
        try {
            Place::updateOrCreate(['id' => $id], [
                'placeName' => $request->placeName,
                'countryName' => $request->countryName,
                'description' => $request->description,
            ]);
            if(!empty($request->id)) {
                session(['error' => 'Place '. $request->id.' modifié avec succès.']);
            } else {
                session(['success' => 'Nouveau Place enregistré']);
            }
            return redirect()->route('places.index')->with('success','Place added successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('places.index')->with(['error' => $th->getMessage()], 500);
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
        //
    }
}
