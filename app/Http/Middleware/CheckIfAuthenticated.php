<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckIfAuthenticated
{
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

        return $next($request);
    }
}
