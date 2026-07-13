@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Gestion des Ventes</h4>
            <h6>Ajouter Vente</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form action="{{ route('sales.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div id="sales-container">
                        <div class="sale-entry">
                            <div class="row">
                                <!-- Hidden field for numeroPurchase -->
                                <div class="col-lg-6 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label for="numeroFacture">numero Facture</label>
                                        <input type="text" name="sales[0][numeroFacture]" id="numeroFacture"  value="{{ $numero_facture }}" class="form-control" readonly>
                                    </div>
                                    @error('numeroFacture')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <input type="text" name="sales[0][store_id]" id="store_id" class="form-control" value="{{ $store_id }}" hidden>
                                <div class="col-lg-6 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label for="product_id">Produit</label>
                                        <select name="sales[0][product_id]" id="product_id" class="form-control">
                                            <option value="">Select le Produit</option>
                                            @foreach ($produits as $item)
                                            @if (Auth::user()->role_id !== 3)
                                            @php
                                            $storeProd = App\Models\StoreProduct::where('product_id', $item->id)->first();
                                                $productQtityglo = $storeProd ? $storeProd->quantity : 0;
                                            @endphp
                                            <option value="{{ $item->id }}">
                                                {{ $item->libelle }} | Stock : {{ $productQtityglo }}
                                            </option>
                                            @else
                                            @php
                                                $prod = App\Models\StoreProduct::where('product_id', $item->id)->where('store_id', $store_id)->first();
                                            @endphp
                                                @if ($prod)
                                            @php
                                                $productQtity = $prod->quantity;
                                            @endphp
                                                @if($productQtity > 0)
                                                <option value="{{ $item->id }}">
                                                    {{ $item->libelle }} | Stock : {{ $productQtity }}
                                                </option>
                                                @endif
                                            @endif
                                            @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('product_id')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label for="quantity">Quantité</label>
                                        <input type="text" name="sales[0][quantity]" id="quantity" class="form-control quantity">
                                    </div>
                                    @error('quantity')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-6 col-sm-6 col-12">
                                    <div class="form-group">
                                        <label for="prix">Prix</label>
                                        <input type="text" name="sales[0][prix]" id="prix" class="form-control prix">
                                    </div>
                                    @error('prix')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <hr>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 col-sm-4 col-12">
                            <div class="form-group">
                                <button type="button" id="add-sale" class="btn btn-secondary">Ajouter Autre Ligne</button>
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-4 col-12">
                            <div class="form-group mt-3">
                                <label for="totalQuantity">Total Quantité</label>
                                <input type="text" id="totalQuantity" class="form-control" readonly>
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-4 col-12">
                            <div class="form-group mt-3">
                                <label for="totalAmount">Total Montant</label>
                                <input type="text" id="totalAmount" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-submit me-2" id="soumission">Soumettre</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    function updateTotals() {
        let totalQuantity = 0;
        let totalAmount = 0;

        document.querySelectorAll('.sale-entry').forEach(entry => {
            const quantity = parseFloat(entry.querySelector('.quantity').value) || 0;
            const price = parseFloat(entry.querySelector('.prix').value) || 0;

            totalQuantity += quantity;
            totalAmount += quantity * price;
        });

        document.getElementById('totalQuantity').value = totalQuantity;
        document.getElementById('totalAmount').value = totalAmount.toFixed(2);
    }

    document.getElementById('add-sale').addEventListener('click', function() {
        const container = document.getElementById('sales-container');
        const index = container.getElementsByClassName('sale-entry').length;
        const newEntry = document.querySelector('.sale-entry').cloneNode(true);
        newEntry.querySelectorAll('input, select, textarea').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
            if (input.name.includes('numeroFacture')) {
                input.value = document.getElementById('numeroFacture').value;
            } else if (input.name.includes('store_id')) {
                input.value = document.getElementById('store_id').value;
            } else {
                input.value = '';
            }
        });
        newEntry.querySelector('.quantity').addEventListener('input', updateTotals);
        newEntry.querySelector('.prix').addEventListener('input', updateTotals);

        container.appendChild(newEntry);
        updateTotals();
    });

    document.querySelectorAll('.quantity, .prix').forEach(input => {
        input.addEventListener('input', updateTotals);
    });

    window.addEventListener('load', updateTotals);
</script>
<script>
    let shouldConfirmExit = true;

    // Function to open the exit confirmation modal
    function showExitModal(event) {
        if (shouldConfirmExit) {
            // Prevent the default action (navigating away)
            event.preventDefault();
            event.stopPropagation();

            // Show the modal
            $('#exitModal').modal('show');
        }
    }

    // Add event listener for all links (you can customize this to include specific actions)
    document.querySelectorAll('a, button').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (element.id !== 'soumission' && element.id !== 'add-sale') {
                showExitModal(event);  // Show the modal when user attempts to navigate (except submit button)
            }
        });
    });

    // Disable confirmation when form is submitted
    document.getElementById('exitForm').addEventListener('submit', function () {
        shouldConfirmExit = false; // Allow form submission without the modal
    });

    // Handle the back and forward button navigation
    window.addEventListener('popstate', function (event) {
        showExitModal();  // Show the modal when the back or forward buttons are clicked
    });
</script>
@include('sales.exitmodal')
@endsection
