<?php

namespace App\Http\Controllers;

use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentSettingController extends Controller
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
        $dataTable = PaymentSetting::all();
        return view('paymentSetting.index', ['dataTable'=>$dataTable]);
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

    public function validateform(Request $request) {
        $validate = Validator::make($request->all(), [
            'typeName'=>'required|max:25',
            'status'=>'required|integer',
        ],[
            'typeName.required' => 'champ typeName doit etre rempli.',
            'typeName.max' => 'champ typeName prend maximum 25 charactere.',
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
                $paymentSetting = new PaymentSetting();
                $paymentSetting->typeName = $request->typeName;
                $paymentSetting->status = $request->status;
                $paymentSetting->save();

                session(['success' => '']);
                flash('Nouveau Payement Type enregistré');

                return back();
            } catch (\Throwable $th) {
                session(['warning' => 'Payement Type '. $request->id.' pas enregistré. Veuillez changer de nom car celui existe déjà']);
                return back()->with(['noEntry' => 'Veuillez changer de slug car celui existe déjà']);
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
        if ($this->validateform($request)->fails()) {
            return back()->with(['error' => $this->validateform($request)->errors()]);
        }else {
            try {
                $paymentSetting = PaymentSetting::where($id);
                $paymentSetting->typeName = $request->typeName;
                $paymentSetting->status = $request->status;
                $paymentSetting->update();

                session(['success' => 'Type de payment modifié']);
                flash('Type de payment modifié');

                return back();
            } catch (\Throwable $th) {
                session(['warning' => 'Payement Type '. $request->id.' pas enregistré. Veuillez changer de nom car celui existe déjà']);
                return back()->with(['noEntry' => 'Veuillez changer de slug car celui existe déjà']);
            }
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
