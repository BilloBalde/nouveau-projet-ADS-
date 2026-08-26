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
                border: 1px solid #dcefe3;
                border-radius: 10px;
                box-shadow: 0 1px 3px rgba(12, 44, 28, 0.06);
                transition: box-shadow .15s ease, transform .15s ease;
                animation: rowIn .25s ease;
            }

            .product-row:hover {
                box-shadow: 0 4px 10px rgba(12, 44, 28, 0.12);
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
                color: #1f6f43;
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
                background-color: #1f6f43;
                color: #fff;
            }

            .qty-stepper {
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .qty-stepper button {
                width: 28px;
                height: 28px;
                border: 1px solid #b9dfc8;
                background: #f0f8f3;
                color: #1f6f43;
                border-radius: 6px;
                font-weight: bold;
                line-height: 1;
                cursor: pointer;
                transition: background-color .15s ease;
            }

            .qty-stepper button:hover {
                background-color: #1f6f43;
                color: #fff;
            }

            .qty-stepper input {
                width: 50px !important;
                text-align: center;
            }

            #emptyCartState {
                text-align: center;
                padding: 40px 10px;
                color: #5d6b64;
            }

            #emptyCartState i {
                font-size: 42px;
                color: #b9dfc8;
                margin-bottom: 10px;
                display: block;
            }

            #cartCountBadge {
                background: #1f6f43;
                color: #fff;
                border-radius: 999px;
                font-size: 12px;
                padding: 2px 9px;
                margin-left: 8px;
                vertical-align: middle;
            }

            @keyframes addPulse {
                0%   { box-shadow: 0 0 0 0 rgba(31, 111, 67, 0.55); }
                100% { box-shadow: 0 0 0 16px rgba(31, 111, 67, 0); }
            }

            .product-item.just-added .productset {
                animation: addPulse .5s ease-out;
            }

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
                color: #1f6f43;
            }

            .stock-badge.stock-low {
                background: #fdecea;
                color: #b42318;
            }

            .price-vente {
                color: #1f6f43;
                font-weight: 700;
                font-size: 16px;
            }

            .store-stock-breakdown {
                margin-top: 6px;
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }

            .store-pill {
                font-size: 10px;
                font-weight: 600;
                color: #195836;
                background: #dcefe3;
                border-radius: 999px;
                padding: 2px 7px;
                white-space: nowrap;
            }

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
                border: 1px solid #b9dfc8;
                background: #fff;
                color: #1f6f43;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: background-color .15s ease, color .15s ease;
            }

            .pos-pagination button:hover {
                background: #f0f8f3;
            }

            .pos-pagination button.active {
                background: #1f6f43;
                color: #fff;
                border-color: #1f6f43;
            }

            .pos-pagination button:disabled {
                opacity: .4;
                cursor: not-allowed;
            }

            .product-item.pos-hidden {
                display: none !important;
            }

            .tabs_wrapper ul.tabs li.active,
            .productset .check-product {
                background-color: #1f6f43 !important;
            }

            .productset.active,
            .productset:hover {
                border-color: #1f6f43 !important;
            }

            .product-details:hover {
                background: #1f6f43;
            }

            .product-details.active {
                background-color: #1f6f43 !important;
            }

            .btn-totallabel,
            .btn-scanner-set {
                background-color: #1f6f43 !important;
                color: #fff !important;
            }

            /* .setvalue, .totalitem et .setvaluecash n'existent plus sur cette page :
               le bloc des totaux a été remplacé par .pos-totals. */

            .owl-product .owl-nav button {
                color: #1f6f43 !important;
            }

            .cat-chip {
                display: inline-block;
                background: #dcefe3;
                color: #195836;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: .04em;
                text-transform: uppercase;
                padding: 2px 8px;
                border-radius: 999px;
                margin-bottom: 4px;
            }

            .productsetimg {
                background: #f0f8f3;
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
                color: #8bc8a5;
            }

            .productset {
                transition: box-shadow .15s ease, transform .15s ease;
            }

            .productset:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 18px rgba(31, 111, 67, 0.15) !important;
            }

            .tab-item:not(.active) .product-details {
                background: #f7faf8;
                border: 1px solid #b9dfc8;
                transition: background-color .15s ease, box-shadow .15s ease;
            }

            .tab-item:not(.active):hover .product-details {
                background: #f0f8f3;
                box-shadow: 0 2px 8px rgba(31, 111, 67, 0.1);
            }

            .btn-adds {
                border-color: #1f6f43 !important;
                color: #1f6f43 !important;
            }

            .btn-adds:hover {
                background: #1f6f43 !important;
                color: #fff !important;
            }

            .card-order {
                border-radius: 12px;
                box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            }

            .check-product {
                display: none;
                position: absolute;
                top: 10px;
                right: 10px;
                background-color: #1f6f43;
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
                display: flex;
            }
            .qty-price-group {
                display: flex;
                flex-direction: row;
                gap: 16px;
                flex-wrap: wrap;
            }

            .qty-price-group .form-group {
                flex: 1;
                min-width: 120px;
            }

            @media (max-width: 768px) {
                .qty-price-group {
                    flex-direction: column;
                }
            }

            /* ══════════════════════════════════════
               Colonne de droite : récapitulatif, client, paiement
            ══════════════════════════════════════ */
            .pos-section-title {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #1f6f43;
                margin: 22px 0 10px;
                padding-bottom: 7px;
                border-bottom: 1px solid #dcefe3;
            }

            .pos-label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #5d6b64;
                margin-bottom: 5px;
            }
            .pos-optional {
                font-weight: 400;
                color: #93a09a;
            }

            /* Les totaux étaient une liste indifférenciée : le montant à payer,
               qui est l'information que le vendeur annonce au client, se noyait
               au milieu du sous-total et des frais. */
            .pos-totals {
                background: #f0f8f3;
                border: 1px solid #dcefe3;
                border-radius: 12px;
                padding: 12px 14px;
                margin-top: 4px;
            }
            .pos-total-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 5px 0;
            }
            .pos-total-row span,
            .pos-total-row label {
                font-size: 13px;
                color: #5d6b64;
                margin: 0;
            }
            .pos-total-row strong {
                font-size: 15px;
                font-weight: 600;
                color: #10221a;
            }
            .pos-total-input {
                width: 110px;
                height: 34px;
                text-align: right;
                font-size: 13px;
            }
            .pos-total-row--final {
                margin-top: 6px;
                padding-top: 11px;
                border-top: 1px dashed #8bc8a5;
            }
            .pos-total-row--final span {
                font-size: 14px;
                font-weight: 700;
                color: #10221a;
            }
            .pos-total-row--final strong {
                font-size: 22px;
                font-weight: 800;
                color: #1f6f43;
                line-height: 1.1;
            }

            .btn-pos-validate {
                background: linear-gradient(135deg, #1f6f43, #195836);
                color: #fff;
                font-weight: 700;
                font-size: 15px;
                padding: 13px;
                border: none;
                border-radius: 10px;
                margin-top: 6px;
                transition: box-shadow .2s ease, transform .1s ease;
            }
            .btn-pos-validate:hover {
                color: #fff;
                box-shadow: 0 10px 22px -10px rgba(31,111,67,.9);
                transform: translateY(-1px);
            }
            .btn-pos-validate:active { transform: translateY(0); }

            /* ── Barre d'outils : recherche + action ── */
            .pos-toolbar {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
                margin-bottom: 18px;
            }
            .pos-search {
                position: relative;
                flex: 1 1 280px;
                min-width: 220px;
                max-width: 440px;
            }
            .pos-search input {
                padding-left: 38px;
                padding-right: 34px;
                height: 42px;
            }
            .pos-search input:focus {
                border-color: #2e8b57;
                box-shadow: 0 0 0 3px rgba(46,139,87,.14);
            }
            .pos-search-icon {
                position: absolute; left: 14px; top: 50%;
                transform: translateY(-50%);
                color: #93a09a; font-size: 13px; pointer-events: none;
            }
            .pos-search-clear {
                position: absolute; right: 12px; top: 50%;
                transform: translateY(-50%);
                cursor: pointer; color: #93a09a;
            }
            .pos-search-clear:hover { color: #5d6b64; }

            /* btn-warning (jaune Bootstrap) jurait avec la charte verte */
            .btn-pos-secondary {
                height: 42px;
                white-space: nowrap;
                background: #fff;
                color: #1f6f43;
                border: 1.5px solid #8bc8a5;
                border-radius: 10px;
                font-weight: 600;
                font-size: 13.5px;
                padding: 0 18px;
                transition: background .18s ease, border-color .18s ease;
            }
            .btn-pos-secondary:hover {
                background: #f0f8f3;
                border-color: #2e8b57;
                color: #195836;
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
                                    <h4>Point de vente</h4>
                                    <h6>Enregistrer une vente en caisse</h6>
                                </div>
                            </div>
                            {{-- Recherche et action sur une seule ligne : elles étaient
                                 empilées, ce qui repoussait la grille produits vers le bas. --}}
                            <div class="pos-toolbar">
                                <div class="pos-search">
                                    <i class="fa fa-search pos-search-icon"></i>
                                    <input type="text" class="form-control" id="posSearchInput" placeholder="Rechercher un produit ou une catégorie…">
                                    <span id="clearSearchBtn" class="pos-search-clear" style="display:none;">
                                        <i class="fa fa-times-circle"></i>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-pos-secondary" data-bs-toggle="modal" data-bs-target="#thirdPartyQuickModal">
                                    <i class="fa fa-plus me-1"></i> Vente tierce rapide
                                </button>
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
                        {{-- col-md-12 : sur tablette la colonne de gauche occupe déjà toute
                             la largeur, un col-md-5 ici cassait la grille (12 + 5 = 17). --}}
                        <div class="col-lg-5 col-md-12 col-sm-12">
                            <div class="order-list">
                                <div class="orderid">
                                    <h4>Panier <span id="cartCountBadge" style="display:none;">0</span></h4>
                                    <h5>Facture n° {{ $numeroFacture }}</h5>
                                </div>
                            </div>
                            <div class="card card-order">
                                <form action="{{ route('sales.store') }}" method="POST" enctype="multipart/form-data" id="formVente">
                                    @csrf
                                    <div class="card-body pt-0">
                                        <div class="product-table" id="product-list">
                                            <h5>Articles sélectionnés</h5>
                                            <div id="emptyCartState">
                                                <i class="fa fa-shopping-basket"></i>
                                                Panier vide — cliquez sur un produit pour l'ajouter
                                            </div>
                                        </div>
                                    </div>
                                    <div class="split-card">
                                    </div>
                                    <div class="card-body pt-0 pb-3">

                                        {{-- ── Récapitulatif ── --}}
                                        <div class="pos-totals">
                                            <div class="pos-total-row">
                                                <span>Sous-total</span>
                                                <strong id="subtotal">0.00</strong>
                                            </div>
                                            <div class="pos-total-row">
                                                <label for="tax">Autres frais</label>
                                                <input type="text" id="tax" class="form-control pos-total-input" value="0" inputmode="decimal">
                                            </div>
                                            <div class="pos-total-row pos-total-row--final">
                                                <span>Total à payer</span>
                                                <strong id="finalTotal">0.00</strong>
                                                <input type="hidden" id="final_total" name="final_total">
                                            </div>
                                        </div>

                                        {{-- ── Client et magasin ── --}}
                                        <div class="pos-section-title">Client et magasin</div>
                                        <input type="hidden" name="numeroFacture" value="{{ $numeroFacture }}">
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <label for="customer_id" class="pos-label">Client</label>
                                                <select name="customer_id" id="customer_id" class="form-control">
                                                    <option value="">Sélectionner un client</option>
                                                    @foreach ($customers as $item)
                                                        <option value="{{ $item->id }}" {{ old('customer_id') == $item->id ? 'selected' : '' }}>
                                                            {{ $item->customerName.' '.$item->mark }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12" id="addCustomerButton">
                                                <a href="javascript:void(0);" class="btn btn-adds w-100" data-bs-toggle="modal" data-bs-target="#create">
                                                    <i class="fa fa-plus me-2"></i>Nouveau client
                                                </a>
                                            </div>
                                            <div class="col-12">
                                                <label for="store_id" class="pos-label">Magasin</label>
                                                <select name="store_id" id="store_id" class="form-control">
                                                    <option value="">Sélectionner un magasin</option>
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

                                        {{-- ── Paiement ── --}}
                                        <div class="pos-section-title">Paiement</div>
                                        {{-- Chaque champ dans sa colonne : « Notes » et le bouton étaient
                                             posés directement dans le .row, sans col-*, ce qui les sortait
                                             de la grille Bootstrap. --}}
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label for="avance" class="pos-label">Avance versée</label>
                                                <input type="text" id="avance" name="avance" value="0" class="form-control" inputmode="decimal">
                                            </div>
                                            <div class="col-6">
                                                <label for="paid_by" class="pos-label">Mode de paiement</label>
                                                <select name="paid_by" id="paid_by" class="form-control">
                                                    <option value="cash">Espèces</option>
                                                    <option value="check">Chèque</option>
                                                    <option value="orange money">Orange Money</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label for="notes" class="pos-label">Notes <span class="pos-optional">(facultatif)</span></label>
                                                <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Précision sur la vente, la livraison…"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-pos-validate w-100">
                                                    <i class="fa fa-check-circle me-2"></i>Valider la vente
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
                        <h5 class="modal-title">Nouveau client</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="addCustomerForm">
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
                        <h5 class="modal-title">Retirer l'article</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="delete-order">
                            <img src="{{ asset('assets/img/icons/close-circle1.svg') }}" alt="img">
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

        <div class="modal fade" id="thirdPartyQuickModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vente Tierce Rapide - Nouveau Produit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="quickThirdPartyForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <label>Libellé du Produit</label>
                                    <input type="text" name="libelle" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label>SKU / Référence</label>
                                    <input type="text" name="sku" class="form-control" required>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <label>Prix de Vente au Client</label>
                                    <input type="number" step="0.01" name="prix" class="form-control" required>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <label>Prix donné par l'Ami</label>
                                    <input type="number" step="0.01" name="purchase_price_from_partner" class="form-control" required>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <label>Partenaire / Ami</label>
                                    <div class="d-flex" style="gap:6px;">
                                        <select name="partner_id" class="form-control partner-select" style="flex:1;" required>
                                            <option value="">Choisir un partenaire...</option>
                                            @foreach($partners as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }} {{ $p->phone ? '('.$p->phone.')' : '' }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-sm btn-add-partner" style="background:#1f6f43; color:#fff; white-space:nowrap;">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 mt-3">
                                    <label>Quantité</label>
                                    <input type="number" name="quantity" value="1" min="1" class="form-control" required>
                                </div>
                                <div class="col-12 mt-3">
                                    <label>Notes</label>
                                    <textarea name="notes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" id="saveQuickThirdParty" class="btn btn-primary">Enregistrer la Vente</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="quickPartnerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background: #f7faf8; border-bottom: 1px solid #b9dfc8;">
                        <h6 class="modal-title" style="color: #1f6f43;">
                            <i class="fa fa-user-plus me-2"></i>Nouveau Partenaire
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="quickPartnerForm">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" style="font-size: 13px; font-weight: 600;">Nom du partenaire *</label>
                                <input type="text" name="name" id="quickPartnerName" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-size: 13px; font-weight: 600;">Téléphone</label>
                                <input type="text" name="phone" id="quickPartnerPhone" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="font-size: 13px; font-weight: 600;">Email</label>
                                <input type="email" name="email" id="quickPartnerEmail" class="form-control">
                            </div>
                            <div class="text-danger" id="quickPartnerError" style="font-size: 12px; display:none;"></div>
                        </form>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #b9dfc8;">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-sm" id="saveQuickPartner" style="background: #1f6f43; color: white;">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>

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
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const productList = document.getElementById('product-list');

                // Dernier verrou avant envoi. Le contrôle faisant foi reste celui du
                // serveur (SaleController::store) : celui-ci évite juste au vendeur de
                // saisir toute une vente pour se la faire refuser à la validation.
                document.getElementById('formVente')?.addEventListener('submit', function (e) {
                    const fautives = productList.querySelectorAll('.price-field[data-below-cost="1"]');
                    if (!fautives.length) return;

                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Vente à perte',
                        html: fautives.length > 1
                            ? fautives.length + ' produits sont vendus en dessous de leur prix de revient.<br>Corrigez les prix en rouge avant de valider.'
                            : 'Un produit est vendu en dessous de son prix de revient.<br>Corrigez le prix en rouge avant de valider.',
                        confirmButtonText: 'Corriger',
                        confirmButtonColor: '#1f6f43',
                    });
                    fautives[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    fautives[0].focus();
                });

                let i = 0;
                let targetPartnerSelect = null;
                const partnersList = @json($partners);
                const quickPartnerModal = new bootstrap.Modal(document.getElementById('quickPartnerModal'));
                const thirdPartyQuickModalEl = document.getElementById('thirdPartyQuickModal');
                const thirdPartyQuickModal = new bootstrap.Modal(thirdPartyQuickModalEl);

                // Bouton "+" à côté de "Partenaire / Ami" dans la modale "Vente Tierce
                // Rapide" : contrairement à celui des lignes de vente normales (câblé
                // dans initializeRowEvents pour chaque ligne ajoutée dynamiquement),
                // celui-ci est statique dans le HTML et n'avait jamais d'écouteur.
                const thirdPartyQuickAddPartnerBtn = thirdPartyQuickModalEl.querySelector('.btn-add-partner');
                if (thirdPartyQuickAddPartnerBtn) {
                    thirdPartyQuickAddPartnerBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        targetPartnerSelect = thirdPartyQuickModalEl.querySelector('.partner-select');
                        document.getElementById('quickPartnerForm').reset();
                        document.getElementById('quickPartnerError').style.display = 'none';
                        quickPartnerModal.show();
                    });
                }

                // Fonction pour peupler les selects de partenaires
                function populatePartnerSelects() {
                    document.querySelectorAll('.partner-select').forEach(select => {
                        const currentValue = select.value;
                        while (select.options.length > 1) {
                            select.remove(1);
                        }
                        partnersList.forEach(partner => {
                            const phoneText = partner.phone ? ' (' + partner.phone + ')' : '';
                            const option = document.createElement('option');
                            option.value = partner.id;
                            option.textContent = partner.name + phoneText;
                            select.appendChild(option);
                        });
                        if (currentValue) {
                            select.value = currentValue;
                        }
                    });
                }

                // Gestion du clic sur les produits
                document.addEventListener('click', function (event) {
                    const item = event.target.closest('.product-item');
                    if (!item) return;

                    i++;
                    const sku = item.getAttribute('data-sku');
                    const product_id = item.getAttribute('data-id');
                    const numeroFacture = item.getAttribute('data-numeroFacture');
                    const productPrice = parseFloat(item.getAttribute('data-price')) || 0;
                    // Prix de revient : plancher absolu, vendre en dessous est refusé.
                    const productCost = parseFloat(item.getAttribute('data-cost')) || 0;
                    const libelle = item.querySelector('.productsetcontent h5').textContent;
                    const imageUrl = item.querySelector('.productsetimg img').src;

                    if (document.querySelector('#product-list li[data-sku="' + sku + '"]')) {
                        return;
                    }

                    item.classList.add('selected', 'just-added');
                    setTimeout(() => item.classList.remove('just-added'), 500);

                    var partnerOptions = '<option value="">Choisir un partenaire...</option>';
                    partnersList.forEach(function(partner) {
                        var phoneText = partner.phone ? ' (' + partner.phone + ')' : '';
                        partnerOptions += '<option value="' + partner.id + '">' + partner.name + phoneText + '</option>';
                    });

                    const newRow = document.createElement('li');
                    newRow.classList.add('product-row');
                    newRow.setAttribute('data-sku', sku);

                    newRow.innerHTML = '<ul class="product-lists">' +
                        '<input type="hidden" name="sales[' + i + '][product_id]" value="' + product_id + '">' +
                        '<input type="hidden" name="sales[' + i + '][numeroFacture]" value="' + numeroFacture + '">' +
                        '<input type="hidden" name="sales[' + i + '][is_third_party]" value="0" class="third-party-flag">' +
                        '<li>' +
                            '<div class="productimg">' +
                                '<div class="productimgs">' +
                                    '<img src="' + imageUrl + '" alt="img">' +
                                '</div>' +
                                '<div class="productcontet">' +
                                    '<h4>' + libelle + '</h4>' +
                                    '<div class="productlinkset">' +
                                        '<h5>' + sku + '</h5>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</li>' +
                        '<li class="qty-price-group">' +
                            '<div class="quantity-set form-group">' +
                                '<h6>Qté</h6>' +
                                '<div class="qty-stepper">' +
                                    '<button type="button" class="qty-decrement">-</button>' +
                                    '<input type="number" min="1" name="sales[' + i + '][quantity]" value="1" class="quantity-field form-control">' +
                                    '<button type="button" class="qty-increment">+</button>' +
                                '</div>' +
                            '</div>' +
                            '<div class="price form-group">' +
                                '<h6>Prix de Vente</h6>' +
                                '<input type="text" style="width:150px" name="sales[' + i + '][prix]" value="' + productPrice + '" class="price-field form-control">' +
                                '<div class="text-danger price-error mt-1" style="font-size: 0.9rem; display: none;"></div>' +
                            '</div>' +
                        '</li>' +
                        '<li style="width:100%; margin-top:8px;">' +
                            '<label style="font-size:13px; cursor:pointer; display:flex; align-items:center;">' +
                                '<input type="checkbox" class="third-party-check" style="margin-right:6px; accent-color:#1f6f43;">' +
                                'Vente pour un tiers' +
                            '</label>' +
                            '<div class="third-party-fields mt-2" style="display:none; background:#f7faf8; padding:10px; border-radius:8px; border:1px solid #b9dfc8;">' +
                                '<div style="display:flex; gap:8px; margin-bottom:8px;">' +
                                    '<select name="sales[' + i + '][partner_id]" class="form-control partner-select" style="flex:1;">' +
                                        partnerOptions +
                                    '</select>' +
                                    '<button type="button" class="btn btn-sm btn-add-partner" style="background:#1f6f43; color:#fff; white-space:nowrap;">' +
                                        '<i class="fa fa-plus"></i>' +
                                    '</button>' +
                                '</div>' +
                                '<input type="number" step="0.01" name="sales[' + i + '][purchase_price_from_partner]" placeholder="Prix payé au partenaire (par unité)" class="form-control">' +
                            '</div>' +
                        '</li>' +
                        '<li>' +
                            '<div class="row-total form-group">' +
                                '<h6>Total ligne</h6>' +
                                '<input type="text" name="sales[' + i + '][total_price]" class="form-control row-total-price" readonly>' +
                            '</div>' +
                        '</li>' +
                        '<li>' +
                            '<button type="button" class="row-remove-btn" title="Retirer">' +
                                '<i class="fa fa-trash"></i>' +
                            '</button>' +
                        '</li>' +
                    '</ul>';

                    productList.appendChild(newRow);
                    initializeRowEvents(newRow, productPrice, productCost);
                });

                // productCost doit être passé en paramètre : initializeRowEvents est
                // déclarée hors du gestionnaire de clic et ne voit donc pas ses variables.
                function initializeRowEvents(row, productPrice, productCost) {
                    const quantityField = row.querySelector('.quantity-field');
                    const priceField = row.querySelector('.price-field');
                    const rowTotalField = row.querySelector('.row-total-price');
                    const thirdPartyCheck = row.querySelector('.third-party-check');
                    const thirdPartyFields = row.querySelector('.third-party-fields');
                    const thirdPartyFlag = row.querySelector('.third-party-flag');
                    const addPartnerBtn = row.querySelector('.btn-add-partner');
                    const partnerSelect = row.querySelector('.partner-select');

                    if (thirdPartyCheck) {
                        thirdPartyCheck.addEventListener('change', function() {
                            if (this.checked) {
                                thirdPartyFields.style.display = 'block';
                                thirdPartyFlag.value = "1";
                            } else {
                                thirdPartyFields.style.display = 'none';
                                thirdPartyFlag.value = "0";
                                if (partnerSelect) partnerSelect.value = '';
                                const purchasePrice = thirdPartyFields.querySelector('input[name*="purchase_price_from_partner"]');
                                if (purchasePrice) purchasePrice.value = '';
                            }
                        });
                    }

                    if (addPartnerBtn) {
                        addPartnerBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            targetPartnerSelect = partnerSelect;
                            document.getElementById('quickPartnerForm').reset();
                            document.getElementById('quickPartnerError').style.display = 'none';
                            quickPartnerModal.show();
                        });
                    }

                    row.querySelector('.qty-increment').addEventListener('click', function() {
                        quantityField.value = parseInt(quantityField.value) + 1;
                        updateRowTotal();
                    });
                    row.querySelector('.qty-decrement').addEventListener('click', function() {
                        quantityField.value = Math.max(1, parseInt(quantityField.value) - 1);
                        updateRowTotal();
                    });

                    function updateRowTotal() {
                        const qty = parseFloat(quantityField.value) || 0;
                        const price = parseFloat(priceField.value) || 0;
                        rowTotalField.value = (qty * price).toFixed(2);
                        updateTotalPrice();
                    }

                    quantityField.addEventListener('input', updateRowTotal);
                    // Plancher applicable à la ligne : pour une vente tierce c'est le prix
                    // payé au partenaire, sinon le prix de revient du produit.
                    function plancherDeLaLigne() {
                        if (row.querySelector('.third-party-check')?.checked) {
                            const achat = parseFloat(row.querySelector('input[name*="purchase_price_from_partner"]')?.value) || 0;
                            return { valeur: achat, origine: "prix d'achat au partenaire" };
                        }
                        return { valeur: productCost, origine: 'prix de revient' };
                    }

                    // Deux situations distinctes, qu'il ne faut pas confondre :
                    // vendre sous le prix affiché est une remise légitime, vendre sous le
                    // prix de revient est une perte — seule la seconde bloque.
                    function controlerPrix() {
                        const saisi = parseFloat(priceField.value) || 0;
                        const priceError = row.querySelector('.price-error');
                        const plancher = plancherDeLaLigne();

                        if (plancher.valeur > 0 && saisi < plancher.valeur) {
                            priceField.dataset.belowCost = '1';
                            priceField.classList.add('is-invalid');
                            if (priceError) {
                                priceError.textContent = 'Vente à perte : minimum '
                                    + plancher.valeur.toLocaleString('fr-FR') + ' GNF (' + plancher.origine + ')';
                                priceError.style.color = '#d92d20';
                                priceError.style.display = 'block';
                            }
                        } else if (saisi < productPrice) {
                            delete priceField.dataset.belowCost;
                            priceField.classList.remove('is-invalid');
                            if (priceError) {
                                priceError.textContent = 'Remise appliquée — prix affiché : '
                                    + productPrice.toLocaleString('fr-FR') + ' GNF';
                                priceError.style.color = '#b45309';
                                priceError.style.display = 'block';
                            }
                        } else {
                            delete priceField.dataset.belowCost;
                            priceField.classList.remove('is-invalid');
                            if (priceError) priceError.style.display = 'none';
                        }
                        updateRowTotal();
                    }

                    priceField.addEventListener('input', controlerPrix);
                    row.querySelector('input[name*="purchase_price_from_partner"]')?.addEventListener('input', controlerPrix);
                    row.querySelector('.third-party-check')?.addEventListener('change', controlerPrix);
                    controlerPrix();

                    row.querySelector('.row-remove-btn').addEventListener('click', function() {
                        row.remove();
                        updateTotalPrice();
                    });

                    updateRowTotal();
                }

                function updateTotalPrice() {
                    let subtotal = 0;
                    const rows = document.querySelectorAll('.product-row');
                    rows.forEach(row => {
                        const rowTotal = parseFloat(row.querySelector('.row-total-price')?.value) || 0;
                        subtotal += rowTotal;
                    });

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
                        subtotalElement.textContent = subtotal.toFixed(2);
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

                // Gestion création partenaire
                document.getElementById('saveQuickPartner').addEventListener('click', function () {
                    const form = document.getElementById('quickPartnerForm');
                    const name = document.getElementById('quickPartnerName').value.trim();
                    const errorBox = document.getElementById('quickPartnerError');

                    if (!name) {
                        errorBox.textContent = 'Le nom du partenaire est requis.';
                        errorBox.style.display = 'block';
                        return;
                    }

                    const formData = new FormData(form);
                    const saveBtn = this;
                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Enregistrement...';

                    fetch('{{ route("pos.storePartner") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Enregistrer';

                        if (!data.success) {
                            errorBox.textContent = data.message || "Erreur lors de la création du partenaire.";
                            errorBox.style.display = 'block';
                            return;
                        }

                        const newPartner = data.partner;
                        document.querySelectorAll('.partner-select').forEach(select => {
                            const phoneText = newPartner.phone ? ' (' + newPartner.phone + ')' : '';
                            const option = document.createElement('option');
                            option.value = newPartner.id;
                            option.textContent = newPartner.name + phoneText;
                            select.appendChild(option);
                        });

                        if (targetPartnerSelect) {
                            targetPartnerSelect.value = newPartner.id;
                        }

                        quickPartnerModal.hide();
                        form.reset();
                    })
                    .catch(error => {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Enregistrer';
                        errorBox.textContent = "Erreur réseau, veuillez réessayer.";
                        errorBox.style.display = 'block';
                        console.error('Erreur:', error);
                    });
                });

                // Gestion vente tierce rapide
                document.getElementById('saveQuickThirdParty').addEventListener('click', function () {
                    const form = document.getElementById('quickThirdPartyForm');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }

                    const formData = new FormData(form);
                    formData.append('store_id', document.getElementById('store_id').value);
                    formData.append('customer_id', document.getElementById('customer_id').value || '');
                    
                    const saveBtn = this;
                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Enregistrement...';

                    fetch('{{ route("pos.storeQuickThirdParty") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Enregistrer la Vente';
                        if (data.success) {
                            alert(data.message);
                            thirdPartyQuickModal.hide();
                            form.reset();
                            window.location.href = data.redirect;
                        } else {
                            alert(data.message || "Erreur lors de l'enregistrement de la vente.");
                        }
                    })
                    .catch(error => {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Enregistrer la Vente';
                        console.error('Erreur:', error);
                        alert("Erreur réseau, veuillez réessayer.");
                    });
                });

                // Gestion client
                const addCustomerButton = document.getElementById('addCustomerButton');
                const selectCustomer = document.getElementById('customer_id');

                function showHideCustomerButton() {
                    if (!selectCustomer.value || selectCustomer.value === 'Selectionner Client') {
                        addCustomerButton.style.display = 'block';
                    } else {
                        addCustomerButton.style.display = 'none';
                    }
                }

                showHideCustomerButton();
                selectCustomer.addEventListener('change', showHideCustomerButton);

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
                            let newOption = document.createElement('option');
                            newOption.value = data.customer.id;
                            newOption.textContent = data.customer.customerName + ' ' + data.customer.mark;
                            customerSelect.appendChild(newOption);
                            customerSelect.value = data.customer.id;
                            showHideCustomerButton();
                            addCustomerModal.hide();
                            document.getElementById('addCustomerForm').reset();
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

                document.addEventListener('keydown', function (e) {
                    if (e.key === '/' && document.activeElement !== searchInput && document.activeElement.tagName !== 'TEXTAREA' && document.activeElement.tagName !== 'INPUT') {
                        e.preventDefault();
                        searchInput.focus();
                    }
                });

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
                        const savedTabItem = document.querySelector('.tab-item[data-tab="' + savedTab + '"]');
                        const savedContent = document.querySelector('.tab_content[data-tab="' + savedTab + '"]');
                        if (savedTabItem && savedContent) {
                            targetTab = savedTabItem;
                            targetContent = savedContent;
                        }
                    }
                    targetTab.classList.add('active');
                    targetContent.classList.add('active');
                    targetContent.style.display = 'block';
                }

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
                    let html = '<button type="button" data-page="prev" ' + (current === 1 ? 'disabled' : '') + '>&laquo;</button>';
                    for (let p = 1; p <= totalPages; p++) {
                        html += '<button type="button" data-page="' + p + '" class="' + (p === current ? 'active' : '') + '">' + p + '</button>';
                    }
                    html += '<button type="button" data-page="next" ' + (current === totalPages ? 'disabled' : '') + '>&raquo;</button>';
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
                    tabItems.forEach(tab => tab.classList.remove('active'));
                });

                clearSearchBtn.addEventListener('click', function () {
                    searchInput.value = '';
                    clearSearchBtn.style.display = 'none';
                    resetToInitialState();
                    paginateAllTabs();
                    searchInput.focus();
                });

                tabItems.forEach(tab => {
                    tab.addEventListener('click', () => {
                        const target = tab.getAttribute('data-tab');
                        localStorage.setItem('activeTab', target);
                        tabItems.forEach(t => t.classList.remove('active'));
                        tabContents.forEach(c => {
                            c.classList.remove('active');
                            c.style.display = 'none';
                        });
                        tab.classList.add('active');
                        const contentToShow = document.querySelector('.tab_content[data-tab="' + target + '"]');
                        if (contentToShow) {
                            contentToShow.classList.add('active');
                            contentToShow.style.display = 'block';
                        }
                    });
                });

                resetToInitialState();
                paginateAllTabs();
            });
        </script>
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