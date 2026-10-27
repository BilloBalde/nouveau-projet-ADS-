@php
    // $company vient du View Composer (AppServiceProvider) : une seule requête
    // pour toute la page. Repli sur le logo de l'entreprise quand l'utilisateur
    // n'a pas de photo — sans ce garde-fou, asset('avatars/'.null) demandait
    // « /avatars » et renvoyait un 404 à chaque page.
    // Pas de logo téléversé : aucune image. Afficher celui livré avec le thème
    // reviendrait à coiffer l'installation d'un client avec l'emblème d'un autre.
    $logoUrl = $company?->logo ? asset('companies/'.$company->logo) : null;
    $nomEntreprise = $company?->name ?? config('app.name');
    $initiales = $company?->initiales() ?? '?';
    // asset('avatars/') sans fichier renvoyait un 404 à chaque page ; sans logo
    // non plus, c'est le monogramme qui tient lieu de vignette.
    // Le nom enregistré ne garantit pas que le fichier existe : les comptes
    // créés par le seeder portaient « default.png », qui n'a jamais été livré.
    // Résultat, trois images cassées par page. Un is_file() par requête — ce
    // bloc n'est évalué qu'une fois — vaut mieux que trois 404.
    $avatarFichier = Auth::user()?->profilePic;
    $avatarUrl = $avatarFichier && is_file(public_path('avatars/'.$avatarFichier))
        ? asset('avatars/'.$avatarFichier)
        : $logoUrl;
    $avatarInitiales = \App\Support\Monogramme::pour(Auth::user()?->name ?? $nomEntreprise);
@endphp
<div class="header">

    <div class="header-left active">
        <a href="{{ route('home') }}" class="logo">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $nomEntreprise }}">
            @else
                <span class="logo-monogramme">{{ $initiales }}</span>
            @endif
            <span class="logo-mot">{{ $nomEntreprise }}</span>
        </a>
        <a href="{{ route('home') }}" class="logo-small">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $nomEntreprise }}">
            @else
                <span class="logo-monogramme">{{ $initiales }}</span>
            @endif
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
        {{-- Magasin de l'employé rattaché. Il n'a pas de choix à faire : c'est
             son affectation, et c'est la seule indication de l'endroit où il
             travaille. Les profils à vue globale n'ont rien à afficher ici. --}}
        @if (Auth::check() && ! Auth::user()->isAssociate() && Auth::user()->isRestrictedToStore())
        @php($magasin = \App\Models\Store::find(Auth::user()->assignedStoreId()))
        <li class="nav-item" style="display:flex;align-items:center;margin-right:10px;">
            <span class="nav-link" style="font-weight:600;color:var(--adm-brand-600,#3E4C21);white-space:nowrap;">
                <i class="fas fa-store me-1"></i>{{ $magasin?->store_name ?? 'Aucun magasin' }}
            </span>
        </li>
        @endif
        <li class="nav-item" style="display:flex;align-items:center;">
            @include('partials.notification-bell')
        </li>
        @unless ((Auth::check() && Auth::user()->isAssociate()) || \App\Support\ModeDeVente::parInventaire())
        <li class="nav-item has-arrow flag-nav">
            <a class="nav-link" href="{{ route('pos') }}" style="background-color: var(--adm-brand-600,#3E4C21); color: #fff;">
                <img src="{{ asset('assets/img/icons/sales1.svg') }}" alt="" height="20">POS
            </a>
        </li>
        @endunless
        <li class="nav-item dropdown has-arrow main-drop">
            <a href="javascript:void(0);" class="dropdown-toggle nav-link userset" data-bs-toggle="dropdown">
                <span class="user-img">@include('partials.avatar')
                <span class="status online"></span></span>
            </a>
            <div class="dropdown-menu menu-drop-user">
                <div class="profilename">
                    <div class="profileset">
                        @if (Auth::check())
                        <span class="user-img">
                            @include('partials.avatar')
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
                    @unless (\App\Support\ModeDeVente::parInventaire())<a class="dropdown-item" href="{{ route('accueil') }}"><i class="me-2" data-feather="home"></i>Voir Site</a>@endunless
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
                    @include('partials.avatar')
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
            <a class="dropdown-item" href="{{ route('pos') }}" style="background-color: var(--adm-brand-600,#3E4C21); color: #fff;">
                <img src="{{ asset('assets/img/icons/sales1.svg') }}" class="me-2" alt="" height="20">POS
            </a>
            @endunless
            @unless (\App\Support\ModeDeVente::parInventaire())<a class="dropdown-item" href="{{ route('accueil') }}"><i class="me-2" data-feather="home"></i>Voir Site</a>@endunless
            <a class="dropdown-item" href="{{ route('profile') }}"> <i class="me-2" data-feather="user"></i>Mon Profile</a>
            <form action="{{ route('logout') }}" method="POST" class="p-0 m-0">
                @csrf
                <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent"><img src="{{ asset('assets/img/icons/log-out.svg') }}" class="me-2" alt="img">Deconnection</button>
            </form>
        </div>
    </div>

</div>
