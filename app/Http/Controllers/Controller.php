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
     * Un employé rattaché à un magasin (vendeur, magasinier, responsable — tout
     * profil sans la permission « stores.all ») ne doit pouvoir écrire que pour
     * son propre magasin : store_id ne doit jamais venir du formulaire pour lui,
     * car c'est un champ falsifiable côté client. Retourne null pour les profils
     * à vue globale (admin, manager, comptable, achat...), qui restent libres de
     * choisir une boutique.
     */
    protected function ownStoreIdOverride(): ?int
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return null;
        }

        return $user->forcedStoreId();
    }

    /** 403 si l'emplacement est hors du périmètre de l'utilisateur connecté. */
    protected function authorizeStore($storeId): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->canAccessStore($storeId)) {
            abort(403, 'Cet emplacement ne fait pas partie de votre périmètre.');
        }
    }
}
