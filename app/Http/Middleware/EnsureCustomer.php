<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCustomer
{
    /**
     * Routes réservées aux comptes clients e-commerce.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('otp.login')->with('error', 'Connectez-vous pour accéder à cette page.');
        }

        if (! Auth::user()->isCustomer()) {
            return redirect()->route('home')->with('info', 'Cet espace est réservé aux clients.');
        }

        return $next($request);
    }
}
