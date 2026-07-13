<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Store;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }

    /**
     * Vérifie si l'utilisateur est admin ou superuser.
     */
    private function isAdmin()
    {
        $user = auth()->user();
        return $user && in_array((int) $user->role_id, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_MANAGER]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::with('store', 'category'); // charge les relations pour l'affichage
        $categories_expenses = ExpenseCategory::all();

        // 🔒 Restriction par magasin : si pas admin, on filtre par le store de l'utilisateur
        if (!$this->isAdmin()) {
            $query->where('store_id', auth()->user()->store_id);
        }

        // Filtres de recherche
        if ($request->filled('reference')) {
            $query->where('reference', 'like', '%' . $request->reference . '%');
        }
        if ($request->filled('expense_categories_id')) {
            $query->where('expense_categories_id', $request->expense_categories_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('amount')) {
            $query->where('amount', 'like', '%' . $request->amount . '%');
        }
        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        $expenses = $query->get();

        return view('expenses.index', compact('expenses', 'categories_expenses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ExpenseCategory::all();
        $stores = $this->isAdmin() ? Store::all() : Store::where('id', auth()->user()->store_id)->get();
        $ref = "DEP" . Carbon::now()->format('Ym') . sprintf("%04d", Expense::count() + 1);
        return view('expenses.create', compact('categories', 'ref', 'stores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'reference' => 'required|string|max:255|unique:expenses,reference',
            'expense_categories_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric',
            'status' => 'required|string',
            'description' => 'nullable|string',
            'store_id' => 'required|exists:stores,id', // on s'assure que store_id est présent
        ], [
            // messages en français (optionnel)
        ]);

        try {
            //dd($request->all());
            Expense::create($request->all());
            return redirect()->route('expenses.index')->with('success', 'Dépense créée avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('expenses.index')->with('error', 'Erreur lors de la création : ' . $th->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        // 🔒 Vérification : un non-admin ne peut modifier que ses propres dépenses (même store)
        if (!$this->isAdmin() && $expense->store_id !== auth()->user()->store_id) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cette dépense.');
        }

        $categories = ExpenseCategory::all();
        $stores = $this->isAdmin() ? Store::all() : Store::where('id', auth()->user()->store_id)->get();
        return view('expenses.create', compact('expense', 'categories', 'stores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        // 🔒 Même vérification
        if (!$this->isAdmin() && $expense->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        $request->validate([
            'reference' => 'required|string|max:255|unique:expenses,reference,' . $expense->id,
            'expense_categories_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric',
            'status' => 'required|string',
            'description' => 'nullable|string',
            'store_id' => 'required|exists:stores,id',
        ]);

        try {
            $expense->update($request->all());
            return redirect()->route('expenses.index')->with('success', 'Dépense mise à jour avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('expenses.index')->with('error', 'Erreur lors de la mise à jour : ' . $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        // 🔒 Même vérification
        if (!$this->isAdmin() && $expense->store_id !== auth()->user()->store_id) {
            abort(403);
        }

        try {
            $expense->delete();
            return redirect()->route('expenses.index')->with('success', 'Dépense supprimée avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('expenses.index')->with('error', 'Erreur lors de la suppression : ' . $th->getMessage());
        }
    }
}