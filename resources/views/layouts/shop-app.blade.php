<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Boutique en ligne SMH">
    <title>@yield('title', 'Espace client') — SMH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
    {{-- Chargée en asynchrone : un CDN externe lent/injoignable ne doit jamais
         bloquer l'exécution des scripts de la page (une feuille de style <link>
         classique bloque le JS tant qu'elle n'est pas chargée). --}}
    <link rel="preload" as="style"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
</head>
<body class="bg-gray-50 min-h-screen">
    @include('shop.partials.nav')

    <div class="pt-20"></div>

    @if(session('success'))
        <div class="container mx-auto px-4 pt-4">
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm" role="alert">{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="container mx-auto px-4 pt-4">
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm" role="alert">{{ session('error') }}</div>
        </div>
    @endif
    @if(session('info'))
        <div class="container mx-auto px-4 pt-4">
            <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm" role="alert">{{ session('info') }}</div>
        </div>
    @endif

    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>

    @include('partials.storefront-footer')
</body>
</html>
