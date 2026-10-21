<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

/**
 * Le mot de passe est stocké deux fois : `password`, le hash qui authentifie,
 * et `motdepasse`, la copie en clair que l'écran des utilisateurs affiche et
 * que le formulaire de modification pré-remplit.
 *
 * Les trois chemins de changement doivent écrire les deux colonnes. Si l'une
 * seulement est écrite, l'écran des utilisateurs affiche un mot de passe qui
 * n'ouvre plus la session — et le formulaire de modification le propose.
 */
class MotDePasseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        Mail::fake();
    }

    private function user(string $motDePasse, int $role = User::ROLE_STORE_MANAGER): User
    {
        $suffixe = uniqid();

        return User::create([
            'name' => 'Employe '.$suffixe,
            'email' => 'e'.$suffixe.'@example.com',
            'username' => 'e'.$suffixe,
            'password' => Hash::make($motDePasse),
            'motdepasse' => $motDePasse,
            'role_id' => $role,
            'phone' => '620000001',
            'status' => 'Active',
            'token' => '',
        ]);
    }

    /** Les deux colonnes décrivent-elles bien le même mot de passe ? */
    private function assertMotDePasse(User $user, string $attendu): void
    {
        $user->refresh();

        $this->assertTrue(
            Hash::check($attendu, $user->password),
            'Le hash ne correspond pas au mot de passe attendu : la connexion échouerait.'
        );
        $this->assertSame(
            $attendu,
            $user->motdepasse,
            'La copie en clair est restée sur l\'ancien mot de passe : l\'écran des utilisateurs affiche une valeur fausse.'
        );
    }

    public function test_changement_depuis_mon_profil_met_a_jour_les_deux_colonnes(): void
    {
        $user = $this->user('Ancien@123');

        $this->actingAs($user)
            ->post(route('passwordupdate'), [
                'emailupdate' => $user->email,
                'oldpassword' => 'Ancien@123',
                'password' => 'Nouveau@456',
                'password_confirmation' => 'Nouveau@456',
            ])
            ->assertRedirect(route('login'));

        $this->assertMotDePasse($user, 'Nouveau@456');
    }

    public function test_reinitialisation_par_email_met_a_jour_les_deux_colonnes(): void
    {
        $user = $this->user('Ancien@123');
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Reinit@789',
            'password_confirmation' => 'Reinit@789',
        ])->assertRedirect(route('login'));

        $this->assertMotDePasse($user, 'Reinit@789');
    }

    public function test_modification_par_l_administrateur_met_a_jour_les_deux_colonnes(): void
    {
        $admin = $this->user('Admin@123', User::ROLE_ADMIN);
        $employe = $this->user('Ancien@123');

        $this->actingAs($admin)
            ->put(route('updateUser', $employe->id), [
                'name' => $employe->name,
                'username' => $employe->username,
                'phone' => $employe->phone,
                'email' => $employe->email,
                'password' => 'ParAdmin@1',
                'password_confirmation' => 'ParAdmin@1',
            ])
            ->assertRedirect();

        $this->assertMotDePasse($employe, 'ParAdmin@1');
    }

    /**
     * Le formulaire de modification pré-remplit le champ avec la copie en clair
     * et le resoumet tel quel : une modification de nom ou de téléphone ne doit
     * pas être comptée comme un changement de mot de passe (sinon rehash et
     * notification à chaque édition).
     */
    public function test_resoumettre_le_mot_de_passe_pre_rempli_ne_le_change_pas(): void
    {
        $admin = $this->user('Admin@123', User::ROLE_ADMIN);
        $employe = $this->user('Actuel@123');
        $hashAvant = $employe->password;

        $this->actingAs($admin)
            ->put(route('updateUser', $employe->id), [
                'name' => 'Nom corrige',
                'username' => $employe->username,
                'phone' => '620999999',
                'email' => $employe->email,
                // Valeur pré-remplie par la vue : le mot de passe actuel en clair.
                'password' => 'Actuel@123',
                'password_confirmation' => 'Actuel@123',
            ])
            ->assertRedirect();

        $employe->refresh();
        $this->assertSame($hashAvant, $employe->password, 'Le hash a été recalculé alors que le mot de passe n\'a pas changé.');
        $this->assertSame('Actuel@123', $employe->motdepasse);
        $this->assertSame('Nom corrige', $employe->name);
    }

    /**
     * Après un changement depuis le profil, un administrateur qui édite la
     * fiche ne doit pas y trouver l'ancien mot de passe. C'est le scénario qui
     * échouait : la copie en clair n'était pas mise à jour, donc l'écran
     * proposait l'ancien, et le resoumettre le rétablissait pour de bon.
     */
    public function test_la_fiche_affiche_le_mot_de_passe_courant_apres_un_changement_au_profil(): void
    {
        $admin = $this->user('Admin@123', User::ROLE_ADMIN);
        $employe = $this->user('Ancien@123');

        $this->actingAs($employe)->post(route('passwordupdate'), [
            'emailupdate' => $employe->email,
            'oldpassword' => 'Ancien@123',
            'password' => 'ChoisiParLui@1',
            'password_confirmation' => 'ChoisiParLui@1',
        ]);

        // Le profil force la déconnexion : on repart de l'administrateur.
        Auth::logout();

        $this->actingAs($admin)
            ->get(route('editUser', $employe->id))
            ->assertOk()
            ->assertSee('ChoisiParLui@1', false)
            ->assertDontSee('Ancien@123', false);
    }
}
