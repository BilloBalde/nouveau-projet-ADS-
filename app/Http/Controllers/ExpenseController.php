<?php

namespace App\Http\Controllers;

use App\Models\Associate;
use App\Models\Expense;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Dépenses : les sorties d'argent données aux associés, avec leur motif. Le
 * total de l'année de chacun est déduit de sa part du bénéfice à la clôture.
 */
class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
        // Aucune permission n'était vérifiée : tout employé connecté pouvait
        // enregistrer et supprimer une dépense, donc une écriture financière.
        $this->middleware('permission:expenses.view')->only(['index']);
        $this->middleware('permission:expenses.create')->only(['create', 'store']);
        $this->middleware('permission:expenses.edit')->only(['edit', 'update']);
        $this->middleware('permission:expenses.delete')->only(['destroy']);
    }

    /**
     * Vue globale (accès à tous les magasins) : même règle que le reste du
     * logiciel. Un employé rattaché ne voit que les dépenses de son périmètre
     * (son magasin et les entrepôts / stocks qui en dépendent).
     */
    private function isAdmin()
    {
        $user = auth()->user();

        return $user && ! $user->isStoreScoped();
    }

    private function peutVoir($storeId): bool
    {
        return $this->isAdmin() || auth()->user()->canAccessStore($storeId);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::with('store', 'associate', 'user');
        $associes = Associate::orderBy('name')->get(['id', 'name']);

        // 🔒 Restriction par magasin : si pas admin, on filtre par le store de l'utilisateur
        if (! $this->isAdmin()) {
            $query->whereIn('store_id', auth()->user()->accessibleStoreIds());
        }

        // Filtres de recherche
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%'.$request->reference.'%');
        }
        if ($request->filled('associate_id')) {
            $query->where('associate_id', $request->associate_id);
        }
        if ($request->filled('du')) {
            $query->whereDate('created_at', '>=', $request->du);
        }
        if ($request->filled('au')) {
            $query->whereDate('created_at', '<=', $request->au);
        }

        $expenses = $query->orderByDesc('created_at')->orderByDesc('id')->get();

        return view('expenses.index', compact('expenses', 'associes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = $this->isAdmin() ? Store::all() : Store::whereIn('id', auth()->user()->accessibleStoreIds())->get();
        $associes = Associate::active()->orderBy('name')->get(['id', 'name']);
        // Sur le plus grand numéro, pas sur le nombre de lignes : après une
        // suppression, le compte redonnait une référence déjà prise.
        $ref = 'DEP'.Carbon::now()->format('Ym').sprintf('%04d', (Expense::max('id') ?? 0) + 1);

        return view('expenses.create', compact('ref', 'stores', 'associes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 🔒 Un non-admin ne peut créer une dépense que pour sa propre boutique
        // (edit/update/destroy appliquaient déjà cette règle, pas store()).
        if (! $this->isAdmin() && ! auth()->user()->canAccessStore($request->input('store_id'))) {
            $request->merge(['store_id' => auth()->user()->store_id]);
        }

        // Le résultat de validate() ne contient que les champs déclarés ici.
        // C'est lui qu'on écrit en base : $request->all() laissait passer tout le
        // reste, dont user_id — le formulaire l'envoie en champ caché, il suffisait
        // de le modifier pour attribuer la dépense à un collègue.
        $donnees = $request->validate([
            'reference' => 'required|string|max:255|unique:expenses,reference',
            ...$this->regles(),
        ], $this->messages());

        // L'auteur vient de la session, jamais de la requête.
        $donnees['user_id'] = auth()->id();
        $donnees['status'] = 'Completed';

        try {
            Expense::create($donnees);

            return redirect()->route('expenses.index')->with('success', 'Sortie enregistrée.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        // 🔒 Vérification : un non-admin ne peut modifier que ses propres dépenses (même store)
        if (! $this->peutVoir($expense->store_id)) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cette dépense.');
        }

        $stores = $this->isAdmin() ? Store::all() : Store::whereIn('id', auth()->user()->accessibleStoreIds())->get();
        // Un associé désactivé depuis reste proposé sur ses propres sorties.
        $associes = Associate::where('active', true)->orWhere('id', $expense->associate_id)->orderBy('name')->get(['id', 'name']);

        return view('expenses.create', compact('expense', 'stores', 'associes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        // 🔒 Même vérification
        if (! $this->peutVoir($expense->store_id)) {
            abort(403);
        }

        // Même raison qu'à la création : on n'écrit que les champs validés.
        // L'auteur d'origine n'est pas modifié.
        $donnees = $request->validate([
            'reference' => 'required|string|max:255|unique:expenses,reference,'.$expense->id,
            ...$this->regles(),
        ], $this->messages());

        try {
            $expense->update($donnees);

            return redirect()->route('expenses.index')->with('success', 'Sortie mise à jour.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        // 🔒 Même vérification
        if (! $this->peutVoir($expense->store_id)) {
            abort(403);
        }

        try {
            $expense->delete();

            return redirect()->route('expenses.index')->with('success', 'Sortie supprimée.');
        } catch (\RuntimeException $e) {
            return redirect()->route('expenses.index')->with('error', $e->getMessage());
        }
    }

    /** Une sortie : à qui, combien, pourquoi, depuis quel magasin. */
    private function regles(): array
    {
        return [
            'associate_id' => 'required|exists:associates,id',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:1000',
            'store_id' => 'required|exists:stores,id',
        ];
    }

    private function messages(): array
    {
        return [
            'associate_id.required' => "Choisissez l'associé qui reçoit l'argent.",
            'amount.required' => 'Indiquez le montant donné.',
            'amount.min' => 'Le montant doit être positif.',
            'description.required' => 'Indiquez le motif de la sortie.',
            'store_id.required' => "Choisissez le magasin d'où sort l'argent.",
        ];
    }
}
