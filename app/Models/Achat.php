<?php

namespace App\Models;

use App\Models\Store;
use App\Models\Product;
use App\Models\LigneCommande;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Achat extends Model
{
    use HasFactory;
    protected $guarded = ['id'];


    public function ligneCommandes()
    {
        return $this->hasMany(LigneCommande::class);
    }

    public function products()
    {
        return $this->belongsTo(Product::class);
    }
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

}
