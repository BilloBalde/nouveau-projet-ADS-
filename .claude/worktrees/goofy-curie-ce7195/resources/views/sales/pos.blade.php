<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            .total {
                font-size: 18px;
                font-weight: bold;
                margin-top: 20px;
            }

            .product-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin: 8px 0;
                padding: 12px;
                background: #fff;
                border: 1px solid #f0e4d8;
                border-radius: 10px;
                box-shadow: 0 1px 3px rgba(193, 104, 47, 0.06);
                transition: box-shadow .15s ease, transform .15s ease;
                animation: rowIn .25s ease;
            }

            .product-row:hover {
                box-shadow: 0 4px 10px rgba(193, 104, 47, 0.12);
            }

            @keyframes rowIn {
                from { opacity: 0; transform: translateY(-6px); }
                to   { opacity: 1; transform: translateY(0); }
            }

            .product-row .quantity-set,
            .product-row .price,
            .product-row .row-total {
                margin-right: 10px;
            }

            .row-remove-btn {
                background-color: #fdecea;
                color: #c1682f;
                border: none;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background-color .15s ease, color .15s ease;
            }

            .row-remove-btn:hover {
                background-color: #c1682f;
                color: #fff;
            }

            /* Stepper +/- pour la quantité */
            .qty-stepper {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .qty-stepper button {
                width: 28px;
                height: 28px;
                border: 1px solid #e3d3c2;
                background: #fbf3ea;
                color: #c1682f;
                border-radius: 6px;
                font-weight: bold;
                line-height: 1;
                cursor: pointer;
                transition: background-color .15s ease;
            }

            .qty-stepper button:hover {
                background-color: #c1682f;
                color: #fff;
            }

            .qty-stepper input {
                width: 50px !important;
                text-align: center;
            }

            /* Panier vide */
            #emptyCartState {
                text-align: center;
                padding: 40px 10px;
                color: #a99b8c;
            }

            #emptyCartState i {
                font-size: 42px;
                color: #e3d3c2;
                margin-bottom: 10px;
                display: block;
            }

            /* Compteur d'articles dans l'entête du panier */
            #cartCountBadge {
                background: #c1682f;
                color: #fff;
                border-radius: 999px;
                font-size: 12px;
                padding: 2px 9px;
                margin-left: 8px;
                vertical-align: middle;
            }

            /* Pulse quand un produit est ajouté au panier */
            @keyframes addPulse {
                0%   { box-shadow: 0 0 0 0 rgba(193, 104, 47, 0.55); }
                100% { box-shadow: 0 0 0 16px rgba(193, 104, 47, 0); }
            }

            .product-item.just-added .productset {
                animation: addPulse .5s ease-out;
            }

            /* Badge de stock */
            .stock-badge {
                display: inline-block;
                font-size: 11px;
                font-weight: 600;
                padding: 2px 8px;
                border-radius: 999px;
                margin-top: 4px;
            }

            .stock-badge.stock-ok {
                background: #e8f6ec;
                color: #1f9d55;
            }

            .stock-badge.stock-low {
                background: #fdecea;
                color: #d9534f;
            }

            .price-vente {
                color: #c1682f;
                font-weight: 700;
                font-size: 16px;
            }

            /* Répartition du stock par magasin */
            .store-stock-breakdown {
                margin-top: 6px;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }

            .store-pill {
                font-size: 10px;
                font-weight: 600;
                color: #8a5a34;
                background: #f6ece0;
                border-radius: 999px;
                padding: 2px 7px;
                white-space: nowrap;
            }

            /* Pagination des produits (10 par page) */
            .pos-pagination {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 6px;
                margin: 18px 0 10px;
            }

            .pos-pagination button {
                min-width: 34px;
                height: 34px;
                border: 1px solid #e3d3c2;
                background: #fff;
                color: #c1682f;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: background-color .15s ease, color .15s ease;
            }

            .pos-pagination button:hover {
                background: #fbf3ea;
            }

            .pos-pagination button.active {
                background: #c1682f;
                color: #fff;
                border-color: #c1682f;
            }

            .pos-pagination button:disabled {
                opacity: .4;
                cursor: not-allowed;
            }

            /* Bootstrap's .d-flex utility is !important, so hiding a product
               card (pagination/search) needs to win with the same weapon. */
            .product-item.pos-hidden {
                display: none !important;
            }

            /* Le thème d'origine de cette page utilisait un violet (#7367f0)
               jamais retouché lors du passage au terracotta — on l'aligne ici. */
            .tabs_wrapper ul.tabs li.active,
            .productset .check-product {
                background-color: #c1682f !important;
            }

            .productset.active,
            .productset:hover {
                border-color: #c1682f !important;
            }

            .product-details:hover {
                background: #c1682f;
            }

            .product-details.active {
                background-color: #c1682f !important;
            }

            .btn-totallabel,
            .btn-scanner-set {
                background-color: #c1682f !important;
                color: #fff !important;
            }

            .setvalue ul li.total-value h5,
            .setvalue ul li.total-value h6,
            .totalitem h4 {
                color: #c1682f !important;
            }

            .setvaluecash ul li a:hover {
                border-color: #c1682f !important;
                color: #c1682f !important;
            }

            .owl-product .owl-nav button {
                color: #c1682f !important;
            }

            /* Étiquette catégorie : le thème de base la colorait en rouge "danger",
               ce qui n'a jamais été voulu ici. Petit chip neutre à la place. */
            .cat-chip {
                display: inline-block;
                background: #f6ece0;
                color: #a5702f;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
                padding: 2px 8px;
                border-radius: 999px;
                margin-bottom: 4px;
            }

            /* Emplacement produit : fond doux + icône si l'image manque/échoue,
               au lieu d'un rectangle blanc vide avec une icône cassée. */
            .productsetimg {
                background: #fbf3ea;
                border-radius: 8px;
                position: relative;
                overflow: hidden;
            }

            .productsetimg.img-fallback img {
                opacity: 0;
            }

            .productsetimg.img-fallback::after {
                content: "\f03e";
                font-family: "Font Awesome 5 Free";
                font-weight: 900;
                position: absolute;
                inset: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 32px;
                color: #e3c8a8;
            }

            /* Petite élévation au survol des cartes produit : plus vivant, retour
               visuel immédiat que la carte est cliquable. */
            .productset {
                transition: box-shadow .15s ease, transform .15s ease;
            }

            .productset:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 18px rgba(193, 104, 47, 0.15) !important;
            }

            /* Onglets catégories inactifs : le blanc plat manquait de relief. */
            .tab-item:not(.active) .product-details {
                background: #fffaf5;
                border: 1px solid #f0e0c8;
                transition: background-color .15s ease, box-shadow .15s ease;
            }

            .tab-item:not(.active):hover .product-details {
                background: #fbf3ea;
                box-shadow: 0 2px 8px rgba(193, 104, 47, 0.1);
            }

            /* Bouton "Add Customer" : vert par défaut, incohérent avec le thème. */
            .btn-adds {
                border-color: #c1682f !important;
                color: #c1682f !important;
            }

            .btn-adds:hover {
                background: #c1682f !important;
                color: #fff !important;
            }

            /* Carte du panier : un peu plus de relief pour la détacher du fond. */
            .card-order {
                border-radius: 12px;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            }
        </style>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.header')

            <div class="page-wrapper ms-0">
                <div class="content">
                    <div class="row">
                        <div class="col-lg-7 col-md-12 col-sm-12 tabs_wrapper">
                            <div class="page-header ">
                                <div class="page-title">
                                    <h4>POS</h4>
                                    <h6>Manage your sales</h6>
                                </div>
                            </div>
                            <div class="form-group position-relative mb-3" style="max-width: 400px;">
                                <input type="text" class="form-control" id="posSearchInput" placeholder="Rechercher produit ou catégorie...">
                                <span id="clearSearchBtn" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; display:none;">
                                    <i class="fa fa-times-circle text-muted"></i>
                                </span>
                            </div>
                            @include('layouts.flash')
                            <ul class="tabs owl-carousel owl-theme owl-product border-0">
                                <li class="tab-item active" data-tab="all">
                                    <div class="product-details">
                                        <h6>Tous</h6>
                                    </div>
                                </li>
                                @foreach ($categories as $item)
                                <li class="tab-item" data-tab="{{ $item->slug }}">
                                    <div class="product-details">
                                        <h6>{{ $item->slug }}</h6>
                                    </div>
                                </li>
                                @endforeach
                            </ul>
                            <div class="tabs_container">
                                <div class="tab_content active" data-tab="all">
                                    <div class="row product-grid">
                                        @foreach ($produits as $dataItem)
                                            @include('sales.partials.pos-product-card', ['dataItem' => $dataItem])
                                        @endforeach
                                    </div>
                                    <div class="pos-pagination"></div>
                                </div>
                                @foreach ($categories as $category)
                                <div class="tab_content" data-tab="{{ $category->slug }}">
                                    <div class="row product-grid">
                                        @foreach ($produits->filter(fn($p) => $p->categories->contains('slug', $category->slug)) as $dataItem)
                                            @include('sales.partials.pos-product-card', ['dataItem' => $dataItem])
                                        @endforeach
                                    </div>
                                    <div class="pos-pagination"></div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-5 col-sm-12 ">
                            <div class="order-list">
                                <div class="orderid">
                                    <h4>Order List <span id="cartCountBadge" style="display:none;">0</span></h4>
                                    <h5>Invoice id : #{{ $numeroFacture }}</h5>
                                </div>
                                <div class="actionproducts">
                                    <ul>
                                        <li>
                                            <a href="javascript:void(0);"><img src="assets/img/icons/return1.svg" alt="img"></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card card-order">
                                <form action="{{ route('sales.store') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="card-body pt-0">
                                        <div class="product-table" id="product-list">
                                            <h5>Products List</h5>
                                            <div id="emptyCartState">
                                                <i class="fa fa-shopping-basket"></i>
                                                Panier vide — cliquez sur un produit pour l'ajouter
                                            </div>
                                        </div>
                                    </div>
                                    <div class="split-card">
                                    </div>
                                    <div class="card-body pt-0 pb-2">
                                        <div class="setvalue">
                                            <ul>
                                                <li>
                                                    <h5>Subtotal </h5>
                                                    <h4 id="subtotal">0.00</h4>
                                                </li>
                                                <li>
                                                    <h5>Autres Frais </h5>
                                                    <input type="text" id="tax" style="width: 60px" class="form-control" value="0">
                                                    {{-- <h6><span id="tax">0.00$</span></h6> --}}
                                                </li>
                                                <li>
                                                    <h5>Total</h5>
                                                    <h4 id="finalTotal">0.00</h4>
                                                    <input type="hidden" id="final_total" name="final_total">
                                                </li>
                                            </ul>
                                        </div>
                                        <h5>Information Customer</h5>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="select-split form-group">
                                                        <div class="select-group w-100">
                                                            <select name="customer_id" id="customer_id" class="form-control">
                                                                <option value="">Sélectionner Client</option> <!-- Ajout d'une valeur vide -->
                                                                @foreach ($customers as $item)
                                                                    <option value="{{ $item->id }}" {{ old('customer_id') == $item->id ? 'selected' : '' }}>
                                                                        {{ $item->customerName.' '.$item->mark }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12" id="addCustomerButton">
                                                    <a href="javascript:void(0);" class="btn btn-adds" data-bs-toggle="modal" data-bs-target="#create"><i class="fa fa-plus me-2"></i>Add Customer</a>
                                                </div>
                                                <div class="col-lg-12">
                                                    <input type="hidden" name="numeroFacture" value="{{ $numeroFacture }}">
                                                    <div class="select-split form-group">
                                                        <div class="select-group w-100">
                                                            <select name="store_id" id="store_id" class="form-control">
                                                                <option value="">Sélectionner Magasin</option> <!-- Ajout d'une valeur vide -->
                                                                @if ($userStoreId)
                                                                    <option value="{{ $userStoreId }}" selected>{{ App\Models\Store::find($userStoreId)->store_name }}</option>
                                                                @else
                                                                @foreach ($boutiques as $item)
                                                                    <option value="{{ $item->id }}">{{ $item->store_name }}</option>
                                                                @endforeach
                                                                @endif
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="split-card">
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6 col-lg-6 col-12">
                                                <div class="form-group">
                                                    <label for="avance">Avance Paid</label>
                                                    <input type="text" id="avance" name="avance" value="0" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-sm-6 col-lg-6 col-12">
                                                <div class="form-group">
                                                    <label for="paid_by">Method Payement</label>
                                                    <select name="paid_by" id="paid_by" class="form-control">
                                                        <option value="cash">Cash</option>
                                                        <option value="check">Card</option>
                                                        <option value="orange money">OM</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="notes">Notes</label>
                                                <textarea name="notes" id="notes" class="form-control"></textarea>
                                            </div>
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-primary form-control">Confirm Command
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="create" tabindex="-1" aria-labelledby="create" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Creer Client</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="addCustomerForm">
                            <meta name="csrf-token" content="{{ csrf_token() }}">
                            <div class="row">
                                <div class="col-lg-6 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="customerName">Customer Name</label>
                                        <input type="text" name="customerName" id="customerName" required>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="mark">Customer Mark</label>
                                        <input type="text" name="mark" id="mark" required>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="text" name="email" id="email">
                                    </div>
                                </div>
                                <div class="col-lg-6 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="tel">Phone</label>
                                        <input type="text" name="tel" id="tel">
                                    </div>
                                </div>
                                <div class="col-lg-12 col-sm-12 col-12">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea name="address" id="address" cols="30" rows="10"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-submit me-2">Submit</button>
                                <a class="btn btn-cancel" data-bs-dismiss="modal">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="delete" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Order Deletion</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="delete-order">
                            <img src="assets/img/icons/close-circle1.svg" alt="img">
                        </div>
                        <div class="para-set text-center">
                            <p>The current order will be deleted as no payment has been <br> made so far.</p>
                        </div>
                        <div class="col-lg-12 text-center">
                            <a class="btn btn-danger me-2">Yes</a>
                            <a class="btn btn-cancel" data-bs-dismiss="modal">No</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .check-product {
                display: none; /* Hide checkbox initially */
                position: absolute;
                top: 10px;
                right: 10px;
                background-color: #c1682f;
                color: white;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                font-weight: bold;
                animation: checkPop .2s ease;
            }

            @keyframes checkPop {
                from { transform: scale(0); }
                to   { transform: scale(1); }
            }

            .product-item.selected .check-product {
                display: flex; /* Show checkbox when selected */
            }
            .qty-price-group {
                display: flex;
                flex-direction: row;
                gap: 16px; /* space between Qty and Prix */
                flex-wrap: wrap;
            }

            .qty-price-group .form-group {
                flex: 1;
                min-width: 120px;
            }

            /* Stack vertically on small screens */
            @media (max-width: 768px) {
                .qty-price-group {
                    flex-direction: column;
                }
            }

        </style>
        <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
        <script src="{{ asset('assets/js/feather.min.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}"></script>
        <script src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('assets/js/dataTables.bootstrap4.min.js') }}"></script>
        <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/sweetalert/sweetalert2.all.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/sweetalert/sweetalerts.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
        <script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
        <script src="{{ asset('assets/js/script.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const productList = document.getElementById('product-list'); // Reference to the product list
                let i = 0;

                document.addEventListener('click', function (event) {
                    const item = event.target.closest('.product-item');
                    if (!item) {
                        return;
                    }

                    i++;
                    // Get the SKU and other details from the selected product
                    const sku = item.getAttribute('data-sku');
                    const product_id = item.getAttribute('data-id');
                    const numeroFacture = item.getAttribute('data-numeroFacture');
                    const productPrice = parseFloat(item.getAttribute('data-price')) || 0;
                    const libelle = item.querySelector('.productsetcontent h5').textContent;
                    const imageUrl = item.querySelector('.productsetimg img').src;

                    // Check if the product is already in the product list
                    const existingRow = document.querySelector(`#product-list li[data-sku="${sku}"]`);
                    if (existingRow) {
                        // If the product already exists, remove it
                        existingRow.remove();
                        item.classList.remove('selected');
                        updateTotalPrice(); // Update total after removal
                        updateTotalCheckout(); // Update total after removal after
                        return; // Exit to avoid adding the product again
                    }

                    item.classList.add('selected');
                    item.classList.add('just-added');
                    setTimeout(() => item.classList.remove('just-added'), 500);

                    // Create a new row for the product list
                    const newRow = document.createElement('li');
                    newRow.classList.add('product-row');
                    newRow.setAttribute('data-sku', sku); // Add SKU to the row for easy identification
                    newRow.innerHTML = `
                            <ul class="product-lists">
                                <input type="hidden" name="sales[${i}][product_id]" value="${product_id}">
                                <input type="hidden" name="sales[${i}][numeroFacture]" value="${numeroFacture}">
                                <li>
                                    <div class="productimg">
                                        <div class="productimgs">
                                            <img src="${imageUrl}" alt="img">
                                        </div>
                                        <div class="productcontet">
                                            <h4>${libelle}</h4>
                                            <div class="productlinkset">
                                                <h5>${sku}</h5>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                               <li class="qty-price-group">
                                   <div class="quantity-set form-group">
                                       <h6>Qty</h6>
                                       <div class="qty-stepper">
                                           <button type="button" class="qty-decrement">−</button>
                                           <input type="number" min="1" name="sales[${i}][quantity]" value="1" class="quantity-field form-control">
                                           <button type="button" class="qty-increment">+</button>
                                       </div>
                                   </div>

                                   <div class="price form-group">
                                       <h6>Prix</h6>
                                       <input type="text" style="width:150px" name="sales[${i}][prix]" value="${productPrice}" class="price-field form-control">
                                       <div class="text-danger price-error mt-1" style="font-size: 0.9rem; display: none;"></div>
                                   </div>
                               </li>
                                <li>
                                    <div class="row-total form-group">
                                        <h6>Total</h6>
                                        <input type="text" name="sales[${i}][total_price]" class="form-control row-total-price" readonly>
                                    </div>
                                </li>
                                <li>
                                    <button type="button" class="row-remove-btn" title="Retirer">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </li>
                            </ul>
                        `;

                    // Append the new row to the product list
                    productList.appendChild(newRow);

                    // Update the total price when quantity or price changes
                    const quantityField = newRow.querySelector('.quantity-field');
                    const priceField = newRow.querySelector('.price-field');
                    const priceError = newRow.querySelector('.price-error');

                    // Stepper +/-
                    newRow.querySelector('.qty-increment').addEventListener('click', function () {
                        quantityField.value = (parseInt(quantityField.value) || 0) + 1;
                        updateRowTotal();
                    });
                    newRow.querySelector('.qty-decrement').addEventListener('click', function () {
                        const current = parseInt(quantityField.value) || 1;
                        quantityField.value = Math.max(1, current - 1);
                        updateRowTotal();
                    });

                    // Retirer directement depuis la ligne du panier
                    newRow.querySelector('.row-remove-btn').addEventListener('click', function () {
                        newRow.remove();
                        item.classList.remove('selected');
                        updateTotalPrice();
                    });

                    quantityField.addEventListener('input', updateRowTotal);
                    priceField.addEventListener('input', function () {
                        const enteredPrice = parseFloat(priceField.value) || 0;

                        if (enteredPrice < productPrice) {
                            priceError.textContent = `Le prix ne peut pas être inférieur à ${productPrice.toFixed(2)} GNF`;
                            priceError.style.display = 'block';
                            priceField.classList.add('is-invalid');
                        } else {
                            priceError.textContent = '';
                            priceError.style.display = 'none';
                            priceField.classList.remove('is-invalid');
                        }

                        updateRowTotal();
                    });

                    // Function to update the row total
                    function updateRowTotal() {
                        const quantity = parseFloat(quantityField.value) || 0;
                        const price = parseFloat(priceField.value) || 0;
                        const rowTotal = quantity * price;
                        newRow.querySelector('.row-total-price').value = rowTotal.toFixed(2);
                        updateTotalPrice(); // Recalculate total price whenever a value changes
                    }
                    updateRowTotal();
                    // Update total price after adding the product
                    updateTotalPrice();
                    //updateTotalCheckout();
                });
                // Function to update the total price, subtotal, and final price with tax
                function updateTotalPrice() {
                    let subtotal = 0;
                    const rows = document.querySelectorAll('.product-row');
                    rows.forEach(row => {
                        const rowTotal = parseFloat(row.querySelector('.row-total-price').value) || 0;
                        subtotal += rowTotal;
                    });

                    // État panier vide + compteur d'articles
                    const emptyState = document.getElementById('emptyCartState');
                    const cartCountBadge = document.getElementById('cartCountBadge');
                    if (emptyState) {
                        emptyState.style.display = rows.length === 0 ? 'block' : 'none';
                    }
                    if (cartCountBadge) {
                        if (rows.length > 0) {
                            cartCountBadge.textContent = rows.length;
                            cartCountBadge.style.display = 'inline-block';
                        } else {
                            cartCountBadge.style.display = 'none';
                        }
                    }

                    const subtotalElement = document.getElementById('subtotal');
                    const taxElement = document.getElementById('tax');

                    if (subtotalElement) {
                        subtotalElement.textContent = `${subtotal.toFixed(2)}`;
                        const taxAmount = parseFloat(taxElement?.value || 0) || 0;
                        const finalTotal = subtotal + taxAmount;
                        const finalTotalElement = document.getElementById('finalTotal');
                        const totalAmountElement = document.getElementById('final_total');
                        if (totalAmountElement) {
                            totalAmountElement.value = finalTotal.toFixed(2);
                        }
                        if (finalTotalElement) {
                            finalTotalElement.textContent = finalTotal.toFixed(2);
                        }
                    }
                }
                const taxInput = document.getElementById('tax');
                if (taxInput) {
                    taxInput.addEventListener('input', updateTotalPrice);
                }

                const addCustomerButton = document.getElementById('addCustomerButton');
                const selectCustomer = document.getElementById('customer_id');

                // Vérifier au chargement
                showHideCustomerButton();

                // Ajouter un écouteur sur le changement de sélection
                selectCustomer.addEventListener('change', function () {
                    console.log('Sélection modifiée :', selectCustomer.value);
                    showHideCustomerButton();
                });

                function showHideCustomerButton() {
                    console.log('Vérification du bouton...');

                    if (!selectCustomer.value || selectCustomer.value === 'Selectionner Client') {
                        addCustomerButton.style.display = 'block'; // Afficher le bouton
                    } else {
                        addCustomerButton.style.display = 'none'; // Masquer le bouton
                    }
                }
                const addCustomerModal = new bootstrap.Modal(document.getElementById('create'));
                document.getElementById('addCustomerForm').addEventListener('submit', function (e) {
                    e.preventDefault();
                    let formData = new FormData(this);

                    fetch('{{ route("pos.storeCustomer") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const customerSelect = document.getElementById('customer_id');
                            console.log(customerSelect);

                            let newOption = document.createElement('option');
                            newOption.value = data.customer.id;
                            newOption.textContent = `${data.customer.customerName} ${data.customer.mark}`;
                            customerSelect.appendChild(newOption);

                            customerSelect.value = data.customer.id;
                            showHideCustomerButton();

                            // Ferme le modal et reset le formulaire
                            addCustomerModal.hide();
                            document.getElementById('addCustomerForm').reset();
                        } else {
                            console.log('Erreur lors de l’ajout du client.');
                        }
                    })
                    .catch(error => console.error('Erreur:', error));
                });
            });
        </script>
      <script>
          document.addEventListener('DOMContentLoaded', function () {
              const searchInput = document.getElementById('posSearchInput');
              const clearSearchBtn = document.getElementById('clearSearchBtn');
              const tabContents = document.querySelectorAll('.tab_content');
              const tabItems = document.querySelectorAll('.tab-item');

              // Raccourci clavier "/" pour aller directement à la recherche
              document.addEventListener('keydown', function (e) {
                  if (e.key === '/' && document.activeElement !== searchInput && document.activeElement.tagName !== 'TEXTAREA' && document.activeElement.tagName !== 'INPUT') {
                      e.preventDefault();
                      searchInput.focus();
                  }
              });

              // Fonction principale de réinitialisation
              function resetToInitialState() {
                  const savedTab = localStorage.getItem('activeTab');

                  tabItems.forEach(tab => tab.classList.remove('active'));
                  tabContents.forEach(tab => {
                      tab.classList.remove('active');
                      tab.style.display = 'none';
                  });

                  let targetTab = tabItems[0];
                  let targetContent = tabContents[0];

                  if (savedTab) {
                      const savedTabItem = document.querySelector(`.tab-item[data-tab="${savedTab}"]`);
                      const savedContent = document.querySelector(`.tab_content[data-tab="${savedTab}"]`);
                      if (savedTabItem && savedContent) {
                          targetTab = savedTabItem;
                          targetContent = savedContent;
                      }
                  }

                  targetTab.classList.add('active');
                  targetContent.classList.add('active');
                  targetContent.style.display = 'block';
              }

              // --- Pagination des produits (10 par page, par onglet) ---
              const PAGE_SIZE = 10;
              const pageState = {};

              function paginateTab(tabContent) {
                  const slug = tabContent.getAttribute('data-tab');
                  const pageContainer = tabContent.querySelector('.pos-pagination');
                  const items = Array.from(tabContent.querySelectorAll('.product-item'));

                  if (!items.length) {
                      if (pageContainer) pageContainer.innerHTML = '';
                      return;
                  }

                  const totalPages = Math.ceil(items.length / PAGE_SIZE);
                  let current = pageState[slug] || 1;
                  if (current > totalPages) current = totalPages;
                  pageState[slug] = current;

                  items.forEach((item, idx) => {
                      const page = Math.floor(idx / PAGE_SIZE) + 1;
                      item.classList.toggle('pos-hidden', page !== current);
                  });

                  if (!pageContainer) return;

                  if (totalPages <= 1) {
                      pageContainer.innerHTML = '';
                      return;
                  }

                  let html = `<button type="button" data-page="prev" ${current === 1 ? 'disabled' : ''}>&laquo;</button>`;
                  for (let p = 1; p <= totalPages; p++) {
                      html += `<button type="button" data-page="${p}" class="${p === current ? 'active' : ''}">${p}</button>`;
                  }
                  html += `<button type="button" data-page="next" ${current === totalPages ? 'disabled' : ''}>&raquo;</button>`;
                  pageContainer.innerHTML = html;

                  pageContainer.querySelectorAll('button').forEach(btn => {
                      btn.addEventListener('click', function () {
                          const val = this.getAttribute('data-page');
                          if (val === 'prev') pageState[slug] = Math.max(1, current - 1);
                          else if (val === 'next') pageState[slug] = Math.min(totalPages, current + 1);
                          else pageState[slug] = parseInt(val);
                          paginateTab(tabContent);
                      });
                  });
              }

              function paginateAllTabs() {
                  tabContents.forEach(paginateTab);
              }

              // Gestion de la recherche (la pagination ne s'applique pas pendant une recherche)
              searchInput.addEventListener('input', function () {
                  const searchTerm = this.value.toLowerCase();

                  clearSearchBtn.style.display = searchTerm ? 'inline' : 'none';

                  if (!searchTerm) {
                      resetToInitialState();
                      paginateAllTabs();
                      return;
                  }

                  tabContents.forEach(tab => {
                      let hasVisibleProducts = false;
                      const products = tab.querySelectorAll('.product-item');
                      const pageContainer = tab.querySelector('.pos-pagination');
                      if (pageContainer) pageContainer.innerHTML = '';

                      products.forEach(product => {
                          const libelle = product.querySelector('.productsetcontent h5')?.textContent.toLowerCase() || '';
                          const sku = product.getAttribute('data-sku')?.toLowerCase() || '';
                          const cat = product.querySelector('.productsetcontent h6')?.textContent.toLowerCase() || '';

                          const match = libelle.includes(searchTerm) || sku.includes(searchTerm) || cat.includes(searchTerm);
                          product.classList.toggle('pos-hidden', !match);
                          if (match) hasVisibleProducts = true;
                      });

                      tab.style.display = hasVisibleProducts ? 'block' : 'none';
                      tab.classList.remove('active');
                  });

                  // On enlève la classe active des onglets pour empêcher l'affichage par défaut
                  tabItems.forEach(tab => tab.classList.remove('active'));
              });

              // Bouton pour vider la recherche
              clearSearchBtn.addEventListener('click', function () {
                  searchInput.value = '';
                  clearSearchBtn.style.display = 'none';
                  resetToInitialState();
                  paginateAllTabs();
                  searchInput.focus();
              });

              // Gestion du clic sur les onglets (catégories)
              tabItems.forEach(tab => {
                  tab.addEventListener('click', () => {
                      const target = tab.getAttribute('data-tab');

                      // Sauvegarde de l'onglet actif
                      localStorage.setItem('activeTab', target);

                      // Réinitialisation des classes
                      tabItems.forEach(t => t.classList.remove('active'));
                      tabContents.forEach(c => {
                          c.classList.remove('active');
                          c.style.display = 'none';
                      });

                      // Activation du bon onglet
                      tab.classList.add('active');
                      const contentToShow = document.querySelector(`.tab_content[data-tab="${target}"]`);
                      if (contentToShow) {
                          contentToShow.classList.add('active');
                          contentToShow.style.display = 'block';
                      }
                  });
              });

              // Initialisation
              resetToInitialState();
              paginateAllTabs();
          });
      </script>
        <!-- Owl Carousel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">

        <script>
            $(document).ready(function() {
                $(".owl-carousel").owlCarousel({
                    items: 5,
                    loop: false,
                    margin: 10,
                    nav: true
                });
            });
        </script>
    </body>
</html>
