<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Gestion commerciale — {{ $company?->name ?? config('app.name') }}">
    <meta name="author" content="{{ $company?->name ?? config('app.name') }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $company?->name ?? config('app.name') }}</title>

    {{-- Emblème du baobab : lisible en petite taille, contrairement au logo complet. --}}
    <link rel="icon" type="image/png" href="{{ asset('favicon-telico.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('companies/telico-embleme.png') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/animate.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/dataTables.bootstrap4.min.css') }}">


    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

    {{-- Habillage maison, chargé après le thème : barre latérale verte, tableaux,
         cartes. Isolé dans son propre fichier pour que style.css reste remplaçable. --}}
    <link rel="stylesheet" href="{{ asset('assets/css/admin-theme.css') }}">

    {{-- Typographies TELICO : Cinzel (grands titres, comme le logo),
         Source Sans 3 (texte), Barlow Condensed (références, prix, étiquettes). --}}
    {{-- Chargée en asynchrone : une police Google lente/inaccessible ne doit jamais
         bloquer l'exécution des scripts de la page (une feuille de style <link>
         classique bloque le JS tant qu'elle n'est pas chargée). --}}
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&family=Barlow+Condensed:wght@500;600;700&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&family=Barlow+Condensed:wght@500;600;700&display=swap"></noscript>
    <style>
        body, .sidebar, .header, .table, .form-control, .btn { font-family: 'Source Sans 3', 'Segoe UI', Arial, sans-serif; }
        h1, h2 { font-family: 'Cinzel', Georgia, serif; letter-spacing: .02em; }
    </style>
</head>
