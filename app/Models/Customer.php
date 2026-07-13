<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $guarded = ['id'];
    
    public function dettes(){
        return $this->hasMany(Dette::class);
    }

    public function transaction_factures(){
        return $this->hasMany(TransactionFacture::class);
    }
    // Relationships with soft delete cascade
    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
