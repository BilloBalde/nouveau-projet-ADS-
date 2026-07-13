@extends('layouts.shop-app')

@section('title', 'Mon panier')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">Mon panier</h1>
    <p class="text-gray-600 text-sm mb-8">Gérez vos articles avant de commander</p>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produit</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Prix</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qté</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $total = 0; @endphp
                    @forelse($cart as $id => $details)
                        @php $total += $details['price'] * $details['quantity']; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-14 h-14 rounded-lg bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                        <img src="{{ asset('products/' . $details['image']) }}" alt="" class="w-full h-full object-cover"
                                             onerror="this.onerror=null;this.src='{{ asset('assets/img/product/noimage.png') }}'">
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ $details['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-700 text-right whitespace-nowrap">{{ number_format($details['price'], 0, ',', ' ') }} GNF</td>
                            <td class="px-4 py-4 text-sm text-gray-700 text-right">{{ $details['quantity'] }}</td>
                            <td class="px-4 py-4 text-sm font-medium text-gray-900 text-right whitespace-nowrap">{{ number_format($details['price'] * $details['quantity'], 0, ',', ' ') }} GNF</td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('cart.remove', $id) }}" class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-red-600 hover:bg-red-50 transition" title="Retirer">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-gray-500">Votre panier est vide.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @php
        $continueUrl = Auth::check() && Auth::user()->isCustomer() ? route('shop.home') : route('accueil');
    @endphp

    @if(count($cart) > 0)
        <div class="mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <a href="{{ $continueUrl }}" class="inline-flex justify-center items-center px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition">
                Continuer mes achats
            </a>
            <div class="text-right">
                <p class="text-sm text-gray-600 mb-1">Total</p>
                <p class="text-2xl font-bold text-amber-600 mb-4">{{ number_format($total, 0, ',', ' ') }} GNF</p>
                @auth
                    @if(Auth::user()->isCustomer())
                        <a href="{{ route('checkout') }}" class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                            Passer à la caisse <i class="fas fa-arrow-right text-sm"></i>
                        </a>
                    @else
                        <p class="text-sm text-gray-500 max-w-xs ml-auto">Connectez-vous avec un compte client pour commander.</p>
                        <a href="{{ route('otp.login') }}" class="mt-2 inline-block text-amber-600 font-medium hover:underline">Espace client</a>
                    @endif
                @else
                    <a href="{{ route('otp.login') }}?redirect=checkout" class="inline-flex items-center justify-center gap-2 px-8 py-3 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                        Se connecter pour commander
                    </a>
                @endauth
            </div>
        </div>
    @else
        <div class="text-center mt-10">
            <a href="{{ $continueUrl }}" class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                Retour à la boutique
            </a>
        </div>
    @endif
</div>
@endsection
