<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Journalier extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    public function paiementJournaliers(){
        return $this->hasMany(PaiementJournalier::class);
    }
}
