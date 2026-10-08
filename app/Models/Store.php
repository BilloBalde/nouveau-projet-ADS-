<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Emplacement de stock : magasin (point de vente), entrepôt, ou stock interne
 * d'un magasin. Le nom historique « Store / boutique » est conservé pour ne
 * casser aucune relation existante (sales, factures, store_products…).
 */
class Store extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public const TYPE_MAGASIN = 'magasin';
    public const TYPE_ENTREPOT = 'entrepot';
    public const TYPE_STOCK = 'stock';

    public const TYPES = [
        self::TYPE_MAGASIN  => 'Magasin',
        self::TYPE_ENTREPOT => 'Entrepôt',
        self::TYPE_STOCK    => 'Stock interne',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function getUserAttribute(){
        $user = User::find($this->user_id);
        return $user?->username;
    }

    public function getPlaceAttribute(){
        $place = Place::find($this->place_id);
        return $place?->placeName;
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
        return $this->belongsToMany(Product::class, 'store_products')->withPivot('quantity', 'fund_id');
    }

      public function manager()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Employés affectés à cet emplacement (vendeurs, magasiniers…). */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Store::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Store::class, 'parent_id');
    }

    public function stockRows()
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst((string) $this->type);
    }

    /** « Magasin Madina / Stock A1 » : nom complet, parent compris. */
    public function getFullNameAttribute(): string
    {
        $parent = $this->parent_id ? $this->parent : null;

        return $parent ? $parent->store_name . ' / ' . $this->store_name : $this->store_name;
    }

    /**
     * Magasin de vente (caisse) qui encaisse une vente sortie de cet
     * emplacement : lui-même s'il est un magasin, sinon le premier magasin
     * parmi ses parents, sinon le magasin principal, sinon lui-même.
     */
    public function pointDeVenteId(): int
    {
        $courant = $this;
        $vus = [];

        while ($courant && ! in_array($courant->id, $vus, true)) {
            if ($courant->type === self::TYPE_MAGASIN) {
                return (int) $courant->id;
            }
            $vus[] = $courant->id;
            $courant = $courant->parent_id ? static::find($courant->parent_id) : null;
        }

        return (int) (static::where('is_main', true)->value('id') ?? $this->id);
    }

    public function isWarehouse(): bool
    {
        return $this->type === self::TYPE_ENTREPOT;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /** Emplacements visibles par l'utilisateur (tous s'il a une vue globale). */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        return $query->whereIn('id', $user->accessibleStoreIds());
    }

    /**
     * L'emplacement et tous ses descendants (Magasin → Entrepôts → …).
     * Parcours en largeur : une requête par niveau, sans limite de profondeur
     * ni de nombre d'emplacements. Protégé contre les cycles.
     */
    public static function withDescendantIds(int $storeId): array
    {
        $ids = [$storeId];
        $frontier = [$storeId];

        while ($frontier) {
            $children = static::whereIn('parent_id', $frontier)->pluck('id')->all();
            $frontier = array_values(array_diff($children, $ids));
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /**
     * Sections : emplacements de premier niveau, chacun avec la liste des ids
     * qu'il couvre (lui-même et tout ce qui lui est rattaché), dans
     * l'attribut « section_ids ». Une seule requête pour tout l'arbre.
     */
    public static function sectionsWithIds()
    {
        $stores = static::orderByDesc('is_main')->orderBy('store_name')->get();
        $children = $stores->groupBy('parent_id');
        $collect = function (int $id, array $seen = []) use (&$collect, $children) {
            $ids = [$id];
            foreach ($children->get($id, collect()) as $child) {
                if (! in_array($child->id, $seen, true)) {
                    $ids = array_merge($ids, $collect($child->id, array_merge($seen, $ids)));
                }
            }
            return $ids;
        };

        return $stores->whereNull('parent_id')->each(fn ($s) => $s->setAttribute('section_ids', $collect($s->id)))->values();
    }

    /** Liste pour les sélecteurs : « Nom complet (type) », triée. */
    public static function optionsFor(?User $user, bool $activeOnly = true)
    {
        return static::query()
            ->with('parent:id,store_name')
            ->visibleTo($user)
            ->when($activeOnly, fn ($q) => $q->where(fn ($w) => $w->where('status', 1)->orWhereNull('status')))
            ->orderByDesc('is_main')
            ->orderBy('type')
            ->orderBy('store_name')
            ->get();
    }
}
