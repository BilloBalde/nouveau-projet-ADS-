<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaiementJournalier extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    public function journalier(){
        return $this->belongsTo(Journalier::class);
    }
}
