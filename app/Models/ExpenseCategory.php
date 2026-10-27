<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    /**
     * Le slug sert de clé au code (« transport », « loyer »…) : il se pose à la
     * création d'une catégorie système, jamais depuis un formulaire. Il reste
     * donc hors de cette liste, et les seeders passent par forceCreate().
     */
    protected $fillable = [
        'categoryName',
    ];
}
