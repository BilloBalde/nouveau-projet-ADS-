<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ligne d'une séance d'inventaire : un produit compté, et tous les termes du
 * calcul qui mène à sa part de chiffre d'affaires.
 *
 * Les termes sont figés à la validation plutôt que recalculés à l'affichage :
 * le rapport doit rester identique dans six mois, et le gérant doit pouvoir
 * refaire la soustraction à la main, terme par terme.
 */
class InventorySessionLine extends Model
{
    use Concerns\FigeUniteProduit;

    protected $guarded = ['id'];

    protected $casts = [
        'unit_price_gnf' => 'decimal:2',
        'revenue_gnf' => 'decimal:2',
    ];

    public function session()
    {
        return $this->belongsTo(InventorySession::class, 'inventory_session_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * Le trait FigeUniteProduit lit `quantity` pour l'affichage ; ici la
     * quantité de référence est celle comptée.
     */
    public function getQuantityAttribute(): int
    {
        return (int) $this->counted_quantity;
    }

    /** Quantité vendue avec son unité : « 12 cartons ». */
    public function getQuantiteVendueAfficheeAttribute(): string
    {
        return Product::quantiteEnUnite($this->sold_quantity, $this->unite);
    }

    /** Perte déclarée, valorisée au prix de vente figé. */
    public function perteValorisee(): float
    {
        return (float) $this->loss_quantity * (float) $this->unit_price_gnf;
    }

    /**
     * Le calcul de la ligne se vérifie-t-il ?
     *
     * compté au début + achats + entrants − sortants − pertes − autres
     *   − compté à la fin = vendu
     *
     * Sert aux tests et à l'écran : si l'égalité tombe en défaut, le chiffre
     * ne doit pas être présenté comme un chiffre d'affaires.
     */
    public function calculCoherent(): bool
    {
        $attendu = (int) $this->opening_quantity
            + (int) $this->purchased_quantity
            + (int) $this->transfer_in_quantity
            - (int) $this->transfer_out_quantity
            - (int) $this->loss_quantity
            + (int) $this->other_quantity
            - (int) $this->counted_quantity;

        return $attendu === (int) $this->sold_quantity;
    }
}
