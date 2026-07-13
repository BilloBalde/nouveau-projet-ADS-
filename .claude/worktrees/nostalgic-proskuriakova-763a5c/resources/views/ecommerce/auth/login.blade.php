@extends('layouts.shop-app')

@section('title', 'Connexion')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <h1 class="text-xl font-bold text-gray-900 text-center mb-2">Connexion</h1>
        <p class="text-sm text-gray-600 text-center mb-6">Entrez votre numéro pour recevoir un code par SMS</p>

        @if (session()->has('buy_now_product_id'))
        <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm flex items-center gap-2">
            <i class="fas fa-info-circle"></i>
            <span>Après connexion, vous serez redirigé(e) vers le checkout pour finaliser votre commande.</span>
        </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('otp.login_submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required autocomplete="tel"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition"
                       placeholder="77XXXXXXX">
            </div>
            <button type="submit" class="w-full inline-flex justify-center items-center py-3 px-4 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                Envoyer le code OTP
            </button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
            Pas encore de compte ?
            <a href="{{ route('otp.register') }}" class="font-medium text-amber-600 hover:text-amber-700">S'inscrire</a>
        </p>
        <p class="text-center mt-4">
            <a href="{{ route('accueil') }}" class="text-sm text-gray-500 hover:text-amber-600">← Retour à la boutique</a>
        </p>
    </div>
</div>
@endsection
