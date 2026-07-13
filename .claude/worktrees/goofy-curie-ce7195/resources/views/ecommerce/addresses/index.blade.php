@extends('layouts.shop-app')

@section('title', 'Mes adresses')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 mb-1">Mes adresses de livraison</h1>
            <p class="text-gray-600 text-sm">Lieux de réception pour vos commandes</p>
        </div>
        {{-- Bouton Retour au panier --}}
         <a href="{{ route('checkout') }}" class="inline-flex justify-center items-center px-6 py-2.5 rounded-lg border border-amber-300 text-amber-700 font-medium hover:bg-amber-50 transition">
            ← Retour au checkout
        </a>
        <a href="{{ route('addresses.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white text-sm font-semibold shadow-md hover:opacity-95 transition">
            <i class="fas fa-plus"></i> Ajouter une adresse
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($addresses as $address)
            <div class="bg-white rounded-xl border-2 {{ $address->is_default ? 'border-amber-400 shadow-amber-100' : 'border-gray-200' }} shadow-sm p-5 flex flex-col h-full">
                <div class="flex justify-between items-start gap-2 mb-3">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $address->city }}</h2>
                    @if($address->is_default)
                        <span class="shrink-0 text-xs font-semibold uppercase tracking-wide bg-amber-100 text-amber-800 px-2 py-1 rounded">Défaut</span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 flex-1">
                    <span class="font-medium text-gray-700">Adresse</span><br>
                    {{ $address->full_address }}
                </p>
                <p class="text-sm text-gray-600 mt-2">
                    <span class="font-medium text-gray-700">Téléphone</span><br>
                    {{ $address->phone }}
                </p>
                @if($address->instructions)
                    <p class="text-xs text-gray-500 mt-3 flex gap-2">
                        <i class="fas fa-info-circle mt-0.5 text-amber-500"></i>
                        {{ $address->instructions }}
                    </p>
                @endif
                <form action="{{ route('addresses.destroy', $address->id) }}" method="POST" class="mt-4 pt-4 border-t border-gray-100">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 text-sm font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition" onclick="return confirm('Supprimer cette adresse ?');">
                        <i class="fas fa-trash-alt mr-1"></i> Supprimer
                    </button>
                </form>
            </div>
        @empty
            <div class="col-span-full text-center py-16 px-4 bg-white rounded-xl border border-dashed border-gray-300">
                <i class="fas fa-map-marked-alt text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucune adresse enregistrée</h3>
                <p class="text-gray-600 text-sm mb-6 max-w-md mx-auto">Ajoutez une adresse pour faciliter vos livraisons.</p>
                <a href="{{ route('addresses.create') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                    Ajouter ma première adresse
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
