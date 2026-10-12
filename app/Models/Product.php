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
        'unites_vente' => 'array',
    ];

    /**
     * Unités dans lesquelles un produit peut se vendre. Le stock est compté
     * dans l'unité de base : la pièce, ou le rouleau pour un produit vendu au
     * rouleau (un rouleau ne se combine donc pas avec les autres unités).
     */
    public const UNITES = [
        'pcs'      => ['libelle' => 'Pièce',    'pluriel' => 'pièces'],
        'douzaine' => ['libelle' => 'Douzaine', 'pluriel' => 'douzaines'],
        'carton'   => ['libelle' => 'Carton',   'pluriel' => 'cartons'],
        'paquet'   => ['libelle' => 'Paquet',   'pluriel' => 'paquets'],
        'rouleau'  => ['libelle' => 'Rouleau',  'pluriel' => 'rouleaux'],
    ];

    /** Codes d'unité activés, dans l'ordre de UNITES ; « pcs » par défaut. */
    public function codesUnites(): array
    {
        $codes = array_values(array_intersect(array_keys(self::UNITES), (array) ($this->unites_vente ?: [])));

        return $codes ?: ['pcs'];
    }

    /**
     * Nombre d'unités de base contenues dans une unité de vente, ou null si
     * l'unité n'est pas proposée pour ce produit (ou mal renseignée).
     */
    public function facteurUnite(string $code): ?int
    {
        if (! in_array($code, $this->codesUnites(), true)) {
            return null;
        }

        return match ($code) {
            'pcs', 'rouleau' => 1,
            'douzaine'       => 12,
            'carton'         => $this->pcs_par_carton ? (int) $this->pcs_par_carton : null,
            'paquet'         => $this->pcs_par_paquet ? (int) $this->pcs_par_paquet : null,
            default          => null,
        };
    }

    /** Unité de base du stock : « rouleau » pour un produit vendu au rouleau. */
    public function uniteDeBase(): string
    {
        return $this->codesUnites() === ['rouleau'] ? 'rouleau' : 'pcs';
    }

    /**
     * Unités proposées à la vente : code => libellé détaillé, facteur.
     * Ex. « Carton (24 pcs) », « Rouleau (50 m) ».
     */
    public function unitesDisponibles(): array
    {
        $unites = [];
        foreach ($this->codesUnites() as $code) {
            $facteur = $this->facteurUnite($code);
            if (! $facteur) {
                continue;
            }
            $detail = match ($code) {
                'douzaine' => '12 pcs',
                'carton', 'paquet' => $facteur.' pcs',
                'rouleau' => $this->dimension_rouleau,
                default => null,
            };
            $unites[$code] = [
                'code'    => $code,
                'libelle' => self::UNITES[$code]['libelle'].($detail ? ' ('.$detail.')' : ''),
                'facteur' => $facteur,
            ];
        }

        return $unites ?: ['pcs' => ['code' => 'pcs', 'libelle' => 'Pièce', 'facteur' => 1]];
    }

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
        return $this->belongsToMany(Store::class, 'store_products')->withPivot('quantity', 'fund_id');
    }

    public function ligneCommande(){
        return $this->hasMany(LigneCommande::class);
    }
    
    /**
     * Coût d'achat unitaire (prix de revient), règle unique pour toutes les
     * ventes : point de vente, facturation, commandes en ligne, devis.
     *
     * price_sale est synchronisé à chaque achat (ancien flux Purchase et module
     * PurchaseOrder) : c'est la source la plus fiable et la plus à jour.
     * L'ancienne chaîne LigneCommande/Purchase ne sert qu'en dernier recours,
     * pour un produit jamais touché par un achat synchronisé. 0 = coût inconnu.
     */
    public function coutUnitaire(): float
    {
        if ($this->price_sale !== null && (float) $this->price_sale > 0) {
            return (float) $this->price_sale;
        }

        $latest = $this->latestLigneCommande;
        if ($latest && $latest->unit_price_purchase !== null) {
            return (float) $latest->unit_price_purchase;
        }
        if ($latest && $latest->quantity > 0 && $latest->total_price_purchase !== null) {
            return (float) ($latest->total_price_purchase / $latest->quantity);
        }

        $purchasePrice = Purchase::where('product_id', $this->id)->latest()->value('price');
        if ($purchasePrice !== null) {
            return (float) $purchasePrice;
        }

        return 0.0;
    }

    /**
     * Intérêt d'une ligne vendue : (prix − coût) × quantité, ou 0 si le coût
     * est inconnu — on ne compte pas tout le prix de vente comme bénéfice.
     */
    public function interetPour(float $prixUnitaire, int $quantite): float
    {
        $cout = $this->coutUnitaire();

        return $cout > 0 ? ($prixUnitaire - $cout) * $quantite : 0.0;
    }

    public function latestLigneCommande()
    {
        return $this->hasOne(LigneCommande::class)->latestOfMany(); // uses created_at or id
    }

        
    
    

}
