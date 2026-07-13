<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Product extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $casts = [
        'promo_start_date' => 'date',
        'promo_end_date' => 'date',
    ];

    /**
     * Une promo peut être configurée à l'avance ou laissée expirer sans être
     * désactivée manuellement — on vérifie donc la fenêtre de dates à chaque
     * lecture plutôt que de se fier uniquement au booléen is_promo.
     */
    public function isPromoActive(): bool
    {
        if (!$this->is_promo || !$this->promo_price) {
            return false;
        }

        $today = Carbon::today();

        if ($this->promo_start_date && $today->lt($this->promo_start_date)) {
            return false;
        }

        if ($this->promo_end_date && $today->gt($this->promo_end_date)) {
            return false;
        }

        return true;
    }

    public function getEffectivePromoPriceAttribute(): ?float
    {
        return $this->isPromoActive() ? (float) $this->promo_price : null;
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_products');
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_products')->withPivot('quantity');
    }

    public function ligneCommande(){
        return $this->hasMany(LigneCommande::class);
    }
    
    public function latestLigneCommande()
    {
        return $this->hasOne(LigneCommande::class)->latestOfMany(); // uses created_at or id
    }

        
    
    

}
