<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SMH - Meubles de qualité en Guinée</title>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        amber: {
                            50:  '#faf6ef',
                            100: '#f4ece1',
                            200: '#e4d3b8',
                            300: '#d9c3a3',
                            400: '#c9986a',
                            500: '#c1682f',
                            600: '#a8532a',
                            700: '#8a5a34',
                            800: '#5a3820',
                            900: '#2b1d14',
                        },
                    },
                },
            },
        };
    </script>

    {{-- Google Fonts - Fraunces (titres), Poppins et Inter (texte) --}}
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Font Awesome pour les icônes --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { font-family: 'Poppins', sans-serif; }
        h1, h2, h3 { font-family: 'Fraunces', serif; }

        /* ── Hero ── */
        .hero-section {
            background: linear-gradient(135deg, #061633 0%, #0c2c58 35%, #123f74 65%, #1e5fa8 100%);
            min-height: 620px;
            position: relative;
            overflow: hidden;
        }
        .hero-section::before {
            content: '';
            position: absolute; top: -80px; right: -80px;
            width: 520px; height: 520px;
            background: radial-gradient(circle, rgba(255,138,26,0.20) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-section::after {
            content: '';
            position: absolute; bottom: -100px; left: -100px;
            width: 450px; height: 450px;
            background: radial-gradient(circle, rgba(255,255,255,0.10) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-wave { position: absolute; bottom: -1px; left: 0; width: 100%; line-height: 0; z-index: 5; }
        .hero-wave svg { display: block; width: 100%; height: 56px; }
        .hero-content { position: relative; z-index: 10; }

        /* ── Hero Carousel ── */
        .hero-carousel {
            position: relative;
            width: 100%;
            height: 420px;
        }
        .hero-slide {
            position: absolute; inset: 0;
            opacity: 0;
            transition: opacity 0.7s ease, transform 0.7s ease;
            transform: translateX(40px) scale(0.97);
        }
        .hero-slide.active {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        .hero-slide.exit {
            opacity: 0;
            transform: translateX(-40px) scale(0.97);
        }
        .hero-slide img {
            width: 100%; height: 100%;
            object-fit: cover;
            border-radius: 1.5rem;
        }
        .hero-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.35);
            cursor: pointer;
            transition: all 0.3s;
        }
        .hero-dot.active {
            width: 28px;
            border-radius: 4px;
            background: #fff;
        }
        .hero-badge {
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 999px;
            padding: 6px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .hero-stat {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 1rem;
            padding: 12px 20px;
            text-align: center;
        }
        .carousel-arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; cursor: pointer;
            transition: background 0.3s;
            z-index: 20;
        }
        .carousel-arrow:hover { background: rgba(255,255,255,0.28); }
        .carousel-arrow.prev { left: 8px; }
        .carousel-arrow.next { right: 8px; }
        .floating-card {
            position: absolute;
            background: rgba(255,255,255,0.95);
            border-radius: 1rem;
            padding: 10px 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            display: flex; align-items: center; gap: 10px;
            min-width: 140px;
            animation: floatY 3s ease-in-out infinite;
        }
        .floating-card.card-top { top: 16px; right: -16px; animation-delay: 0s; }
        .floating-card.card-bot { bottom: 24px; left: -16px; animation-delay: 1.5s; }
        @keyframes floatY {
            0%,100% { transform: translateY(0); }
            50%      { transform: translateY(-8px); }
        }
        @media (max-width: 768px) {
            .floating-card { display: none; }
            .hero-carousel { height: 280px; }
        }

        /* ── Misc helpers ── */
        .gradient-text {
            background: linear-gradient(135deg, #c9986a, #c1682f);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .navbar-transparent {
            position: fixed; top: 0; width: 100%; z-index: 50;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .feature-icon {
            width: 60px; height: 60px;
            background: linear-gradient(135deg, #c9986a, #a8532a);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 28px;
            margin: 0 auto 20px;
        }

        /* ══════════════════════════════════════
           SECTION PRODUITS — onglets + sidebar
        ══════════════════════════════════════ */
        .tabs {
            display: flex; gap: 4px;
            border-bottom: 2px solid #f3f4f6;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 12px 26px; font-size: 14px; font-weight: 600;
            border: none; background: none;
            color: #6b7280; cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            border-radius: 6px 6px 0 0;
            display: flex; align-items: center; gap: 8px;
        }
        .tab-btn:hover { color: #a8532a; background: #f4ece1; }
        .tab-btn.active { color: #a8532a; border-bottom-color: #a8532a; background: none; }

        .panel { display: none; }
        .panel.active { display: block; }

        /* Grille catégories */
        .cat-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }
        @media (max-width: 1024px) { .cat-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 768px)  { .cat-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 480px)  { .cat-grid { grid-template-columns: repeat(2, 1fr); } }
        .cat-card {
            background: #fff; border: 1.5px solid #f3f4f6;
            border-radius: 20px; overflow: hidden;
            text-align: center; cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .cat-card:hover {
            border-color: #c1682f;
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(193,104,47,0.15);
        }
        .cat-img-wrap {
            width: 100%; height: 140px;
            overflow: hidden; position: relative;
            background: #fafafa;
        }
        .cat-img-wrap img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform 0.3s;
        }
        .cat-card:hover .cat-img-wrap img { transform: scale(1.06); }
        .cat-icon-wrap {
            width: 100%; height: 140px;
            display: flex; align-items: center; justify-content: center;
        }
        .cat-icon {
            width: 70px; height: 70px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
        }
        .cat-body { padding: 12px 10px 16px; }
        .cat-name { font-size: 13px; font-weight: 700; color: #1f2937; }
        .cat-count { font-size: 11px; color: #9ca3af; margin-top: 4px; }

        /* Layout liste produits + sidebar */
        .products-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 768px) {
            .products-layout { grid-template-columns: 1fr; }
            .sidebar { position: static !important; }
        }

        /* Sidebar */
        .sidebar {
            background: #fff; border: 1.5px solid #f3f4f6;
            border-radius: 16px; padding: 1.5rem;
            position: sticky; top: 90px;
        }
        .sidebar-title {
            font-size: 11px; font-weight: 700; color: #9ca3af;
            text-transform: uppercase; letter-spacing: 0.08em;
            margin-bottom: 16px;
        }
        .filter-group { margin-bottom: 1.5rem; }
        .filter-label {
            font-size: 13px; font-weight: 600; color: #374151;
            margin-bottom: 10px; display: block;
        }
        .filter-option {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 0; cursor: pointer;
        }
        .filter-option input[type=checkbox],
        .filter-option input[type=radio] {
            width: 15px; height: 15px;
            accent-color: #a8532a; cursor: pointer;
        }
        .filter-option span { font-size: 13px; color: #374151; }
        .filter-option .f-badge {
            margin-left: auto; font-size: 11px;
            background: #f3f4f6; color: #6b7280;
            padding: 1px 8px; border-radius: 20px;
        }
        .filter-scroll::-webkit-scrollbar { width: 4px; }
        .filter-scroll::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 4px; }
        .filter-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
        .filter-scroll::-webkit-scrollbar-thumb:hover { background: #c1682f; }
        .price-inputs { display: flex; gap: 8px; align-items: center; }
        .price-inputs input {
            width: 72px; padding: 6px 10px; font-size: 12px;
            border: 1.5px solid #e5e7eb; border-radius: 8px;
            background: #fff; color: #374151;
            font-family: 'Poppins', sans-serif;
            outline: none;
        }
        .price-inputs input:focus { border-color: #c1682f; }
        .price-sep { font-size: 12px; color: #9ca3af; }
        .reset-btn {
            width: 100%; padding: 8px; font-size: 12px; font-weight: 600;
            color: #a8532a; border: 1.5px solid #d9c3a3;
            border-radius: 8px; background: none; cursor: pointer;
            margin-top: 4px; transition: background 0.2s;
        }
        .reset-btn:hover { background: #f4ece1; }
        .filter-divider { border: none; border-top: 1px solid #f3f4f6; margin: 0 0 1.25rem; }

        /* Grille produits */
        .results-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 16px;
            flex-wrap: wrap; gap: 8px;
        }
        .results-count { font-size: 13px; color: #6b7280; }
        .sort-sel {
            font-size: 12px; padding: 6px 12px;
            border: 1.5px solid #e5e7eb; border-radius: 8px;
            background: #fff; color: #374151; cursor: pointer;
            font-family: 'Poppins', sans-serif; outline: none;
        }
        .sort-sel:focus { border-color: #c1682f; }

        .prod-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
        }
        @media (max-width: 1024px) { .prod-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 768px)  { .prod-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 480px)  { .prod-grid { grid-template-columns: repeat(2, 1fr); } }
        .prod-card {
            background: #fff; border: 1.5px solid #f3f4f6;
            border-radius: 20px; overflow: hidden;
            transition: all 0.25s; cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .prod-card:hover {
            border-color: #c1682f;
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(193,104,47,0.15);
        }
        .prod-img {
            height: 180px; display: flex; align-items: center;
            justify-content: center; position: relative;
            background: #fafafa;
            overflow: hidden;
        }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
        .prod-card:hover .prod-img img { transform: scale(1.05); }
        .prod-img .no-img { font-size: 48px; }
        .prod-badge {
            position: absolute; top: 10px; left: 10px;
            font-size: 10px; font-weight: 700;
            padding: 4px 10px; border-radius: 20px;
            backdrop-filter: blur(4px);
        }
        .badge-promo { background: rgba(244,236,225,0.95); color: #7a3a19; }
        .badge-best { background: rgba(209,250,229,0.95); color: #065f46; }

        .prod-info { padding: 14px 14px 16px; }
        .prod-name {
            font-size: 13px; font-weight: 600; color: #1f2937;
            margin-bottom: 3px; line-height: 1.4;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .prod-cat-tag {
            font-size: 11px; color: #fff; background: #c1682f;
            display: inline-block; padding: 2px 8px; border-radius: 20px;
            margin-bottom: 8px; font-weight: 500;
        }
        .prod-stars { font-size: 12px; color: #c1682f; margin-bottom: 8px; }
        .tier-table { margin-bottom: 10px; }
        .tier-label {
            font-size: 10px; font-weight: 600; color: #a8532a;
            text-transform: uppercase; letter-spacing: 0.03em;
            margin-bottom: 6px;
        }
        .tier-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; }
        .tier-cell { background: #f4ece1; border-radius: 8px; padding: 4px 2px; text-align: center; }
        .tier-qty { font-size: 10px; font-weight: 700; color: #7a3a19; }
        .tier-price { font-size: 11px; font-weight: 600; color: #4a3220; }
        .tier-badge {
            display: inline-block; margin-top: 2px;
            font-size: 9px; font-weight: 700; color: #fff;
            background: #16a34a; border-radius: 20px; padding: 1px 5px;
        }
        .prod-qty { display: flex; align-items: center; gap: 6px; margin: 10px 0; }
        .qty-btn {
            width: 24px; height: 24px; border-radius: 6px;
            border: 1.5px solid #e5e7eb; background: #fff;
            color: #a8532a; font-weight: 700; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; line-height: 1;
        }
        .qty-btn:hover { background: #f4ece1; border-color: #c1682f; }
        .qty-input {
            width: 40px; text-align: center; font-size: 13px;
            border: 1.5px solid #e5e7eb; border-radius: 6px;
            padding: 3px; font-family: 'Poppins', sans-serif;
        }
        .prod-price-row {
            display: flex; align-items: baseline; gap: 6px;
            margin-bottom: 12px; flex-wrap: wrap;
        }
        .prod-price { font-size: 17px; font-weight: 700; color: #a8532a; }
        .prod-price-old {
            font-size: 12px; color: #9ca3af;
            text-decoration: line-through;
        }
        .prod-btns { display: flex; gap: 6px; }
        .prod-btn {
            flex: 1; padding: 9px 4px; font-size: 11px;
            font-weight: 600; text-align: center;
            background: linear-gradient(135deg, #c9986a, #a8532a);
            color: #fff; border: none; border-radius: 10px; cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            font-family: 'Poppins', sans-serif;
        }
        .prod-btn:hover { opacity: 0.88; transform: scale(1.02); }
        .prod-btn-order {
            flex: 1; padding: 9px 4px; font-size: 11px;
            font-weight: 600; text-align: center;
            background: none; color: #a8532a;
            border: 2px solid #a8532a; border-radius: 10px; cursor: pointer;
            transition: all 0.2s; font-family: 'Poppins', sans-serif;
        }
        .prod-btn-order:hover { background: #a8532a; color: #fff; transform: scale(1.02); }

        .empty-state {
            text-align: center; padding: 3rem 1rem;
            color: #9ca3af; font-size: 14px;
        }
        .empty-state i { font-size: 32px; margin-bottom: 12px; display: block; color: #d1d5db; }
    </style>
</head>
<body class="bg-white">

    @include('partials.storefront-nav')

    {{-- ══════════════════════════════════════════
        HERO — Carousel élégant
    ══════════════════════════════════════════ --}}
    <section id="accueil" class="hero-section pt-28 pb-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="hero-content grid grid-cols-1 md:grid-cols-2 gap-12 items-center min-h-[500px]">

                {{-- ── Côté texte ── --}}
                <div class="flex flex-col justify-center">

                    {{-- Badge --}}
                    <div class="hero-badge mb-5 w-fit">
                        <span class="w-2 h-2 rounded-full bg-orange-400 inline-block animate-pulse"></span>
                        Meubles & décoration · Guinée
                    </div>

                    {{-- Titre --}}
                    <h1 class="text-5xl md:text-6xl font-extrabold text-white mb-5 leading-tight tracking-tight">
                        Des meubles qui<br>
                        <span style="background:linear-gradient(90deg,#ff9a4d,#ffe6cf);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                            transforment votre intérieur
                        </span>
                    </h1>

                    {{-- Sous-titre --}}
                    <p class="text-white/80 text-base md:text-lg mb-8 leading-relaxed max-w-md">
                        Salons, chambres, salles à manger, bureaux et pièces sur mesure — SMH vous accompagne avec des meubles alliant élégance, confort et durabilité, partout en Guinée.
                    </p>

                    {{-- Boutons --}}
                    <div class="flex flex-wrap gap-3 mb-10">
                        <a href="#produits" class="px-7 py-3 rounded-full font-semibold text-blue-900 transition duration-300 hover:shadow-xl hover:scale-105 inline-flex items-center gap-2"
                           style="background:linear-gradient(90deg,#ff9a4d,#ffcf94);">
                            <i class="fas fa-sparkles text-sm"></i> Découvrir
                        </a>
                        <a href="{{ route('products.index') }}" class="px-7 py-3 rounded-full font-semibold text-white border border-white/30 hover:bg-white/10 transition duration-300 inline-flex items-center gap-2">
                            <i class="fas fa-th-large text-sm"></i> Catalogue
                        </a>
                    </div>

                    {{-- Stats --}}
                    <div class="flex gap-4 flex-wrap">
                        <div class="hero-stat">
                            <div class="text-2xl font-bold text-orange-300">{{ $totalProducts }}+</div>
                            <div class="text-xs text-white/70 mt-1">Produits</div>
                        </div>
                        <div class="hero-stat">
                            <div class="text-2xl font-bold text-orange-300">{{ $totalCategories }}</div>
                            <div class="text-xs text-white/70 mt-1">Catégories</div>
                        </div>
                        <div class="hero-stat">
                            <div class="text-2xl font-bold text-orange-300">Pro</div>
                            <div class="text-xs text-white/70 mt-1">Qualité</div>
                        </div>
                    </div>
                </div>

                {{-- ── Côté carousel ── --}}
                <div class="flex justify-center md:justify-end">
                    <div class="relative" style="width:320px;">

                        {{-- Carousel --}}
                        <div class="hero-carousel shadow-2xl rounded-3xl overflow-hidden border border-white/10" id="heroCarousel">
                            @php
                                $heroImages = $allProducts->filter(fn($p) => !empty($p['image']))->take(6)->values();
                                if ($heroImages->isEmpty()) {
                                    $heroImages = collect([['image' => null, 'name' => 'SMH']]);
                                }
                            @endphp
                            @foreach($heroImages as $i => $prod)
                            <div class="hero-slide {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}">
                                @if($prod['image'])
                                    <img src="{{ $prod['image'] }}" alt="{{ $prod['name'] }}"
                                         onerror="this.onerror=null;this.parentElement.style.background='linear-gradient(135deg,#c9986a,#c1682f)'">
                                @else
                                    <div class="w-full h-full flex items-center justify-center"
                                         style="background:linear-gradient(135deg,#123f74,#1e5fa8);">
                                        <img src="{{ asset('images/customers/logo.jpg') }}" alt="SMH" class="w-32 h-32 object-contain opacity-80">
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent rounded-3xl"></div>
                                {{-- Nom produit en bas --}}
                                <div class="absolute bottom-4 left-4 right-4">
                                    <span class="text-white text-sm font-semibold bg-black/30 backdrop-blur px-3 py-1 rounded-full">
                                        {{ $prod['name'] }}
                                    </span>
                                </div>
                            </div>
                            @endforeach

                            {{-- Flèches --}}
                            <button class="carousel-arrow prev" id="heroPrev" aria-label="Précédent">
                                <i class="fas fa-chevron-left text-xs"></i>
                            </button>
                            <button class="carousel-arrow next" id="heroNext" aria-label="Suivant">
                                <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>

                        {{-- Dots --}}
                        <div class="flex justify-center gap-2 mt-4" id="heroDots">
                            @foreach($heroImages as $i => $prod)
                            <button class="hero-dot {{ $i === 0 ? 'active' : '' }}" data-dot="{{ $i }}" aria-label="Slide {{ $i+1 }}"></button>
                            @endforeach
                        </div>

                        {{-- Floating cards --}}
                        <div class="floating-card card-top">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-tree text-blue-600 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-800">Bois massif</div>
                                <div class="text-xs text-gray-500">Finition soignée</div>
                            </div>
                        </div>
                        <div class="floating-card card-bot">
                            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-truck text-orange-600 text-xs"></i>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-800">Livraison rapide</div>
                                <div class="text-xs text-gray-500">Partout en Guinée</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- Transition douce vers la section suivante --}}
        <div class="hero-wave" aria-hidden="true">
            <svg viewBox="0 0 1440 60" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                <path fill="#ffffff" d="M0,32 C240,64 480,0 720,16 C960,32 1200,64 1440,32 L1440,60 L0,60 Z"></path>
            </svg>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         CARROUSEL DES CATÉGORIES (défilement auto, boucle infinie)
    ══════════════════════════════════════════ --}}
    <section id="categories-carousel" class="py-14 bg-white">
        <style>
            .cat-carousel-track {
                display: flex; gap: 18px; overflow-x: auto; scroll-behavior: auto;
                -ms-overflow-style: none; scrollbar-width: none; padding: 6px 2px 14px;
            }
            .cat-carousel-track::-webkit-scrollbar { display: none; }
            .cat-slide {
                flex: 0 0 150px; text-align: center; text-decoration: none; cursor: pointer;
                transition: transform .2s;
            }
            .cat-slide:hover { transform: translateY(-4px); }
            .cat-slide-img {
                width: 120px; height: 120px; margin: 0 auto 10px; border-radius: 50%;
                overflow: hidden; border: 3px solid #f4ece1; background: #faf6ef;
                display: flex; align-items: center; justify-content: center;
                transition: border-color .2s, box-shadow .2s;
            }
            .cat-slide:hover .cat-slide-img { border-color: #c1682f; box-shadow: 0 8px 20px rgba(193,104,47,.18); }
            .cat-slide-img img { width: 100%; height: 100%; object-fit: cover; }
            .cat-slide-img .cat-slide-icon { font-size: 36px; color: #c1682f; }
            .cat-slide-name {
                font-size: 13px; font-weight: 700; color: #1f2937;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .cat-slide-count { font-size: 11px; color: #9ca3af; }
            .cat-progress { display: flex; justify-content: center; gap: 6px; margin-top: 10px; }
            .cat-progress-dot {
                width: 22px; height: 4px; border-radius: 999px; background: #f4ece1;
                overflow: hidden; position: relative;
            }
            .cat-progress-dot.active::after {
                content: ''; position: absolute; inset: 0; background: #c1682f; border-radius: 999px;
            }
        </style>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-6">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-800">Parcourez nos <span class="gradient-text">catégories</span></h2>
                    <p class="text-gray-500 mt-2">Cliquez sur une catégorie pour filtrer le catalogue</p>
                </div>
                <a href="{{ route('public.categories') }}" class="hidden sm:inline-flex items-center gap-2 text-amber-600 font-semibold hover:text-amber-700 whitespace-nowrap">
                    Tout voir <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>

            <div class="cat-carousel-track" id="cat-carousel-track">
                @foreach($categories as $cat)
                <a href="{{ route('products.index') }}?cat={{ urlencode($cat->name) }}" class="cat-slide" data-cat-slide>
                    <div class="cat-slide-img">
                        @if($cat->image_url)
                            <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}" loading="lazy"
                                 onerror="this.outerHTML='<span class=&quot;cat-slide-icon&quot;><i class=&quot;fas fa-couch&quot;></i></span>'">
                        @else
                            <span class="cat-slide-icon"><i class="fas fa-couch"></i></span>
                        @endif
                    </div>
                    <div class="cat-slide-name">{{ $cat->name }}</div>
                    <div class="cat-slide-count">{{ $cat->products_count }} produit{{ $cat->products_count > 1 ? 's' : '' }}</div>
                </a>
                @endforeach
            </div>
            <div class="cat-progress" id="cat-progress"></div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         SECTION PRODUITS (onglets)
    ══════════════════════════════════════════ --}}
    <section id="produits" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">

            {{-- En-tête --}}
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                    Nos <span class="gradient-text">Produits</span>
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Explorez notre gamme de meubles — salons, chambres, salles à manger, bureaux et articles de décoration. Qualité soignée, prix compétitifs.
                </p>
            </div>

            {{-- Onglets --}}
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('categories')">
                    <i class="fas fa-th-large"></i> Catégories
                </button>
                <button class="tab-btn" onclick="switchTab('best')">
                    <i class="fas fa-star"></i> Meilleurs produits
                </button>
                <button class="tab-btn" onclick="switchTab('promo')">
                    <i class="fas fa-tag"></i> Promotions
                </button>
            </div>

            {{-- ─── Panel : Catégories ─── --}}
            <div id="panel-categories" class="panel active">
                @php
                    $catIcons = [
                        'ENCRE'           => ['icon'=>'fas fa-fill-drip',    'bg'=>'#ede9fe','color'=>'#7c3aed'],
                        'Vinyl'           => ['icon'=>'fas fa-scroll',        'bg'=>'#d1fae5','color'=>'#059669'],
                        'BACHES'          => ['icon'=>'fas fa-image',         'bg'=>'#e0f2fe','color'=>'#0284c7'],
                        'FOREX'           => ['icon'=>'fas fa-layer-group',   'bg'=>'#fce7f3','color'=>'#db2777'],
                        'Papier'          => ['icon'=>'fas fa-file-alt',      'bg'=>'#fef3c7','color'=>'#d97706'],
                        'PAPIER'          => ['icon'=>'fas fa-file-alt',      'bg'=>'#fef3c7','color'=>'#d97706'],
                        'AUTOCOLLANT'     => ['icon'=>'fas fa-sticky-note',   'bg'=>'#dcfce7','color'=>'#16a34a'],
                        'FILM'            => ['icon'=>'fas fa-film',          'bg'=>'#fef9c3','color'=>'#b45309'],
                        'MACHINE'         => ['icon'=>'fas fa-cog',           'bg'=>'#fee2e2','color'=>'#dc2626'],
                        'PRESS'           => ['icon'=>'fas fa-compress-alt',  'bg'=>'#e0e7ff','color'=>'#4338ca'],
                        'KIKEMONO'        => ['icon'=>'fas fa-ruler-vertical','bg'=>'#fdf4ff','color'=>'#9333ea'],
                        'KAKIMONO'        => ['icon'=>'fas fa-ruler-vertical','bg'=>'#fdf4ff','color'=>'#9333ea'],
                        'ALICOBON'        => ['icon'=>'fas fa-th-large',      'bg'=>'#f0fdf4','color'=>'#15803d'],
                        'PLEXI'           => ['icon'=>'fas fa-border-all',    'bg'=>'#f0f9ff','color'=>'#0369a1'],
                        'LED'             => ['icon'=>'fas fa-lightbulb',     'bg'=>'#fffbeb','color'=>'#d97706'],
                        'IMPRIMENTE'      => ['icon'=>'fas fa-print',         'bg'=>'#fce7f3','color'=>'#db2777'],
                        'LAMINATOR'       => ['icon'=>'fas fa-layer-group',   'bg'=>'#e0f2fe','color'=>'#0284c7'],
                        'ANNEAU'          => ['icon'=>'fas fa-circle',        'bg'=>'#f5f3ff','color'=>'#7c3aed'],
                        'DECOUPE'         => ['icon'=>'fas fa-cut',           'bg'=>'#fef2f2','color'=>'#dc2626'],
                        'BIC'             => ['icon'=>'fas fa-pen',           'bg'=>'#f0fdf4','color'=>'#16a34a'],
                        'CACHET'          => ['icon'=>'fas fa-stamp',         'bg'=>'#fdf4ff','color'=>'#9333ea'],
                        'TASSE'           => ['icon'=>'fas fa-mug-hot',       'bg'=>'#fffbeb','color'=>'#f59e0b'],
                        'POUDRE'          => ['icon'=>'fas fa-mortar-pestle', 'bg'=>'#f5f3ff','color'=>'#7c3aed'],
                        'SUPPORT'         => ['icon'=>'fas fa-image',         'bg'=>'#fef3c7','color'=>'#d97706'],
                        'TABLEAU'         => ['icon'=>'fas fa-photo-video',   'bg'=>'#ede9fe','color'=>'#7c3aed'],
                        'TOTEM'           => ['icon'=>'fas fa-columns',       'bg'=>'#e0e7ff','color'=>'#4338ca'],
                    ];
                    $defaultIcon = ['icon'=>'fas fa-box','bg'=>'#f3f4f6','color'=>'#6b7280'];
                @endphp
                <div class="cat-grid">
                    @foreach($categories->sortByDesc('products_count')->take(10) as $cat)
                    @php
                        $ic    = $catIcons[$cat->name] ?? $defaultIcon;
                        $icBg  = $ic['bg'];
                        $icClr = $ic['color'];
                        $icCls = $ic['icon'];
                        $catNameJs = addslashes($cat->name);
                    @endphp
                    <div class="cat-card" onclick="switchTabWithFilter('best', '{{ $catNameJs }}')">
                        @if($cat->image_url)
                        <div class="cat-img-wrap">
                            <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}"
                                 onerror="this.parentElement.outerHTML='<div class=\'cat-icon-wrap\'><div class=\'cat-icon\' style=\'background:{{ $icBg }};color:{{ $icClr }}\'><i class=\'{{ $icCls }}\'></i></div></div>'">
                        </div>
                        @else
                        <div class="cat-icon-wrap">
                            <div class="cat-icon" style="background:{{ $icBg }};color:{{ $icClr }};">
                                <i class="{{ $icCls }}"></i>
                            </div>
                        </div>
                        @endif
                        <div class="cat-body">
                            <div class="cat-name">{{ $cat->name }}</div>
                            <div class="cat-count">{{ $cat->products_count }} produit{{ $cat->products_count > 1 ? 's' : '' }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Bouton voir toutes les catégories --}}
                <div class="text-center mt-8">
                    <a href="{{ route('public.categories') }}"
                       class="inline-flex items-center gap-2 px-7 py-3 border-2 border-amber-500 text-amber-600 rounded-full font-semibold text-sm hover:bg-amber-500 hover:text-white transition duration-200">
                        <i class="fas fa-th-large"></i>
                        Voir toutes les catégories ({{ $totalCategories }})
                    </a>
                </div>

                {{-- ── 10 produits en vedette ── --}}
                @if(isset($allProducts) && count($allProducts) > 0)
                <div class="mt-14">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">
                        <i class="fas fa-box-open text-amber-500 mr-2"></i> Nos produits
                        <span class="text-sm font-normal text-gray-400 ml-2">({{ $totalProducts }} au total)</span>
                    </h3>
                    <div class="prod-grid" id="grid-all">
                        @foreach($allProducts->take(10) as $p)
                        <div class="prod-card" style="cursor:pointer;" onclick="window.location='{{ route('productDetail', $p['id']) }}'">
                            <div class="prod-img">
                                @if($p['image'])
                                    <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}"
                                         onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='block'">
                                    <span class="no-img" style="display:none">🛋️</span>
                                @else
                                    <span class="no-img">🪑</span>
                                @endif
                                @if($p['old_price'])
                                    <span class="prod-badge badge-promo">-{{ $p['discount'] }}%</span>
                                @endif
                            </div>
                            <div class="prod-info">
                                <div class="prod-cat-tag">{{ $p['category_name'] }}</div>
                                <div class="prod-name">{{ $p['name'] }}</div>
                                <div class="prod-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($p['rating']))★@else☆@endif
                                    @endfor
                                </div>
                                <div class="tier-table">
                                    <div class="tier-label">Tarifs de gros — prix unitaire</div>
                                    <div class="tier-grid">
                                        @foreach(\App\Support\PricingTiers::displayTiers($p['price']) as $tier)
                                        <div class="tier-cell">
                                            <div class="tier-qty">{{ $tier['qty'] }}+</div>
                                            <div class="tier-price">{{ number_format($tier['price'], 0, ',', ' ') }}</div>
                                            @if($tier['percent'] > 0)
                                                <div class="tier-badge">-{{ $tier['percent'] }}%</div>
                                            @endif
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="prod-qty" onclick="event.stopPropagation()">
                                    <button type="button" class="qty-btn" onclick="changeQty(this,-1)">−</button>
                                    <input type="number" class="qty-input" min="1" value="1" data-base-price="{{ $p['price'] }}" onchange="updateCardPrice(this)">
                                    <button type="button" class="qty-btn" onclick="changeQty(this,1)">+</button>
                                </div>
                                <div class="prod-price-row">
                                    <span class="prod-price" data-computed-price>{{ number_format($p['price'], 0, ',', ' ') }} GNF</span>
                                    @if($p['old_price'])
                                        <span class="prod-price-old">{{ number_format($p['old_price'], 0, ',', ' ') }} GNF</span>
                                    @endif
                                </div>
                                <div class="prod-btns">
                                    <button class="prod-btn" onclick="event.stopPropagation();addToCartWithQty({{ $p['id'] }}, this)">
                                        <i class="fas fa-shopping-bag mr-1"></i> Panier
                                    </button>
                                    <button class="prod-btn-order" onclick="event.stopPropagation();orderNowWithQty({{ $p['id'] }}, this)">
                                        <i class="fas fa-bolt mr-1"></i> Commander
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Lien vers tous les produits --}}
                    @if($totalProducts > 10)
                    <div class="text-center mt-8">
                        <a href="{{ route('products.index') }}"
                           class="inline-flex items-center gap-2 px-7 py-3 border-2 border-amber-500 text-amber-600 rounded-full font-semibold text-sm hover:bg-amber-500 hover:text-white transition duration-200">
                            <i class="fas fa-box-open"></i>
                            Voir tous les produits ({{ $totalProducts }})
                        </a>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            {{-- ─── Panel : Meilleurs produits ─── --}}
            <div id="panel-best" class="panel">
                <div class="products-layout">

                    {{-- Sidebar --}}
                    <aside class="sidebar">
                        <p class="sidebar-title"><i class="fas fa-sliders-h mr-2"></i>Filtres</p>

                        <div class="filter-group">
                            <span class="filter-label">Catégories</span>
                            <div class="filter-scroll" style="max-height:200px;overflow-y:auto;padding-right:4px;">
                            @foreach($categories->where('products_count', '>', 0)->sortByDesc('products_count') as $cat)
                            <label class="filter-option">
                                <input type="checkbox" class="cat-filter-best" value="{{ $cat->name }}" onchange="filterProducts('best')">
                                <span>{{ $cat->name }}</span>
                                <span class="f-badge">{{ $cat->products_count }}</span>
                            </label>
                            @endforeach
                            @if($categories->isEmpty())
                                @foreach(['Papier','Rouleaux','Encres','Cartouches','Presses','Rubans','Plastification'] as $cn)
                                <label class="filter-option">
                                    <input type="checkbox" class="cat-filter-best" value="{{ $cn }}" onchange="filterProducts('best')">
                                    <span>{{ $cn }}</span>
                                </label>
                                @endforeach
                            @endif
                            </div>
                        </div>

                        <hr class="filter-divider">

                        <div class="filter-group">
                            <span class="filter-label">Prix (GNF)</span>
                            <div class="price-inputs">
                                <input type="number" id="min-best" placeholder="Min" min="0" oninput="filterProducts('best')">
                                <span class="price-sep">—</span>
                                <input type="number" id="max-best" placeholder="Max" min="0" oninput="filterProducts('best')">
                            </div>
                        </div>

                        <hr class="filter-divider">

                        <div class="filter-group">
                            <span class="filter-label">Note minimale</span>
                            <label class="filter-option"><input type="radio" name="rating-best" value="0" checked onchange="filterProducts('best')"><span>Toutes</span></label>
                            <label class="filter-option"><input type="radio" name="rating-best" value="4" onchange="filterProducts('best')"><span>4+ étoiles</span></label>
                            <label class="filter-option"><input type="radio" name="rating-best" value="4.5" onchange="filterProducts('best')"><span>4.5+ étoiles</span></label>
                        </div>

                        <button class="reset-btn" onclick="resetFilters('best')">
                            <i class="fas fa-undo mr-1"></i> Réinitialiser
                        </button>
                    </aside>

                    {{-- Grille --}}
                    <div>
                        <div class="results-header">
                            <span class="results-count" id="count-best">— produits</span>
                            <select class="sort-sel" id="sort-best" onchange="filterProducts('best')">
                                <option value="rating">Mieux notés</option>
                                <option value="price-asc">Prix croissant</option>
                                <option value="price-desc">Prix décroissant</option>
                            </select>
                        </div>
                        <div class="prod-grid" id="grid-best"></div>
                        <div id="sentinel-best" style="display:none;text-align:center;padding:18px;color:#c1682f;">
                            <i class="fas fa-spinner fa-spin"></i> Chargement…
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── Panel : Promotions ─── --}}
            <div id="panel-promo" class="panel">
                <div class="products-layout">

                    {{-- Sidebar --}}
                    <aside class="sidebar">
                        <p class="sidebar-title"><i class="fas fa-sliders-h mr-2"></i>Filtres</p>

                        <div class="filter-group">
                            <span class="filter-label">Catégories</span>
                            <div class="filter-scroll" style="max-height:200px;overflow-y:auto;padding-right:4px;">
                            @foreach($categories->where('products_count', '>', 0)->sortByDesc('products_count') as $cat)
                            <label class="filter-option">
                                <input type="checkbox" class="cat-filter-promo" value="{{ $cat->name }}" onchange="filterProducts('promo')">
                                <span>{{ $cat->name }}</span>
                                <span class="f-badge">{{ $cat->products_count }}</span>
                            </label>
                            @endforeach
                            @if($categories->isEmpty())
                                @foreach(['Papier','Rouleaux','Encres','Cartouches'] as $cn)
                                <label class="filter-option">
                                    <input type="checkbox" class="cat-filter-promo" value="{{ $cn }}" onchange="filterProducts('promo')">
                                    <span>{{ $cn }}</span>
                                </label>
                                @endforeach
                            @endif
                            </div>
                        </div>

                        <hr class="filter-divider">

                        <div class="filter-group">
                            <span class="filter-label">Réduction minimale</span>
                            <label class="filter-option"><input type="radio" name="disc-promo" value="0" checked onchange="filterProducts('promo')"><span>Toutes</span></label>
                            <label class="filter-option"><input type="radio" name="disc-promo" value="20" onchange="filterProducts('promo')"><span>-20% et plus</span></label>
                            <label class="filter-option"><input type="radio" name="disc-promo" value="30" onchange="filterProducts('promo')"><span>-30% et plus</span></label>
                        </div>

                        <hr class="filter-divider">

                        <div class="filter-group">
                            <span class="filter-label">Prix promo</span>
                            <div class="price-inputs">
                                <input type="number" id="min-promo" placeholder="Min" min="0" oninput="filterProducts('promo')">
                                <span class="price-sep">—</span>
                                <input type="number" id="max-promo" placeholder="Max" min="0" oninput="filterProducts('promo')">
                            </div>
                        </div>

                        <button class="reset-btn" onclick="resetFilters('promo')">
                            <i class="fas fa-undo mr-1"></i> Réinitialiser
                        </button>
                    </aside>

                    {{-- Grille --}}
                    <div>
                        <div class="results-header">
                            <span class="results-count" id="count-promo">— produits</span>
                            <select class="sort-sel" id="sort-promo" onchange="filterProducts('promo')">
                                <option value="discount">Meilleures réductions</option>
                                <option value="price-asc">Prix croissant</option>
                                <option value="price-desc">Prix décroissant</option>
                            </select>
                        </div>
                        <div class="prod-grid" id="grid-promo"></div>
                        <div id="sentinel-promo" style="display:none;text-align:center;padding:18px;color:#c1682f;">
                            <i class="fas fa-spinner fa-spin"></i> Chargement…
                        </div>
                    </div>
                </div>
            </div>

          
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         SECTIONS : PLUS VENDUS / MIEUX NOTÉS / TENDANCES
    ══════════════════════════════════════════ --}}
    <style>
        .prod-row {
            display: flex; gap: 18px; overflow-x: auto; padding: 8px 2px 16px;
            -ms-overflow-style: none; scrollbar-width: thin; scrollbar-color: #e4d3b8 transparent;
        }
        .prod-row .prod-card { flex: 0 0 262px; }
        .section-row-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .section-row-head .row-icon {
            width: 44px; height: 44px; border-radius: 12px; background: #f4ece1; color: #a8532a;
            display: inline-flex; align-items: center; justify-content: center; font-size: 19px;
        }
        .section-row-head h2 { font-size: 26px; font-weight: 800; color: #1f2937; }
        .section-row-head p { font-size: 13px; color: #9ca3af; }
    </style>
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 space-y-14">

            <div>
                <div class="section-row-head">
                    <span class="row-icon"><i class="fas fa-fire"></i></span>
                    <div>
                        <h2>Les plus vendus</h2>
                        <p>Classement basé sur nos ventes réelles</p>
                    </div>
                </div>
                <div class="prod-row" id="row-best-sellers"></div>
            </div>

            <div>
                <div class="section-row-head">
                    <span class="row-icon"><i class="fas fa-star"></i></span>
                    <div>
                        <h2>Les mieux notés</h2>
                        <p>Plébiscités par nos clients</p>
                    </div>
                </div>
                <div class="prod-row" id="row-top-rated"></div>
            </div>

            <div>
                <div class="section-row-head">
                    <span class="row-icon"><i class="fas fa-chart-line"></i></span>
                    <div>
                        <h2>Tendances du moment</h2>
                        <p>Les plus demandés ces 30 derniers jours</p>
                    </div>
                </div>
                <div class="prod-row" id="row-trending"></div>
            </div>

        </div>
    </section>

    {{-- ══════════════════════════════════════════
         MODES DE LIVRAISON
    ══════════════════════════════════════════ --}}
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Modes de <span class="gradient-text">livraison</span></h2>
                <p class="text-gray-500 max-w-xl mx-auto">Choisissez la formule qui vous convient — nous livrons vos meubles partout en Guinée</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach([
                    ['icon' => 'fa-truck',        'title' => 'Livraison à domicile', 'text' => 'Recevez vos meubles chez vous en 24 à 48h à Conakry, montés et prêts à l\'emploi.'],
                    ['icon' => 'fa-store',        'title' => 'Retrait en magasin',   'text' => 'Commandez en ligne et récupérez gratuitement à Madina (Centre Faloulay) ou à T6 Sonfonia.'],
                    ['icon' => 'fa-bolt',         'title' => 'Livraison express',    'text' => 'Besoin urgent ? Livraison le jour même à Conakry pour les commandes passées avant midi.'],
                    ['icon' => 'fa-map',          'title' => 'Livraison en région',  'text' => 'Nous expédions dans toutes les régions de la Guinée via nos transporteurs partenaires.'],
                ] as $mode)
                <div class="bg-white rounded-2xl p-7 text-center border border-gray-100 shadow-sm hover:shadow-lg hover:border-amber-200 transition">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:#f4ece1;color:#a8532a;font-size:22px;">
                        <i class="fas {{ $mode['icon'] }}"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-2">{{ $mode['title'] }}</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">{{ $mode['text'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         POURQUOI NOUS
    ══════════════════════════════════════════ --}}
    <section id="pourquoi" class="py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                    Pourquoi nous <span class="gradient-text">choisir</span>
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Trois raisons principales pour faire confiance à SMH pour l'aménagement de votre intérieur
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                <div class="text-center">
                    <div class="feature-icon"><i class="fas fa-truck"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Livraison Rapide</h3>
                    <p class="text-gray-600 leading-relaxed">Livraison partout en Guinée en 24 à 48h. Commandez vos meubles et recevez-les directement chez vous ou dans votre bureau, prêts à l'emploi.</p>
                </div>
                <div class="text-center">
                    <div class="feature-icon"><i class="fas fa-couch"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Meubles de Qualité</h3>
                    <p class="text-gray-600 leading-relaxed">Bois massif, finitions soignées, rembourrage durable et assemblage solide — des meubles conçus pour durer, du style moderne au classique.</p>
                </div>
                <div class="text-center">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Conseil & Support</h3>
                    <p class="text-gray-600 leading-relaxed">Notre équipe vous conseille selon votre espace et votre budget, pour choisir les meubles adaptés à votre intérieur. Disponible par téléphone et en boutique à nos adresses.</p>
                </div>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════
         À PROPOS
    ══════════════════════════════════════════ --}}
    <section id="apropos" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="inline-block px-4 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold mb-4">
                        Qui sommes-nous ?
                    </span>
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-6 leading-tight">
                        <span class="gradient-text">SMH</span>
                    </h2>
                    <p class="text-gray-600 text-lg leading-relaxed mb-6">
                        Nous sommes <strong>SMH</strong>, votre partenaire de confiance pour l'<strong>ameublement</strong> en Guinée. Salons, chambres, salles à manger, bureaux et pièces sur mesure — nous accompagnons particuliers et professionnels avec qualité, élégance et fiabilité.
                    </p>
                    <p class="text-gray-600 leading-relaxed mb-8">
                        Notre mission : vous offrir des meubles de qualité à des prix accessibles, avec un service de proximité et un accompagnement personnalisé à chaque étape de votre projet d'aménagement. Chez SMH, au-delà de la qualité des autres.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-3 shadow-sm border border-gray-100">
                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Localisation</p>
                                <p class="text-sm font-semibold text-gray-700">Centre Faloulay, 1er étage B30, Madina route Niger</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-3 shadow-sm border border-gray-100">
                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-award text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Qualité</p>
                                <p class="text-sm font-semibold text-gray-700">Meubles durables, garantis</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center">
                    <div class="relative w-full max-w-sm">
                        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-3xl p-8 text-white text-center shadow-2xl">
                            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-couch text-white text-4xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold mb-2">SMH</h3>
                            <p class="text-white/80 text-sm mb-6">Spécialiste en ameublement<br>Chez SMH, au-delà de la qualité des autres.</p>
                            <div class="space-y-3 text-left">
                                <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-2">
                                    <i class="fas fa-phone text-white/80 text-sm"></i>
                                    <span class="text-sm">+224 626 311 915</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-2">
                                    <i class="fas fa-envelope text-white/80 text-sm"></i>
                                    <span class="text-sm">saikououmar47@gmail.com</span>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -top-4 -right-4 w-24 h-24 bg-amber-200 rounded-full opacity-40 -z-10"></div>
                        <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-amber-300 rounded-full opacity-30 -z-10"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         NOS BOUTIQUES
    ══════════════════════════════════════════ --}}
    @if(isset($stores) && $stores->count() > 0)
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">
                <span class="inline-block px-4 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold mb-4">
                    Où nous trouver
                </span>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                    Nos <span class="gradient-text">Boutiques</span>
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Retrouvez nos points de vente partout en Guinée — chaque boutique est gérée par une équipe dédiée.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min($stores->count(), 4) }} gap-8">
                @foreach($stores as $store)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-lg hover:border-amber-300 transition duration-300 flex flex-col">

                    {{-- Image boutique --}}
                    @if($store->image_url)
                        <img src="{{ $store->image_url }}" alt="{{ $store->name }}"
                             class="w-full h-44 object-cover"
                             onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="w-full h-44 bg-gradient-to-br from-amber-100 to-amber-200 items-center justify-center hidden">
                            <i class="fas fa-store text-amber-500 text-5xl"></i>
                        </div>
                    @else
                        <div class="w-full h-44 bg-gradient-to-br from-amber-100 to-amber-200 flex items-center justify-center">
                            <i class="fas fa-store text-amber-500 text-5xl"></i>
                        </div>
                    @endif

                    {{-- Infos --}}
                    <div class="p-6 flex flex-col flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $store->name }}</h3>

                        @if($store->address)
                        <div class="flex items-start gap-2 text-sm text-gray-500 mb-2">
                            <i class="fas fa-map-marker-alt text-amber-500 mt-0.5 flex-shrink-0"></i>
                            <span>{{ $store->address }}</span>
                        </div>
                        @endif

                        @if($store->description)
                        <p class="text-sm text-gray-600 leading-relaxed mb-4 flex-1">{{ $store->description }}</p>
                        @endif

                        {{-- Manager --}}
                        @if($store->manager || $store->phone)
                        <div class="border-t border-gray-100 pt-4 mt-auto">
                            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-2">Responsable</p>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-amber-600 text-sm"></i>
                                </div>
                                <div>
                                    @if($store->manager)
                                    <p class="text-sm font-semibold text-gray-800">{{ $store->manager }}</p>
                                    @endif
                                    @if($store->phone)
                                    <a href="tel:{{ $store->phone }}"
                                       class="text-xs text-amber-600 hover:text-amber-700 font-medium transition">
                                        <i class="fas fa-phone text-xs mr-1"></i>{{ $store->phone }}
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </section>
    @endif

    {{-- ══════════════════════════════════════════
         CONTACT
    ══════════════════════════════════════════ --}}
    <section id="contact" class="py-20 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <span class="inline-block px-4 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold mb-4">
                    Contactez-nous
                </span>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                    Nous sommes à <span class="gradient-text">votre écoute</span>
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Besoin d'un conseil, d'un devis ou d'une livraison ? Contactez SMH directement.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <div class="text-center bg-gray-50 rounded-2xl p-8 border border-gray-100 hover:border-amber-300 hover:shadow-lg transition duration-300">
                    <div class="feature-icon mx-auto mb-5"><i class="fas fa-map-marker-alt"></i></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Adresse</h3>
                    <p class="text-gray-600 leading-relaxed">
                        1<sup>er</sup> adresse : Centre Faloulay, 1<sup>er</sup> étage B30, Madina route Niger<br>
                        2<sup>e</sup> adresse : T6, en face de la station Star, en partant pour Sonfonia
                    </p>
                </div>
                <div class="text-center bg-gray-50 rounded-2xl p-8 border border-gray-100 hover:border-amber-300 hover:shadow-lg transition duration-300">
                    <div class="feature-icon mx-auto mb-5"><i class="fas fa-phone-alt"></i></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Appelez-nous</h3>
                    <p class="text-gray-600 leading-relaxed">
                        <a href="tel:+224626311915" class="hover:text-amber-600 transition font-medium">+224 626 311 915</a><br>
                        <a href="tel:+224626314400" class="hover:text-amber-600 transition font-medium">+224 626 31 44 00</a>
                    </p>
                </div>
                <div class="text-center bg-gray-50 rounded-2xl p-8 border border-gray-100 hover:border-amber-300 hover:shadow-lg transition duration-300">
                    <div class="feature-icon mx-auto mb-5"><i class="fas fa-envelope"></i></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Email</h3>
                    <p class="text-gray-600 leading-relaxed">
                        <a href="mailto:saikououmar47@gmail.com" class="hover:text-amber-600 transition font-medium break-all">
                            saikououmar47@gmail.com
                        </a>
                    </p>
                </div>
            </div>
            <div class="text-center mt-12">
                <a href="{{ route('contact') }}" class="px-10 py-4 bg-amber-600 text-white rounded-full font-semibold text-lg hover:bg-amber-700 transition duration-300 inline-block shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i> Envoyer un message
                </a>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         NEWSLETTER
    ══════════════════════════════════════════ --}}
    <section class="py-16 bg-gradient-to-r from-amber-500 to-amber-600">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center text-white">
                <h2 class="text-4xl font-bold mb-4">Offres et nouveautés meubles</h2>
                <p class="text-lg text-white/90 mb-8">Inscrivez-vous à notre newsletter et soyez le premier informé de nos promotions sur les salons, chambres et articles de décoration</p>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex gap-2 max-w-md mx-auto">
                    @csrf
                    <input type="email" name="email" placeholder="Votre email" required
                        class="flex-1 px-6 py-3 rounded-full text-gray-800 focus:outline-none focus:ring-2 focus:ring-white">
                    <button type="submit" class="px-8 py-3 bg-white text-amber-600 rounded-full font-semibold hover:shadow-lg transition">
                        S'inscrire
                    </button>
                </form>
                @if(session('newsletter_success'))
                    <p class="text-green-200 mt-2">{{ session('newsletter_success') }}</p>
                @endif
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         FOOTER
    ══════════════════════════════════════════ --}}
    @include('partials.storefront-footer')

    {{-- ══════════════════════════════════════════
         SCRIPTS
    ══════════════════════════════════════════ --}}
    <script src="{{ asset('assets/js/pricing-tiers.js') }}"></script>
    <script>
        {{-- Données injectées depuis le contrôleur --}}
        {{-- Chaque produit doit avoir : name, category_name, price, old_price (nullable),
             discount (nullable, ex: 25), rating, image (nullable), is_best, is_promo --}}
        const bestProducts  = @json($bestProducts ?? []);
        const promoProducts = @json($promoProducts ?? []);
        const BEST_SELLERS  = @json($bestSellers ?? []);
        const TOP_RATED     = @json($topRated ?? []);
        const TRENDING      = @json($trending ?? []);

        /* ── Rendu d'une carte produit ── */
        function renderCard(p) {
            const stars    = '★'.repeat(Math.floor(p.rating)) + (p.rating % 1 >= 0.5 ? '½' : '');
            const isPromo  = !!p.old_price;
            const badgeTxt = isPromo ? `-${p.discount}%` : 'Top vente';
            const badgeCls = isPromo ? 'badge-promo' : 'badge-best';
            const oldHTML  = p.old_price
                ? `<span class="prod-price-old">${Number(p.old_price).toLocaleString('fr-FR')} GNF</span>`
                : '';
            
            const imgHTML = p.image
                ? `<img src="${p.image}" alt="Image non trouvée: ${p.image}" loading="lazy" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null; this.src='https://placehold.co/400x400?text=Introuvable'">`
                : `<span class="no-img">🪑</span>`;
            const tierHTML = displayTiersFor(p.price).map(t => `
                <div class="tier-cell">
                    <div class="tier-qty">${t.qty}+</div>
                    <div class="tier-price">${Number(t.price).toLocaleString('fr-FR')}</div>
                    ${t.percent > 0 ? `<div class="tier-badge">-${t.percent}%</div>` : ''}
                </div>`).join('');
            return `<div class="prod-card" style="cursor:pointer;" onclick="window.location='/product/${p.id}'">
                <div class="prod-img">
                    <span class="prod-badge ${badgeCls}">${badgeTxt}</span>
                    ${imgHTML}
                </div>
                <div class="prod-info">
                    <div class="prod-name">${p.name}</div>
                    <div class="prod-cat-tag">${p.category_name ?? ''}</div>
                    <div class="prod-stars">${stars} <span style="color:#9ca3af;font-size:11px;">${parseFloat(p.rating).toFixed(1)}</span></div>
                    <div class="tier-table">
                        <div class="tier-label">Tarifs de gros — prix unitaire</div>
                        <div class="tier-grid">${tierHTML}</div>
                    </div>
                    <div class="prod-qty" onclick="event.stopPropagation()">
                        <button type="button" class="qty-btn" onclick="changeQty(this,-1)">−</button>
                        <input type="number" class="qty-input" min="1" value="1" data-base-price="${p.price}" onchange="updateCardPrice(this)">
                        <button type="button" class="qty-btn" onclick="changeQty(this,1)">+</button>
                    </div>
                    <div class="prod-price-row">
                        <span class="prod-price" data-computed-price>${Number(p.price).toLocaleString('fr-FR')} GNF</span>
                        ${oldHTML}
                    </div>
                    <div class="prod-btns">
                        <button class="prod-btn" onclick="event.stopPropagation();addToCartWithQty(${p.id}, this)">
                            <i class="fas fa-shopping-bag mr-1"></i> Panier
                        </button>
                        <button class="prod-btn-order" onclick="event.stopPropagation();orderNowWithQty(${p.id}, this)">
                            <i class="fas fa-bolt mr-1"></i> Commander
                        </button>
                    </div>
                </div>
            </div>`;
        }

        /* ── Filtrage / tri ── */
        function filterProducts(tab) {
            const isPromo = tab === 'promo';
            let data      = isPromo ? [...promoProducts] : [...bestProducts];

            /* Catégories cochées */
            const checked = [...document.querySelectorAll(`.cat-filter-${tab}:checked`)].map(el => el.value);
            if (checked.length) data = data.filter(p => checked.includes(p.category_name));

            /* Fourchette de prix */
            const minP = parseFloat(document.getElementById(`min-${tab}`).value) || 0;
            const maxP = parseFloat(document.getElementById(`max-${tab}`).value) || Infinity;
            data = data.filter(p => p.price >= minP && p.price <= maxP);

            /* Filtre spécifique */
            if (isPromo) {
                const disc = parseFloat(document.querySelector('input[name="disc-promo"]:checked')?.value ?? 0);
                data = data.filter(p => (p.discount ?? 0) >= disc);
            } else {
                const minR = parseFloat(document.querySelector('input[name="rating-best"]:checked')?.value ?? 0);
                data = data.filter(p => p.rating >= minR);
            }

            /* Tri */
            const sort = document.getElementById(`sort-${tab}`).value;
            if (sort === 'price-asc')  data.sort((a,b) => a.price - b.price);
            if (sort === 'price-desc') data.sort((a,b) => b.price - a.price);
            if (sort === 'rating')     data.sort((a,b) => b.rating - a.rating);
            if (sort === 'discount')   data.sort((a,b) => (b.discount??0) - (a.discount??0));

            /* Affichage progressif (scroll infini par lots de 12) */
            const grid = document.getElementById(`grid-${tab}`);
            gridState[tab] = { data, rendered: 0 };
            grid.innerHTML = data.length
                ? ''
                : `<div class="empty-state col-span-full">
                       <i class="fas fa-box-open"></i>
                       Aucun produit trouvé avec ces filtres.
                   </div>`;
            if (data.length) renderChunk(tab);
            else document.getElementById(`sentinel-${tab}`).style.display = 'none';
            document.getElementById(`count-${tab}`).textContent =
                `${data.length} produit${data.length !== 1 ? 's' : ''}`;
        }

        /* ── Scroll infini : rendu par lots via IntersectionObserver ── */
        const CHUNK_SIZE = 12;
        const gridState = {};

        function renderChunk(tab) {
            const st = gridState[tab];
            if (!st || st.rendered >= st.data.length) return;
            const slice = st.data.slice(st.rendered, st.rendered + CHUNK_SIZE);
            document.getElementById(`grid-${tab}`).insertAdjacentHTML('beforeend', slice.map(renderCard).join(''));
            st.rendered += slice.length;
            document.getElementById(`sentinel-${tab}`).style.display =
                st.rendered < st.data.length ? '' : 'none';
        }

        ['best', 'promo'].forEach(tab => {
            const sentinel = document.getElementById(`sentinel-${tab}`);
            if (!sentinel || !('IntersectionObserver' in window)) return;
            new IntersectionObserver(entries => {
                if (entries[0].isIntersecting) renderChunk(tab);
            }, { rootMargin: '300px' }).observe(sentinel);
        });

        function resetFilters(tab) {
            document.querySelectorAll(`.cat-filter-${tab}`).forEach(el => el.checked = false);
            document.getElementById(`min-${tab}`).value = '';
            document.getElementById(`max-${tab}`).value = '';
            const name = tab === 'promo' ? 'disc-promo' : 'rating-best';
            const first = document.querySelector(`input[name="${name}"]`);
            if (first) first.checked = true;
            document.getElementById(`sort-${tab}`).selectedIndex = 0;
            filterProducts(tab);
        }

        /* ── Navigation onglets ── */
        function switchTab(tab) {
            const order = ['categories','best','promo'];
            document.querySelectorAll('.tab-btn').forEach((btn, i) => {
                btn.classList.toggle('active', order[i] === tab);
            });
            document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
            document.getElementById(`panel-${tab}`).classList.add('active');
            if (tab === 'best' || tab === 'promo') filterProducts(tab);
        }

        function switchTabWithFilter(tab, cat) {
            switchTab(tab);
            setTimeout(() => {
                document.querySelectorAll(`.cat-filter-${tab}`).forEach(el => {
                    el.checked = (el.value === cat);
                });
                filterProducts(tab);
            }, 10);
        }

        /* ── Panier (avec débogage) ── */
        /* ── Toast notification ── */
        function showToast(msg, type = 'success') {
            const existing = document.getElementById('cart-toast');
            if (existing) existing.remove();

            const icons = { success: '🛍️', error: '❌' };
            const colors = {
                success: 'linear-gradient(135deg,#c9986a,#a8532a)',
                error:   'linear-gradient(135deg,#ef4444,#dc2626)'
            };

            const toast = document.createElement('div');
            toast.id = 'cart-toast';
            toast.innerHTML = `<span style="font-size:20px">${icons[type]}</span><span>${msg}</span>`;
            toast.style.cssText = `
                position:fixed; bottom:28px; right:28px; z-index:9999;
                display:flex; align-items:center; gap:12px;
                padding:14px 22px; border-radius:14px;
                background:${colors[type]}; color:#fff;
                font-size:14px; font-weight:600;
                box-shadow:0 8px 30px rgba(0,0,0,0.18);
                transform:translateY(80px); opacity:0;
                transition:transform 0.35s cubic-bezier(.34,1.56,.64,1), opacity 0.3s;
                font-family:'Poppins',sans-serif;
            `;
            document.body.appendChild(toast);

            requestAnimationFrame(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity   = '1';
            });

            setTimeout(() => {
                toast.style.transform = 'translateY(80px)';
                toast.style.opacity   = '0';
                setTimeout(() => toast.remove(), 350);
            }, 2800);
        }

        function changeQty(btn, delta) {
            const container = btn.closest('.prod-qty');
            const input = container.querySelector('.qty-input');
            let val = (parseInt(input.value, 10) || 1) + delta;
            if (val < 1) val = 1;
            input.value = val;
            updateCardPrice(input);
        }

        function updateCardPrice(input) {
            const card = input.closest('.prod-card');
            const base = parseFloat(input.dataset.basePrice);
            const qty = parseInt(input.value, 10) || 1;
            const unit = unitPriceForQuantity(base, qty);
            const priceEl = card?.querySelector('[data-computed-price]');
            if (priceEl) priceEl.textContent = new Intl.NumberFormat('fr-FR').format(unit) + ' GNF';
        }

        function addToCartWithQty(id, btn) {
            const card = btn.closest('.prod-card');
            const qty = parseInt(card?.querySelector('.qty-input')?.value, 10) || 1;
            addToCart(id, qty);
        }

        function orderNowWithQty(id, btn) {
            const card = btn.closest('.prod-card');
            const qty = parseInt(card?.querySelector('.qty-input')?.value, 10) || 1;
            orderNow(id, qty);
        }

        function addToCart(id, quantity = 1) {
            fetch(`{{ url('/cart/add') }}/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ quantity })
            })
            .then(response => {
                if (!response.ok) throw new Error();
                return response.json();
            })
            .then(data => {
                const badge = document.getElementById('cart-count');
                if (badge && data.count !== undefined) {
                    badge.textContent = data.count;
                    badge.style.transform = 'scale(1.5)';
                    setTimeout(() => badge.style.transform = 'scale(1)', 300);
                }
                showToast('Produit ajouté au panier !');
            })
            .catch(() => showToast('Impossible d\'ajouter ce produit.', 'error'));
        }

        function orderNow(id, quantity = 1) {
            // Ajouter au panier en premier
            fetch(`{{ url('/cart/add') }}/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ quantity })
            })
            .then(r => r.json())
            .then(data => {
                console.log('Product added to cart:', data);
                // Mettre à jour le compteur du panier
                const badge = document.getElementById('cart-count');
                if (badge && data.count !== undefined) {
                    badge.textContent = data.count;
                    badge.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        badge.style.transform = 'scale(1)';
                    }, 300);
                }
                showToast('Produit ajouté ! Redirection...');
                
                // Vérifier si l'utilisateur est connecté
                @if(auth()->check() && auth()->user()->isCustomer())
                    // Connecté : aller au checkout
                    setTimeout(() => {
                        window.location.href = '{{ route("checkout") }}';
                    }, 500);
                @else
                    // Non connecté : rediriger vers login avec product_id
                    setTimeout(() => {
                        window.location.href = '{{ route("otp.login") }}?product_id=' + id;
                    }, 500);
                @endif
            })
            .catch((err) => {
                console.error('Erreur:', err);
                showToast('Impossible de passer la commande', 'error');
            });
        }

        /* ── Init ── */
        filterProducts('best');
        filterProducts('promo');

        /* ── Smooth scroll ── */
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault();
                const t = document.querySelector(a.getAttribute('href'));
                if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        /* ── Hero Carousel ── */
        (function() {
            const slides = document.querySelectorAll('.hero-slide');
            const dots   = document.querySelectorAll('.hero-dot');
            if (!slides.length) return;

            let current = 0;
            let timer   = null;

            function goTo(n) {
                slides[current].classList.remove('active');
                slides[current].classList.add('exit');
                dots[current]?.classList.remove('active');

                const old = current;
                current = (n + slides.length) % slides.length;

                slides[current].classList.remove('exit');
                slides[current].classList.add('active');
                dots[current]?.classList.add('active');

                setTimeout(() => slides[old].classList.remove('exit'), 750);
            }

            function startAuto() {
                clearInterval(timer);
                timer = setInterval(() => goTo(current + 1), 3500);
            }

            document.getElementById('heroNext')?.addEventListener('click', () => { goTo(current + 1); startAuto(); });
            document.getElementById('heroPrev')?.addEventListener('click', () => { goTo(current - 1); startAuto(); });
            dots.forEach(d => d.addEventListener('click', () => { goTo(+d.dataset.dot); startAuto(); }));

            startAuto();
        })();

        /* ── Rangées : plus vendus / mieux notés / tendances ── */
        (function () {
            const rows = [
                ['row-best-sellers', BEST_SELLERS],
                ['row-top-rated',    TOP_RATED],
                ['row-trending',     TRENDING],
            ];
            rows.forEach(([id, data]) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.innerHTML = data.length
                    ? data.map(renderCard).join('')
                    : '<p style="color:#9ca3af;padding:12px;">Aucun produit à afficher pour le moment.</p>';
            });
        })();

        /* ── Carrousel des catégories : boucle infinie + défilement auto ── */
        (function () {
            const track = document.getElementById('cat-carousel-track');
            if (!track || track.children.length === 0) return;

            // Dupliquer les items pour une boucle sans couture
            const originalWidth = track.scrollWidth;
            track.innerHTML += track.innerHTML;

            // Indicateurs de progression (max 8 points)
            const progress = document.getElementById('cat-progress');
            const dotCount = Math.min(8, Math.max(2, Math.ceil(track.children.length / 4)));
            for (let i = 0; i < dotCount; i++) {
                const d = document.createElement('span');
                d.className = 'cat-progress-dot' + (i === 0 ? ' active' : '');
                progress.appendChild(d);
            }
            const dots = progress.children;

            let paused = false;
            track.addEventListener('mouseenter', () => paused = true);
            track.addEventListener('mouseleave', () => paused = false);
            track.addEventListener('touchstart', () => paused = true, { passive: true });
            track.addEventListener('touchend',   () => setTimeout(() => paused = false, 2500), { passive: true });

            setInterval(() => {
                if (paused) return;
                track.scrollLeft += 1.2;
                // Boucle : revenir au début (copie identique, invisible à l'œil)
                if (track.scrollLeft >= originalWidth) track.scrollLeft -= originalWidth;

                const ratio = track.scrollLeft / originalWidth;
                const active = Math.floor(ratio * dots.length) % dots.length;
                for (let i = 0; i < dots.length; i++) {
                    dots[i].classList.toggle('active', i === active);
                }
            }, 16);
        })();
    </script>
</body>
</html>