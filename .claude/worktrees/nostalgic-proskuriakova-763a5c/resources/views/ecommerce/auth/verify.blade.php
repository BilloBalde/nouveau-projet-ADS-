@extends('layouts.shop-app')

@section('title', 'Code de vérification')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
        <h1 class="text-xl font-bold text-gray-900 text-center mb-2">Code de vérification</h1>
        <p class="text-sm text-gray-600 text-center mb-6">
            Entrez le code envoyé au <strong class="text-gray-900">{{ $phone }}</strong>
        </p>

        @if (session('otp_demo'))
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                <strong class="font-semibold">Mode test :</strong> code
                <span class="font-mono text-lg tracking-widest">{{ session('otp_demo') }}</span>
                <span class="block text-xs text-amber-800 mt-1">Désactivez l’affichage avec SMS_SHOW_CODE=false en production.</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('otp.verify_submit') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="phone" value="{{ $phone }}">
            <div>
                <label for="code" class="sr-only">Code à 6 chiffres</label>
                <input type="text" name="code" id="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus
                       class="w-full rounded-xl border-2 border-gray-200 px-4 py-4 text-center text-2xl font-bold tracking-[0.5em] text-gray-900 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition"
                       placeholder="000000">
            </div>
            <button type="submit" class="w-full inline-flex justify-center items-center py-3 px-4 rounded-lg bg-gradient-to-r from-amber-500 to-amber-600 text-white font-semibold shadow-md hover:opacity-95 transition">
                Vérifier le code
            </button>
        </form>

        <p class="text-center mt-6">
            <a href="{{ route('otp.login') }}" class="text-sm text-amber-600 hover:text-amber-700">← Changer de numéro</a>
        </p>
    </div>
</div>
@endsection
