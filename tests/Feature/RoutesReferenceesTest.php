<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Tout nom de route écrit dans le code doit exister.
 *
 * route('nom-inexistant') ne se voit pas à l'écriture : Laravel ne lève une
 * RouteNotFoundException qu'au moment où la ligne est atteinte. La suppression
 * définitive d'un client redirigeait ainsi vers une page « corbeille » retirée
 * du projet : l'opération réussissait, puis l'utilisateur recevait une erreur
 * 500 — et le bloc catch visait la même route, ce qui masquait les vraies
 * erreurs. Personne ne pouvait le voir sans exécuter ce chemin précis.
 *
 * Ce test relit tout le code à la recherche de route('…') et vérifie chaque
 * nom. Il coûte une seconde et couvre tous les écrans d'un coup.
 */
class RoutesReferenceesTest extends TestCase
{
    /**
     * Fonctionnalités mises de côté volontairement : la vue et la méthode du
     * contrôleur sont conservées, seule la route est retirée, pour pouvoir les
     * remettre en service en la rétablissant.
     *
     * Chaque entrée doit renvoyer à une décision écrite dans le code. Ce n'est
     * pas une liste où ranger un nom cassé : une route qui devrait exister est
     * un bug, et ce test est là pour le signaler.
     *
     * - otp.verify_submit : étape de vérification du code OTP, retirée du
     *   parcours client (voir routes/web.php, section 5, et le commentaire en
     *   tête de AuthOtpController). showVerify()/verify() et le mécanisme
     *   complet — OtpCode, AuthService, SmsService — restent en place.
     */
    private const ROUTES_MISES_DE_COTE = [
        'otp.verify_submit',
    ];

    public function test_tous_les_noms_de_routes_utilises_existent(): void
    {
        $declarees = collect(Route::getRoutes()->getRoutesByName())->keys();
        $manquantes = [];

        foreach ($this->fichiers() as $fichier) {
            $code = file_get_contents($fichier);

            // route('nom') et route("nom"), en ignorant une variable ou une
            // concaténation, qui ne sont pas vérifiables statiquement.
            preg_match_all('/\broute\(\s*[\'"]([a-zA-Z0-9_.\-]+)[\'"]/', $code, $trouvees);

            foreach (array_unique($trouvees[1]) as $nom) {
                if (in_array($nom, self::ROUTES_MISES_DE_COTE, true)) {
                    continue;
                }

                if (! $declarees->contains($nom) && ! $this->estCommentee($code, $nom)) {
                    $manquantes[$nom][] = str_replace(base_path().'/', '', $fichier);
                }
            }
        }

        $this->assertSame([], $manquantes, "Noms de routes utilisés mais non déclarés :\n".collect($manquantes)
            ->map(fn ($fichiers, $nom) => "  - $nom  (".implode(', ', array_unique($fichiers)).')')
            ->implode("\n"));
    }

    /**
     * Une route citée uniquement dans un commentaire — par exemple pour
     * expliquer pourquoi elle a été retirée — n'est pas un appel.
     */
    private function estCommentee(string $code, string $nom): bool
    {
        foreach (explode("\n", $code) as $ligne) {
            if (! str_contains($ligne, $nom)) {
                continue;
            }

            $debut = ltrim($ligne);
            $estCommentaire = str_starts_with($debut, '//')
                || str_starts_with($debut, '*')
                || str_starts_with($debut, '/*')
                || str_starts_with($debut, '{{--');

            if (! $estCommentaire) {
                return false;
            }
        }

        return true;
    }

    /** @return iterable<string> */
    private function fichiers(): iterable
    {
        $racines = [app_path(), resource_path('views'), base_path('routes')];

        foreach ($racines as $racine) {
            $iterateur = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

            foreach ($iterateur as $fichier) {
                if ($fichier->isFile() && str_ends_with($fichier->getFilename(), '.php')) {
                    yield $fichier->getPathname();
                }
            }
        }
    }
}
