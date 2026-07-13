<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CatalogueCustomer extends Authenticatable
{
    use Notifiable;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
    ];

    public function orders()
    {
        return $this->hasMany(CustomerOrder::class);
    }
}

