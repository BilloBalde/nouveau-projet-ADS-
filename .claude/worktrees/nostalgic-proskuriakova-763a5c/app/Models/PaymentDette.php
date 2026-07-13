<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDette extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    public function dette(){
        return $this->belongsTo(Dette::class);
    }
}
