<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                @permission('dashboard.view')
                <li class="{{ isActiveRoute(['home']) }}">
                    <a href="{{ route('home') }}"><img src="{{ asset('assets/img/icons/dashboard.svg') }}" alt="img"><span>Tableau de Bord</span> </a>
                </li>
                @endpermission
                @permission('products.view')
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ isActiveRoute(['produits.index', 'categories.index', 'transfers.index']) }}" ><img src="{{ asset('assets/img/icons/product.svg') }}" alt="img"><span> Product</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('categories.index') }}">Category List</a></li>
                        <li><a href="{{ route('produits.index') }}">Produit List</a></li>
                        <li><a href="{{ route('transfers.index') }}">Transfert Produit List</a></li>
                    </ul>
                </li>
                @endpermission
                @permission('purchases.view')
                <li class="submenu">
                    <a class="{{ isActiveRoute(['purchases.index', 'logistics.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/purchase1.svg') }}" alt="img"><span> Achat </span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('purchases.index') }}">Achat  List</a></li>
                        <li><a href="{{ route('logistics.index') }}">Logistic List</a></li>
                    </ul>
                </li>
                @endpermission
                @permission('sales.view')
                <li class="submenu">
                    <a  class="{{ isActiveRoute(['sales.index', 'factures.index', 'payments.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/sales1.svg') }}" alt="img"><span> Ventes</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('sales.index') }}">Vente List</a></li>
                        <li><a href="{{ route('factures.index') }}">Facture List</a></li>
                        @permission('payments.view')
                        <li><a href="{{ route('payments.index') }}">Paiement List</a></li>
                        <li><a href="{{ route('dettes.index') }}">Dettes</a></li>
                        <li><a href="{{ route('admin.debt-reminders.index') }}">Rappels de dettes</a></li>
                        @endpermission
                  </ul>
                </li>
                @endpermission
                @permission('orders.view')
                <li class="submenu">
                    <a  class="{{ isActiveRoute(['admin.orders.index', 'admin.orders.confirmed', 'admin.orders.factures']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/order.svg') }}" alt="img"><span> Commandes</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('admin.orders.index') }}">Commandes à valider</a></li>
                        <li><a href="{{ route('admin.orders.confirmed') }}">Commandes confirmées</a></li>
                        <li><a href="{{ route('admin.orders.factures') }}">Factures e-commerce</a></li>
                    </ul>
                </li>
                @endpermission
                <li class="submenu">
                    <a class="{{ isActiveRoute(['expenses.index', 'expensesCategory.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/expense1.svg') }}" alt="img"><span>Depenses</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('expenses.index') }}">Depense List</a></li>
                        <li><a href="{{ route('expensesCategory.index') }}">Depense Category</a></li>
                    </ul>
                </li>
                @if (Auth::check() && (Auth::user()->hasPermission('users.view') || (Auth::user()->hasPermission('customers.view') && Auth::user()->role_id != 3)))
                <li class="submenu">
                    <a  class="{{ isActiveRoute(['customers.index', 'users.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/users1.svg') }}" alt="img"><span>Personnes</span> <span class="menu-arrow"></span></a>
                    <ul>
                        @permission('customers.view')
                        <li><a href="{{ route('customers.index') }}">Client List</a></li>
                        @endpermission
                        @permission('users.view')
                        <li><a href="{{ route('users.index') }}">Utilisateur List</a></li>
                        @endpermission
                    </ul>
                </li>
                @endif
                @permission('stores.view')
                @if (Auth::check() && Auth::user()->role_id != 3)
                <li class="submenu">
                    <a class="{{ isActiveRoute(['places.index', 'boutiques.index']) }}" href="javascript:void(0);"><img src="{{ asset('assets/img/icons/places.svg') }}" alt="img"><span>Places</span> <span class="menu-arrow"></span></a>
                    <ul>
                        <li><a href="{{ route('places.index') }}">Place list</a></li>
                        <li><a href="{{ route('boutiques.index') }}">Boutique List</a></li>
                    </ul>
                </li>
                @endif
                @endpermission
                @if(Auth::check() && Auth::user()->role_id == 1 )
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
                @endif
            </ul>
        </div>
    </div>
</div>
