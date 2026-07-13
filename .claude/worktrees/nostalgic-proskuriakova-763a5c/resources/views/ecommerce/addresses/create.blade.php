@extends('layouts.shop-app')

@section('title', 'Nouvelle adresse')

@section('content')
<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">Ajouter une adresse</h1>
    <p class="text-gray-600 text-sm mb-8">Informations de livraison</p>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
        <form action="{{ route('addresses.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="sm:col-span-1">
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ville / quartier <span class="text-red-500">*</span></label>
                    <input type="text" name="city" id="city" value="{{ old('city') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition"
                           placeholder="Ex. Conakry, Kaloum" required>
                    @error('city')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-1">
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone livraison <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition"
                           placeholder="77XXXXXXX" required>
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div>
                <label for="full_address" class="block text-sm font-medium text-gray-700 mb-1">Adresse complète <span class="text-red-500">*</span></label>
                <textarea name="full_address" id="full_address" rows="3"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition"
                          placeholder="Rue, porte, étage, repères…" required>{{ old('full_address') }}</textarea>
                @error('full_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="instructions" class="block text-sm font-medium text-gray-700 mb-1">Instructions (optionnel)</label>
                <textarea name="instructions" id="instructions" rows="2"
                          class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition"
                          placeholder="Ex. près de la mosquée, 2ᵉ portail bleu…">{{ old('instructions') }}</textarea>
                @error('instructions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" class="inline-flex justify-center items-center px-6 py-2.5 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                    Enregistrer l'adresse
                </button>
                <a href="{{ route('addresses.index') }}" class="inline-flex justify-center items-center px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
