<?php

namespace App\Http\Controllers;

use App\Models\TailleEnsemble;
use Illuminate\Http\Request;

class TailleEnsembleController extends Controller
{
    public function index()
    {
        $categories = TailleEnsemble::all();
        return view('category_taille.index', compact('categories'));
    }

    public function create()
    {
        return view('category_taille.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|unique:taille_ensembles',
            'category_name' => 'required'
        ]);

        TailleEnsemble::create($request->all());
        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function show(TailleEnsemble $categoryTaille)
    {
        //
    }

    public function edit(TailleEnsemble $categoryTaille)
    {
        return view('category_taille.create', compact('categoryTaille'));
    }

    public function update(Request $request, TailleEnsemble $categoryTaille)
    {
        $request->validate([
            'slug' => 'required|unique:taille_ensembles,slug,' . $categoryTaille->id,
            'category_name' => 'required'
        ]);

        $categoryTaille->update($request->all());
        return redirect()->route('categoryTaille.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(TailleEnsemble $categoryTaille)
    {
        try {
            $categoryTaille->delete();
            return redirect()->route('categoryTaille.index')->with('success', 'Category deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->route('categoryTaille.index')->with('error', 'Category not deleted successfully.'.$th->getMessage());
        }

    }
}
