<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function getProduitAttribute(){
        return $this->product?->libelle;
    }

    public function getProduitImageAttribute(){
        return $this->product?->image;
    }

    /**
     * Quantité telle que vendue, pour la facture : « 2 cartons (48 pcs) »,
     * « 3 rouleaux », « 5 pcs ». Les lignes antérieures aux unités de vente
     * n'ont pas d'unité et s'affichent en pièces, comme avant.
     */
    public function getQuantiteAfficheeAttribute(): string
    {
        $nombre = fn ($n) => number_format((float) $n, 0, ',', ' ');

        if (! $this->unite || $this->unite === 'pcs' || ! $this->quantite_unite) {
            return $nombre($this->quantity).' pcs';
        }

        $unite = Product::UNITES[$this->unite] ?? null;
        $mot = $unite
            ? mb_strtolower($this->quantite_unite > 1 ? $unite['pluriel'] : $unite['libelle'])
            : $this->unite;
        $texte = $nombre($this->quantite_unite).' '.$mot;

        return $this->unite === 'rouleau' ? $texte : $texte.' ('.$nombre($this->quantity).' pcs)';
    }

    /** Prix par unité vendue (par carton, par paquet…), sinon par pièce. */
    public function getPrixAfficheAttribute(): float
    {
        return (float) ($this->prix_unite ?? $this->prix);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'numeroFacture', 'numero_facture');
    }
    
    public function store(){
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Lignes dont l'emplacement source est visible par l'utilisateur. */
    public function scopeVisibleTo(\Illuminate\Database\Eloquent\Builder $query, ?User $user)
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        return $query->whereIn('store_id', $user->accessibleStoreIds());
    }

}
