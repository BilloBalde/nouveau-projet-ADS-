<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthOtpController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function showRegister()
    {
        return view('ecommerce.auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());
        $this->authService->generateOtp($user->phone);

        return redirect()->route('otp.verify', ['phone' => $user->phone])
            ->with('success', 'Inscription réussie. Veuillez entrer le code OTP.');
    }

    public function showLogin(Request $request)
    {
        if ($request->query('redirect')) {
            session(['auth_redirect' => $request->query('redirect')]);
        }
        if ($request->query('product_id')) {
            session(['buy_now_product_id' => $request->query('product_id')]);
        }
        return view('ecommerce.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        $user = $this->authService->login($request->phone);

        if (!$user) {
            return back()->withErrors(['phone' => 'Numéro non trouvé.']);
        }

        $this->authService->generateOtp($user->phone);

        return redirect()->route('otp.verify', ['phone' => $user->phone])
            ->with('success', 'Code OTP envoyé sur votre téléphone.');
    }

    public function showVerify(Request $request)
    {
        $phone = $request->query('phone');
        if (! $phone) {
            return redirect()->route('otp.login')->with('error', 'Indiquez d\'abord votre numéro sur la page de connexion.');
        }

        return view('ecommerce.auth.verify', compact('phone'));
    }

    public function verify(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code'  => 'required|string|size:6',
        ]);

        $verified = $this->authService->verifyOtp($request->phone, $request->code);

        if ($verified) {
            $user = $this->authService->login($request->phone);
            Auth::login($user);

            $redirect = session()->pull('auth_redirect');
            $buyNowProductId = session()->pull('buy_now_product_id');

            // Si l'utilisateur venait de cliquer "Commander", rediriger vers checkout
            if ($buyNowProductId) {
                return redirect()->route('checkout')->with('success', 'Bienvenue ! Veuillez confirmer votre commande.');
            }

            if ($redirect === 'checkout') {
                return redirect()->route('checkout')->with('success', 'Bienvenue !');
            }

            return redirect()->route('shop.home')->with('success', 'Bienvenue !');
        }

        return back()->withErrors(['code' => 'Code OTP invalide ou expiré.']);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('accueil');
    }
}
