<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Facture;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'delivery_address_id', 'total_amount', 'status', 'payment_method',
        'payment_status', 'transaction_id', 'invoice_number',
        'store_id', 'assigned_by', 'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
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

    /** Boutique à qui la commande est attribuée (null = traitée au siège). */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Commandes visibles : toutes pour un profil à vue globale ; pour un
     * employé de boutique (ou le propriétaire entré dans une section), seules
     * celles attribuées à son périmètre. Une commande non attribuée reste au
     * siège.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        return $query->whereIn('store_id', $user->accessibleStoreIds());
    }

    public function isVisibleTo(User $user): bool
    {
        return ! $user->isStoreScoped()
            || ($this->store_id && in_array((int) $this->store_id, $user->accessibleStoreIds(), true));
    }
}
