<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function getUserAttribute(){
        $user = User::find($this->user_id);
        return $user->username;
    }

    public function getPlaceAttribute(){
        $place = Place::find($this->place_id);
        return $place->placeName;
    }

    public function logistic(){
        return $this->hasMany(Logistic::class);
    }

    public function facture(){
        return $this->hasMany(Facture::class);
    }

    public function place(){
        return $this->belongsTo(Place::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'store_products')->withPivot('quantity');
    }

      public function manager()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
