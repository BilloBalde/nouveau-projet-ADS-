<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ecommerce\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Connexion des clients de la boutique en ligne.
 *
 * Le code OTP par SMS a été retiré du parcours : le client s'inscrit avec un
 * numéro guinéen valide et se connecte directement. Le fournisseur SMS rejetait
 * tout numéro hors Guinée, et ce projet n'en a plus aucun de configuré : le code
 * n'aurait de toute façon jamais pu arriver.
 *
 * Le code OTP lui-même reste en place — AuthService::generateOtp/verifyOtp, le
 * modèle OtpCode et SmsService — ainsi que showVerify()/verify() ci-dessous, qui
 * ne sont plus routés. Tout est prêt à resservir si la vérification revient.
 */
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

        Auth::login($user);

        return $this->redirigerApresConnexion('Bienvenue ! Votre compte est créé.');
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
        $request->validate(
            ['phone' => ['required', 'string', AuthService::REGLE_TELEPHONE]],
            ['phone.regex' => AuthService::MESSAGE_TELEPHONE]
        );

        $user = $this->authService->login($request->phone);

        if (! $user) {
            return back()->withErrors([
                'phone' => "Aucun compte n'est enregistré avec ce numéro. Créez-en un en quelques secondes.",
            ])->withInput();
        }

        // Un compte du personnel qui passerait par ce formulaire serait connecté
        // ici puis refusé par EnsureCustomer, avec un message peu clair. Autant
        // l'orienter tout de suite vers l'espace de gestion.
        if (! $user->isCustomer()) {
            return back()->withErrors([
                'phone' => 'Ce numéro est rattaché à un compte du personnel. Connectez-vous depuis l\'espace de gestion.',
            ])->withInput();
        }

        Auth::login($user);

        return $this->redirigerApresConnexion('Bienvenue !');
    }

    /**
     * Destination après une connexion ou une inscription réussie.
     *
     * Reprend l'intention posée en session avant la connexion — « Commander »
     * depuis une fiche produit, ou un retour au paiement — et retombe sinon sur
     * l'espace client.
     */
    private function redirigerApresConnexion(string $message)
    {
        $redirect = session()->pull('auth_redirect');
        $buyNowProductId = session()->pull('buy_now_product_id');

        if ($buyNowProductId || $redirect === 'checkout') {
            return redirect()->route('checkout')->with('success', $message);
        }

        return redirect()->route('shop.home')->with('success', $message);
    }

    /**
     * Vérification OTP — plus routée, conservée pour un éventuel retour.
     */
    public function showVerify(Request $request)
    {
        $phone = $request->query('phone');
        if (! $phone) {
            return redirect()->route('otp.login')->with('error', 'Indiquez d\'abord votre numéro sur la page de connexion.');
        }

        return view('ecommerce.auth.verify', compact('phone'));
    }

    /**
     * Vérification OTP — plus routée, conservée pour un éventuel retour.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        if (! $this->authService->verifyOtp($request->phone, $request->code)) {
            return back()->withErrors(['code' => 'Code OTP invalide ou expiré.']);
        }

        Auth::login($this->authService->login($request->phone));

        return $this->redirigerApresConnexion('Bienvenue !');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('accueil');
    }
}
