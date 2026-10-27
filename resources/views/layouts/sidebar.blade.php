<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                {{-- Compte d'associé : son relevé, rien d'autre. --}}
                @if (Auth::check() && Auth::user()->isAssociate())
                <li class="{{ isActiveRoute(['associates.show', 'associates.me']) }}">
                    <a href="{{ route('associates.me') }}"><img src="{{ asset('assets/img/icons/dashboard.svg') }}" alt="img"><span>Mon relevé d'associé</span></a>
                </li>
                @else
                @permission('dashboard.view')
                <li class="{{ isActiveRoute(['home']) }}">
                    <a href="{{ route('home') }}"><img src="{{ asset('assets/img/icons/dashboard.svg') }}" alt="img"><span>Tableau de Bord</span> </a>
                </li>
                @endpermission
                @permission('products.view')
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ isActiveRoute(['produits.index', 'categories.index', 'transfers.index']) }}" ><img src="{{ asset('assets/img/icons/product.svg') }}" alt="img"><span> Produits</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('categories.index') }}">Catégories</a></li>
                        <li><a href="{{ route('produits.index') }}">Liste des produits</a></li>
                    </ul>
                </li>
                @endpermission
                @permission('stock.view')
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ isActiveRoute(['stock.index', 'stock.show', 'stock.movements', 'stock.trace', 'stock.adjust.form', 'transfers.index']) }}"><img src="{{ asset('assets/img/icons/product.svg') }}" alt="img"><span> Stock</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('stock.index') }}">Stock par emplacement</a></li>
                        @permission('transfers.view')
                        <li><a href="{{ route('transfers.index') }}">Transferts</a></li>
                        @endpermission
                        @permission('stock.movements')
                        <li><a href="{{ route('stock.movements') }}">Journal des mouvements</a></li>
                        <li><a href="{{ route('stock.trace') }}">Traçabilité produit</a></li>
                        @endpermission
                        @if (\App\Support\ModeDeVente::parInventaire())
                        {{-- Mode inventaire : le comptage complet d'une boutique remplace
                             l'ajustement produit par produit, et c'est lui qui établit le
                             chiffre d'affaires de la période. --}}
                        <li><a href="{{ route('inventaires.index') }}">Inventaires et chiffre d'affaires</a></li>
                        @endif
                        @permission('stock.adjust')
                        <li><a href="{{ route('stock.adjust.form') }}">Inventaire / ajustement</a></li>
                        @endpermission
                    </ul>
                </li>
                @endpermission
                @permission('reports.view')
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ isActiveRoute(['reports.sales', 'reports.daily', 'reports.flash', 'reports.tiers']) }}"><img src="{{ asset('assets/img/icons/sales1.svg') }}" alt="img"><span> Rapports</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('reports.sales') }}">Rapport des ventes</a></li>
                        {{-- Ventes flash et ventes tierces se saisissent au point de vente.
                             En mode inventaire il est fermé : ces deux écrans ne peuvent
                             rien contenir, par construction. --}}
                        @if (\App\Support\ModeDeVente::parCaisse())
                        <li><a href="{{ route('reports.flash') }}">Ventes flash</a></li>
                        <li><a href="{{ route('reports.tiers') }}">Ventes tierces</a></li>
                        @endif
                        <li><a href="{{ route('reports.daily') }}">Rapport journalier</a></li>
                    </ul>
                </li>
                @endpermission
                @permission('purchases.view')
                <li class="submenu">
                    <a class="{{ isActiveRoute(['purchase-orders.index', 'purchase-orders.create']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/purchase1.svg') }}" alt="img"><span> Achat </span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchase-orders.index') }}">Liste des Achats</a></li>
                        <li><a href="{{ route('purchase-orders.create') }}">Nouvel Achat</a></li>
                    </ul>
                </li>
                @endpermission
                @permission('sales.view')
                @if (\App\Support\ModeDeVente::parCaisse())
                <li class="submenu">
                    <a  class="{{ isActiveRoute(['sales.index', 'factures.index', 'payments.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/sales1.svg') }}" alt="img"><span> Ventes</span> <span class="menu-arrow"></span></a>
                    <ul>
                        {{-- En mode inventaire, aucune vente n'est saisie : ces deux écrans
                             resteraient vides. Les paiements et les dettes, eux, servent
                             toujours (un client peut devoir de l'argent). --}}
                        @if (\App\Support\ModeDeVente::parCaisse())
                        <li><a href="{{ route('sales.index') }}">Liste des ventes</a></li>
                        <li><a href="{{ route('factures.index') }}">Liste des factures</a></li>
                        @endif
                        {{-- Un paiement est rattaché à une facture (clé étrangère
                             obligatoire). Sans ventes saisies ni boutique en ligne, il ne
                             peut exister ni facture, ni paiement, ni dette. --}}
                        @permission('payments.view')
                        @if (\App\Support\ModeDeVente::parCaisse())
                        <li><a href="{{ route('payments.index') }}">Liste des paiements</a></li>
                        <li><a href="{{ route('admin.debt-reminders.index') }}">Rappels de dettes</a></li>
                        @endif
                        @endpermission
                  </ul>
                </li>
                @endif
                @endpermission
                {{-- Commandes en ligne : sans boutique en ligne, ces écrans restent vides. --}}
                @permission('orders.view')
                @if (\App\Support\ModeDeVente::parCaisse())
                <li class="submenu">
                    <a  class="{{ isActiveRoute(['admin.orders.index', 'admin.orders.confirmed', 'admin.orders.factures']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/order.svg') }}" alt="img"><span> Commandes</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('admin.orders.index') }}">Commandes à valider</a></li>
                        <li><a href="{{ route('admin.orders.confirmed') }}">Commandes confirmées</a></li>
                        <li><a href="{{ route('admin.orders.factures') }}">Factures e-commerce</a></li>
                        {{-- L'écran existait mais n'était relié à aucun menu : les
                             messages du formulaire de contact s'accumulaient sans
                             que personne puisse les lire. --}}
                        <li><a href="{{ route('contact.messages') }}">Messages reçus</a></li>
                    </ul>
                </li>
                @endif
                @endpermission
                @permission('expenses.view')
                <li class="submenu">
                    <a class="{{ isActiveRoute(['expenses.index', 'expensesCategory.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/expense1.svg') }}" alt="img"><span>Depenses</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('expenses.index') }}">Liste des dépenses</a></li>
                        <li><a href="{{ route('expensesCategory.index') }}">Depense Category</a></li>
                    </ul>
                </li>
                @endpermission
                @if (Auth::check() && (Auth::user()->hasPermission('users.view') || (Auth::user()->hasPermission('customers.view') && ! Auth::user()->isRestrictedToStore())))
                <li class="submenu">
                    <a  class="{{ isActiveRoute(['customers.index', 'users.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/users1.svg') }}" alt="img"><span>Personnes</span> <span class="menu-arrow"></span></a>
                    <ul>
                        @permission('customers.view')
                        <li><a href="{{ route('customers.index') }}">Liste des clients</a></li>
                        @endpermission
                        @permission('users.view')
                        <li><a href="{{ route('users.index') }}">Liste des utilisateurs</a></li>
                        @endpermission
                    </ul>
                </li>
                @endif
                @permission('stores.view')
                @if (Auth::check() && ! Auth::user()->isRestrictedToStore())
                <li class="submenu">
                    <a class="{{ isActiveRoute(['places.index', 'boutiques.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/places.svg') }}" alt="img"><span>Magasins & entrepôts</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('places.index') }}">Localités</a></li>
                        <li><a href="{{ route('boutiques.index') }}">Magasins, entrepôts et stocks</a></li>
                    </ul>
                </li>
                @endif
                @endpermission
                {{-- Le menu suivait « role_id == 1 » : un rôle créé plus tard et
                     doté de settings.view ne le voyait pas. --}}
                @permission('settings.view')
                <li class="submenu">
                    <a lass="{{ isActiveRoute(['paymentSetting.index', 'currencySetting.index', 'roles.index', 'baseDonnee.index', 'clear-cache']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/settings.svg') }}" alt="img"><span>Parametres</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('paymentSetting.index') }}">Paiement Parametres</a></li>
                        <li><a href="{{ route('currencySetting.index') }}">Monnaie Parametres</a></li>
                        <li><a href="{{ route('roles.index') }}">Group Permissions</a></li>
                        <li><a href="{{ route('baseDonnee.index') }}">Gestion DB</a></li>
                        <li><a href="{{ route('clear-cache') }}">Rafraichir</a></li>
                        <li><a href="#">Administration</a></li>
                    </ul>
                </li>
                @endpermission
                @permission('associates.manage')
                <li class="{{ isActiveRoute(['associates.index', 'associates.show', 'associates.create', 'associates.edit']) }}">
                    <a href="{{ route('associates.index') }}"><img src="{{ asset('assets/img/icons/users1.svg') }}" alt="img"><span>Associés</span></a>
                </li>
                @endpermission
                @endif
            </ul>
        </div>
    </div>
</div>
