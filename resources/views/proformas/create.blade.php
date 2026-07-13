<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<body>
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
                        <h4>Ajout Achat</h4>
                    </div>
                    <div class="page-btn">
                        <a href="{{ url()->previous() }}" class="btn btn-added">
                            <img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2">
                        </a>
                    </div>
                </div>
                @include('layouts.flash')
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('commandes.store') }}" id="commandForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <meta name="csrf-token" content="{{ csrf_token() }}">
                            <div class="row">
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="store_id">Magasin</label>
                                        <select name="store_id" id="store_id" class="form-control">
                                            <option value="">Sélectionner Magasin</option> <!-- Ajout d'une valeur vide -->
                                            @if ($userStoreId)
                                                <option value="{{ $userStoreId }}" selected>{{ App\Models\Store::find($userStoreId)->store_name }}</option>
                                            @else
                                            @foreach ($shops as $item)
                                                <option value="{{ $item->id }}">{{ $item->store_name }}</option>
                                            @endforeach
                                            @endif
                                        </select>
                                        @error('store_id')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="date_achat">Date Achat</label>
                                        <input type="date" class="form-control" id="date_achat" name="date_achat" placeholder="Date Proforma" value="{{ old('date_achat', now()->toDateString()) }}">
                                        @error('date_proforma')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="shippment">Frais Transport, DéDouanement et Divers</label>
                                        <input type="text" class="form-control" id="shippment" name="shippment" placeholder="Frais transport dedouanement et Divers" value="{{ old('shippment') }}">
                                        @error('shippement')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Table for adding products dynamically -->
                            <div class="row">
                                <div class="table-responsive mb-3">
                                    <table class="table" id="orderTable">
                                        <thead>
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Category</th>
                                                <th>Cartons</th>
                                                <th>Prix Achat</th>
                                                <th>Prix Revient</th>
                                                <th>Prix Vente</th>
                                                <th>Image</th>
                                                <th>Subtotal</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Dynamic Rows Will Be Added Here -->
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-right"><strong>Totaux</strong></th>
                                                <th id="totalCarton" class="text-right" style="font-weight:bold;">0</th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th></th>
                                                <th id="totalSubtotal" class="text-right" style="font-weight:bold;">0.00</th>
                                                <input type="hidden" name="total_subtotal" id="total_subtotal" />FG
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Add Row Button -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <a href="javascript:void(0);" class="btn btn-primary" id="addRowButton">Ajouter Ligne</a>
                                </div>
                            </div>

                            <!-- Submit / Cancel -->
                            <div class="col-lg-12 mt-3">
                                <button type="submit" id="submitFormbutton" class="btn btn-submit me-2">Soumettre</button>
                                {{-- <a href="javascript:void(0);" class="btn btn-cancel">Cancel</a> --}}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">Avertissement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Veuillez sélectionner un magasin ou entrer la date, transport avant de soumettre.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
    @include('proformas.footer')
</body>
</html>
