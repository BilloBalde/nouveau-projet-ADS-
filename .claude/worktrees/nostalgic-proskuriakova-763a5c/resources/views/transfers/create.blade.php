<div class="modal fade" id="addTransfer" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Infos du Transfert</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('transfers.store') }}" method="post">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="product_id">Produit</label>
                                <select name="product_id" id="product_id" class="form-control">
                                    <option value="">Selectionner le Produit</option>
                                    @foreach ($produits as $item)
                                    @php
                                        $product = App\Models\Product::with('categories')->find($item->id);
                                    @endphp
                                    <option value="{{ $item->id }}">
                                        {{ $item->libelle }}-
                                        @foreach ($product->categories as $category)
                                        {{ $category->slug . ' (' . $category->category_type . ')' }}
                                        @endforeach
                                    </option>
                                    @endforeach
                                </select>
                                @error('store_id')
                                    <span class="text-error"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="from_store_id">Source</label>
                                <select id="from_store_id" name="from_store_id" class="form-control">
                                    <option value="">Choisir Stock</option>
                                    @foreach($boutiques as $boutique)
                                    <option value="{{ $boutique->id }}">{{ $boutique->store_name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger">
                                    <strong class="from_store_id-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="to_store_id">Destination</label>
                                <select id="to_store_id" name="to_store_id" class="form-control">
                                    <option value="">Choisir Stock</option>
                                    @foreach($boutiques as $boutique)
                                    <option value="{{ $boutique->id }}">{{ $boutique->store_name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger">
                                    <strong class="to_store_id-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="quantity">Quantité</label>
                                <input type="text" name="quantity" id="quantity" class="form-control">
                                <span class="text-danger">
                                    <strong id="quantity-error"></strong>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ">
                    <button type="submit" class="btn btn-submit">Confirm</button>
                    <button type="reset" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
