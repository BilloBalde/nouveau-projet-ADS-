<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            .form-section {
                border: 1px solid var(--adm-brand-200,#CED5AE);
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 24px;
                background: #fffdfb;
            }

            .form-section-title {
                font-size: 15px;
                font-weight: 700;
                color: var(--adm-brand-600,#3E4C21);
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .form-section-title i {
                font-size: 14px;
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
                            <h4>Gestion Produits</h4>
                            <h6>Ajout Produit</h6>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('produits.store') }}" method="post" id="Register" enctype="multipart/form-data">
                                @csrf

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-box"></i> Informations générales</div>
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="libelle">Libelle</label>
                                                <input type="text" id="libelle" name="libelle" class="form-control" value="{{ old('libelle') }}">
                                                @error('libelle')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="sku">Identifiant Stock</label>
                                                <input type="text" id="sku" name="sku" class="form-control" value="{{ old('sku') }}">
                                                @error('sku')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="categories">Categories:</label>
                                                <select name="categories[]" id="categories" multiple required class="form-control">
                                                    @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}">{{ $category->category_type }}</option>
                                                    @endforeach
                                                </select>
                                                @error('categories')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="store_id">Magasin</label>
                                                <select name="store_id" id="store_id" class="form-control">
                                                    <option value="">Sélectionner Magasin</option>
                                                    @foreach($stores as $store)
                                                        <option value="{{ $store->id }}">{{ $store->store_name ?? $store->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('store_id')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="stock_initial">Stock initial (en pièces)</label>
                                                <input type="number" id="stock_initial" name="stock_initial" class="form-control" value="{{ old('stock_initial') }}">
                                                @error('stock_initial')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea id="description" name="description" class="form-control">{{ old('description') }}</textarea>
                                                @error('description')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-tag"></i> Tarification</div>
                                    <div class="row">
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="price_sale">Prix de Revient par pièce (FG)</label>
                                                <input type="text" inputmode="decimal" id="price_sale" name="price_sale" class="form-control" value="{{ old('price_sale') }}" required>
                                                @error('price_sale')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                @if (\App\Support\ModeDeVente::parInventaire())
                                                {{-- Le chiffre d'affaires se déduit des comptages : ce prix en est la
                                                     seule source, c'est au gérant de le fixer, pas à une marge de le deviner. --}}
                                                <label for="price">Prix de Vente par pièce (FG) <small class="text-muted">— c'est lui qui valorise vos inventaires</small></label>
                                                <input type="number" min="0" step="1" id="price" name="price" class="form-control" value="{{ old('price') }}" required>
                                                @else
                                                <label for="price">Prix de Vente par pièce (FG) <small class="text-muted">— calculé automatiquement (+{{ (int) \App\Models\Product::margeDeVente() }}%)</small></label>
                                                <input type="text" id="price" name="price" class="form-control" value="{{ old('price') }}" readonly style="background:#f5f5f5">
                                                @endif
                                                @error('price')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        @include('products.partials.prix-minimum')
                                    </div>
                                </div>

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-image"></i> Image</div>
                                    <div class="form-group">
                                        <div class="image-upload">
                                            <input type="file" name="image" value="{{ old('image') }}">
                                            <div class="image-uploads">
                                                <img src="{{ asset('assets/img/icons/upload.svg') }}" alt="img">
                                                <h4>Glissez un fichier ici pour l'envoyer</h4>
                                            </div>
                                        </div>
                                        @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <x-form-actions cancel-route="{{ route('produits.index') }}"/>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                $('#categories').select2({
                    width: '100%',
                    placeholder: 'Rechercher une catégorie…',
                });

                const priceInput = document.getElementById('price');
                const priceSaleInput = document.getElementById('price_sale');

                // Prix de vente = prix de revient + la marge configurée (même règle que pour
                // le calcul du coût de revient à l'achat). Le serveur recalcule de toute
                // façon la valeur : ce champ n'est qu'un aperçu pour le gérant.
                const MARGE_DE_VENTE = {{ 1 + \App\Models\Product::margeDeVente() / 100 }};

                function recomputeSalePrice() {
                    // En mode inventaire le prix est saisi à la main : on ne
                    // l'écrase pas (le champ n'est alors pas en lecture seule).
                    if (!priceInput.readOnly) {
                        return;
                    }
                    const priceSale = parseFloat(priceSaleInput.value) || 0;
                    priceInput.value = priceSale > 0 ? Math.round(priceSale * MARGE_DE_VENTE) : '';
                }
                priceSaleInput.addEventListener('input', recomputeSalePrice);

                recomputeSalePrice();
            });
        </script>
    </body>
</html>
