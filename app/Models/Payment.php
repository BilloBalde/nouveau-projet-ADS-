<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function getNumeroFactureAttribute()
    {
        $payement = Facture::find($this->facture_id);

        return $payement->numero_facture;
    }

    /**
     * Paiements visibles par l'utilisateur : tous pour une vue globale, sinon
     * ceux des factures de son périmètre (même règle que les factures).
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        return $query->whereHas('facture', fn ($q) => $q->whereIn('store_id', $user->accessibleStoreIds()));
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
