<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        // Route::resource('users', ...) n'appliquait aucune permission : n'importe
        // quel rôle connecté (vendeur, comptable, achats) pouvait voir/créer/
        // modifier des comptes utilisateurs, alors que seuls admin/manager le
        // devraient (cf. presets PermissionSeeder).
        $this->middleware('permission:users.view')->only(['index', 'show']);
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.edit')->only(['edit', 'update']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        // Les comptes administrateurs ne sont visibles que d'un administrateur :
        // c'est une protection du compte lui-même, pas un droit d'écran.
        if (Auth::user()->role_id === User::ROLE_ADMIN) {
            $dataTable = User::all();
        } else {
            $dataTable = User::where('role_id', '!=', User::ROLE_ADMIN)->get();
        }

        return view('users.index', compact('dataTable'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        // Le formulaire complet (rôle, permissions, magasin d'affectation et son
        // historique) est servi par Authentification::edit : cette version-ci ne
        // transmettait pas les magasins et la vue échouait.
        return redirect()->route('editUser', $id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        // dd('Here for the delete action');
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return back()->with('success', 'Utilisateur supprimé avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression.');
        }
    }

    /**
     * Active/suspend un compte utilisateur en un clic. Un compte suspendu ne peut plus
     * se connecter (Authentification::login_submit rejette tout statut différent de
     * "Active"), mais reste dans la liste et peut être réactivé à tout moment.
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ((int) $user->role_id === User::ROLE_ADMIN) {
            return back()->with('error', 'Le compte administrateur ne peut pas être suspendu.');
        }

        $isActive = strtolower((string) $user->status) === 'active';
        $user->status = $isActive ? 'Inactive' : 'Active';
        $user->save();

        return back()->with('success', $isActive
            ? "Compte de {$user->name} suspendu."
            : "Compte de {$user->name} activé.");
    }
}
