<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Usage : ->middleware('permission:orders.validate')
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (! Auth::check() || ! Auth::user()->hasPermission($permission)) {
            abort(403, 'Vous n\'avez pas la permission d\'effectuer cette action.');
        }

        return $next($request);
    }
}
