<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Compte d'un associé arrêté à la fin d'une année, et figé :
 * report de l'année précédente + part du bénéfice − sorties = solde.
 * Un solde positif lui est versé ; un solde négatif est reporté sur l'année
 * suivante, où il viendra en déduction de sa part.
 */
class AssociateYearClosing extends Model
{
    protected $fillable = [
        'associate_id', 'year', 'carried_in', 'profit_share', 'withdrawals',
        'balance', 'paid', 'carried_out', 'user_id', 'closed_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'carried_in' => 'decimal:2',
        'profit_share' => 'decimal:2',
        'withdrawals' => 'decimal:2',
        'balance' => 'decimal:2',
        'paid' => 'decimal:2',
        'carried_out' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function associate()
    {
        return $this->belongsTo(Associate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
