<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $guarded = ['id'];

    /**
     * Recalcule total_taken, total_repaid et balance depuis les factures.
     *
     * Ces trois colonnes étaient tenues par incréments, à chaque vente et à
     * chaque encaissement. Un incrément manqué s'installait définitivement :
     * l'approbation d'une commande en ligne, par exemple, créait une facture
     * sans jamais toucher aux compteurs, et le client restait débiteur d'un
     * montant qui n'apparaissait nulle part.
     *
     * Les factures, elles, se sont révélées fiables : montant_total correspond
     * aux lignes de vente et reste au montant encore dû, sans écart. On repart
     * donc d'elles, à chaque mutation, plutôt que d'ajouter et de retrancher.
     *
     * Convention : balance = total_taken - total_repaid, un solde POSITIF
     * signifiant que le client doit de l'argent.
     */
    public function recalculerCompteurs(): void
    {
        $factures = $this->factures()->get();

        $pris = round((float) $factures->sum('montant_total'), 2);
        $du = round((float) $factures->sum('reste'), 2);

        $this->forceFill([
            'total_taken' => $pris,
            'total_repaid' => round($pris - $du, 2),
            'balance' => $du,
        ])->save();
    }

    public function transaction_factures()
    {
        return $this->hasMany(TransactionFacture::class);
    }

    // Relationships with soft delete cascade
    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
