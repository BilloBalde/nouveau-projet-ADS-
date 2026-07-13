<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneCommande extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function achat(){
        return $this->belongsTo(Achat::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }
}
