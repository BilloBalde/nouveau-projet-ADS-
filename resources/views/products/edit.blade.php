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
                            <h4>Modification Produit</h4>
                            <h6>Modifier le Produit</h6>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('produits.update', $product->id) }}" method="POST" id="Register" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="id" value="{{ $product->id }}">

                                <div class="form-section">
                                    <div class="form-section-title"><i class="fa fa-box"></i> Informations générales</div>
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="libelle">Libelle</label>
                                                <input type="text" id="libelle" name="libelle" class="form-control" value="{{ old('libelle', $product->libelle) }}" required>
                                                @error('libelle')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="sku">Identifiant Stock</label>
                                                <input type="text" id="sku" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                                                @error('sku')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="categories">Categories:</label>
                                                <select name="categories[]" id="categories" multiple class="form-control" required>
                                                    @foreach ($categories as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ in_array($category->id, $product->categories->pluck('id')->toArray()) ? 'selected' : '' }}>
                                                        {{ $category->category_type }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                                @error('categories')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            @if ($userStoreId)
                                                {{-- Locked to a single store --}}
                                                <div class="mb-2">
                                                    <strong>Magasin :</strong>
                                                    {{ \App\Models\Store::find($userStoreId)?->store_name ?? 'N/A' }}
                                                </div>
                                                <div class="mb-2">
                                                    <strong>Stock dans ce magasin :</strong>
                                                    <span id="stockForStore">{{ $quantityForUser }}</span> pièces
                                                </div>
                                                <div class="text-muted">
                                                    <strong>Stock total (tous magasins) :</strong>
                                                    <span>{{ $totalQty }}</span> pièces
                                                </div>
                                                <input type="hidden" id="store_id" value="{{ $userStoreId }}">
                                            @else
                                                {{-- Multi-store: let user pick a store and show its stock --}}
                                                <div class="row g-3 align-items-end">
                                                    <div class="col-md-6">
                                                        <label for="store_id" class="form-label">Magasin</label>
                                                        <select id="store_id" name="store_id" class="form-control">
                                                            <option value="">-- Sélectionner magasin --</option>
                                                            @foreach ($stores as $s)
                                                                <option value="{{ $s->id }}">{{ $s->store_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('store_id')
                                                        <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div>
                                                            <strong>Stock dans ce magasin :</strong>
                                                            <span id="stockForStore">0</span> pièces
                                                        </div>
                                                        <div class="text-muted">
                                                            <strong>Stock total (tous magasins) :</strong>
                                                            <span>{{ $totalQty }}</span> pièces
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-6">
                                            <div class="form-group">
                                                <label for="stock_restant">Stock global (en pièces)</label>
                                                <input type="number" id="stock_restant" name="stock_restant" class="form-control" value="{{ old('stock_restant', $totalQty) }}">
                                                @error('stock_restant')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea id="description" name="description" class="form-control" required>{{ old('description', $product->description) }}</textarea>
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
                                                <label for="price">Prix de Vente par pièce (FG)</label>
                                                <input type="text" id="price" name="price" class="form-control" value="{{ old('price', $product->price) }}">
                                                @error('price')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="price_sale">Prix de Revient par pièce (FG)</label>
                                                <input type="text" id="price_sale" name="price_sale" class="form-control" value="{{ old('price_sale', $product->price_sale ?? 0) }}">
                                                @error('price_sale')
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
                                        @if ($product->image)
                                            <img src="{{ asset('products/' . $product->image) }}" alt="product image" style="width: 150px; height: auto; border: 1px solid #ccc; margin-bottom: 10px;">
                                        @else
                                            <p>Aucune Image associated</p>
                                        @endif

                                        <input type="file" name="image" id="image" class="form-control">
                                        <small class="form-text text-muted">Mettre a jour l'image of product.</small>
                                        @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <x-form-actions mode="edit" cancel-route="{{ route('produits.index') }}"/>
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

                // Preloaded per-store quantities from controller
                const perStore = @json($perStoreQuantities);
                const storeSelect = document.getElementById('store_id');
                const out = document.getElementById('stockForStore');

                function updateQty() {
                    const id = storeSelect?.value || storeSelect?.getAttribute('value');
                    const qty = (id && perStore[id]) ? perStore[id] : 0;
                    if (out) out.textContent = qty;
                }

                if (storeSelect) {
                    // initialize if a value is already set
                    updateQty();
                    storeSelect.addEventListener('change', updateQty);
                }

            });
        </script>
    </body>
</html>
