<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /** Rôles système non supprimables (le e-commerce et le bypass admin en dépendent). */
    private const PROTECTED_SLUGS = ['admin', 'customer'];

    public function __construct()
    {
        $this->middleware('auth.check');
        // Réservé aux utilisateurs pouvant gérer les paramètres (admin par défaut)
        $this->middleware('permission:settings.edit');
    }

    public function index()
    {
        $dataTable = Role::withCount('permissions')->get()->map(function ($role) {
            $role->users_count = User::where('role_id', $role->id)->count();
            return $role;
        });

        return view('roles.index', compact('dataTable'));
    }

    public function create()
    {
        $permissionModules = Permission::orderBy('module')->orderBy('id')->get()->groupBy('module');

        return view('roles.create', compact('permissionModules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nameRole'      => 'required|string|max:100',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ], [
            'nameRole.required' => 'Le nom du rôle est obligatoire.',
        ]);

        $slug = Str::slug($request->nameRole);
        if (Role::where('slug', $slug)->exists()) {
            return back()->withInput()->with('error', 'Un rôle avec ce nom existe déjà.');
        }

        $role = Role::create([
            'slug'     => $slug,
            'nameRole' => $request->nameRole,
        ]);

        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('roles.index')->with('success', 'Rôle « ' . $role->nameRole . ' » créé avec ' . count($request->input('permissions', [])) . ' permission(s).');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        if ($role->slug === 'admin') {
            return redirect()->route('roles.index')->with('error', 'Le rôle Administrateur a toujours toutes les permissions — il n\'est pas modifiable.');
        }

        $permissionModules = Permission::orderBy('module')->orderBy('id')->get()->groupBy('module');
        $checked = $role->permissions()->pluck('permissions.id')->all();

        return view('roles.edit', compact('role', 'permissionModules', 'checked'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->slug === 'admin') {
            return redirect()->route('roles.index')->with('error', 'Le rôle Administrateur n\'est pas modifiable.');
        }

        $request->validate([
            'nameRole'      => 'required|string|max:100',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        // Le slug reste stable (référencé par le code) — seul le libellé change.
        $role->update(['nameRole' => $request->nameRole]);
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('roles.index')->with('success', 'Rôle « ' . $role->nameRole . ' » mis à jour. Les employés ayant ce rôle (sans permissions personnalisées) suivent automatiquement ces changements.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if (in_array($role->slug, self::PROTECTED_SLUGS, true)) {
            return back()->with('error', 'Le rôle « ' . $role->nameRole . ' » est un rôle système, il ne peut pas être supprimé.');
        }

        $usersCount = User::where('role_id', $role->id)->count();
        if ($usersCount > 0) {
            return back()->with('error', 'Impossible de supprimer : ' . $usersCount . ' utilisateur(s) ont encore ce rôle. Réaffectez-les d\'abord.');
        }

        $role->permissions()->detach();
        $role->delete();

        return back()->with('success', 'Rôle supprimé.');
    }
}
