<?php

use App\Models\Company;
use App\Support\IdentiteVisuelle;
use App\Support\Monogramme;
use Carbon\Carbon;

if (! function_exists('getNow')) {
    function getNow()
    {
        return Carbon::now();
    }
}

if (! function_exists('generateReceiptNumber')) {
    function generateReceiptNumber(string $prefix, int $sequence)
    {
        return $prefix.Carbon::now()->format('Ym').str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}

if (! function_exists('getImage')) {
    function getImage($folder, $value)
    {
        return asset($folder.''.$value);
    }
}
if (! function_exists('containsAll')) {
    function containsAll($string, $substrings)
    {
        foreach ($substrings as $substring) {
            if (strpos($string, $substring) === false) {
                return false;
            }
        }

        return true;
    }
}
if (! function_exists('numberDelimiter')) {
    function numberDelimiter($number)
    {
        return number_format($number, 2, '.', ',');
    }
}

if (! function_exists('convertHundredsToWords')) {
    function convertHundredsToWords($number, $ones, $tens)
    {
        $result = '';

        // Hundreds
        if ($number >= 100) {
            $hundreds = floor($number / 100);
            if ($hundreds == 1) {
                $result .= 'cent';
            } else {
                $result .= $ones[$hundreds].' cent';
            }
            $number %= 100;
            if ($number > 0) {
                $result .= ' ';
            }
        }

        // Tens and ones
        if ($number >= 20) {
            $ten = floor($number / 10);
            $one = $number % 10;

            if ($ten == 7) {
                $result .= 'soixante';
                if ($one == 0) {
                    $result .= '-dix';
                } elseif ($one == 1) {
                    $result .= ' et onze';
                } else {
                    $result .= '-'.$ones[10 + $one];
                }
            } elseif ($ten == 8) {
                $result .= 'quatre-vingt';
                if ($one > 0) {
                    $result .= '-'.$ones[$one];
                } else {
                    $result .= 's';
                }
            } elseif ($ten == 9) {
                $result .= 'quatre-vingt-'.$ones[10 + $one];
            } else {
                $result .= $tens[$ten];
                if ($one > 0) {
                    $result .= '-'.$ones[$one];
                }
            }
        } elseif ($number > 0) {
            $result .= $ones[$number];
        }

        return trim($result);
    }
}

if (! function_exists('numberToWords')) {
    function numberToWords($number)
    {
        $ones = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        if ($number == 0) {
            return 'zéro';
        }

        $number = (int) $number;
        $result = '';

        // Billions
        if ($number >= 1000000000) {
            $milliards = floor($number / 1000000000);
            if ($milliards == 1) {
                $result .= 'un milliard ';
            } else {
                $result .= convertHundredsToWords($milliards, $ones, $tens).' milliards ';
            }
            $number %= 1000000000;
        }

        // Millions
        if ($number >= 1000000) {
            $millions = floor($number / 1000000);
            if ($millions == 1) {
                $result .= 'un million ';
            } else {
                $result .= convertHundredsToWords($millions, $ones, $tens).' millions ';
            }
            $number %= 1000000;
        }

        // Thousands
        if ($number >= 1000) {
            $thousands = floor($number / 1000);
            if ($thousands == 1) {
                $result .= 'mille ';
            } else {
                $result .= convertHundredsToWords($thousands, $ones, $tens).' mille ';
            }
            $number %= 1000;
        }

        // Hundreds and below
        if ($number > 0) {
            $result .= convertHundredsToWords($number, $ones, $tens);
        }

        return trim($result);
    }
}
if (! function_exists('isActiveRoute')) {
    function isActiveRoute($routes)
    {
        foreach ($routes as $route) {
            if (Route::is($route)) {
                return 'active';
            }
        }

        return '';
    }
}

/*
|--------------------------------------------------------------------------
| Identité du commerçant
|--------------------------------------------------------------------------
|
| Le nom du premier client était écrit en dur dans quatre-vingts endroits : titres de
| pages, en-têtes de factures, e-mails, reçus, site public. Ailleurs il servait
| de valeur de repli — « $company?->name ?? '…' » — ce qui revient au même
| dès qu'une vue est rendue sans qu'on lui passe la fiche entreprise, ce qui
| arrive pour tous les PDF servis par un contrôleur qui l'oublie.
|
| Ces fonctions lisent la fiche enregistrée, mémorisée une fois par requête
| (Company::current). Aucune vue n'a plus besoin qu'on lui passe quoi que ce
| soit, et aucun nom de client ne reste dans le code.
*/

if (! function_exists('marque')) {
    function marque(): ?Company
    {
        return Company::current();
    }
}

if (! function_exists('marqueNom')) {
    /** Nom du commerçant, tel qu'il doit apparaître sur les écrans et les documents. */
    function marqueNom(): string
    {
        return marque()?->name ?: config('app.name');
    }
}

if (! function_exists('marqueLogoUrl')) {
    /** URL du logo, ou null s'il n'y en a pas : c'est alors au monogramme de servir. */
    function marqueLogoUrl(): ?string
    {
        $logo = marque()?->logo;

        return $logo ? asset('companies/'.$logo) : null;
    }
}

if (! function_exists('marqueInitiales')) {
    function marqueInitiales(): string
    {
        return Monogramme::pour(marqueNom());
    }
}

if (! function_exists('marqueCouleur')) {
    /**
     * Un ton de la couleur de marque, en toutes lettres.
     *
     * Les PDF en ont besoin écrit : dompdf ne connaît pas les variables CSS et
     * rendrait la couleur transparente. Les écrans, eux, passent par les jetons.
     */
    function marqueCouleur(string $ton = '600'): string
    {
        return IdentiteVisuelle::ton($ton);
    }
}

if (! function_exists('marqueAccent')) {
    /** Couleur d'accent, écrite en toutes lettres (PDF et e-mails). */
    function marqueAccent(): string
    {
        return IdentiteVisuelle::accent();
    }
}

if (! function_exists('marqueEncre')) {
    /** Un neutre de la marque (ink, ink-muted, ink-faint, paper-warm, hairline). */
    function marqueEncre(string $cle): string
    {
        return IdentiteVisuelle::encre()[$cle] ?? '#1F2414';
    }
}
