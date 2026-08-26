<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    /**
     * Téléphone au format international, sans séparateur — la forme qu'exigent
     * les liens « tel: » et « wa.me ». Le numéro saisi reste affiché tel quel.
     */
    public function phoneInternational(): ?string
    {
        return self::auFormatInternational($this->phone);
    }

    /**
     * Second numéro au format international.
     */
    public function phoneSecondaryInternational(): ?string
    {
        return self::auFormatInternational($this->phone_secondary);
    }

    private static function auFormatInternational(?string $numero): ?string
    {
        $chiffres = preg_replace('/\D/', '', (string) $numero);

        if ($chiffres === '') {
            return null;
        }

        // Numéro guinéen local (9 chiffres) : on préfixe l'indicatif pays.
        return strlen($chiffres) === 9 ? '224'.$chiffres : $chiffres;
    }

    /**
     * Société affichée dans l'interface et en tête des documents.
     *
     * Mémorisée le temps de la requête : l'en-tête, le <head> et les PDF la
     * demandaient chacun de leur côté, ce qui rejouait la même requête jusqu'à
     * cinq fois par page. Renvoie null tant qu'aucune société n'est enregistrée,
     * aux appelants de prévoir un repli.
     */
    public static function current(): ?self
    {
        return once(function () {
            try {
                return static::latest()->first();
            } catch (\Throwable) {
                // Base injoignable ou pas encore migrée : la page d'erreur passe
                // par le même layout et doit rester affichable.
                return null;
            }
        });
    }
}
