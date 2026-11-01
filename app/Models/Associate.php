<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Associé de la boutique. Il reçoit chaque année une part du bénéfice, saisie
 * à la main, et prend de l'argent en cours d'année (les sorties, enregistrées
 * comme dépenses). La clôture de l'année fait la différence.
 */
class Associate extends Model
{
    protected $fillable = ['name', 'phone', 'notes', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function profitShares()
    {
        return $this->hasMany(AssociateProfitShare::class);
    }

    public function closings()
    {
        return $this->hasMany(AssociateYearClosing::class);
    }

    /** Sorties d'argent données à l'associé. */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
