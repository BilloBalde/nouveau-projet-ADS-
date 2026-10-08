<?php /** Header sur une seule ligne, équilibré : logo | menu | recherche + compte. */ ?>
@php
    // Bandeau clair : liens vert TELICO, page courante soulignée d'or.
    $navLinkClass = fn($active) => $active
        ? 'relative text-brand-800 font-semibold whitespace-nowrap text-[15px] tracking-wide after:absolute after:left-0 after:right-0 after:-bottom-2 after:h-0.5 after:rounded-full after:bg-accent-500'
        : 'text-brand-700/80 font-medium hover:text-brand-900 transition whitespace-nowrap text-[15px] tracking-wide';
@endphp
{{-- Fond, flou et ombre vivent dans theme-head (.nav-dark) et non en style inline :
     un style inline l'emporterait sur la variante .is-scrolled appliquée au défilement. --}}
<nav class="nav-dark fixed top-0 w-full z-50">
    {{-- Filet bicolore : rappelle les deux couleurs du logo en haut de chaque page --}}
    <div style="height:3px;background:linear-gradient(90deg,var(--brand-600) 0 45%,var(--accent-600) 45% 100%);"></div>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20 gap-6">
            <!-- Logo -->
            <a href="{{ route('accueil') }}" class="flex items-center gap-2.5 shrink-0">
                {{-- Bandeau blanc : le logo se pose directement, sans pastille. --}}
                <img src="{{ asset('companies/telico.png') }}" alt="TELICO" class="h-16 w-auto object-contain shrink-0">
                <span class="text-2xl font-semibold text-brand-700 hidden sm:inline" style="font-family:'Cinzel',Georgia,serif;letter-spacing:.18em;">TELICO</span>
            </a>

            <!-- Menu (centré dans l'espace restant) -->
            <div class="hidden lg:flex flex-1 items-center justify-center gap-5 xl:gap-9">
                <a href="{{ route('accueil') }}" class="{{ $navLinkClass(request()->routeIs('accueil')) }}">Accueil</a>
                <a href="{{ route('products.index') }}" class="{{ $navLinkClass(request()->routeIs('products.index')) }}">Catalogue</a>
                <a href="{{ route('public.categories') }}" class="{{ $navLinkClass(request()->routeIs('public.categories')) }}">Catégories</a>
                <a href="{{ route('about') }}" class="{{ $navLinkClass(request()->routeIs('about')) }}">À propos</a>
                <a href="{{ route('contact') }}" class="{{ $navLinkClass(request()->routeIs('contact')) }}">Contact</a>
            </div>

            <!-- Recherche + compte, groupés à droite -->
            <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                <!-- Recherche -->
                <form action="{{ route('products.index') }}" method="GET" class="hidden sm:block w-40 lg:w-36 xl:w-56 2xl:w-64">
                    <div class="flex items-center border border-brand-200 rounded-full overflow-hidden bg-brand-50/60 focus-within:bg-white focus-within:border-brand-500 transition">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Rechercher..."
                            class="flex-1 min-w-0 pl-4 pr-2 py-2 text-sm text-gray-800 placeholder-gray-400 outline-none bg-transparent"
                        >
                        <button type="submit"
                            class="shrink-0 flex items-center justify-center w-8 h-8 mr-1 rounded-full bg-brand-600 text-white hover:bg-brand-700 transition"
                            aria-label="Rechercher">
                            <i class="fas fa-search text-xs"></i>
                        </button>
                    </div>
                </form>

                <!-- Recherche compacte (icône seule) pour très petits écrans -->
                <a href="{{ route('products.index') }}" class="sm:hidden w-9 h-9 flex items-center justify-center rounded-full text-brand-700 hover:bg-brand-50 hover:text-brand-900 transition" title="Rechercher">
                    <i class="fas fa-search"></i>
                </a>

                <span class="hidden sm:block w-px h-6 bg-brand-100"></span>

                <!-- Compte / panier -->
                <div class="flex items-center gap-1">
                    @include('partials.notification-bell')
                    @auth
                        @if(Auth::user()->isCustomer())
                            <a href="{{ route('orders.index') }}" class="w-9 h-9 flex items-center justify-center rounded-full text-brand-700 hover:bg-brand-50 hover:text-brand-900 transition" title="Mes commandes">
                                <i class="fas fa-receipt"></i>
                            </a>
                            <a href="{{ route('addresses.index') }}" class="hidden sm:flex w-9 h-9 items-center justify-center rounded-full text-brand-700 hover:bg-brand-50 hover:text-brand-900 transition" title="Mes adresses">
                                <i class="fas fa-map-marker-alt"></i>
                            </a>
                            <form method="POST" action="{{ route('shop.logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="w-9 h-9 flex items-center justify-center rounded-full text-brand-700 hover:bg-brand-50 hover:text-brand-900 transition" title="Déconnexion">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('home') }}" class="w-9 h-9 flex items-center justify-center rounded-full text-brand-700 hover:bg-brand-50 hover:text-brand-900 transition" title="Espace gestion">
                                <i class="fas fa-tachometer-alt"></i>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="w-9 h-9 flex items-center justify-center rounded-full text-brand-700 hover:bg-brand-50 hover:text-brand-900 transition" title="Déconnexion">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('otp.login') }}" class="w-9 h-9 flex items-center justify-center rounded-full text-brand-700 hover:bg-brand-50 hover:text-brand-900 transition" title="Espace client">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                        <a href="{{ route('login') }}" class="w-9 h-9 flex items-center justify-center rounded-full text-brand-600 hover:bg-brand-50 hover:text-brand-900 transition" title="Espace gestion">
                            <i class="fas fa-user-shield"></i>
                        </a>
                    @endauth
                    <a href="{{ route('panier') }}" class="relative w-9 h-9 flex items-center justify-center rounded-full text-brand-700 hover:bg-brand-50 hover:text-brand-900 transition" title="Panier">
                        <i class="fas fa-shopping-bag"></i>
                        {{-- Liseré blanc : détache la pastille de l'icône sur le bandeau clair. --}}
                        <span id="cart-count" class="absolute top-0.5 right-0.5 bg-accent-600 text-white text-[10px] font-semibold rounded-full w-4 h-4 flex items-center justify-center ring-2 ring-white">
                            {{ count(session('cart', [])) }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    // Densifie le bandeau dès que la page défile (classe .is-scrolled, cf. theme-head).
    (function () {
        const nav = document.querySelector('nav.nav-dark');
        if (!nav) return;

        const seuil = 24;
        let enAttente = false;

        function majEtat() {
            nav.classList.toggle('is-scrolled', window.scrollY > seuil);
            enAttente = false;
        }

        // requestAnimationFrame : l'événement de défilement se déclenche des dizaines
        // de fois par seconde, on ne recalcule qu'une fois par rendu.
        window.addEventListener('scroll', function () {
            if (!enAttente) {
                enAttente = true;
                requestAnimationFrame(majEtat);
            }
        }, { passive: true });

        // Au chargement : le navigateur peut restaurer une position déjà défilée.
        majEtat();
    })();
</script>
