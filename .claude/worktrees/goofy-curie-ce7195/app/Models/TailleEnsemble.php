<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TailleEnsemble extends Model
{
    use HasFactory;
    protected $fillable = ['slug', 'category_name'];
    public function produits(){
        return $this->hasMany(Product::class);
    }
}
