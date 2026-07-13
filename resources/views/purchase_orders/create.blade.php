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
            .origin-toggle input:checked + span {
                color: #c1682f;
            }
            .origin-toggle label:has(input:checked) {
                border-color: #c1682f;
                background: #fbf3ea;
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
                            <h6>Nouvel achat / import</h6>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <form action="{{ route('purchase-orders.store') }}" method="POST" id="poForm">
                        @csrf

                        <div class="po-section">
                            <div class="po-section-title">Informations générales</div>
                            <div class="row">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Référence</label>
                                        <input type="text" name="reference" class="form-control" value="{{ old('reference', $reference) }}" readonly>
                                        @error('reference') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Magasin de destination</label>
                                        <select name="store_id" class="form-control" required>
                                            <option value="">Sélectionner</option>
                                            @foreach($stores as $store)
                                                <option value="{{ $store->id }}">{{ $store->store_name ?? $store->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('store_id') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Date d'émission</label>
                                        <input type="date" name="date_emis" class="form-control" value="{{ old('date_emis', now()->toDateString()) }}" required>
                                        @error('date_emis') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Date de réception (optionnel)</label>
                                        <input type="date" name="date_recu" class="form-control" value="{{ old('date_recu') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Origine de l'achat</label>
                                <div class="origin-toggle">
                                    <label>
                                        <input type="radio" name="origin" value="guinee" id="originGuinee" {{ old('origin', 'guinee') == 'guinee' ? 'checked' : '' }}>
                                        <span>🇬🇳 Achat local (Guinée)</span>
                                    </label>
                                    <label>
                                        <input type="radio" name="origin" value="chine" id="originChine" {{ old('origin') == 'chine' ? 'checked' : '' }}>
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
                                                <option value="{{ $currency->currencyCode }}" data-rate="{{ $currency->rate_to_gnf }}">
                                                    {{ $currency->currencyName }} ({{ $currency->currencyCode }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('currency_code') <span class="text-danger" data-error-for="currencyCode">{{ $message }}</span> @enderror
                                        @if($currencies->isEmpty())
                                            <span class="text-danger">Aucun taux configuré — <a href="{{ route('currencySetting.index') }}">configurez-les ici</a>.</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Taux du jour (1 unité = X GNF)</label>
                                        <input type="text" inputmode="decimal" name="exchange_rate_used" id="exchangeRate" class="form-control decimal-input" value="{{ old('exchange_rate_used') }}">
                                        @error('exchange_rate_used') <span class="text-danger" data-error-for="exchangeRate">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="po-section">
                            <div class="po-section-title">Frais de l'arrivage</div>
                            <div class="row">
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Transport (GNF)</label>
                                        <input type="text" inputmode="decimal" name="transport_cost_gnf" id="transportCost" class="form-control decimal-input" value="{{ old('transport_cost_gnf', 0) }}">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12 po-import-only">
                                    <div class="form-group">
                                        <label>Douane / Dédouanement (GNF)</label>
                                        <input type="text" inputmode="decimal" name="customs_cost_gnf" id="customsCost" class="form-control decimal-input" value="{{ old('customs_cost_gnf', 0) }}">
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label>Frais divers (GNF)</label>
                                        <input type="text" inputmode="decimal" name="other_fees_gnf" id="otherFees" class="form-control decimal-input" value="{{ old('other_fees_gnf', 0) }}">
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
                            <textarea name="notes" class="form-control">{{ old('notes') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-submit">Enregistrer l'achat</button>
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-cancel">Annuler</a>
                    </form>
                </div>
            </div>
        </div>

        <template id="poLineTemplate">
            <div class="po-line" data-line>
                <button type="button" class="po-line-remove" data-remove-line title="Retirer"><i class="fa fa-trash"></i></button>
                <div class="row">
                    <div class="col-lg-4 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Produit</label>
                            <select name="lines[__INDEX__][product_id]" class="form-control" data-field="product_id" required>
                                <option value="">Sélectionner le produit</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->libelle }} ({{ $product->sku }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Quantité</label>
                            <input type="number" min="1" name="lines[__INDEX__][quantity]" class="form-control" data-field="quantity" required>
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
                            <label>CBM / unité</label>
                            <input type="text" inputmode="decimal" name="lines[__INDEX__][cbm_per_unit]" class="form-control decimal-input" data-field="cbm_per_unit">
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

        @include('layouts.scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const linesContainer = document.getElementById('poLines');
            const template = document.getElementById('poLineTemplate');
            const addLineBtn = document.getElementById('addLineBtn');
            let lineIndex = 0;

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

            document.getElementById('currencyCode').addEventListener('change', function () {
                const selected = this.options[this.selectedIndex];
                const rate = selected ? selected.getAttribute('data-rate') : '';
                if (rate) {
                    document.getElementById('exchangeRate').value = rate;
                }
                recompute();
            });

            function addLine() {
                const html = template.innerHTML.replaceAll('__INDEX__', lineIndex);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                const lineEl = wrapper.firstElementChild;
                linesContainer.appendChild(lineEl);
                lineIndex++;

                lineEl.querySelector('[data-remove-line]').addEventListener('click', function () {
                    if (linesContainer.querySelectorAll('[data-line]').length <= 1) {
                        return; // garder au moins une ligne
                    }
                    lineEl.remove();
                    recompute();
                });

                lineEl.querySelectorAll('input, select').forEach(function (el) {
                    if (el.classList.contains('decimal-input')) {
                        wireDecimalInput(el);
                    } else {
                        el.addEventListener('input', recompute);
                    }
                    el.addEventListener('change', recompute);
                });

                setOriginClass();
            }

            function recompute() {
                const isChine = document.getElementById('originChine').checked;
                const rate = parseFloat(document.getElementById('exchangeRate').value) || (isChine ? 0 : 1);
                const transport = parseFloat(document.getElementById('transportCost').value) || 0;
                const customs = isChine ? (parseFloat(document.getElementById('customsCost').value) || 0) : 0;

                const lines = Array.from(linesContainer.querySelectorAll('[data-line]'));
                let totalCbm = 0;
                const computed = lines.map(function (lineEl) {
                    const quantity = parseFloat(lineEl.querySelector('[data-field="quantity"]').value) || 0;
                    const unitPriceForeign = parseFloat(lineEl.querySelector('[data-field="unit_price_foreign"]').value) || 0;
                    const cbmField = lineEl.querySelector('[data-field="cbm_per_unit"]');
                    const cbmPerUnit = isChine ? (parseFloat(cbmField?.value) || 0) : 0;
                    const lineTotalCbm = cbmPerUnit * quantity;
                    totalCbm += lineTotalCbm;

                    const unitPriceGnf = isChine ? unitPriceForeign * rate : unitPriceForeign;

                    return { lineEl, quantity, unitPriceGnf, lineTotalCbm };
                });

                let grandTotalCost = 0;
                computed.forEach(function (item) {
                    const share = totalCbm > 0 ? (item.lineTotalCbm / totalCbm) : 0;
                    const freightShare = transport * share;
                    const customsShare = customs * share;
                    const landedTotal = (item.unitPriceGnf * item.quantity) + freightShare + customsShare;
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

            addLineBtn.addEventListener('click', addLine);
            addLine(); // au moins une ligne au chargement
            setOriginClass();
        });
        </script>
    </body>
</html>
