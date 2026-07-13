<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logistic extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function store(){
        return $this->belongsTo(Store::class);
    }

   
}
