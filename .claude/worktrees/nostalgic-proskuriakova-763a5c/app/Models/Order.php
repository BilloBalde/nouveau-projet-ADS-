<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Facture;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'delivery_address_id', 'total_amount', 'status', 'payment_method',
        'payment_status', 'transaction_id', 'invoice_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(DeliveryAddress::class, 'delivery_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function facture()
    {
        return $this->hasOne(Facture::class, 'numero_facture', 'invoice_number');
    }

    public function sales()
    {
        return $this->hasMany(\App\Models\Sale::class, 'numeroFacture', 'invoice_number');
    }
}
