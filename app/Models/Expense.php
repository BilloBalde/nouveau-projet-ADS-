<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Sortie d'argent donnée à un associé, avec son motif. À la clôture de
 * l'année, le total des sorties de chaque associé est déduit de sa part du
 * bénéfice.
 */
class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'associate_id',
        'amount',
        'status',
        'store_id',
        'description',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Une année clôturée est figée : ses sorties ont été déduites et le solde
     * versé. En ajouter, en modifier ou en retirer une fausserait un compte
     * déjà réglé. Le garde-fou est sur le modèle : il vaut pour tous les
     * chemins, pas seulement pour l'écran des dépenses.
     */
    protected static function booted(): void
    {
        $verifier = function (self $sortie, ?int $annee = null) {
            $annee ??= (int) ($sortie->created_at ?? now())->year;

            if (AssociateYearClosing::where('year', $annee)->exists()) {
                throw new RuntimeException("L'année $annee est clôturée : ses sorties ne peuvent plus changer.");
            }
        };

        static::saving(function (self $sortie) use ($verifier) {
            $verifier($sortie);
            // Une sortie déplacée d'une année à l'autre touche les deux.
            if ($sortie->exists && $sortie->isDirty('created_at') && $sortie->getOriginal('created_at')) {
                $verifier($sortie, (int) $sortie->getOriginal('created_at')->year);
            }
        });
        static::deleting(fn (self $sortie) => $verifier($sortie));
    }

    public function associate()
    {
        return $this->belongsTo(Associate::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
