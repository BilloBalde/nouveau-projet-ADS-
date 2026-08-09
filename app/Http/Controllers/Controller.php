<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Un gérant de boutique (role_id = ROLE_STORE_MANAGER) ne doit pouvoir écrire que
     * pour sa propre boutique : store_id ne doit jamais venir du formulaire pour ce
     * rôle, car c'est un champ falsifiable côté client. Retourne null pour les autres
     * rôles (admin, manager, comptable, achat...), qui restent libres de choisir une
     * boutique.
     */
    protected function ownStoreIdOverride(): ?int
    {
        $user = auth()->user();

        if (! $user || (int) $user->role_id !== User::ROLE_STORE_MANAGER) {
            return null;
        }

        return $user->store_id;
    }
}
