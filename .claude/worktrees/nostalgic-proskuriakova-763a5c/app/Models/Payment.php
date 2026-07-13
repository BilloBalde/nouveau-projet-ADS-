<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public function getNumeroFactureAttribute(){
        $payement = Facture::find($this->facture_id);
        return $payement->numero_facture;
    }

    public function facture(){
        return $this->belongsTo(Facture::class);
    }
}
