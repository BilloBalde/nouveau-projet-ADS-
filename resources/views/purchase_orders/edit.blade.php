<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            .po-section {
                background: #fff;
                border: 1px solid #f0e4d8;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .po-section-title {
                font-weight: 700;
                color: #c1682f;
                margin-bottom: 15px;
                font-size: 15px;
            }
            .po-import-only {
                display: none;
            }
            body.origin-chine .po-import-only {
                display: block;
            }
            .po-line {
                border: 1px solid #f0e4d8;
                border-radius: 8px;
                padding: 15px;
                margin-bottom: 12px;
                position: relative;
            }
            .po-line-remove {
                position: absolute;
                top: 10px;
                right: 10px;
                background: #fdecea;
                color: #c1682f;
                border: none;
                border-radius: 50%;
                width: 26px;
                height: 26px;
                cursor: pointer;
            }
            .po-line-remove:hover {
                background: #c1682f;
                color: #fff;
            }
            .po-line-cost-preview {
                background: #fbf3ea;
                border-radius: 6px;
                padding: 8px 12px;
                font-size: 13px;
                color: #8a5a34;
                margin-top: 10px;
            }
            .po-grand-total {
                background: #fdf6ec;
                border: 1px solid #f0e0c8;
                border-radius: 10px;
                padding: 15px 20px;
                font-size: 15px;
            }
            .po-grand-total strong {
                color: #c1682f;
            }
            .origin-toggle {
                display: flex;
                gap: 12px;
            }
            .origin-toggle label {
                flex: 1;
                border: 1px solid #e3d3c2;
                border-radius: 8px;
                padding: 12px;
                text-align: center;
                cursor: pointer;
                font-weight: 600;
            }
            .origin-toggle input {
                display: none;
            }
            .origin-toggle label:has(input:checked) {
                border-color: #c1682f;
                background: #fbf3ea;
            }
            .po-field-with-add {
                display: flex;
                gap: 6px;
                align-items: stretch;
            }
            .po-field-with-add select {
                flex: 1;
            }
            /* Select2 remplace le <select> par un span.select2-container qui n'hérite
               pas de la règle ci-dessus : sans ça, il ne s'étire pas et chevauche le
               bouton "+" (cf. sélecteur de produit). Le !important est nécessaire car
               public/assets/css/style.css force globalement
               ".select2-container{min-width:100%!important}", ce qui l'empêche sinon
               de rétrécir pour laisser la place au bouton. */
            .po-field-with-add .select2-container {
                flex: 1 1 0% !important;
                min-width: 0 !important;
                width: auto !important;
            }
            .po-add-btn {
                flex-shrink: 0;
                width: 38px;
                border: 1px solid #e3d3c2;
                background: #fdf6ec;
                color: #c1682f;
                border-radius: 6px;
                font-size: 14px;
                cursor: pointer;
            }
            .po-add-btn:hover {
                background: #c1682f;
                color: #fff;
            }
        </style>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>
        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Gestion des Achats</h4>
                            <h6>Modifier l'achat {{ $order->reference }}</h6>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <form action="{{ route('purchase-orders.update', $order->id) }}" method="POST" id="poForm">
                        @csrf
                        @method('PUT')

                        <div class="po-section">
                            <div class="po-section-title">Informations générales</div>
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Référence</label>
                                        <input type="text" class="form-control" value="{{ $order->reference }}" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Magasin de destination</label>
                                        <div class="po-field-with-add">
                                            <select name="store_id" id="storeSelect" class="form-control" required>
                                                @foreach($stores as $store)
                                                    <option value="{{ $store->id }}" {{ old('store_id', $order->store_id) == $store->id ? 'selected' : '' }}>{{ $store->store_name ?? $store->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="po-add-btn" data-bs-toggle="modal" data-bs-target="#newStoreModal" title="Ajouter une boutique">
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                        @error('store_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Date d'émission</label>
                                        <input type="date" name="date_emis" class="form-control" value="{{ old('date_emis', \Illuminate\Support\Carbon::parse($order->date_emis)->toDateString()) }}" required>
                                        @error('date_emis') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Date de réception (optionnel)</label>
                                        <input type="date" name="date_recu" class="form-control" value="{{ old('date_recu', $order->date_recu ? \Illuminate\Support\Carbon::parse($order->date_recu)->toDateString() : '') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Origine de l'achat</label>
                                <div class="origin-toggle">
                                    <label>
                                        <input type="radio" name="origin" value="guinee" id="originGuinee" {{ old('origin', $order->origin) == 'guinee' ? 'checked' : '' }}>
                                        <span>🇬🇳 Achat local (Guinée)</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="origin" value="chine" id="originChine" {{ old('origin', $order->origin) == 'chine' ? 'checked' : '' }}>
                                        <span>🇨🇳 Import (Chine)</span>
                                    </label>
                                </div>
                                @error('origin') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="po-section po-import-only">
                            <div class="po-section-title">Devise &amp; taux de change</div>
                            <div class="row">
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Devise de paiement</label>
                                        <select name="currency_code" id="currencyCode" class="form-control">
                                            <option value="">Sélectionner</option>
                                            @foreach($currencies as $currency)
                                                <option value="{{ $currency->currencyCode }}" data-rate="{{ $currency->rate_to_gnf }}" data-updated="{{ $currency->updated_at?->format('d/m/Y') }}" {{ old('currency_code', $order->currency_code) == $currency->currencyCode ? 'selected' : '' }}>
                                                    {{ $currency->currencyName }} ({{ $currency->currencyCode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('currency_code') <span class="text-danger" data-error-for="currencyCode">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Taux du jour (1 unité = X GNF)</label>
                                        <input type="text" inputmode="decimal" name="exchange_rate_used" id="exchangeRate" class="form-control decimal-input" value="{{ old('exchange_rate_used', $order->exchange_rate_used) }}">
                                        @error('exchange_rate_used') <span class="text-danger" data-error-for="exchangeRate">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted" style="font-size:13px;" id="rateReference"></p>
                        </div>

                        <div class="po-section">
                            <div class="po-section-title">Frais de l'arrivage</div>
                            <div class="row">
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Transport (GNF)</label>
                                        <input type="text" inputmode="decimal" name="transport_cost_gnf" id="transportCost" class="form-control decimal-input" value="{{ old('transport_cost_gnf', $order->transport_cost_gnf) }}">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12 po-import-only">
                                    <div class="form-group">
                                        <label>Douane / Dédouanement (GNF)</label>
                                        <input type="text" inputmode="decimal" name="customs_cost_gnf" id="customsCost" class="form-control decimal-input" value="{{ old('customs_cost_gnf', $order->customs_cost_gnf) }}">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Frais divers (GNF)</label>
                                        <input type="text" inputmode="decimal" name="other_fees_gnf" id="otherFees" class="form-control decimal-input" value="{{ old('other_fees_gnf', $order->other_fees_gnf) }}">
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted" style="font-size:13px;">
                                Le transport et la douane sont répartis automatiquement entre les produits, au prorata du volume (CBM) de chaque ligne.
                            </p>
                        </div>

                        <div class="po-section">
                            <div class="po-section-title">Produits achetés</div>
                            <div id="poLines"></div>
                            <button type="button" class="btn btn-secondary" id="addLineBtn">
                                <i class="fa fa-plus"></i> Ajouter une ligne
                            </button>
                        </div>

                        <div class="po-grand-total mb-3">
                            <div>Volume total : <strong id="grandTotalCbm">0.000</strong> CBM</div>
                            <div>Coût de revient total estimé : <strong id="grandTotalCost">0</strong> GNF</div>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control">{{ old('notes', $order->notes) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-submit">Mettre à jour l'achat</button>
                        <a href="{{ route('purchase-orders.show', $order->id) }}" class="btn btn-cancel">Annuler</a>
                    </form>
                </div>
            </div>
        </div>

        <template id="poLineTemplate">
            <div class="po-line" data-line>
                <button type="button" class="po-line-remove" data-remove-line title="Retirer"><i class="fa fa-trash"></i></button>
                <div class="row">
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Produit</label>
                            <div class="po-field-with-add">
                                <select name="lines[__INDEX__][product_id]" class="form-control" data-field="product_id" required>
                                    <option value="">Sélectionner le produit</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->libelle }} ({{ $product->sku }})</option>
                                    @endforeach
                                </select>
                                <button type="button" class="po-add-btn" data-bs-toggle="modal" data-bs-target="#newProductModal" title="Ajouter un produit">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nombre de cartons</label>
                            <input type="number" min="0" name="lines[__INDEX__][cartons]" class="form-control" data-field="cartons" value="0">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Pièces par carton</label>
                            <input type="number" min="0" name="lines[__INDEX__][pieces_per_carton]" class="form-control" data-field="pieces_per_carton" value="0">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Quantité totale (pièces)</label>
                            <input type="number" min="1" name="lines[__INDEX__][quantity]" class="form-control" data-field="quantity" readonly style="background:#f5f5f5" required>
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-6 col-12">
                        <div class="form-group">
                            <label class="po-price-label">Prix unitaire</label>
                            <input type="text" inputmode="decimal" name="lines[__INDEX__][unit_price_foreign]" class="form-control decimal-input" data-field="unit_price_foreign" required>
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-6 col-12 po-import-only">
                        <div class="form-group">
                            <label>CBM / carton</label>
                            <input type="text" inputmode="decimal" name="lines[__INDEX__][cbm_per_carton]" class="form-control decimal-input" data-field="cbm_per_carton" placeholder="Ex : 0.085">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-6 col-12 po-import-only">
                        <div class="form-group">
                            <label>CBM / unité (calculé)</label>
                            <input type="text" class="form-control" data-field="cbm_per_unit_display" readonly style="background:#f5f5f5">
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="lines[__INDEX__][description]" class="form-control" data-field="description">
                        </div>
                    </div>
                </div>
                <div class="po-line-cost-preview" data-preview>
                    Coût de revient estimé : <strong data-preview-cost>0</strong> GNF / unité
                </div>
            </div>
        </template>

        <!-- Modale : nouvelle boutique -->
        <div class="modal fade" id="newStoreModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouvelle boutique</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div id="newStoreError" class="alert alert-danger" style="display:none;"></div>
                        <div class="form-group">
                            <label>Nom de la boutique</label>
                            <input type="text" id="newStoreName" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Ville</label>
                            <select id="newStorePlace" class="form-control">
                                <option value="">Sélectionner</option>
                                @foreach($places as $place)
                                    <option value="{{ $place->id }}">{{ $place->placeName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Responsable</label>
                            <select id="newStoreManager" class="form-control">
                                <option value="">Sélectionner</option>
                                @foreach($storeManagers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-submit" id="newStoreSubmit">Créer la boutique</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modale : nouveau produit -->
        <div class="modal fade" id="newProductModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nouveau produit</h5>
                        <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div id="newProductError" class="alert alert-danger" style="display:none;"></div>
                        <div class="form-group">
                            <label>Nom du produit</label>
                            <input type="text" id="newProductLibelle" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" id="newProductSku" class="form-control">
                        </div>
                        <p class="text-muted" style="font-size:13px;">
                            Le prix de revient n'est pas demandé ici : il sera calculé automatiquement
                            (avec le prix de vente, +30%) une fois cet achat enregistré.
                        </p>
                        <div class="form-group">
                            <label>Catégorie</label>
                            <select id="newProductCategory" class="form-control">
                                <option value="">Sélectionner</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->category_type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-submit" id="newProductSubmit">Créer le produit</button>
                    </div>
                </div>
            </div>
        </div>

        @php
            $existingLinesData = $order->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'cartons' => $item->cartons,
                    'pieces_per_carton' => $item->pieces_per_carton,
                    'unit_price_foreign' => $item->unit_price_foreign,
                    'unit_price_gnf' => $item->unit_price_gnf,
                    'cbm_per_carton' => $item->cbm_per_carton,
                    'description' => $item->description,
                ];
            })->all();
        @endphp
        @include('layouts.scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const linesContainer = document.getElementById('poLines');
            const template = document.getElementById('poLineTemplate');
            const addLineBtn = document.getElementById('addLineBtn');
            let lineIndex = 0;

            const existingLines = @json($existingLinesData);

            $('#newProductCategory').select2({
                width: '100%',
                placeholder: 'Rechercher une catégorie…',
                dropdownParent: $('#newProductModal'),
            });

            // ============================================================
            // Modales "Nouvelle boutique" / "Nouveau produit"
            // ============================================================
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function postJson(url, payload) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify(payload),
                }).then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                });
            }

            function showModalError(elId, message) {
                const el = document.getElementById(elId);
                el.textContent = message;
                el.style.display = 'block';
            }

            function hideModalError(elId) {
                document.getElementById(elId).style.display = 'none';
            }

            const newStoreModalEl = document.getElementById('newStoreModal');
            const newStoreModal = bootstrap.Modal.getOrCreateInstance(newStoreModalEl);
            const newProductModalEl = document.getElementById('newProductModal');
            const newProductModal = bootstrap.Modal.getOrCreateInstance(newProductModalEl);

            // --- Nouvelle boutique ---
            document.getElementById('newStoreSubmit').addEventListener('click', function () {
                hideModalError('newStoreError');
                postJson('{{ route('boutiques.quickCreate') }}', {
                    store_name: document.getElementById('newStoreName').value,
                    place_id: document.getElementById('newStorePlace').value,
                    user_id: document.getElementById('newStoreManager').value,
                }).then(function (result) {
                    if (!result.ok || !result.data.success) {
                        const errors = result.data.errors
                            ? Object.values(result.data.errors).flat().join(' ')
                            : (result.data.message || 'Erreur lors de la création.');
                        showModalError('newStoreError', errors);
                        return;
                    }

                    const store = result.data.store;
                    const storeSelect = document.getElementById('storeSelect');
                    const option = new Option(store.store_name, store.id, true, true);
                    storeSelect.add(option);
                    storeSelect.dispatchEvent(new Event('change', { bubbles: true }));

                    document.getElementById('newStoreName').value = '';
                    document.getElementById('newStorePlace').value = '';
                    document.getElementById('newStoreManager').value = '';
                    newStoreModal.hide();
                }).catch(function () {
                    showModalError('newStoreError', 'Erreur réseau, réessayez.');
                });
            });

            // --- Nouveau produit ---
            let activeProductSelect = null;

            newProductModalEl.addEventListener('show.bs.modal', function (event) {
                const opener = event.relatedTarget;
                const line = opener ? opener.closest('[data-line]') : null;
                activeProductSelect = line ? line.querySelector('[data-field="product_id"]') : null;
            });

            document.getElementById('newProductSubmit').addEventListener('click', function () {
                hideModalError('newProductError');
                postJson('{{ route('produits.quickCreate') }}', {
                    libelle: document.getElementById('newProductLibelle').value,
                    sku: document.getElementById('newProductSku').value,
                    category_id: document.getElementById('newProductCategory').value,
                }).then(function (result) {
                    if (!result.ok || !result.data.success) {
                        const errors = result.data.errors
                            ? Object.values(result.data.errors).flat().join(' ')
                            : (result.data.message || 'Erreur lors de la création.');
                        showModalError('newProductError', errors);
                        return;
                    }

                    const product = result.data.product;
                    const label = product.libelle + ' (' + product.sku + ')';

                    document.querySelectorAll('[data-field="product_id"]').forEach(function (select) {
                        select.add(new Option(label, product.id));
                    });
                    template.content.querySelectorAll('[data-field="product_id"]').forEach(function (select) {
                        select.add(new Option(label, product.id));
                    });

                    if (activeProductSelect) {
                        activeProductSelect.value = product.id;
                        activeProductSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    document.getElementById('newProductLibelle').value = '';
                    document.getElementById('newProductSku').value = '';
                    document.getElementById('newProductCategory').value = '';
                    newProductModal.hide();
                }).catch(function () {
                    showModalError('newProductError', 'Erreur réseau, réessayez.');
                });
            });

            // Champs prix/CBM/taux : on tape librement (virgule ou point), on ne garde
            // que les chiffres et un seul séparateur décimal (converti en point tout de
            // suite) — évite les soucis des inputs type=number avec le clavier/locale française.
            function sanitizeDecimal(el) {
                let value = el.value.replace(/[^0-9.,]/g, '').replace(',', '.');
                const firstDot = value.indexOf('.');
                if (firstDot !== -1) {
                    value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
                }
                el.value = value;
            }

            function wireDecimalInput(el) {
                el.addEventListener('input', function () {
                    sanitizeDecimal(el);
                    recompute();
                });
            }

            [document.getElementById('exchangeRate'), document.getElementById('transportCost'), document.getElementById('customsCost'), document.getElementById('otherFees')].forEach(wireDecimalInput);

            // Les messages d'erreur du serveur (ex: "Le taux de change est obligatoire")
            // restent affichés tant qu'on n'a pas resoumis le formulaire — on les cache
            // dès que l'utilisateur corrige le champ concerné, pour éviter la confusion.
            function clearErrorFor(id) {
                const span = document.querySelector('[data-error-for="' + id + '"]');
                if (span) {
                    span.style.display = 'none';
                }
            }
            ['currencyCode', 'exchangeRate'].forEach(function (id) {
                const el = document.getElementById(id);
                if (!el) return;
                const eventName = el.tagName === 'SELECT' ? 'change' : 'input';
                el.addEventListener(eventName, function () {
                    if (el.value) clearErrorFor(id);
                });
            });

            function setOriginClass() {
                const isChine = document.getElementById('originChine').checked;
                document.body.classList.toggle('origin-chine', isChine);
                document.querySelectorAll('.po-price-label').forEach(function (label) {
                    label.textContent = isChine ? 'Prix unitaire (devise)' : 'Prix unitaire (GNF)';
                });
                recompute();
            }

            document.getElementById('originGuinee').addEventListener('change', setOriginClass);
            document.getElementById('originChine').addEventListener('change', setOriginClass);

            // Rappel du taux de référence enregistré (CurrencySettingController) : reste
            // affiché même si l'utilisateur saisit un taux du jour différent au-dessus.
            function updateRateReference() {
                const select = document.getElementById('currencyCode');
                const ref = document.getElementById('rateReference');
                const selected = select.options[select.selectedIndex];
                const rate = selected ? selected.getAttribute('data-rate') : null;
                const updated = selected ? selected.getAttribute('data-updated') : null;

                if (!select.value || !rate) {
                    ref.textContent = '';
                    return;
                }

                const formattedRate = new Intl.NumberFormat('fr-FR').format(parseFloat(rate));
                ref.textContent = '💡 Taux de référence enregistré : 1 ' + select.value + ' = ' + formattedRate + ' GNF'
                    + (updated ? ' (mis à jour le ' + updated + ')' : '')
                    + '. Vous pouvez saisir un taux du jour différent ci-dessus si besoin.';
            }

            // On ne pré-remplit plus le taux avec la valeur de référence enregistrée —
            // l'utilisateur doit saisir lui-même le taux du jour (le rappel affiché en
            // dessous lui sert juste de repère).
            document.getElementById('currencyCode').addEventListener('change', function () {
                const rateInput = document.getElementById('exchangeRate');

                if (this.value === "") {
                    rateInput.value = "1";
                    rateInput.required = false;
                } else {
                    rateInput.value = "";
                    rateInput.required = true;
                }
                updateRateReference();
                recompute();
            });

            function addLine(data) {
                data = data || {};
                const html = template.innerHTML.replaceAll('__INDEX__', lineIndex);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                const lineEl = wrapper.firstElementChild;
                linesContainer.appendChild(lineEl);
                lineIndex++;

                if (data.product_id) {
                    lineEl.querySelector('[data-field="product_id"]').value = data.product_id;
                }
                if (data.cartons) {
                    lineEl.querySelector('[data-field="cartons"]').value = data.cartons;
                }
                if (data.pieces_per_carton) {
                    lineEl.querySelector('[data-field="pieces_per_carton"]').value = data.pieces_per_carton;
                }
                if (data.quantity) {
                    lineEl.querySelector('[data-field="quantity"]').value = data.quantity;
                }
                const isChineOrder = document.getElementById('originChine').checked;
                const priceField = lineEl.querySelector('[data-field="unit_price_foreign"]');
                if (isChineOrder && data.unit_price_foreign) {
                    priceField.value = data.unit_price_foreign;
                } else if (data.unit_price_gnf) {
                    priceField.value = data.unit_price_gnf;
                }
                if (data.cbm_per_carton) {
                    lineEl.querySelector('[data-field="cbm_per_carton"]').value = data.cbm_per_carton;
                }
                if (data.description) {
                    lineEl.querySelector('[data-field="description"]').value = data.description;
                }

                lineEl.querySelector('[data-remove-line]').addEventListener('click', function () {
                    if (linesContainer.querySelectorAll('[data-line]').length <= 1) {
                        return;
                    }
                    lineEl.remove();
                    recompute();
                });

                // Calcul automatique cartons × pièces = quantité (comme à la création).
                const cartonsField = lineEl.querySelector('[data-field="cartons"]');
                const piecesField = lineEl.querySelector('[data-field="pieces_per_carton"]');
                const quantityField = lineEl.querySelector('[data-field="quantity"]');

                function recalcQuantity() {
                    const cartons = parseInt(cartonsField.value) || 0;
                    const pieces = parseInt(piecesField.value) || 0;
                    if (cartons > 0 && pieces > 0) {
                        quantityField.value = cartons * pieces;
                        recompute();
                    }
                }
                cartonsField.addEventListener('input', recalcQuantity);
                piecesField.addEventListener('input', recalcQuantity);

                lineEl.querySelectorAll('input, select').forEach(function (el) {
                    if (el.classList.contains('decimal-input')) {
                        wireDecimalInput(el);
                    } else {
                        el.addEventListener('input', recompute);
                    }
                    el.addEventListener('change', recompute);
                });

                // Sélecteur de produit avec recherche — initialisé après avoir positionné
                // la valeur ci-dessus, pour que le produit déjà sélectionné s'affiche bien.
                $(lineEl).find('[data-field="product_id"]').select2({
                    width: '100%',
                    placeholder: 'Sélectionner le produit'
                });
            }

            function recompute() {
                const isChine = document.getElementById('originChine').checked;
                const currencySelect = document.getElementById('currencyCode');
                let rate = 1;
                if (currencySelect && currencySelect.value !== '') {
                    rate = parseFloat(document.getElementById('exchangeRate').value) || 1;
                }

                const transport = parseFloat(document.getElementById('transportCost').value) || 0;
                const customs = isChine ? (parseFloat(document.getElementById('customsCost').value) || 0) : 0;
                const otherFees = parseFloat(document.getElementById('otherFees').value) || 0;

                const lines = Array.from(linesContainer.querySelectorAll('[data-line]'));
                let totalCbm = 0;
                let grandTotalPrice = 0;

                const computed = lines.map(function (lineEl) {
                    const quantity = parseFloat(lineEl.querySelector('[data-field="quantity"]').value) || 0;
                    const unitPriceForeign = parseFloat(lineEl.querySelector('[data-field="unit_price_foreign"]').value) || 0;

                    // CBM saisi par carton, divisé par le nombre de pièces du carton pour
                    // obtenir le CBM exact d'une seule unité.
                    const piecesPerCarton = parseInt(lineEl.querySelector('[data-field="pieces_per_carton"]')?.value) || 0;
                    const cbmPerCarton = isChine ? (parseFloat(lineEl.querySelector('[data-field="cbm_per_carton"]')?.value) || 0) : 0;
                    const cbmPerUnit = (isChine && piecesPerCarton > 0) ? (cbmPerCarton / piecesPerCarton) : 0;

                    const cbmDisplay = lineEl.querySelector('[data-field="cbm_per_unit_display"]');
                    if (cbmDisplay) {
                        cbmDisplay.value = cbmPerUnit > 0 ? cbmPerUnit.toFixed(6) : '';
                    }

                    const lineTotalCbm = cbmPerUnit * quantity;
                    totalCbm += lineTotalCbm;

                    const unitPriceGnf = isChine ? unitPriceForeign * rate : unitPriceForeign;
                    const lineTotalPrice = unitPriceGnf * quantity;
                    grandTotalPrice += lineTotalPrice;

                    return { lineEl, quantity, unitPriceGnf, lineTotalCbm, lineTotalPrice };
                });

                let grandTotalCost = 0;
                computed.forEach(function (item) {
                    const share = totalCbm > 0 ? (item.lineTotalCbm / totalCbm) : (1 / computed.length);
                    const freightShare = transport * share;
                    const customsShare = customs * share;
                    const otherFeesShare = otherFees * share;
                    const landedTotal = item.lineTotalPrice + freightShare + customsShare + otherFeesShare;
                    const landedUnit = item.quantity > 0 ? landedTotal / item.quantity : 0;

                    grandTotalCost += landedTotal;

                    const preview = item.lineEl.querySelector('[data-preview-cost]');
                    if (preview) {
                        preview.textContent = new Intl.NumberFormat('fr-FR').format(Math.round(landedUnit));
                    }
                });

                document.getElementById('grandTotalCbm').textContent = totalCbm.toFixed(3);
                document.getElementById('grandTotalCost').textContent = new Intl.NumberFormat('fr-FR').format(Math.round(grandTotalCost));
            }

            addLineBtn.addEventListener('click', function () { addLine(); });

            setOriginClass();
            updateRateReference();
            document.getElementById('exchangeRate').required = document.getElementById('currencyCode').value !== "";
            if (existingLines.length > 0) {
                existingLines.forEach(function (line) { addLine(line); });
            } else {
                addLine();
            }
            recompute();
        });
        </script>
    </body>
</html>
