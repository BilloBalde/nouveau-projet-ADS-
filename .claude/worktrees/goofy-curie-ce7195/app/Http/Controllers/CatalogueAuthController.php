<?php

namespace App\Http\Controllers;

use App\Models\CatalogueCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CatalogueAuthController extends Controller
{
    public function showLogin()
    {
        return view('catalogue.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::guard('catalogue')->attempt($credentials, true)) {
            $request->session()->regenerate();
            return redirect()->route('storefront')->with('success', 'Connexion réussie.');
        }

        return back()->with('error', 'Identifiants invalides.');
    }

    public function showRegister()
    {
        return view('catalogue.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:catalogue_customers,email',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|max:64|confirmed',
        ]);

        $customer = CatalogueCustomer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        Auth::guard('catalogue')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('storefront')->with('success', 'Compte créé.');
    }

    public function logout(Request $request)
    {
        Auth::guard('catalogue')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('storefront')->with('success', 'Déconnexion.');
    }
}

