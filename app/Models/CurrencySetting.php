<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencySetting extends Model
{
    use HasFactory;

    /**
     * Sans garde, Model::create($request->all()) laissait écrire n'importe
     * quelle colonne, « id » compris. Le taux de change sert aux conversions
     * de prix : il n'a rien à faire dans un champ caché de formulaire.
     */
    protected $fillable = [
        'currencyName',
        'currencyCode',
        'currencySymbol',
        'rate_to_gnf',
        'status',
    ];
}
