<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Fonds d'un associé : l'argent qu'il apporte et tout ce qu'il finance.
 * Les fonds propres du patron ne sont pas un enregistrement : partout,
 * fund_id NULL signifie « fonds propres ».
 */
class Fund extends Model
{
    protected $fillable = ['name', 'associate_name', 'phone', 'owner_share', 'associate_share', 'active', 'notes'];

    protected $casts = [
        'owner_share'     => 'float',
        'associate_share' => 'float',
        'active'          => 'boolean',
    ];

    /** Libellé des fonds propres dans les listes (fund_id NULL). */
    public const OWNER_LABEL = 'Fonds propres';

    public function transactions()
    {
        return $this->hasMany(FundTransaction::class)->orderByDesc('date')->orderByDesc('id');
    }

    /** Compte(s) utilisateur de l'associé (lecture seule de son relevé). */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** Libellé d'un fonds à partir de son id (null = fonds propres). */
    public static function labelFor(?int $fundId): string
    {
        if (! $fundId) {
            return self::OWNER_LABEL;
        }

        static $noms = [];

        return $noms[$fundId] ??= (static::whereKey($fundId)->value('name') ?? 'Fonds #' . $fundId);
    }
}
