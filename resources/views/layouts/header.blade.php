@php
    // $company vient du View Composer (AppServiceProvider) : une seule requête
    // pour toute la page. Repli sur le logo de l'entreprise quand l'utilisateur
    // n'a pas de photo — sans ce garde-fou, asset('avatars/'.null) demandait
    // « /avatars » et renvoyait un 404 à chaque page.
    $logoUrl = asset('companies/'.($company?->logo ?? 'telico.png'));
    $avatarUrl = Auth::check() && Auth::user()->profilePic
        ? asset('avatars/'.Auth::user()->profilePic)
        : $logoUrl;
@endphp
<div class="header">

    <div class="header-left active">
        <a href="{{ route('home') }}" class="logo">
            <img src="{{ $logoUrl }}" alt="{{ $company?->name }}">
            <span class="logo-mot">TELICO</span>
        </a>
        <a href="{{ route('home') }}" class="logo-small">
            <img src="{{ $logoUrl }}" alt="{{ $company?->name }}">
        </a>
        <a id="toggle_btn" href="javascript:void(0);"></a>
    </div>

    <a id="mobile_btn" class="mobile_btn" href="#sidebar">
        <span class="bar-icon">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </a>
    <ul class="nav user-menu">
        {{-- Section courante. Le propriétaire passe d'une section à l'autre ou
             revient à la vue globale ; un employé voit sa section, sans choix.

             Avec une seule section, il n'y a rien à choisir : le sélecteur
             disparaît. L'employé rattaché garde le nom de son emplacement —
             un entrepôt ou un stock interne de la boutique — mais en simple
             texte, puisque l'écran « Sections » n'est plus accessible. --}}
        @php
            $plusieursSections = \App\Models\Store::plusieursSections();
        @endphp
        @if (Auth::check() && ! Auth::user()->isAssociate() && ($plusieursSections || Auth::user()->isRestrictedToStore()))
        @php
            $sectionCourante = Auth::user()->currentSection();
            $sectionsMenu = Auth::user()->isRestrictedToStore()
                ? collect()
                : \App\Models\Store::whereNull('parent_id')->orderByDesc('is_main')->orderBy('store_name')->get(['id', 'store_name', 'type']);
        @endphp
        <li class="nav-item dropdown" style="display:flex;align-items:center;margin-right:10px;">
            @if (Auth::user()->isRestrictedToStore())
                @if ($plusieursSections)
                <a class="nav-link" href="{{ route('sections.index') }}" style="font-weight:600;color:#3E4C21;white-space:nowrap;">
                    <i class="fas fa-store me-1"></i>{{ $sectionCourante?->store_name ?? 'Aucun magasin' }}
                </a>
                @else
                <span class="nav-link" style="font-weight:600;color:#3E4C21;white-space:nowrap;">
                    <i class="fas fa-store me-1"></i>{{ $sectionCourante?->store_name ?? 'Aucun magasin' }}
                </span>
                @endif
            @else
                <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" style="font-weight:600;color:#3E4C21;white-space:nowrap;">
                    <i class="fas {{ $sectionCourante ? 'fa-store' : 'fa-globe' }} me-1"></i>
                    {{ $sectionCourante ? 'Section : '.$sectionCourante->store_name : 'Vue globale' }}
                </a>
                <div class="dropdown-menu dropdown-menu-end" style="min-width:260px;">
                    <form method="POST" action="{{ route('sections.switch') }}" class="m-0">@csrf
                        <button class="dropdown-item {{ $sectionCourante ? '' : 'active' }}" name="section_id" value="">
                            <i class="fas fa-globe me-2"></i>Vue globale (toutes les sections)
                        </button>
                        <div class="dropdown-divider"></div>
                        @foreach ($sectionsMenu as $s)
                            <button class="dropdown-item {{ $sectionCourante?->id === $s->id ? 'active' : '' }}" name="section_id" value="{{ $s->id }}">
                                <i class="fas {{ $s->type === 'entrepot' ? 'fa-warehouse' : 'fa-store' }} me-2"></i>{{ $s->store_name }}
                            </button>
                        @endforeach
                    </form>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('sections.index') }}"><i class="fas fa-th-large me-2"></i>Voir toutes les sections</a>
                </div>
            @endif
        </li>
        @endif
        <li class="nav-item" style="display:flex;align-items:center;">
            @include('partials.notification-bell')
        </li>
        @unless ((Auth::check() && Auth::user()->isAssociate()) || \App\Support\ModeDeVente::parInventaire())
        <li class="nav-item has-arrow flag-nav">
            <a class="nav-link" href="{{ route('pos') }}" style="background-color: #3E4C21; color: #fff;">
                <img src="{{ asset('assets/img/icons/sales1.svg') }}" alt="" height="20">POS
            </a>
        </li>
        @endunless
        <li class="nav-item dropdown has-arrow main-drop">
            <a href="javascript:void(0);" class="dropdown-toggle nav-link userset" data-bs-toggle="dropdown">
                <span class="user-img"><img src="{{ $avatarUrl }}" alt="">
                <span class="status online"></span></span>
            </a>
            <div class="dropdown-menu menu-drop-user">
                <div class="profilename">
                    <div class="profileset">
                        @if (Auth::check())
                        <span class="user-img">
                            <img src="{{ $avatarUrl }}" alt="">
                            <span class="status online"></span>
                        </span>
                        <div class="profilesets">
                            @if(Auth::user()!==null)
                            <h6>{{ Auth::user()->username }}</h6>
                            @endif
                            <h5>{{ Auth::user()->role }}</h5>
                        </div>
                        @else
                        <script type="text/javascript">
                            window.location.href = "{{ url('/') }}";
                        </script>
                        @endif

                    </div>
                    <hr class="m-0">
                    <a class="dropdown-item" href="{{ route('accueil') }}"><i class="me-2" data-feather="home"></i>Voir Site</a>
                    <a class="dropdown-item" href="{{ route('profile') }}"> <i class="me-2" data-feather="user"></i>Mon Profile</a>
                    <hr class="m-0">
                    <form action="{{ route('logout') }}" method="POST" class="p-0 m-0">
                        @csrf
                        <button type="submit" class="dropdown-item logout pb-0 w-100 text-start border-0 bg-transparent"><img src="{{ asset('assets/img/icons/log-out.svg') }}" class="me-2" alt="img">Deconnection</button>
                    </form>
                </div>
            </div>
        </li>
    </ul>

    <div class="dropdown mobile-user-menu">
        <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
        <div class="dropdown-menu dropdown-menu-right">
            <a class="dropdown-item">
                @if (Auth::check())
                <span class="user-img">
                    <img src="{{ $avatarUrl }}" alt="">
                    <span class="status online"></span>
                </span>
                <div class="profilesets">
                    @if(Auth::user()!==null)
                    <h6>{{ Auth::user()->username }}</h6>
                    @endif
                </div>
                @else
                <script type="text/javascript">
                    window.location.href = "{{ url('/') }}";
                </script>
                @endif
            </a>
            @unless (\App\Support\ModeDeVente::parInventaire())
            <a class="dropdown-item" href="{{ route('pos') }}" style="background-color: #3E4C21; color: #fff;">
                <img src="{{ asset('assets/img/icons/sales1.svg') }}" class="me-2" alt="" height="20">POS
            </a>
            @endunless
            <a class="dropdown-item" href="{{ route('accueil') }}"><i class="me-2" data-feather="home"></i>Voir Site</a>
            <a class="dropdown-item" href="{{ route('profile') }}"> <i class="me-2" data-feather="user"></i>Mon Profile</a>
            <form action="{{ route('logout') }}" method="POST" class="p-0 m-0">
                @csrf
                <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent"><img src="{{ asset('assets/img/icons/log-out.svg') }}" class="me-2" alt="img">Deconnection</button>
            </form>
        </div>
    </div>

</div>
