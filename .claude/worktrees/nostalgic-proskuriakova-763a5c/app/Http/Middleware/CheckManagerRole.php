<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckManagerRole
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            abort(403, 'Accès non autorisé.');
        }

        $user = Auth::user();

        if (! $user->isStaff()) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}