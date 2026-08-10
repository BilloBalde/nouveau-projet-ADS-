<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\StoreController;

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
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (Auth::user()->role_id == 1) {
            $dataTable = User::all();
        }else {
            $dataTable = User::where('role_id', '!=',  1)->get();
        }
        return view('users.index', compact('dataTable'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);

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
            $roles = $roles->filter(
                fn ($role) => collect($rolePresets[$role->id])->diff($grantable)->isEmpty()
            )->values();
        }

        $userPermissions = $user->permissions()->pluck('permissions.id')->all();

        return view('users.edit', compact('user', 'roles', 'permissionModules', 'rolePresets', 'userPermissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //dd('Here for the delete action');
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

    public function assignStore(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'store_id' => 'required|exists:stores,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->store_id = $request->store_id;
        $user->save();

        return back()->with('success', 'Utilisateur affecté à la boutique avec succès.');
    }
    public function showAssignStoreForm()
    {
        $users = User::where('role_id', '>', 1)->get();
        $stores = Store::all();
        return view('users.assign_store', compact('users', 'stores'));
    }
}
