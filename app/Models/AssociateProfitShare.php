<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Part du bénéfice d'une année attribuée à un associé, saisie à la main. */
class AssociateProfitShare extends Model
{
    protected $fillable = ['associate_id', 'year', 'amount', 'user_id'];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function associate()
    {
        return $this->belongsTo(Associate::class);
    }
}
