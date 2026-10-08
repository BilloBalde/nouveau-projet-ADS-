<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'address', 'notes'];

    /**
     * Un partenaire est un fournisseur : il approvisionne les achats
     * (purchase_orders.partner_id) comme les ventes tierces (sales.partner_id).
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'partner_id');
    }
}