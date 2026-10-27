<?php

namespace App\Support;

/**
 * Couleur de marque de l'installation.
 *
 * Le projet est revendu à plusieurs commerçants, chacun avec ses couleurs. La
 * teinte était écrite en dur dans les vues et les feuilles de style : habiller
 * un nouveau client demandait un rechercher-remplacer dans une cinquantaine de
 * fichiers, à refaire à chaque livraison.
 *
 * Une seule ligne dans .env suffit maintenant :
 *
 *     BRAND_COLOR=#543FC2
 *
 * La nuance donnée est le ton 600 — celui des boutons, de la ligne active du
 * menu et des titres. Les dix autres s'en déduisent : même teinte, même
 * saturation, seule la luminosité change. Sans cette ligne, la palette
 * d'origine est rendue à l'identique, pour que les installations déjà livrées
 * ne bougent pas d'un pixel.
 */
class IdentiteVisuelle
{
    /**
     * Palette d'origine (vert). Conservée telle quelle, valeur par
     * valeur : c'est celle des clients déjà en service.
     */
    private const ORIGINE = [
        '50' => '#F4F5EC',
        '100' => '#E6E9D5',
        '200' => '#CED5AE',
        '300' => '#ADB97E',
        '400' => '#8C9A55',
        '500' => '#6B7A35',
        '600' => '#3E4C21',
        '700' => '#323D1A',
        '800' => '#283114',
        '900' => '#1C230D',
        '950' => '#10150A',
    ];

    /**
     * Écartement des tons, exprimé par rapport à la nuance fournie — qui occupe
     * le ton 600. Positif vers le clair, négatif vers le sombre ; 1 signifie
     * « jusqu'au bout », c'est-à-dire presque blanc ou presque noir.
     *
     * Les luminosités étaient d'abord absolues. Deux défauts, tous deux relevés
     * par le test : la nuance demandée n'était pas rendue exactement — elle ne
     * l'était que pour les couleurs dont la luminosité tombait par hasard sur
     * le palier du ton 600 — et à luminosité égale, le contraste dépend de la
     * teinte, si bien qu'un rouge passait sous le seuil de lisibilité là où un
     * violet le franchissait.
     *
     * Ancrée sur la nuance fournie, la rampe la rend au contraire toujours à
     * l'identique, et les tons plus sombres ne peuvent que mieux contraster.
     */
    private const ECARTS = [
        '50' => 1.00, '100' => 0.90, '200' => 0.72, '300' => 0.52,
        '400' => 0.33, '500' => 0.16, '600' => 0.00, '700' => -0.21,
        '800' => -0.42, '900' => -0.63, '950' => -0.85,
    ];

    /** Bornes de la rampe : ni blanc pur ni noir pur, qui ne tiennent plus la teinte. */
    private const LUMINOSITE_MAX = 0.97;

    private const LUMINOSITE_MIN = 0.12;

    /**
     * Neutres : texte, texte secondaire, filets, fond crème.
     *
     * Ce ne sont pas des gris. Ils étaient tirés du vert d'origine et en
     * gardaient la teinte — ce qui passait inaperçu tant que la marque était
     * verte, et donnait un texte verdâtre sur une interface violette. Ils
     * suivent donc la teinte de la marque, à saturation très basse.
     */
    private const ENCRE_ORIGINE = [
        'ink' => '#1F2414',
        'ink-muted' => '#66695A',
        'ink-faint' => '#9A9C8A',
        'paper-warm' => '#F9F9F3',
        'hairline' => '#E6E6D8',
    ];

    private const ENCRE_LUMINOSITES = [
        'ink' => 0.110,
        'ink-muted' => 0.382,
        'ink-faint' => 0.576,
        'paper-warm' => 0.965,
        'hairline' => 0.875,
    ];

    /** Juste de quoi apparenter les neutres à la marque, sans les colorer. */
    private const ENCRE_SATURATION = 0.09;

    /** @return array<string, string> */
    public static function encre(): array
    {
        $base = config('brand.couleur');

        if (! is_string($base) || ! preg_match('/^#?[0-9a-fA-F]{6}$/', trim($base))) {
            return self::ENCRE_ORIGINE;
        }

        [$teinte] = self::versTsl(ltrim(trim($base), '#'));

        $encre = [];
        foreach (self::ENCRE_LUMINOSITES as $cle => $luminosite) {
            $encre[$cle] = self::versHexa($teinte, $luminosite, self::ENCRE_SATURATION);
        }

        return $encre;
    }

    /** @return array<string, string> Les onze tons, du 50 au 950. */
    public static function rampe(): array
    {
        $base = config('brand.couleur');

        if (! is_string($base) || ! preg_match('/^#?[0-9a-fA-F]{6}$/', trim($base))) {
            return self::ORIGINE;
        }

        [$teinte, $ancre, $saturation] = self::versTsl(ltrim(trim($base), '#'));

        // La saturation du ton fourni est reprise pour toute la rampe : c'est
        // elle qui décide si la marque est franche ou sourde. Plancher à 20 %
        // pour qu'une nuance presque grise ne donne pas onze gris identiques.
        $saturation = max(0.20, $saturation);

        $rampe = [];
        foreach (self::ECARTS as $cle => $ecart) {
            $borne = $ecart >= 0 ? self::LUMINOSITE_MAX : self::LUMINOSITE_MIN;
            $rampe[$cle] = self::versHexa($teinte, $ancre + abs($ecart) * ($borne - $ancre), $saturation);
        }

        return $rampe;
    }

    /** Un ton précis de la rampe. */
    public static function ton(string $cle): string
    {
        return self::rampe()[$cle] ?? self::ORIGINE['600'];
    }

    /** Couleur d'accent : prix, mises en avant, ornements. */
    public static function accent(): string
    {
        $accent = config('brand.accent');

        return is_string($accent) && preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '#C7A150';
    }

    /** @return array{0: float, 1: float, 2: float} teinte, luminosité, saturation */
    private static function versTsl(string $hexa): array
    {
        $r = hexdec(substr($hexa, 0, 2)) / 255;
        $v = hexdec(substr($hexa, 2, 2)) / 255;
        $b = hexdec(substr($hexa, 4, 2)) / 255;

        $max = max($r, $v, $b);
        $min = min($r, $v, $b);
        $luminosite = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, $luminosite, 0.0];
        }

        $ecart = $max - $min;
        $saturation = $luminosite > 0.5 ? $ecart / (2 - $max - $min) : $ecart / ($max + $min);

        $teinte = match ($max) {
            $r => ($v - $b) / $ecart + ($v < $b ? 6 : 0),
            $v => ($b - $r) / $ecart + 2,
            default => ($r - $v) / $ecart + 4,
        };

        return [$teinte / 6, $luminosite, $saturation];
    }

    private static function versHexa(float $teinte, float $luminosite, float $saturation): string
    {
        if ($saturation === 0.0) {
            $valeur = (int) round($luminosite * 255);

            return sprintf('#%02X%02X%02X', $valeur, $valeur, $valeur);
        }

        $q = $luminosite < 0.5
            ? $luminosite * (1 + $saturation)
            : $luminosite + $saturation - $luminosite * $saturation;
        $p = 2 * $luminosite - $q;

        $composante = function (float $t) use ($p, $q): int {
            $t = fmod($t + 1, 1);

            $valeur = match (true) {
                $t < 1 / 6 => $p + ($q - $p) * 6 * $t,
                $t < 1 / 2 => $q,
                $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6,
                default => $p,
            };

            return (int) round($valeur * 255);
        };

        return sprintf('#%02X%02X%02X',
            $composante($teinte + 1 / 3),
            $composante($teinte),
            $composante($teinte - 1 / 3),
        );
    }
}
