<?php

namespace App\Http\Controllers;

use App\Mail\PasswordUpdateMail;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }
    public function index(){
        $connectedUser = Auth::user();
        $compagnie = Company::latest()->first();
        return view('users.profile', compact('connectedUser', 'compagnie'));
    }

    public function profileImage(Request $request){
        $request->validate([
            'profilePic' => 'image'
        ],[
            'profilePic.image' => 'Il faut mettre une image avec extension .jpg, .jpeg, .png, .gif'
        ]);
        try {
            $avatarName = time().'.'.request()->profilePic->getClientOriginalExtension();
            request()->profilePic->move(public_path('avatars'), $avatarName);
            $user = User::find(Auth::id());
            $user->profilePic = $avatarName;
            $user->update();
            return back()->with('success', "Modification de profile réussie");
        } catch (\Throwable $th) {
            return back()->with('fall', "modification de profile non réussie");
        }
    }

    public function profileInfo(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:10',
            'phone' => 'required|string|max:15'
        ],[
            'name.required' => 'Le nom est réquis',
            'name.string' => 'Le nom doit être une chaine de charactère',
            'name.max' => 'Le nom prend au maximum 255 caractère',
            'username.required' => 'Le nom utilisateur est réquis',
            'username.string' => 'Le nom utilisateur doit être une chaine de charactère',
            'username.max' => 'Le nom utilisateur prend au maximum 255 caractère',
            'phone.required' => 'Le phone est réquis',
            'phone.string' => 'Le phone doit être une chaine de charactère',
            'phone.max' => 'Le phone prend au maximum 255 caractère',
        ]);
        try {
            $user = User::find(Auth::id());
            $user->update($validated);
            return back()->with('success', "Modification de profile réussie");
        } catch (\Throwable $th) {
            return back()->with('fall', "modification de profile non réussie");
        }
    }

    public function passwordupdate(Request $request){
        $request->validate([
            'emailupdate' => 'required|email',
            'oldpassword' => 'required|string|min:8|max:16',
            'password' => 'required|string|min:8|max:16',
            'password_confirmation' => 'required|string|min:8|max:16'
        ],[
            'emailupdate.required' => 'Le champ emailupdate doit être rempli',
            'emailupdate.email' => 'Le champ emailupdate doit être un email valid avec @ et .',
            'oldpassword.required' => 'Vous devez obligatoirement remplir le mot de passe ancien',
            'oldpassword.string' => 'Le champ ancient mot de passe doit être une chaine de caractère',
            'oldpassword.min' => 'Le champ ancient mot de passe doit prendre minimum 8 caractères',
            'oldpassword.max' => 'Le champ ancient mot de passe doit prendre maximum 16 caractères',
            'password.required' => 'Vous devez obligatoirement remplir le nouveau mot de passe',
            'password.string' => 'Le champ nouveau mot de passe doit être une chaine de caractère',
            'password.min' => 'Le champ nouveau mot de passe doit prendre minimum 8 caractères',
            'password.max' => 'Le champ nouveau mot de passe doit prendre maximum 16 caractères',
            'password_confirmation.required' => 'Vous devez obligatoirement remplir le mot de passe confirmation',
        ]);
        $user = Auth::user();
        if (!$user || $user->email !== $request->emailupdate) {
            return back()->with('fall', "Email invalide.");
        }
        if (!Hash::check((string) $request->oldpassword, (string) $user->password)) {
            return back()->with('fall', "Ancien mot de passe incorrect.");
        }
        if ($request->password !== $request->password_confirmation) {
            return back()->with('fall', "Confirmation du mot de passe incorrecte.");
        }

        $user->password = Hash::make((string) $request->password);
        try {
            $user->update();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('success', 'Mot de passe modifié. Veuillez vous connecter à nouveau.');
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Erreur lors de la mise à jour du mot de passe.');
        }

    }
    public function companyCreate(Request $request){
        $validated = $request->validate([
            'name' =>'required|string|max:255',
            'address' =>'required|string|max:255',
            'about' =>'required|string',
        ]);
        $compagnie = Company::find($request->id);
        if (isset($request->logo)) {
            $logoName = time().'.'.request()->logo->getClientOriginalExtension();
            request()->logo->move(public_path('companies'), $logoName);
            $validated['logo'] = $logoName;
        }else {
            $validated['logo'] = $compagnie->logo;
        }
        try {

            $compagnie->update($validated);

            $connectedUser = auth()->user(); // Retrieve the authenticated user

            return redirect()->route('profile', [
                'compagnie' => $compagnie,
                'connectedUser' => $connectedUser
            ])->with('success', "Création de votre entreprise réussie");

            return back()->with('success', "Création de votre entreprise réussie");
        } catch (\Throwable $th) {
            return back()->with('fall', "Création de votre entreprise non réussie");
        }
    }
}
