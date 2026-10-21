<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Séance d'inventaire : un comptage complet d'une boutique à une date.
 *
 * Deux séances consécutives d'une même boutique bornent une période. Ce qui a
 * disparu du stock entre les deux, achats et transferts déduits, a été vendu
 * (voir InventaireService).
 */
class InventorySession extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public const BROUILLON = 'draft';

    public const VALIDEE = 'validated';

    protected $casts = [
        'counted_on' => 'date',
        'validated_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(InventorySessionLine::class, 'inventory_session_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /** Séance précédente de la même boutique : borne de début de la période. */
    public function previousSession()
    {
        return $this->belongsTo(self::class, 'previous_session_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function estValidee(): bool
    {
        return $this->status === self::VALIDEE;
    }

    /**
     * Début de la période couverte : le lendemain du comptage précédent.
     *
     * Le jour du comptage précédent appartient à la période précédente — il a
     * déjà été constaté par ce comptage. Null pour une première séance, qui ne
     * fait que constater le stock de départ sans rien déduire.
     */
    public function debutPeriode(): ?Carbon
    {
        return $this->previousSession?->counted_on?->copy()->addDay()->startOfDay();
    }

    public function finPeriode(): Carbon
    {
        return $this->counted_on->copy()->endOfDay();
    }

    /** Première séance d'une boutique : rien à déduire, elle pose le point de départ. */
    public function estPremiereSeance(): bool
    {
        return $this->previous_session_id === null;
    }

    /** Prochaine référence libre : INV-AAAAMM-0001. */
    public static function prochaineReference(): string
    {
        $prefixe = 'INV-'.date('Ym').'-';
        $sequence = (int) static::where('reference', 'like', $prefixe.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(reference, '.(strlen($prefixe) + 1).') AS '.(config('database.default') === 'sqlite' ? 'INTEGER' : 'UNSIGNED').')) as n')
            ->value('n');

        do {
            $sequence++;
            $reference = $prefixe.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /** Dernière séance validée d'une boutique, qui bornera la suivante. */
    public static function derniereValidee(int $storeId): ?self
    {
        return static::where('store_id', $storeId)
            ->where('status', self::VALIDEE)
            ->orderByDesc('counted_on')
            ->orderByDesc('id')
            ->first();
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user || ! $user->isStoreScoped()) {
            return $query;
        }

        return $query->whereIn('store_id', $user->accessibleStoreIds());
    }
}
