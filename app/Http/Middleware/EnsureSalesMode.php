<?php

namespace App\Http\Middleware;

use App\Support\ModeDeVente;
use Closure;
use Illuminate\Http\Request;

/**
 * Réserve un écran au mode de vente qui le concerne.
 *
 * Usage : ->middleware('mode:caisse') ou ->middleware('mode:inventaire')
 *
 * Masquer l'entrée de menu ne suffit pas : une adresse connue, un favori ou
 * un lien dans un ancien e-mail y mènent encore.
 */
class EnsureSalesMode
{
    public function handle(Request $request, Closure $next, string $mode)
    {
        if (ModeDeVente::actuel() === $mode) {
            return $next($request);
        }

        abort(403, $mode === ModeDeVente::CAISSE
            ? 'Cette installation ne saisit pas les ventes une par une : le chiffre d\'affaires se déduit des comptages de stock (Inventaires).'
            : 'Cette installation saisit chaque vente au point de vente : le chiffre d\'affaires vient des ventes, pas des comptages de stock.');
    }
}
