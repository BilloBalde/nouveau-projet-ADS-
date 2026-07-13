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
        return view('users.edit', compact('user', 'roles', 'permissionModules', 'rolePresets', 'userPermissions'));
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
        return view('users.register', compact('roles', 'permissionModules', 'rolePresets'));
    }

    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'username' => 'required|string',
            'password' => 'required|string|min:8|max:15|confirmed',
            'phone' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'role_id' => 'required|integer|exists:roles,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ],[
            'email.required' => 'Vous devez remplir le champ email',
            'email.email' => 'Le champ email doit contenir un @ et .',
            'username.required' => 'Vous devez remplir le champ username',
            'username.string' => 'Le champ username ne prend que des chaines de caracteres',
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
                'status' => 'pending',
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

            $verification_link = url('registration/verification/'.$token.'/'.$request->email);
            $subject = 'Confirmation de compte - SMH';
    
            // Create a clean verification link without HTML for the email body
            $message = $verification_link;

            Mail::to($request->email)->send(new UserMail($subject, $message, $request->name));
            return back()->with('success', 'Utilisateur crée avec succès. Un email de confirmation a été envoyé.');

        }
        catch(\Exception $e) {
            return back()->with('fall', 'une erreur lors de lajout, voici le message : '.$e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        
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

        if ($request->password != NULL) {
            $user->password = Hash::make($request->password);
            $passwordChanged = true;
        } else {
            $passwordChanged = false;
        }
        
        try {
            $user->update();
            
            // Get user name for personalization
            $userName = $user->name ?? $user->username ?? 'Utilisateur';
            
            // Send password change notification
            if (!empty($user->email) && $passwordChanged) {
                Mail::to($user->email)->send(new PasswordMail(
                    '🔐 Modification de votre mot de passe - SMH',
                    'Votre mot de passe a été modifié avec succès.',
                    $userName,
                    'changed'
                ));
            }
            
            // If this is the current user and password was changed, logout
            if ($isCurrentUser && $passwordChanged) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('accueil')->with('success', 'Votre profil a été mis à jour. Veuillez vous reconnecter.');
            }
            
            // Otherwise just redirect back with success
            return redirect()->back()->with('success', 'Utilisateur modifié avec succès.');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Une erreur est survenue: '.$e->getMessage());
        }
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

        try {
            // Using ContactMail (recommended)
            Mail::to('saikououmar47@gmail.com')->send(new ContactMail(
                $request->subject,
                $request->email,
                $request->name,
                $request->message
            ));
            
            // OR using UserMail (your existing)
            Mail::to('saikououmar47@gmail.com')->send(new UserMail(
                $request->subject,
                "Email envoyeur: " . $request->email . "\n" .
                "Nom envoyeur: " . $request->name . "\n" .
                "Message: " . $request->message
            ));
            
            return back()->with('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
            
        } catch (\Exception $e) {
            \Log::error('Contact email error: ' . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue. Veuillez réessayer plus tard.');
        }
    }


}
