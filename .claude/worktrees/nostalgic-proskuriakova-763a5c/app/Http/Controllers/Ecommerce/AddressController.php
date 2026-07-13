<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\AddressRequest;
use App\Models\DeliveryAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = Auth::user()->addresses;
        return view('ecommerce.addresses.index', compact('addresses'));
    }

    public function create()
    {
        return view('ecommerce.addresses.create');
    }

    public function store(AddressRequest $request)
    {
        Auth::user()->addresses()->create($request->validated());
        return redirect()->route('addresses.index')->with('success', 'Adresse ajoutée avec succès.');
    }

    public function destroy($id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $address->delete();
        return redirect()->route('addresses.index')->with('success', 'Adresse supprimée.');
    }
}
