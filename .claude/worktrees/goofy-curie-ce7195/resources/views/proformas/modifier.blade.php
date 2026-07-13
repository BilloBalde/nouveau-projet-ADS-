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
                        <h4>Ajout Proforma</h4>
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
                        <form action="{{ route('commandes.updateInvoice', $proforma->id) }}" id="commandForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <input type="text" id="proformaId" name="proformaId" value="{{ $proforma->id }}" hidden>
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="identifier">Identifier</label>
                                        <input type="text" name="identifier" id="identifier" class="form-control" value="{{ $proforma->identifier }}" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="date_achat">Date Proforma</label>
                                        <input type="date" class="form-control" id="date_achat" name="date_achat" placeholder="Date Proforma" value="{{ $proforma->date_achat }}" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-4 col-12">
                                    <div class="form-group">
                                        <label for="shippment">Transport</label>
                                        <input type="text" class="form-control" id="shippment" name="shippment" placeholder="Frais Divers" value="{{ old('shippment', $proforma->shippment) }}">
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
                                                <th>#</th>
                                                <th>Product Name</th>
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
                                            @foreach ($ligneCommandes as $index => $ligne)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><input type="text" name="products[{{ $index }}][productName]" class="form-control item_no" value="{{ $ligne->product->libelle }}" readonly></td>
                                                <td><input type="text" name="products[{{ $index }}][cartons]" class="form-control cartons" value="{{ $ligne->cartons }}"></td>
                                                <td><input type="text" name="products[{{ $index }}][unit_price_purchase]" class="form-control unit_price_purchase" value="{{ $ligne->unit_price_purchase }}"></td>
                                                <td><input type="text" name="products[{{ $index }}][unit_price_sale]" class="form-control unit_price_sale" value="{{ $ligne->unit_price_sale }}"></td>
                                                <td><input type="text" name="products[{{ $index }}][ctn_price_sale]" class="form-control ctn_price_sale" value="{{ $ligne->ctn_price_sale }}"></td>
                                                <td>
                                                    @if ($ligne->product->image)
                                                        <img src="{{ asset('products/' . $ligne->product->image) }}" alt="Image" width="50">
                                                    @endif
                                                    <input type="file" name="products[{{ $index }}][image]" id="image">
                                                </td>
                                                <td><input type="text" name="products[{{ $index }}][subtotal]" class="form-control subtotal" value="{{ $ligne->total_price_purchase }}" readonly></td>
                                                <td><a href="javascript:void(0);" class="btn btn-danger removeRowButton"><i class="fas fa-trash-alt"></i></a></td>
                                            </tr>
                                            @endforeach
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
                                                <input type="hidden" name="total_subtotal" id="total_subtotal" />
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Submit / Cancel -->
                            <div class="col-lg-12 mt-3">
                                <button type="submit" id="submitFormbutton" class="btn btn-submit me-2">Soumettre</button>
                                {{-- <a href="javascript:void(0);" class="btn btn-cancel" data-bs-toggle="modal" data-bs-target="#confirmationModal">Annuler</a> --}}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmationModalLabel">Confirm Action</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Voulez-vous vraiment annuler?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
            <button type="button" class="btn btn-danger" id="confirmCancel">Oui, Annuler</button>
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
                    Veuillez entrer la commission, ou frais supp avant de soumettre.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="alertModalImage" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alertModalLabel">INFORMATION</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
    <!-- JavaScript for Modal and Table Management -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Store calculation timeout for debouncing
        let calculationTimeout;
        let rowIndex = document.querySelectorAll('#orderTable tbody tr').length;
        const proformaId = document.getElementById('proformaId')?.value;

        // =============== VALIDATION FUNCTIONS ===============
        function validateNumericInput(input) {
            const value = parseFloat(input.value);
            if (isNaN(value) || value <= 0) {
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
                return false;
            } else {
                input.classList.add('is-valid');
                input.classList.remove('is-invalid');
                return true;
            }
        }

        function validateRequiredField(input) {
            if (input.value.trim() === '') {
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
                return false;
            } else {
                input.classList.add('is-valid');
                input.classList.remove('is-invalid');
                return true;
            }
        }

        function showModal(message) {
            document.getElementById('modalMessage').textContent = message;
            const alertModal = new bootstrap.Modal(document.getElementById('alertModalImage'));
            alertModal.show();
        }

        // =============== CALCULATION FUNCTIONS ===============
        function calculateUnitSalePrice(unitPurchasePrice, totalCartons, shippingCost) {
            if (totalCartons === 0 || isNaN(totalCartons) || totalCartons <= 0) return unitPurchasePrice;
            return unitPurchasePrice + (shippingCost / totalCartons);
        }

        function updateAllRows() {
            // Get current shipping cost
            const shippingCost = parseFloat(document.getElementById('shippment')?.value) || 0;
            let totalCartons = 0;
            
            // First pass: calculate total cartons from ALL rows
            document.querySelectorAll('#orderTable tbody tr').forEach(row => {
                const cartons = parseFloat(row.querySelector('.cartons')?.value) || 0;
                totalCartons += cartons;
            });
            
            // Update total cartons display
            const totalCartonEl = document.getElementById('totalCarton');
            if (totalCartonEl) totalCartonEl.textContent = totalCartons;
            
            // Second pass: update unit sale prices and subtotals for ALL rows
            document.querySelectorAll('#orderTable tbody tr').forEach(row => {
                const unitPurchasePrice = parseFloat(row.querySelector('.unit_price_purchase')?.value) || 0;
                const cartons = parseFloat(row.querySelector('.cartons')?.value) || 0;
                
                // Calculate unit sale price based on this row's purchase price and GLOBAL total cartons
                const unitSalePrice = calculateUnitSalePrice(unitPurchasePrice, totalCartons, shippingCost);
                
                // Update unit sale price field
                const unitSaleField = row.querySelector('.unit_price_sale');
                if (unitSaleField) {
                    unitSaleField.value = unitSalePrice.toFixed(2);
                }
                
                // Update subtotal for this row (cartons * unit_price_sale)
                const subtotal = cartons * unitSalePrice;
                const subtotalField = row.querySelector('.subtotal');
                if (subtotalField) {
                    subtotalField.value = subtotal.toFixed(2);
                }
            });
            
            // Update grand total
            updateGrandTotal();
        }

        function updateGrandTotal() {
            let totalSubtotal = 0;
            
            document.querySelectorAll('#orderTable tbody tr').forEach(row => {
                const subtotal = parseFloat(row.querySelector('.subtotal')?.value) || 0;
                totalSubtotal += subtotal;
            });
            
            const totalSubtotalEl = document.getElementById('totalSubtotal');
            const totalSubtotalInput = document.getElementById('total_subtotal');
            
            if (totalSubtotalEl) totalSubtotalEl.textContent = totalSubtotal.toFixed(2);
            if (totalSubtotalInput) totalSubtotalInput.value = totalSubtotal.toFixed(2);
        }

        // Single function to trigger recalculation of ALL rows with debouncing
        function recalculateAllRows() {
            clearTimeout(calculationTimeout);
            calculationTimeout = setTimeout(() => {
                updateAllRows();
            }, 50);
        }

        // Update subtotal for backward compatibility (now just calls recalculateAllRows)
        function updateSubtotal(row) {
            recalculateAllRows();
        }

        // Update total for backward compatibility
        function updateTotal() {
            recalculateAllRows();
        }

        // =============== PRODUCT FETCHING ===============
        function fetchProductDetails(productName, row) {
            fetch(`/fetch-product-details?productName=${encodeURIComponent(productName)}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.product) {
                    const product = data.product;

                    // Show product image if available
                    const imagePreview = row.querySelector('.image-preview');
                    if (imagePreview && product.image) {
                        const imageUrl = `/products/${product.image}`;
                        imagePreview.src = imageUrl;
                        imagePreview.style.display = 'block';
                    } else if (imagePreview) {
                        imagePreview.style.display = 'none';
                    }

                    // Recalculate all rows after fetching product details
                    recalculateAllRows();
                }
            })
            .catch(error => console.error('Error fetching product details:', error));
        }

        // =============== IMAGE PREVIEW ===============
        document.querySelector('#orderTable tbody').addEventListener('change', function (event) {
            if (event.target.type === 'file') {
                const fileInput = event.target;
                const file = fileInput.files[0];
                const previewElement = fileInput.closest('tr').querySelector('.image-preview');

                if (file && previewElement) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewElement.src = e.target.result;
                        previewElement.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // Create image preview if it doesn't exist
        document.querySelector('#orderTable tbody').addEventListener('change', function (event) {
            if (event.target.type === 'file') {
                const fileInput = event.target;
                const file = fileInput.files[0];

                if (file) {
                    let previewElement = fileInput.closest('tr').querySelector('.image-preview');
                    if (!previewElement) {
                        previewElement = document.createElement('img');
                        previewElement.classList.add('image-preview');
                        previewElement.style.maxWidth = '80px';
                        previewElement.style.maxHeight = '80px';
                        previewElement.style.display = 'block';
                        previewElement.style.marginTop = '5px';
                        fileInput.parentNode.appendChild(previewElement);
                    }

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewElement.src = e.target.result;
                        previewElement.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        // =============== EVENT LISTENERS ===============
        // Main input event listener
        document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
            const input = event.target;
            const row = input.closest('tr');

            // Validate product name
            if (input.name && input.name.includes('[productName]')) {
                validateRequiredField(input);
            }

            // Validate numeric fields
            if (input.classList.contains('cartons') || 
                input.classList.contains('unit_price_purchase') || 
                input.classList.contains('unit_price_sale') || 
                input.classList.contains('ctn_price_sale')) {
                validateNumericInput(input);
            }

            // Fetch product details when product name has at least 3 characters
            if (input.name && input.name.includes('[productName]') && input.value.length >= 3) {
                fetchProductDetails(input.value, row);
            }

            // ANY change to cartons or unit_price_purchase affects ALL rows
            if (input.classList.contains('cartons') || 
                input.classList.contains('unit_price_purchase')) {
                recalculateAllRows();
            }
        });

        // Shipping cost change listener - affects ALL rows
        const shippmentInput = document.getElementById('shippment');
        if (shippmentInput) {
            shippmentInput.addEventListener('input', function() {
                recalculateAllRows();
            });
        }

        // Remove row
        document.querySelector('#orderTable tbody').addEventListener('click', function (event) {
            if (event.target.closest('.removeRowButton')) {
                event.target.closest('tr').remove();
                recalculateAllRows(); // Recalculate ALL rows after removal
            }
        });

        // =============== FORM SUBMISSION ===============
        $(document).ready(function () {
            $('#commandForm').on('submit', function (e) {
                e.preventDefault();

                let formData = new FormData(this);
                
                if (!proformaId) {
                    showModal("ID de la proforma manquant.");
                    return;
                }

                $.ajax({
                    url: `/proformas/updating/${proformaId}`,
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function () {
                        $('#preloaderOverlay').show();
                        $('#submitFormbutton').prop('disabled', true);
                    },
                    success: function (response) {
                        $('#preloaderOverlay').hide();
                        $('#submitFormbutton').prop('disabled', false);
                        showModal('Proforma mise à jour avec succès.');
                    },
                    error: function (xhr) {
                        $('#preloaderOverlay').hide();
                        $('#submitFormbutton').prop('disabled', false);
                        
                        let message = 'Une erreur est survenue.';

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            message = '';
                            $.each(errors, function (key, value) {
                                message += value + '<br>';
                            });
                        } else if (xhr.responseJSON && xhr.responseJSON.error) {
                            message = xhr.responseJSON.error;
                        } else {
                            message = xhr.statusText || message;
                        }

                        showModal(message);
                    }
                });
            });
        });

        // Initialize calculations
        recalculateAllRows();
    });
    </script>
</body>
</html>
