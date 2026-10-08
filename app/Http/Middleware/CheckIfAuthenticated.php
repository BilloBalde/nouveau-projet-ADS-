<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckIfAuthenticated
{
    /**
     * Seuls écrans ouverts à un compte d'associé : son relevé, son profil,
     * ses notifications. Tout le reste du back-office lui est fermé, quelle
     * que soit l'URL saisie.
     */
    public const ASSOCIATE_ROUTES = [
        'associates.me', 'associates.show',
        'profile', 'profileImage', 'profileInfo', 'passwordupdate',
        'notifications.*', 'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter.');
        }

        $user = Auth::user();

        if ($user->isCustomer()) {
            return redirect()->route('shop.home')->with('info', 'Utilisez votre espace client pour naviguer.');
        }

        if (! $user->isStaff()) {
            return redirect()->route('accueil')->with('error', 'Ce compte n\'a pas accès à l\'administration.');
        }

        if ($user->isAssociate() && ! $request->routeIs(self::ASSOCIATE_ROUTES)) {
            return redirect()->route('associates.me');
        }

        return $next($request);
    }
}
