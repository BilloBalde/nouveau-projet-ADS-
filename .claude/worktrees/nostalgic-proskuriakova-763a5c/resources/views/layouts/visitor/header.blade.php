<header id="header" class="header d-flex align-items-center fixed-top">
    <div class="container-fluid container-xl position-relative d-flex align-items-center justify-content-between">

        <a href="{{ route('accueil') }}" class="logo d-flex align-items-center">
            <!-- Uncomment the line below if you also wish to use an image logo -->
            <img src="{{ asset('images/customers/logo.jpg') }}" alt="">
            <h1 class="sitename">SMH</h1>
        </a>

        <nav id="navmenu" class="navmenu">
            <ul>
                <li><a href="{{ route('accueil') }}" class="{{ Request::route()->getName()=='accueil' ? 'active' : '' }}">Accueil</a></li>
                <li><a href="{{ Request::route()->getName()=='accueil' ? '#produits' : route('accueil').'#produits' }}">Nos Produits</a></li>
                <li><a href="{{ route('about') }}" class="{{ Request::route()->getName()=='about' ? 'active' : '' }}">A Propos</a></li>
                <li><a href="{{ route('contact') }}" class="{{ Request::route()->getName()=='contact' ? 'active' : '' }}">Contact</a></li>
                <li>
                    @if (Route::has('login'))
                <div class="hidden fixed top-0 right-0 px-6 py-4 sm:block">
                    @auth
                    <a href="{{ route('home') }}" class="{{ Request::route()->getName()=='home' ? 'active' : '' }}">Accueil</a>
                    @else
                    <a href="{{ route('login') }}" class="{{ Request::route()->getName()=='login' ? 'active' : '' }}">Se Conecter</a>
                    @endauth
                </div>
                    @endif
                </li>
            </ul>
            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
    </div>
</header>
