<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.check');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dataTable = ExpenseCategory::all();
        return view('expenses.categoryIndex', compact('dataTable'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('expenses.categoryCreate');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // Store a newly created resource in storage
    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|max:255|unique:expense_categories,slug',
            'categoryName' => 'required|string|max:255',
        ],[
            'slug.required' => 'Le champ slug est obligatoire',
            'slug.unique' => 'Cette entrée existe déjà veuillez changer de slug',
            'slug.max' => 'Au maximum 255 caractères pour ce champ',
            'categoryName.max' => 'Au maximum 255 caractères pour ce champ',
            'categoryName.required' => 'Le champ categoryName est obligatoire',
        ]);

        try {
            $expensesCategory = new ExpenseCategory();
            $expensesCategory->slug = $request->slug;
            $expensesCategory->categoryName = $request->categoryName;
            $expensesCategory->save();

            return redirect()->route('expensesCategory.index')->with('success', 'Categorie de Dépense crée avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('expensesCategory.index')->with('error', 'Error lors de la création de cette Categorie de Dépense. '.$th->getMessage());
        }
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
    public function edit(ExpenseCategory $expensesCategory)
    {
        return view('expenses.categoryCreate', compact('expensesCategory'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ExpenseCategory $expensesCategory)
    {
        try {
            $request->validate([
                'slug' => 'required|string|max:255|unique:expense_categories,slug,' . $expensesCategory->id,
                'categoryName' => 'required|string|max:255',
            ],[
                'slug.required' => 'Le champ slug est obligatoire',
                'slug.unique' => 'Cette entrée existe déjà veuillez changer de slug',
                'slug.max' => 'Au maximum 255 caractères pour ce champ',
                'categoryName.max' => 'Au maximum 255 caractères pour ce champ',
                'categoryName.required' => 'Le champ categoryName est obligatoire',
            ]);

            $expensesCategory->slug = $request->slug;
            $expensesCategory->categoryName = $request->categoryName;
            $expensesCategory->save();

            return redirect()->route('expensesCategory.index')->with('success', 'Categorie de Dépense mis à jour avec succès.');
        } catch (\Throwable $th) {
            return redirect()->route('expensesCategory.index')->with('error', 'Erreur lors de la mise à jour de cette Categorie de Dépense. '.$th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExpenseCategory $expensesCategory)
    {
        $expensesCategory->delete();
        return redirect()->route('expensesCategory.index')->with('success', 'Expenses Category deleted successfully.');
    }
}
