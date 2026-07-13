<?php /** Header sur une seule ligne, équilibré : logo | menu | recherche + compte. */ ?>
@php
    $navLinkClass = fn($active) => $active
        ? 'text-amber-600 font-semibold whitespace-nowrap text-[15px] tracking-wide'
        : 'text-gray-600 font-medium hover:text-amber-600 transition whitespace-nowrap text-[15px] tracking-wide';
@endphp
<nav class="fixed top-0 w-full z-50 bg-white/95 shadow-sm" style="backdrop-filter: blur(10px);">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20 gap-6">
            <!-- Logo -->
            <a href="{{ route('accueil') }}" class="flex items-center gap-2.5 shrink-0">
                <img src="{{ asset('images/customers/logo.jpg') }}" alt="SMH" class="h-11 w-auto object-contain">
                <span class="text-xl font-bold text-gray-800 hidden sm:inline tracking-tight">SMH</span>
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
                    <div class="flex items-center border border-gray-200 rounded-full overflow-hidden bg-gray-50 focus-within:bg-white focus-within:border-amber-500 focus-within:ring-2 focus-within:ring-amber-500/10 transition">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Rechercher..."
                            class="flex-1 min-w-0 pl-4 pr-2 py-2 text-sm text-gray-800 placeholder-gray-400 outline-none bg-transparent"
                        >
                        <button type="submit"
                            class="shrink-0 flex items-center justify-center w-8 h-8 mr-1 rounded-full text-gray-500 hover:bg-amber-100 hover:text-amber-700 transition"
                            aria-label="Rechercher">
                            <i class="fas fa-search text-xs"></i>
                        </button>
                    </div>
                </form>

                <!-- Recherche compacte (icône seule) pour très petits écrans -->
                <a href="{{ route('products.index') }}" class="sm:hidden w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition" title="Rechercher">
                    <i class="fas fa-search"></i>
                </a>

                <span class="hidden sm:block w-px h-6 bg-gray-200"></span>

                <!-- Compte / panier -->
                <div class="flex items-center gap-1">
                    @include('partials.notification-bell')
                    @auth
                        @if(Auth::user()->isCustomer())
                            <a href="{{ route('orders.index') }}" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition" title="Mes commandes">
                                <i class="fas fa-receipt"></i>
                            </a>
                            <a href="{{ route('addresses.index') }}" class="hidden sm:flex w-9 h-9 items-center justify-center rounded-full text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition" title="Mes adresses">
                                <i class="fas fa-map-marker-alt"></i>
                            </a>
                            <form method="POST" action="{{ route('shop.logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition" title="Déconnexion">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('home') }}" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition" title="Espace gestion">
                                <i class="fas fa-tachometer-alt"></i>
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition" title="Déconnexion">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('otp.login') }}" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition" title="Espace client">
                            <i class="fas fa-sign-in-alt"></i>
                        </a>
                        <a href="{{ route('login') }}" class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:bg-amber-50 hover:text-amber-600 transition" title="Espace gestion">
                            <i class="fas fa-user-shield"></i>
                        </a>
                    @endauth
                    <a href="{{ route('panier') }}" class="relative w-9 h-9 flex items-center justify-center rounded-full text-gray-600 hover:bg-amber-50 hover:text-amber-600 transition" title="Panier">
                        <i class="fas fa-shopping-bag"></i>
                        <span id="cart-count" class="absolute top-0.5 right-0.5 bg-amber-600 text-white text-[10px] font-semibold rounded-full w-4 h-4 flex items-center justify-center">
                            {{ count(session('cart', [])) }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
