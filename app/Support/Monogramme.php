<?php

namespace App\Support;

/**
 * Initiales tirées d'un nom, pour tenir lieu d'image quand il n'y en a pas.
 *
 * Sans cela, une installation neuve affichait l'emblème livré avec le thème —
 * celui d'un autre client — en haut de chaque page et dans chaque vignette de
 * compte.
 */
class Monogramme
{
    public static function pour(?string $nom): string
    {
        $mots = preg_split('/[^\p{L}\p{N}]+/u', (string) $nom, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($mots === []) {
            return '?';
        }

        // Un seul mot : ses deux premières lettres se lisent mieux qu'une seule.
        $lettres = count($mots) === 1
            ? mb_substr($mots[0], 0, 2)
            : mb_substr($mots[0], 0, 1).mb_substr($mots[1], 0, 1);

        return mb_strtoupper($lettres);
    }
}
