<?php

namespace App\Http\Controllers;

use App\Models\CurrencySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencySettingController extends Controller
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
        $dataTable = CurrencySetting::all();
        return view('currencySetting.index', ['dataTable'=>$dataTable]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validateform(Request $request) {
        $validate = Validator::make($request->all(), [
            'currencyName'=>'required|max:25',
            'currencyCode'=>'required|max:25',
            'currencySymbol'=>'required|max:25',
            'status'=>'required|integer',
        ],[
            'currencyName.required' => 'champ currencyName doit etre rempli.',
            'currencyName.max' => 'champ currencyName prend maximum 25 charactere.',
            'currencyCode.required' => 'champ currencyCode doit etre rempli.',
            'currencyCode.max' => 'champ currencyCode prend maximum 25 charactere.',
            'currencySymbol.required' => 'champ currencySymbol doit etre rempli.',
            'currencySymbol.max' => 'champ currencySymbol prend maximum 25 charactere.',
            'status.require' => 'champ status doit etre rempli.',
            'status.integer' => 'champ status doit être entier.',
        ]);
        return $validate;
    }

    public function store(Request $request)
    {
        if ($this->validateform($request)->fails()) {
            return back()->with(['error' => $this->validateform($request)->errors()]);
        }else {
            try {
                $currencySetting = new CurrencySetting();
                $currencySetting->currencyName = $request->currencyName;
                $currencySetting->currencyCode = $request->currencyCode;
                $currencySetting->currencySymbol = $request->currencySymbol;
                $currencySetting->status = $request->status;
                $currencySetting->save();

                session(['success' => '']);
                flash('Nouveau Currency Setting enregistré');

                return back();
            } catch (\Throwable $th) {
                session(['warning' => 'Currency Setting '. $request->id.' pas enregistré. Veuillez changer de nom car celui existe déjà']);
                return back()->with(['noEntry' => 'Veuillez changer de nom car celui existe déjà']);
            }
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
        //
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
        //
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
