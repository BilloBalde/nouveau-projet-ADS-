<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>COLEAH BANDEYA & FILS - Quincaillerie Générale à Conakry</title>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Système de design partagé : palette vert / rouge / blanc, typo, composants.
         La config Tailwind vit là-bas et non ici, pour que l'accueil et les autres
         pages publiques ne puissent plus diverger visuellement. --}}
    @include('partials.theme-head')

    {{-- Font Awesome pour les icônes --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* ══════════════════════════════════════
           HERO — grande bande d'images défilantes, plein cadre
        ══════════════════════════════════════ */
        .hero-showcase {
            position: relative;
            /* Se cale juste sous la barre fixe : 80px de hauteur + 3px de filet bicolore */
            margin-top: 83px;
            height: clamp(420px, 78vh, 760px);
            overflow: hidden;
            background: var(--brand-950);
        }
        .hero-track { position: absolute; inset: 0; }

        .hero-slide {
            position: absolute; inset: 0; z-index: 1;
            display: block; text-decoration: none;
            opacity: 0; pointer-events: none;
            transition: opacity .9s ease;
        }
        .hero-slide.active { opacity: 1; pointer-events: auto; z-index: 2; }
        .hero-slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
        /* Zoom lent sur la diapo visible : donne du mouvement à des photos fixes */
        .hero-slide.active img { animation: heroZoom 7s ease-out forwards; }
        @keyframes heroZoom {
            from { transform: scale(1); }
            to   { transform: scale(1.07); }
        }

        /* ── Diapo décorative : tient la section tant que les photos manquent ── */
        .hero-slide--deco { display: flex; align-items: center; justify-content: center; }
        .deco-v0 { background: linear-gradient(135deg, #061710 0%, #0c2c1c 42%, #195836 100%); }
        .deco-v1 { background: linear-gradient(135deg, #0c2c1c 0%, #14452c 45%, #1f6f43 100%); }
        .deco-v2 { background: linear-gradient(135deg, #061710 0%, #14452c 55%, #2e8b57 100%); }
        .hero-slide--deco::before {
            content: ''; position: absolute; inset: 0;
            background-image: repeating-linear-gradient(45deg, rgba(255,255,255,.035) 0 2px, transparent 2px 15px);
        }
        .hero-slide--deco::after {
            content: ''; position: absolute; top: -130px; right: -110px;
            width: 540px; height: 540px; border-radius: 50%;
            background: radial-gradient(circle, rgba(217,45,32,.28) 0%, transparent 68%);
        }
        .hero-deco-body {
            position: relative; z-index: 2;
            display: flex; flex-direction: column; align-items: center; gap: 18px;
            color: #fff; text-align: center; padding: 0 24px;
        }
        .hero-deco-body i { font-size: clamp(38px, 5vw, 58px); color: var(--brand-300); }
        .hero-deco-body span {
            font-family: 'Fraunces', Georgia, serif;
            font-size: clamp(1.6rem, 4.2vw, 2.7rem); font-weight: 600; letter-spacing: -.01em;
        }

        .hero-scrim {
            position: absolute; inset: 0; z-index: 3; pointer-events: none;
            background: linear-gradient(to top, rgba(6,23,16,.55) 0%, rgba(6,23,16,.12) 26%, transparent 55%);
        }

        /* ── Contrôles ── */
        .hero-arrow {
            position: absolute; top: 50%; transform: translateY(-50%); z-index: 4;
            width: 46px; height: 46px; border-radius: 50%;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.3);
            backdrop-filter: blur(8px);
            color: #fff; font-size: 15px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background .25s ease;
        }
        .hero-arrow:hover { background: rgba(255,255,255,.32); }
        .hero-arrow.prev { left: 22px; }
        .hero-arrow.next { right: 22px; }

        .hero-dots {
            position: absolute; z-index: 4; bottom: 22px; left: 0; right: 0;
            display: flex; justify-content: center; gap: 9px;
        }
        .hero-dot {
            width: 9px; height: 9px; border-radius: 50%; border: none; padding: 0;
            background: rgba(255,255,255,.42); cursor: pointer;
            transition: width .3s ease, background .3s ease, border-radius .3s ease;
        }
        .hero-dot.active { width: 30px; border-radius: 5px; background: #fff; }

        /* ══════════════════════════════════════
           INTRODUCTION — le texte sorti du hero
        ══════════════════════════════════════ */
        .hero-intro { background: #fff; padding: 58px 0 46px; }
        .hero-intro-title {
            font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 600;
            color: var(--ink); line-height: 1.12; margin-bottom: 18px;
        }
        .hero-intro-title .gradient-text { display: block; }
        .hero-intro-lead {
            font-size: 1.05rem; line-height: 1.65; color: var(--ink-muted);
            max-width: 620px; margin: 0 auto 30px;
        }
        .hero-stats { display: flex; justify-content: center; flex-wrap: wrap; gap: 14px; }
        /* Variante claire : les cartes vitrées précédentes visaient un fond sombre */
        .hero-stat {
            background: var(--brand-50); border: 1px solid var(--brand-100);
            border-radius: var(--radius); padding: 14px 26px; text-align: center;
            min-width: 128px;
        }
        .hero-stat-num {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 1.6rem; font-weight: 600; color: var(--brand-700); line-height: 1;
        }
        .hero-stat-lbl {
            font-size: .7rem; margin-top: 6px; letter-spacing: .08em;
            text-transform: uppercase; color: var(--ink-muted);
        }

        @media (max-width: 768px) {
            .hero-showcase { height: clamp(320px, 56vh, 480px); }
            .hero-arrow { display: none; }
            .hero-intro { padding: 40px 0 34px; }
            .hero-stat { padding: 12px 18px; min-width: 104px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-slide { transition: none; }
            .hero-slide.active img { animation: none; }
        }

        .feature-icon {
            width: 58px; height: 58px; border-radius: 18px;
            background: linear-gradient(135deg, var(--brand-600), var(--brand-900));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 23px; margin: 0 auto 20px;
        }

        /* ══════════════════════════════════════
           GRILLE & CARTES PRODUITS
        ══════════════════════════════════════ */
        .prod-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 22px; }
        @media (max-width: 1024px) { .prod-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 768px)  { .prod-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 480px)  { .prod-grid { grid-template-columns: repeat(2, 1fr); } }

        .prod-card {
            background: #fff; border: 1px solid var(--hairline);
            border-radius: 18px; overflow: hidden; cursor: pointer;
            box-shadow: 0 1px 2px rgba(12,44,28,.04);
            transition: border-color .22s ease, box-shadow .22s ease, transform .22s ease;
        }
        .prod-card:hover {
            border-color: var(--brand-300);
            transform: translateY(-4px);
            box-shadow: 0 2px 4px rgba(12,44,28,.05), 0 20px 42px -20px rgba(12,44,28,.34);
        }
        .prod-img {
            height: 182px; display: flex; align-items: center; justify-content: center;
            position: relative; background: var(--paper-warm); overflow: hidden;
        }
        .prod-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
        .prod-card:hover .prod-img img { transform: scale(1.05); }
        .prod-img .no-img { font-size: 46px; }
        .prod-badge {
            position: absolute; top: 10px; left: 10px;
            font-size: 10px; font-weight: 700; letter-spacing: .04em;
            padding: 5px 11px; border-radius: 999px; backdrop-filter: blur(4px);
        }
        .badge-promo { background: rgba(217,45,32,.94); color: #fff; }
        .badge-best  { background: rgba(31,111,67,.94); color: #fff; }

        .prod-info { padding: 15px 15px 17px; }
        .prod-cat-tag {
            font-size: 10.5px; font-weight: 600; letter-spacing: .04em;
            color: var(--brand-700); background: var(--brand-50);
            display: inline-block; padding: 3px 9px; border-radius: 999px; margin-bottom: 9px;
        }
        .prod-name {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 14.5px; font-weight: 600; color: var(--ink);
            margin-bottom: 5px; line-height: 1.35;
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .prod-stars { font-size: 12px; color: #e8a020; margin-bottom: 9px; }

        .tier-table { margin-bottom: 11px; }
        .tier-label {
            font-size: 9.5px; font-weight: 700; color: var(--brand-600);
            text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px;
        }
        .tier-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5px; }
        .tier-cell {
            background: var(--brand-50); border: 1px solid var(--brand-100);
            border-radius: 9px; padding: 5px 2px; text-align: center;
        }
        .tier-qty { font-size: 10px; font-weight: 700; color: var(--brand-700); }
        .tier-price { font-size: 11px; font-weight: 600; color: var(--ink); }
        .tier-badge {
            display: inline-block; margin-top: 2px;
            font-size: 9px; font-weight: 700; color: #fff;
            background: var(--accent-600); border-radius: 999px; padding: 1px 6px;
        }

        /* Sélecteur de quantité : styles centralisés dans partials/theme-head */

        .prod-price-row { display: flex; align-items: baseline; gap: 7px; margin-bottom: 13px; flex-wrap: wrap; }
        .prod-price {
            font-family: 'Fraunces', Georgia, serif;
            font-size: 17.5px; font-weight: 700; color: var(--ink);
        }
        .prod-price-old { font-size: 12px; color: var(--ink-faint); text-decoration: line-through; }

        .prod-btns { display: flex; gap: 7px; }
        .prod-btn, .prod-btn-order {
            flex: 1; padding: 10px 4px; font-size: 11.5px; font-weight: 600;
            text-align: center; border-radius: 999px; cursor: pointer;
            font-family: 'Inter', sans-serif; transition: all .18s ease;
        }
        .prod-btn {
            background: linear-gradient(135deg, var(--brand-600), var(--brand-700));
            color: #fff; border: none;
        }
        .prod-btn:hover { box-shadow: 0 10px 20px -10px rgba(31,111,67,.9); transform: translateY(-1px); }
        .prod-btn-order {
            background: #fff; color: var(--accent-600);
            border: 1.5px solid var(--accent-200);
        }
        .prod-btn-order:hover { background: var(--accent-600); border-color: var(--accent-600); color: #fff; transform: translateY(-1px); }

        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--ink-faint); font-size: 14px; }
        .empty-state i { font-size: 32px; margin-bottom: 12px; display: block; color: var(--brand-200); }
    </style>
</head>
<body class="bg-white">

    @include('partials.storefront-nav')

    {{-- ══════════════════════════════════════════
        HERO — grande bande d'images défilantes, plein cadre
    ══════════════════════════════════════════ --}}
    @php
        // Le carrousel se nourrit des photos du catalogue. Tant qu'elles sont trop
        // peu nombreuses, on complète avec des diapos décoratives bâties sur les
        // rayons : une grande section vide serait pire qu'un visuel de marque assumé.
        $photoSlides = $allProducts->filter(fn($p) => !empty($p['image']))->take(6)->values();

        $iconePour = function ($nom) {
            $rayons = [
                'peinture' => 'fa-paint-roller',
                'outil'    => 'fa-screwdriver-wrench',
                'serrur'   => 'fa-lock',
                'plomb'    => 'fa-faucet',
                'lectric'  => 'fa-bolt',
            ];
            $minuscule = mb_strtolower($nom);
            foreach ($rayons as $motCle => $icone) {
                if (str_contains($minuscule, $motCle)) {
                    return $icone;
                }
            }
            return 'fa-toolbox';
        };

        $slides = $photoSlides;
        if ($slides->count() < 4) {
            $slides = $slides->concat(
                $categories->take(6)->map(fn($c) => [
                    'deco'  => true,
                    'name'  => $c->name,
                    'icone' => $iconePour($c->name),
                ])
            )->take(6)->values();
        }
        if ($slides->isEmpty()) {
            $slides = collect([['deco' => true, 'name' => 'Quincaillerie générale', 'icone' => 'fa-toolbox']]);
        }
    @endphp

    <section id="accueil" class="hero-showcase" aria-label="Nos produits en images">
        <div class="hero-track" id="heroCarousel">
            @foreach($slides as $i => $slide)
                @if(!empty($slide['deco']))
                    <div class="hero-slide hero-slide--deco deco-v{{ $i % 3 }} {{ $i === 0 ? 'active' : '' }}" data-index="{{ $i }}">
                        <div class="hero-deco-body">
                            <i class="fas {{ $slide['icone'] }}"></i>
                            <span>{{ $slide['name'] }}</span>
                        </div>
                    </div>
                @else
                    <a href="{{ route('productDetail', $slide['id']) }}"
                       class="hero-slide {{ $i === 0 ? 'active' : '' }}"
                       data-index="{{ $i }}"
                       aria-label="{{ $slide['name'] }}">
                        <img src="{{ $slide['image'] }}" alt="{{ $slide['name'] }}"
                             loading="{{ $i === 0 ? 'eager' : 'lazy' }}"
                             onerror="this.closest('.hero-slide').classList.add('hero-slide--deco','deco-v{{ $i % 3 }}');this.remove()">
                    </a>
                @endif
            @endforeach

            {{-- Voile bas : garde flèches et pastilles lisibles sur une photo claire --}}
            <div class="hero-scrim" aria-hidden="true"></div>

            <button class="hero-arrow prev" id="heroPrev" aria-label="Image précédente">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="hero-arrow next" id="heroNext" aria-label="Image suivante">
                <i class="fas fa-chevron-right"></i>
            </button>

            <div class="hero-dots" id="heroDots">
                @foreach($slides as $i => $slide)
                    <button class="hero-dot {{ $i === 0 ? 'active' : '' }}" data-dot="{{ $i }}" aria-label="Image {{ $i + 1 }}"></button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         INTRODUCTION — le texte que le hero ne porte plus
    ══════════════════════════════════════════ --}}
    <section class="hero-intro">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center">
                <span class="eyebrow mb-4">Quincaillerie générale · Conakry, Guinée</span>
                <h1 class="hero-intro-title">
                    Équipez votre chantier
                    <span class="gradient-text">avec qualité et confiance</span>
                </h1>
                <p class="hero-intro-lead">
                    Peinture, outillage, serrurerie, plomberie et électricité — commandez en ligne,
                    payez à la livraison ou par Orange Money, et recevez vos articles partout en Guinée.
                </p>
                <div class="flex flex-wrap justify-center gap-3 mb-10">
                    <a href="{{ route('products.index') }}" class="btn btn-accent">
                        <i class="fas fa-shopping-bag text-sm"></i> Voir le catalogue
                    </a>
                    <a href="#produits" class="btn btn-outline">
                        <i class="fas fa-arrow-down text-sm"></i> Découvrir
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-num">{{ $totalProducts }}+</div>
                        <div class="hero-stat-lbl">Produits</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-num">{{ $totalCategories }}</div>
                        <div class="hero-stat-lbl">Catégories</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-num">24-48h</div>
                        <div class="hero-stat-lbl">Livraison Conakry</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         BANDE DE CONFIANCE — l'essentiel en un coup d'œil
    ══════════════════════════════════════════ --}}
    <section class="bg-white border-b border-gray-100">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-5 py-7">
                @foreach([
                    ['icon' => 'fa-truck-fast',        'title' => 'Livraison 24-48h',        'text' => 'À Conakry, partout en Guinée'],
                    ['icon' => 'fa-store',             'title' => 'Retrait gratuit',          'text' => 'Madina, Conakry'],
                    ['icon' => 'fa-money-bill-wave',   'title' => 'Paiement flexible',        'text' => 'Orange Money ou à la livraison'],
                    ['icon' => 'fa-headset',           'title' => 'Conseil personnalisé',     'text' => '625 45 35 45'],
                ] as $item)
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" style="background:var(--brand-50);color:var(--brand-600);">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-800">{{ $item['title'] }}</div>
                        <div class="text-xs text-gray-500">{{ $item['text'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
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
                overflow: hidden; border: 3px solid var(--brand-100); background: var(--brand-50);
                display: flex; align-items: center; justify-content: center;
                transition: border-color .2s, box-shadow .2s;
            }
            .cat-slide:hover .cat-slide-img { border-color: var(--brand-500); box-shadow: 0 10px 24px rgba(31,111,67,.20); }
            .cat-slide-img img { width: 100%; height: 100%; object-fit: cover; }
            .cat-slide-img .cat-slide-icon { font-size: 36px; color: var(--brand-600); }
            .cat-slide-name {
                font-size: 13px; font-weight: 700; color: #1f2937;
                white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            }
            .cat-slide-count { font-size: 11px; color: var(--ink-faint); }
            .cat-progress { display: flex; justify-content: center; gap: 6px; margin-top: 10px; }
            .cat-progress-dot {
                width: 22px; height: 4px; border-radius: 999px; background: var(--brand-100);
                overflow: hidden; position: relative;
            }
            .cat-progress-dot.active::after {
                content: ''; position: absolute; inset: 0; background: var(--accent-600); border-radius: 999px;
            }
        </style>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-6">
                <div>
                    <span class="eyebrow mb-3">Nos rayons</span>
                    <h2 class="text-3xl md:text-4xl font-semibold text-gray-900">Parcourez nos <span class="gradient-text">catégories</span></h2>
                    <p class="text-gray-500 mt-2">Cliquez sur une catégorie pour filtrer le catalogue</p>
                </div>
                <a href="{{ route('public.categories') }}" class="hidden sm:inline-flex items-center gap-2 text-brand-600 font-semibold hover:text-brand-700 whitespace-nowrap">
                    Tout voir <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>

            <div class="cat-carousel-track" id="cat-carousel-track">
                @foreach($categories as $cat)
                <a href="{{ route('products.index') }}?cat={{ urlencode($cat->name) }}" class="cat-slide" data-cat-slide>
                    <div class="cat-slide-img">
                        @if($cat->image_url)
                            <img src="{{ $cat->image_url }}" alt="{{ $cat->name }}" loading="lazy"
                                 onerror="this.outerHTML='<span class=&quot;cat-slide-icon&quot;><i class=&quot;fas fa-toolbox&quot;></i></span>'">
                        @else
                            <span class="cat-slide-icon"><i class="fas fa-toolbox"></i></span>
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
                <span class="eyebrow mb-4">Notre sélection</span>
                <h2 class="text-4xl md:text-5xl font-semibold text-gray-900 mb-4">
                    Nos <span class="gradient-text">Produits</span>
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Nos dernières arrivées — qualité soignée, prix dégressifs dès 2 pièces.
                    Besoin de filtrer par catégorie ou par prix ? Tout se passe dans le catalogue.
                </p>
                <div class="rule-duo is-centered"></div>
            </div>
            @if(isset($allProducts) && count($allProducts) > 0)
            <div class="prod-grid" id="grid-all">
                        @foreach($allProducts->take(10) as $p)
                        <div class="prod-card" style="cursor:pointer;" onclick="window.location='{{ route('productDetail', $p['id']) }}'">
                            <div class="prod-img">
                                @if($p['image'])
                                    <img src="{{ $p['image'] }}" alt="{{ $p['name'] }}"
                                         onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='block'">
                                    <span class="no-img" style="display:none">🧰</span>
                                @else
                                    <span class="no-img">🧰</span>
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
                                    <span class="qty-label">Quantité</span>
                                    <button type="button" class="qty-btn" onclick="changeQty(this,-1)" aria-label="Diminuer la quantité">−</button>
                                    <input type="number" class="qty-input" min="1" value="1" data-base-price="{{ $p['price'] }}" onchange="updateCardPrice(this)">
                                    <button type="button" class="qty-btn" onclick="changeQty(this,1)" aria-label="Augmenter la quantité">+</button>
                                </div>
                                <div class="prod-price-row">
                                    <span class="prod-price" data-computed-price>{{ number_format($p['price'], 0, ',', ' ') }} GNF</span>
                                    @if($p['old_price'])
                                        <span class="prod-price-old">{{ number_format($p['old_price'], 0, ',', ' ') }} GNF</span>
                                    @endif
                                </div>
                                <div class="prod-btns">
                                    <button type="button" class="prod-btn" onclick="event.stopPropagation();addToCartWithQty({{ $p['id'] }}, this)">
                                        <i class="fas fa-shopping-bag mr-1"></i> Panier
                                    </button>
                                    <button type="button" class="prod-btn-order" onclick="event.stopPropagation();orderNowWithQty({{ $p['id'] }}, this)">
                                        <i class="fas fa-bolt mr-1"></i> Commander
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                {{-- Liens vers le catalogue complet --}}
                <div class="text-center mt-10 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('products.index') }}"
                       class="btn btn-brand">
                        <i class="fas fa-box-open"></i>
                        Voir tout le catalogue ({{ $totalProducts }} produits)
                    </a>
                    <a href="{{ route('public.categories') }}"
                       class="btn btn-outline">
                        <i class="fas fa-th-large"></i>
                        Parcourir par catégorie
                    </a>
                </div>
            @endif

          
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         SECTIONS : PLUS VENDUS / MIEUX NOTÉS / TENDANCES
    ══════════════════════════════════════════ --}}
    <style>
        .prod-row {
            display: flex; gap: 18px; overflow-x: auto; padding: 8px 2px 16px;
            -ms-overflow-style: none; scrollbar-width: thin; scrollbar-color: var(--brand-200) transparent;
        }
        .prod-row .prod-card { flex: 0 0 262px; }
        .section-row-head { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .section-row-head .row-icon {
            width: 44px; height: 44px; border-radius: 12px; background: var(--brand-50); color: var(--brand-600);
            display: inline-flex; align-items: center; justify-content: center; font-size: 19px;
        }
        .section-row-head h2 { font-size: 26px; font-weight: 800; color: #1f2937; }
        .section-row-head p { font-size: 13px; color: var(--ink-faint); }
    </style>
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 space-y-14">

            @if(isset($promoProducts) && count($promoProducts) > 0)
            <div>
                <div class="section-row-head">
                    <span class="row-icon" style="background:var(--accent-50);color:var(--accent-600);"><i class="fas fa-tag"></i></span>
                    <div>
                        <h2>Promotions en cours</h2>
                        <p>Profitez-en avant la fin de l'offre</p>
                    </div>
                </div>
                <div class="prod-row" id="row-promo"></div>
            </div>
            @endif

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
                <span class="eyebrow mb-4">Recevoir sa commande</span>
                <h2 class="text-3xl md:text-4xl font-semibold text-gray-900 mb-3">Modes de <span class="gradient-text">livraison</span></h2>
                <p class="text-gray-500 max-w-xl mx-auto">Choisissez la formule qui vous convient — nous livrons vos articles de quincaillerie partout en Guinée</p>
                <div class="rule-duo is-centered"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach([
                    ['icon' => 'fa-truck',        'title' => 'Livraison à domicile', 'text' => 'Recevez votre commande chez vous ou sur le chantier en 24 à 48h à Conakry.'],
                    ['icon' => 'fa-store',        'title' => 'Retrait en magasin',   'text' => 'Commandez en ligne et récupérez gratuitement à notre quincaillerie de Madina.'],
                    ['icon' => 'fa-bolt',         'title' => 'Livraison express',    'text' => 'Besoin urgent ? Livraison le jour même à Conakry pour les commandes passées avant midi.'],
                    ['icon' => 'fa-map',          'title' => 'Livraison en région',  'text' => 'Nous expédions dans toutes les régions de la Guinée via nos transporteurs partenaires.'],
                ] as $mode)
                <div class="bg-white rounded-2xl p-7 text-center border border-gray-100 shadow-sm hover:shadow-lg hover:border-brand-200 transition">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:var(--brand-50);color:var(--brand-600);font-size:22px;">
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
         À PROPOS
    ══════════════════════════════════════════ --}}
    <section id="apropos" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="eyebrow mb-4">
                        Qui sommes-nous ?
                    </span>
                    <h2 class="text-4xl md:text-5xl font-semibold text-gray-900 mb-6 leading-tight">
                        <span class="gradient-text">Coleah Bandeya & Fils</span>
                    </h2>
                    <p class="text-gray-600 text-lg leading-relaxed mb-6">
                        Nous sommes <strong>Coleah Bandeya & Fils</strong>, votre <strong>quincaillerie générale</strong> à Conakry. Peinture, outillage, serrurerie, plomberie, électricité et divers — nous accompagnons particuliers et professionnels du bâtiment avec qualité, sérieux et fiabilité.
                    </p>
                    <p class="text-gray-600 leading-relaxed mb-8">
                        Notre mission : vous offrir des articles de quincaillerie de qualité à des prix accessibles, avec un service de proximité et un conseil personnalisé pour chaque chantier. Chez Coleah Bandeya & Fils, au-delà de la qualité des autres.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-3 shadow-sm border border-gray-100">
                            <div class="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-brand-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Localisation</p>
                                <p class="text-sm font-semibold text-gray-700">Madina, Ecole Gare Routière Dabola Camion, Conakry</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-white rounded-xl px-5 py-3 shadow-sm border border-gray-100">
                            <div class="w-10 h-10 bg-brand-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-award text-brand-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium">Qualité</p>
                                <p class="text-sm font-semibold text-gray-700">Matériaux durables, garantis</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center">
                    <div class="relative w-full max-w-sm">
                        <div class="bg-gradient-to-br from-brand-700 to-brand-950 rounded-3xl p-8 text-white text-center shadow-2xl">
                            <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-toolbox text-white text-4xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold mb-2">Coleah Bandeya & Fils</h3>
                            <p class="text-white/80 text-sm mb-6">Quincaillerie générale<br>Chez Coleah Bandeya & Fils, au-delà de la qualité des autres.</p>
                            <div class="space-y-3 text-left">
                                <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-2">
                                    <i class="fas fa-phone text-white/80 text-sm"></i>
                                    <span class="text-sm">625 45 35 45</span>
                                </div>
                                <div class="flex items-center gap-3 bg-white/10 rounded-xl px-4 py-2">
                                    <i class="fas fa-envelope text-white/80 text-sm"></i>
                                    <span class="text-sm">abdulghadirybalde@gmail.com</span>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -top-4 -right-4 w-24 h-24 bg-brand-200 rounded-full opacity-40 -z-10"></div>
                        <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-accent-500 rounded-full opacity-30 -z-10"></div>
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
                <span class="eyebrow mb-4">
                    Où nous trouver
                </span>
                <h2 class="text-4xl md:text-5xl font-semibold text-gray-900 mb-4">
                    Nos <span class="gradient-text">Boutiques</span>
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Retrouvez nos points de vente partout en Guinée — chaque boutique est gérée par une équipe dédiée.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-{{ min($stores->count(), 4) }} gap-8">
                @foreach($stores as $store)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-lg hover:border-brand-300 transition duration-300 flex flex-col">

                    {{-- Image boutique --}}
                    @if($store->image_url)
                        <img src="{{ $store->image_url }}" alt="{{ $store->name }}"
                             class="w-full h-44 object-cover"
                             onerror="this.onerror=null;this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="w-full h-44 bg-gradient-to-br from-brand-100 to-brand-200 items-center justify-center hidden">
                            <i class="fas fa-store text-brand-500 text-5xl"></i>
                        </div>
                    @else
                        <div class="w-full h-44 bg-gradient-to-br from-brand-100 to-brand-200 flex items-center justify-center">
                            <i class="fas fa-store text-brand-500 text-5xl"></i>
                        </div>
                    @endif

                    {{-- Infos --}}
                    <div class="p-6 flex flex-col flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $store->name }}</h3>

                        @if($store->address)
                        <div class="flex items-start gap-2 text-sm text-gray-500 mb-2">
                            <i class="fas fa-map-marker-alt text-brand-500 mt-0.5 flex-shrink-0"></i>
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
                                <div class="w-9 h-9 rounded-full bg-brand-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-brand-600 text-sm"></i>
                                </div>
                                <div>
                                    @if($store->manager)
                                    <p class="text-sm font-semibold text-gray-800">{{ $store->manager }}</p>
                                    @endif
                                    @if($store->phone)
                                    <a href="tel:{{ $store->phone }}"
                                       class="text-xs text-brand-600 hover:text-brand-700 font-medium transition">
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
                <span class="eyebrow mb-4">
                    Contactez-nous
                </span>
                <h2 class="text-4xl md:text-5xl font-semibold text-gray-900 mb-4">
                    Nous sommes à <span class="gradient-text">votre écoute</span>
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Besoin d'un conseil, d'un devis ou d'une livraison ? Contactez Coleah Bandeya & Fils directement.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <div class="text-center bg-gray-50 rounded-2xl p-8 border border-gray-100 hover:border-brand-300 hover:shadow-lg transition duration-300">
                    <div class="feature-icon mx-auto mb-5"><i class="fas fa-map-marker-alt"></i></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Adresse</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Madina, Ecole Gare Routière Dabola Camion, Conakry
                    </p>
                </div>
                <div class="text-center bg-gray-50 rounded-2xl p-8 border border-gray-100 hover:border-brand-300 hover:shadow-lg transition duration-300">
                    <div class="feature-icon mx-auto mb-5"><i class="fas fa-phone-alt"></i></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Appelez-nous</h3>
                    <p class="text-gray-600 leading-relaxed">
                        <a href="tel:+224625453545" class="hover:text-brand-600 transition font-medium">625 45 35 45</a><br>
                        <a href="tel:+224628593110" class="hover:text-brand-600 transition font-medium">628 59 31 10</a><br>
                        <a href="tel:+224628801895" class="hover:text-brand-600 transition font-medium">628 80 18 95</a>
                    </p>
                </div>
                <div class="text-center bg-gray-50 rounded-2xl p-8 border border-gray-100 hover:border-brand-300 hover:shadow-lg transition duration-300">
                    <div class="feature-icon mx-auto mb-5"><i class="fas fa-envelope"></i></div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Email</h3>
                    <p class="text-gray-600 leading-relaxed">
                        <a href="mailto:abdulghadirybalde@gmail.com" class="hover:text-brand-600 transition font-medium break-all">
                            abdulghadirybalde@gmail.com
                        </a>
                    </p>
                </div>
            </div>
            <div class="text-center mt-12">
                <a href="{{ route('contact') }}" class="px-10 py-4 bg-brand-600 text-white rounded-full font-semibold text-lg hover:bg-brand-700 transition duration-300 inline-block shadow-lg">
                    <i class="fas fa-paper-plane mr-2"></i> Envoyer un message
                </a>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         NEWSLETTER
    ══════════════════════════════════════════ --}}
    <section class="py-16 bg-gradient-to-br from-brand-700 via-brand-800 to-brand-950">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto text-center text-white">
                <h2 class="text-4xl font-semibold mb-4">Offres et nouveautés quincaillerie</h2>
                <p class="text-lg text-white/90 mb-8">Inscrivez-vous à notre newsletter et soyez le premier informé de nos promotions sur la peinture, l'outillage et le matériel électrique</p>
                {{-- flex-col en dessous de sm + min-w-0 sur le champ : sans ça le champ
                     et le bouton refusaient de se réduire, débordaient l'écran mobile et
                     élargissaient tout le document (barre de défilement horizontale). --}}
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-2 max-w-md mx-auto">
                    @csrf
                    <input type="email" name="email" placeholder="Votre email" required
                        class="flex-1 min-w-0 px-6 py-3 rounded-full text-gray-800 focus:outline-none focus:ring-2 focus:ring-white">
                    <button type="submit" class="shrink-0 px-8 py-3 bg-white text-brand-700 rounded-full font-semibold hover:shadow-lg transition">
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
             discount (nullable, ex: 25), rating, image (nullable) --}}
        const PROMO         = @json($promoProducts ?? []);
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
                : `<span class="no-img">🧰</span>`;
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
                    <div class="prod-stars">${stars} <span style="color:#93a09a;font-size:11px;">${parseFloat(p.rating).toFixed(1)}</span></div>
                    <div class="tier-table">
                        <div class="tier-label">Tarifs de gros — prix unitaire</div>
                        <div class="tier-grid">${tierHTML}</div>
                    </div>
                    <div class="prod-qty" onclick="event.stopPropagation()">
                        <span class="qty-label">Quantité</span>
                        <button type="button" class="qty-btn" onclick="changeQty(this,-1)" aria-label="Diminuer la quantité">−</button>
                        <input type="number" class="qty-input" min="1" value="1" data-base-price="${p.price}" onchange="updateCardPrice(this)">
                        <button type="button" class="qty-btn" onclick="changeQty(this,1)" aria-label="Augmenter la quantité">+</button>
                    </div>
                    <div class="prod-price-row">
                        <span class="prod-price" data-computed-price>${Number(p.price).toLocaleString('fr-FR')} GNF</span>
                        ${oldHTML}
                    </div>
                    <div class="prod-btns">
                        <button type="button" class="prod-btn" onclick="event.stopPropagation();addToCartWithQty(${p.id}, this)">
                            <i class="fas fa-shopping-bag mr-1"></i> Panier
                        </button>
                        <button type="button" class="prod-btn-order" onclick="event.stopPropagation();orderNowWithQty(${p.id}, this)">
                            <i class="fas fa-bolt mr-1"></i> Commander
                        </button>
                    </div>
                </div>
            </div>`;
        }

        /* ── Panier (avec débogage) ── */
        /* ── Toast notification ── */
        function showToast(msg, type = 'success') {
            const existing = document.getElementById('cart-toast');
            if (existing) existing.remove();

            const icons = { success: '🛍️', error: '❌' };
            const colors = {
                success: 'linear-gradient(135deg,#1f6f43,#195836)',
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

            const piste = document.getElementById('heroCarousel');

            // Une seule diapo : les contrôles n'ont plus de sens et le défilement
            // automatique ferait clignoter la même image.
            if (slides.length < 2) {
                piste?.querySelectorAll('.hero-arrow, .hero-dots').forEach(el => el.remove());
                return;
            }

            const animationReduite = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

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

                setTimeout(() => slides[old].classList.remove('exit'), 950);
            }

            function startAuto() {
                clearInterval(timer);
                if (animationReduite) return;
                // 5 s : sur une image plein cadre, 3,5 s ne laissait pas le temps de regarder.
                timer = setInterval(() => goTo(current + 1), 5000);
            }
            function stopAuto() { clearInterval(timer); }

            document.getElementById('heroNext')?.addEventListener('click', () => { goTo(current + 1); startAuto(); });
            document.getElementById('heroPrev')?.addEventListener('click', () => { goTo(current - 1); startAuto(); });
            dots.forEach(d => d.addEventListener('click', () => { goTo(+d.dataset.dot); startAuto(); }));

            // On suspend pendant que le visiteur regarde ou touche l'image.
            piste?.addEventListener('mouseenter', stopAuto);
            piste?.addEventListener('mouseleave', startAuto);
            piste?.addEventListener('touchstart', stopAuto, { passive: true });
            piste?.addEventListener('touchend', () => setTimeout(startAuto, 2500), { passive: true });

            startAuto();
        })();

        /* ── Rangées : plus vendus / mieux notés / tendances ── */
        (function () {
            const rows = [
                ['row-promo',        PROMO],
                ['row-best-sellers', BEST_SELLERS],
                ['row-top-rated',    TOP_RATED],
                ['row-trending',     TRENDING],
            ];
            rows.forEach(([id, data]) => {
                const el = document.getElementById(id);
                if (!el) return;
                el.innerHTML = data.length
                    ? data.map(renderCard).join('')
                    : '<p style="color:#93a09a;padding:12px;">Aucun produit à afficher pour le moment.</p>';
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