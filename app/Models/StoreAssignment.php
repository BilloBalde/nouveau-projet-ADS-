<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Historique des affectations d'un employé à un magasin. L'affectation en
 * cours est celle dont ended_at est vide ; elle reflète users.store_id.
 */
class StoreAssignment extends Model
{
    protected $fillable = ['user_id', 'store_id', 'assigned_by', 'started_at', 'ended_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
