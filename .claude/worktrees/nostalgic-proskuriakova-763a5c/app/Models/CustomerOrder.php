<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function catalogueCustomer()
    {
        return $this->belongsTo(CatalogueCustomer::class);
    }

    public function items()
    {
        return $this->hasMany(CustomerOrderItem::class);
    }
}
