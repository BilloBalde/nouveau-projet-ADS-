<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtReminder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'last_sent_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function dette()
    {
        return $this->belongsTo(Dette::class);
    }

    public function facture()
    {
        return $this->belongsTo(Facture::class);
    }
}
