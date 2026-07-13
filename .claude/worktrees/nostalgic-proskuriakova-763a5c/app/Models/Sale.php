<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function getProduitAttribute(){
        return $this->product?->libelle;
    }

    public function getProduitImageAttribute(){
        return $this->product?->image;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class, 'numeroFacture', 'numero_facture');
    }
    
    public function store(){
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
