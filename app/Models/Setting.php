<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * Table de réglages sans colonne métier à ce jour. La garde est posée
     * d'avance : une colonne ajoutée plus tard serait sinon assignable en
     * masse sans que personne n'y pense.
     */
    protected $fillable = [];
}
