<?php

namespace App\Http\Controllers;

use App\Mail\PasswordMail;
use App\Mail\UserMail;
use App\Mail\ContactMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Authentification extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            return Auth::user()->isCustomer()
                ? redirect()->route('shop.home')
                : redirect()->route('home');
        }

        return view('users.login');
    }

    public function edit($id){
        $user = User::find($id);
        [$roles, $permissionModules, $rolePresets] = $this->permissionFormData();
        $userPermissions = $user->permissions()->pluck('permissions.id')->all();
        [$stores, $places, $scopedRoleIds] = $this->storeAssignmentFormData();
        $currentStoreId = $user->assignedStoreId();
        $assignmentHistory = $user->storeAssignments()->with(['store', 'assignedBy'])->get();
        return view('users.edit', compact('user', 'roles', 'permissionModules', 'rolePresets', 'userPermissions', 'stores', 'currentStoreId', 'places', 'scopedRoleIds', 'assignmentHistory'));
    }

    /**
     * Données du bloc « magasin d'affectation » : magasins proposés, localités
     * (création d'un magasin à la volée) et rôles limités à un magasin, c'est-à-
     * dire ceux dont le preset n'inclut pas « stores.all ». Rien n'est codé en
     * dur : un nouveau rôle sans cette permission affiche aussi le bloc.
     */
    private function storeAssignmentFormData(): array
    {
        $stores = \App\Models\Store::optionsFor(Auth::user()->isRestrictedToStore() ? Auth::user() : null)->loadCount('users');
        $places = \App\Models\Place::orderBy('placeName')->get();
        $globalRoleIds = \App\Models\Role::whereHas('permissions', fn ($q) => $q->where('slug', 'stores.all'))->pluck('id');
        $scopedRoleIds = \App\Models\Role::whereNotIn('slug', ['admin', 'customer', 'associe'])
            ->whereNotIn('id', $globalRoleIds)
            ->pluck('id')
            ->all();

        return [$stores, $places, $scopedRoleIds];
    }

    /** Règles de validation du bloc « magasin d'affectation ». */
    private function storeAssignmentRules(): array
    {
        return [
            'store_mode'             => 'nullable|in:existing,new',
            'store_id'               => 'nullable|exists:stores,id',
            'new_store.store_name'   => 'required_if:store_mode,new|nullable|string|max:255|unique:stores,store_name',
            'new_store.place_id'     => 'required_if:store_mode,new|nullable|exists:places,id',
            'new_store.address'      => 'nullable|string|max:255',
        ];
    }

    /**
     * Applique le choix du formulaire : magasin existant, ou nouveau magasin
     * créé ici puis affecté automatiquement. Ne s'applique qu'aux rôles limités
     * à un magasin ; un profil à vue globale n'a pas d'affectation.
     *
     * @param bool $clearIfEmpty  en modification, « aucun magasin » désaffecte.
     */
    private function applyStoreAssignment(User $user, Request $request, bool $clearIfEmpty): void
    {
        [, , $scopedRoleIds] = $this->storeAssignmentFormData();
        if (! in_array((int) $user->role_id, array_map('intval', $scopedRoleIds), true)) {
            return;
        }

        if ($request->input('store_mode') === 'new') {
            $store = \App\Models\Store::create([
                'store_name'    => $request->input('new_store.store_name'),
                'place_id'      => $request->input('new_store.place_id'),
                'address'       => $request->input('new_store.address'),
                // Responsable par défaut : l'employé qui y est affecté (modifiable
                // ensuite depuis la fiche du magasin).
                'user_id'       => $user->id,
                'type'          => \App\Models\Store::TYPE_MAGASIN,
                'status'        => 1,
                'store_picture' => '',
                'description'   => $request->input('new_store.store_name'),
            ]);
            $user->assignToStore($store->id, Auth::id());

            return;
        }

        if ($request->filled('store_id')) {
            if (Auth::user()->isRestrictedToStore()) {
                $this->authorizeStore($request->store_id);
            }
            $user->assignToStore((int) $request->store_id, Auth::id());
        } elseif ($clearIfEmpty && $request->has('store_mode')) {
            $user->assignToStore(null, Auth::id());
        }
    }

    /**
     * Un non-admin ne peut affecter que des rôles dont le preset est
     * entièrement couvert par ses propres permissions.
     */
    private function canAssignRole(\App\Models\Role $role): bool
    {
        $granter = Auth::user();
        if (! $granter || (int) $granter->role_id === User::ROLE_ADMIN) {
            return true;
        }

        $rolePerms = $role->permissions()->pluck('permissions.id');

        return $rolePerms->diff($granter->effectivePermissionIds())->isEmpty();
    }

    /**
     * Ne stocke des permissions explicites que si elles diffèrent du preset
     * du rôle — sinon l'utilisateur suit le rôle dynamiquement.
     *
     * Anti-escalade : un non-admin ne peut ni accorder ni retirer une
     * permission qu'il ne possède pas lui-même.
     */
    private function syncUserPermissions(User $user, array $submitted): void
    {
        $submitted = collect($submitted)->map(fn ($v) => (int) $v)->unique();

        $granter = Auth::user();
        if ($granter && (int) $granter->role_id !== User::ROLE_ADMIN) {
            $grantable = $granter->effectivePermissionIds();
            $existing  = $user->permissions()->pluck('permissions.id');

            // On ne garde des cases cochées que ce que l'accordant possède,
            // et on préserve les grants hors de sa portée (posés par l'admin).
            $submitted = $submitted->intersect($grantable)
                ->merge($existing->diff($grantable))
                ->unique();
        }

        $submitted = $submitted->sort()->values();
        // Role::find (et non la relation) : le role_id peut venir d'être changé sans être encore persisté
        $preset = \App\Models\Role::find($user->role_id)?->permissions()->pluck('permissions.id')->sort()->values() ?? collect();

        $user->permissions()->sync(
            $submitted->all() === $preset->all() ? [] : $submitted->all()
        );
    }

    /**
     * Données communes aux formulaires utilisateur : rôles assignables,
     * permissions groupées par module, presets par rôle (pour le JS).
     * Un non-admin ne voit que les rôles et permissions qu'il peut accorder.
     */
    private function permissionFormData(): array
    {
        $granter   = Auth::user();
        $isAdmin   = $granter && (int) $granter->role_id === User::ROLE_ADMIN;
        $grantable = $isAdmin ? null : $granter?->effectivePermissionIds() ?? collect();

        $permissions = \App\Models\Permission::orderBy('module')->orderBy('id')->get();
        if (! $isAdmin) {
            $permissions = $permissions->whereIn('id', $grantable->all());
        }
        $permissionModules = $permissions->groupBy('module');

        $roles = \App\Models\Role::whereNotIn('slug', ['admin', 'customer'])->get();
        $rolePresets = $roles->mapWithKeys(
            fn ($role) => [$role->id => $role->permissions()->pluck('permissions.id')->all()]
        );

        if (! $isAdmin) {
            // Ne proposer que les rôles entièrement couverts par les permissions de l'accordant
            $roles = $roles->filter(
                fn ($role) => collect($rolePresets[$role->id])->diff($grantable)->isEmpty()
            )->values();
        }

        return [$roles, $permissionModules, $rolePresets];
    }

    public function forgotPass(){
        return view('users.forgot_password');
    }

    public function register(){
        [$roles, $permissionModules, $rolePresets] = $this->permissionFormData();
        [$stores, $places, $scopedRoleIds] = $this->storeAssignmentFormData();
        return view('users.register', compact('roles', 'permissionModules', 'rolePresets', 'stores', 'places', 'scopedRoleIds'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:8|max:15|confirmed',
            'phone' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'role_id' => 'required|integer|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ] + $this->storeAssignmentRules(),[
            'email.required' => 'Vous devez remplir le champ email',
            'email.email' => 'Le champ email doit contenir un @ et .',
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'username.required' => 'Vous devez remplir le champ username',
            'username.string' => 'Le champ username ne prend que des chaines de caracteres',
            'username.unique' => 'Ce nom d\'utilisateur est déjà pris.',
            'password.required' => 'Vous devez remplir le champ password',
            'password.string' => 'Le champ password ne prend que des chaines de caracteres',
            'password.min' => 'Le champ password doit contenir au moins 8 caracteres',
            'password.max' => 'Le champ password ne doit pas depasser 15 caracteres',
            'password.confirmed' => 'Le champ password confirmation ne correspond pas',
            'phone.required' => 'Vous devez remplir le champ phone',
            'phone.string' => 'Le champ phone ne prend que des chaines de caracteres',
            'phone.max' => 'Le champ phone ne doit pas depasser 20 caracteres',
            'name.required' => 'Vous devez remplir le champ name',
            'name.string' => 'Le champ name ne prend que des chaines de caracteres',
            'name.max' => 'Le champ name ne doit pas depasser 255 caracteres'
        ]);
        // Les rôles système admin/customer ne sont pas assignables via ce formulaire
        $role = \App\Models\Role::find($request->role_id);
        if (in_array($role?->slug, ['admin', 'customer'], true)) {
            return back()->withInput()->with('fall', 'Ce rôle ne peut pas être attribué à un employé.');
        }

        // Anti-escalade : un non-admin ne peut pas affecter un rôle dont le
        // preset dépasse ses propres permissions.
        if ($role && ! $this->canAssignRole($role)) {
            return back()->withInput()->with('fall', 'Ce rôle contient des permissions que vous ne possédez pas — seul un administrateur peut l\'attribuer.');
        }

        if(request()->hasfile('profilePic')){
            $avatarName = time().'.'.request()->profilePic->getClientOriginalExtension();
            request()->profilePic->move(public_path('avatars'), $avatarName);
        }
        $token = Str::random(64);
        try{
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'phone' => $request->phone,
                'role_id' => (int) $request->role_id,
                'email' => $request->email,
                'profilePic' => '1725478994.png',
                // Créé par un admin/manager depuis l'espace interne : le compte est actif
                // immédiatement, pas besoin de cliquer un lien de confirmation par email
                // (ça reste utile pour l'inscription libre côté client, pas ici).
                'status' => 'Active',
                'token' => $token,
                'description' => $request->description ?? 'user',
                'password' => Hash::make($request->password),
                'motdepasse' => $request->password
            ]);

            // Si les cases cochées correspondent exactement au preset du rôle,
            // on ne stocke rien : l'employé suit le rôle dynamiquement (les
            // modifications futures du rôle s'appliquent automatiquement).
            // Sinon, on stocke les grants explicites qui priment sur le rôle.
            $this->syncUserPermissions($user, $request->input('permissions', []));

            // Un vendeur n'est rattaché à une boutique que via Store.user_id (pas
            // l'inverse) — cf. IndexController qui résout la boutique du vendeur par
            // Store::where('user_id', ...). On répercute donc le choix fait ici sur
            // la boutique sélectionnée, qui perd son ancien responsable le cas échéant.
            // Affectation à un seul magasin (users.store_id + historique). Un
            // magasin peut désormais avoir plusieurs vendeurs : on ne retire plus
            // la boutique à son responsable actuel (stores.user_id).
            $this->applyStoreAssignment($user, $request, false);
        }
        catch(\Exception $e) {
            return back()->withInput()->with('fall', 'une erreur lors de lajout, voici le message : '.$e->getMessage());
        }

        // Le compte est déjà actif (cf. status => 'Active' ci-dessus) : cet email est
        // juste une notification de bienvenue, pas une étape bloquante. Une panne SMTP
        // ne doit pas faire croire à l'admin que la création a échoué alors que le
        // compte existe bel et bien.
        try {
            $verification_link = url('registration/verification/'.$token.'/'.$request->email);
            $subject = 'Confirmation de compte - SMH';
            $message = $verification_link;

            Mail::to($request->email)->send(new UserMail($subject, $message, $request->name));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Echec envoi email de bienvenue utilisateur: '.$e->getMessage());
            return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès. (L\'email de notification n\'a pas pu être envoyé — vérifiez la configuration SMTP.)');
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur crée avec succès. Un email de confirmation a été envoyé.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', Rule::unique('users', 'username')->ignore($user->id)],
            'phone' => 'required|string|max:20',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ] + $this->storeAssignmentRules(), [
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'username.unique' => 'Ce nom d\'utilisateur est déjà pris.',
        ]);

        // Store whether this is the current user BEFORE any changes
        $isCurrentUser = Auth::check() && Auth::id() == $user->id;

        $user->name = $request->name;
        $user->username = $request->username;
        $user->phone = $request->phone;
        $user->email = $request->email;

        // Rôle et permissions (uniquement si le formulaire les soumet — l'édition de profil simple ne les touche pas)
        if ($request->filled('role_id') && (int) $user->role_id !== User::ROLE_ADMIN) {
            $newRole = \App\Models\Role::find($request->role_id);
            if ($newRole && ! in_array($newRole->slug, ['admin', 'customer'], true) && $this->canAssignRole($newRole)) {
                $user->role_id = (int) $newRole->id;
            }
        }
        if ($request->boolean('sync_permissions')) {
            $this->syncUserPermissions($user, $request->input('permissions', []));
        }

        // Idem création : le rattachement vendeur/boutique vit sur Store.user_id, pas
        // sur l'utilisateur (cf. IndexController). On ne touche à l'affectation que si
        // le rôle EFFECTIF (après le changement éventuel ci-dessus) est Vendeur et
        // qu'une boutique a été choisie — laisser le champ vide ne change rien : la
        // colonne stores.user_id est NOT NULL, donc "retirer" un vendeur suppose de
        // donner sa boutique à quelqu'un d'autre (via Boutiques > Modifier), pas de la
        // laisser vacante.
        // Changement de magasin : l'affectation précédente est close (historique),
        // l'employé n'a plus accès qu'aux données de son nouveau magasin.
        $this->applyStoreAssignment($user, $request, true);

        // Le champ "Mot de Passe" du formulaire est pré-rempli avec le mot de passe
        // actuel (motdepasse) pour que l'admin puisse le consulter en éditant — il
        // est donc soumis tel quel à chaque modification, même anodine (téléphone,
        // nom...). On ne traite ça comme un VRAI changement que si la valeur diffère
        // de celle stockée, sinon ça déclencherait un rehash inutile, un email de
        // notification à chaque édition, et une déconnexion forcée si l'admin édite
        // son propre profil sans toucher au mot de passe.
        $passwordChanged = $request->filled('password') && $request->password !== $user->motdepasse;
        if ($passwordChanged) {
            // La contrainte de longueur ne s'applique qu'ici, sur un VRAI changement —
            // pas dans le validate() ci-dessus, sinon les comptes dont le mot de passe
            // actuel dépasse 15 caractères (créés avant cette règle) bloqueraient toute
            // modification du profil, même sans toucher au mot de passe.
            $request->validate([
                'password' => 'string|min:8|max:15|confirmed',
            ], [
                'password.min' => 'Le champ password doit contenir au moins 8 caracteres',
                'password.max' => 'Le champ password ne doit pas depasser 15 caracteres',
                'password.confirmed' => 'Le champ password confirmation ne correspond pas',
            ]);

            $user->definirMotDePasse((string) $request->password);
        }

        try {
            $user->save();
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Une erreur est survenue: '.$e->getMessage());
        }

        // Notification de changement de mot de passe : un échec SMTP ne doit pas
        // faire croire que la modification a échoué alors qu'elle a bien été
        // enregistrée (même correctif que pour la création de compte).
        if ($passwordChanged && !empty($user->email)) {
            try {
                $userName = $user->name ?? $user->username ?? 'Utilisateur';
                Mail::to($user->email)->send(new PasswordMail(
                    '🔐 Modification de votre mot de passe - SMH',
                    'Votre mot de passe a été modifié avec succès.',
                    $userName,
                    'changed'
                ));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Echec envoi email de notification mot de passe: '.$e->getMessage());
            }
        }

        // Si l'admin modifie son propre mot de passe, on le déconnecte pour qu'il
        // se reconnecte avec le nouveau (mesure de sécurité standard) — uniquement
        // si le mot de passe a réellement changé, pas sur toute édition.
        if ($isCurrentUser && $passwordChanged) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('accueil')->with('success', 'Votre profil a été mis à jour. Veuillez vous reconnecter.');
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur modifié avec succès.');
    }

    public function registration_verify($token, $email){
        $userExist = User::where('email', $email)->where('token', $token)->first();
        if(!$userExist){
            return redirect()->route('login')->with('fall', 'Utilisateur déjà activé ou n\'existe pas.');
        }else{
            $userExist->status = 'Active';
            $userExist->token = '';
            $userExist->update();
            return redirect()->route('login')->with('success', 'Utilisateur activé avec succès.');
        }

    }

    public function passwordRecovery(Request $request){
        // Deprecated insecure flow: send a standard reset link instead of emailing any password/hash.
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));
        return redirect()->route('login')->with('success', 'Si votre email existe, un lien de réinitialisation a été envoyé.');
    }

    public function login_submit(Request $request){
        $identifier = trim((string) $request->email);
        $password = (string) $request->password;

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (!$user) {
            return back()->with('fall', 'Utilisateur n\'existe pas');
        }

        $status = strtolower((string) $user->status);
        if ($status !== 'active') {
            return back()->with('fall', 'Votre compte n\'est pas activé.');
        }

        if (Auth::attempt(['email' => $user->email, 'password' => $password])) {
            return redirect()->route('home');
        }

        return back()->with('fall', 'Mot de passe incorrect.');
    }

    public function sendEmail(Request $request){
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'subject' => 'required|string',
            'message' => 'required|string'
        ]);
        /* ,[
            'email.required' => 'Vous devez remplir le champ email',
            'email.email' => 'Le champ email doit contenir un @ et .',
            'name.required' => 'Vous devez remplir le champ name',
            'name.string' => 'Le champ name ne prend que des chaines de caracteres',
            'subject.required' => 'Vous devez remplir le champ subject',
            'subject.string' => 'Le champ subject ne prend que des chaines de caracteres',
            'message.required' => 'Vous devez remplir le champ message',
            'message.string' => 'Le champ message ne prend que des chaines de caracteres',
        ] */

        // Destinataire : la fiche entreprise, modifiable depuis Profil. Le
        // formulaire envoyait auparavant vers une adresse personnelle codee en
        // dur : les messages des clients n'arrivaient pas a l'entreprise.
        $destinataire = \App\Models\Company::current()?->email
            ?: config('mail.from.address');

        if (! $destinataire) {
            \Illuminate\Support\Facades\Log::warning(
                'Formulaire de contact : aucune adresse de destination. '
                ."Renseigner l'e-mail de l'entreprise dans Profil, ou MAIL_FROM_ADDRESS."
            );

            return back()->with('error', "Le formulaire de contact n'est pas encore configure. "
                .'Merci de nous joindre par telephone.');
        }

        try {
            // Un seul envoi : le doublon via UserMail rendait le message du
            // visiteur en HTML brut ({!! $body !!}), ce qui permettait d'inserer
            // un faux lien dans le courriel recu par le personnel. ContactMail,
            // lui, echappe le contenu avant de l'afficher.
            Mail::to($destinataire)->send(new ContactMail(
                $request->subject,
                $request->email,
                $request->name,
                $request->message
            ));
            
            return back()->with('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
            
        } catch (\Exception $e) {
            \Log::error('Contact email error: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }


}
