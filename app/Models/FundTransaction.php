<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mouvement du compte à part d'un associé. Les achats financés par le fonds
 * et les encaissements de ses ventes ne sont pas saisis ici : ils se déduisent
 * des achats (purchase_orders.fund_id) et des factures (sales.fund_id).
 */
class FundTransaction extends Model
{
    public const APPORT = 'apport';
    public const REMBOURSEMENT_CAPITAL = 'remboursement_capital';
    public const VERSEMENT_BENEFICE = 'versement_benefice';
    public const PRELEVEMENT_PATRON = 'prelevement_patron';

    public const TYPES = [
        self::APPORT                => 'Apport de capital',
        self::REMBOURSEMENT_CAPITAL => 'Remboursement de capital',
        self::VERSEMENT_BENEFICE    => 'Versement de bénéfice à l\'associé',
        self::PRELEVEMENT_PATRON    => 'Prélèvement de la part du patron',
    ];

    protected $fillable = ['fund_id', 'type', 'amount', 'date', 'reference', 'notes', 'user_id'];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date',
    ];

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
