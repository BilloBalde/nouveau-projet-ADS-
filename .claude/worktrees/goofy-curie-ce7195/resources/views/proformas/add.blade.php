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
                        <form action="{{ route('commandes.addInvoice', $proforma->id) }}" id="commandForm" method="POST" enctype="multipart/form-data">
                            <meta name="csrf-token" content="{{ csrf_token() }}">
                            @csrf
                            <div class="row">
                                <div class="col-lg-1 col-sm-1 col-12">
                                    <div class="form-group">
                                        <label for="customer_id">Customer</label>
                                        <input type="text" name="customer_id" id="customer_id" class="form-control" value="{{ $proforma->customer_id }}" readonly>
                                        <input type="hidden" name="proformaId" id="proformaId" class="form-control" value="{{ $proforma->id }}">
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-2 col-12">
                                    <div class="form-group">
                                        <label for="identifier">Identifier</label>
                                        <input type="text" name="identifier" id="identifier" class="form-control" value="{{ $proforma->identifier }}" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="date_proforma">Date Proforma</label>
                                        <input type="date" class="form-control" id="date_proforma" name="date_proforma" placeholder="Date Proforma" value="{{ $proforma->date_proforma }}" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-2 col-12">
                                    <div class="form-group">
                                        <label for="commission_rate">Pourcentage Commission</label>
                                        <input type="text" class="form-control" id="commission_rate" name="commission_rate" placeholder="Pourcentage Commission" value="{{ old('commission_rate', $proforma->commission_rate) }}">
                                        @error('commission_rate')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-2 col-12">
                                    <div class="form-group">
                                        <label for="bonus_percent">Pourcentage Bonus</label>
                                        <input type="text" class="form-control" id="bonus_percent" name="bonus_percent" placeholder="Pourcentage bonus" value="{{ old('bonus_percent', $proforma->bonus_percentage) }}">
                                        @error('bonus_percent')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-2 col-12">
                                    <div class="form-group">
                                        <label for="shippement">Divers</label>
                                        <input type="text" class="form-control" id="shippement" name="shippement" placeholder="Frais Divers" value="{{ old('shippement', $proforma->shippment) }}">
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
                                                <th>QTY/Ctn</th>
                                                <th>Price Shop</th>
                                                <th>Price Client</th>
                                                <th>CBM</th>
                                                <th>Volume</th>
                                                <th>Weight</th>
                                                <th>KG</th>
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
                                                <th colspan="2" class="text-right"><strong>Total</strong></th>
                                                <th id="totalCarton" class="text-right" style="font-weight:bold;">0</th>
                                                <th id="totalPcs" class="text-right" style="font-weight:bold;">0</th>
                                                <th colspan="2" class="text-right"><strong></strong></th>
                                                <th class="text-right"><strong>CBM</strong></th>
                                                <th><input type="text" style="width:80px" id="total_cbm" name="total_cbm"/></th>
                                                <th class="text-right"><strong>KG</strong></th>
                                                <th id="totalWeight" class="text-right" style="font-weight:bold;">0.00</th>
                                                <th></th>
                                                <th id="totalSubtotal" class="text-right" style="font-weight:bold;">0.00</th>
                                                <input type="hidden" name="total_subtotal" id="total_subtotal" />
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Add Row Button -->
                            <div class="row">
                                <div class="col-lg-12">
                                    <a href="javascript:void(0);" class="btn btn-primary" id="addRowButton">Add Row</a>
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
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfTokenMeta) {
                console.error('CSRF token not found!');
                return;
            }
               // Validation function for numeric fields
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

            // Function to check if required fields are filled
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

            // Optional: Preview image before submission (display thumbnail)
            document.querySelector('#orderTable tbody').addEventListener('change', function (event) {
                if (event.target.type === 'file') {
                    const fileInput = event.target;
                    const file = fileInput.files[0];
                    const previewElement = fileInput.closest('tr').querySelector('.image-preview'); // Assuming you want a preview

                    if (file && previewElement) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            previewElement.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });

            // Event listener for real-time validation of table fields
            document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
                const input = event.target;

                // Validate product name (text field)
                if (input.name.includes('[productName]') || input.name.includes('[description]')) {
                    validateRequiredField(input);
                }

                // Validate numeric fields (cartons, qty_per_ctn, price, etc.)
                if (input.classList.contains('cartons') || input.classList.contains('qty_per_ctn') || input.classList.contains('price') || input.classList.contains('price2')) {
                    validateNumericInput(input);
                    updateSubtotal(input.closest('tr'));  // Recalculate subtotal after input validation
                }

                // Trigger update of totals
                updateTotal();
            });
            document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
                if (event.target.name && event.target.name.includes('[productName]')) {
                    const productName = event.target.value;
                    const row = event.target.closest('tr');

                    if (productName.length >= 3) { // Trigger AJAX after 3 characters
                        fetchProductDetails(productName, row);
                    }
                }
            });
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
                        // Populate the row with fetched product details
                        const product = data.product;
                        row.querySelector('input[name*="[cbm]"]').value = product.cbm || 0.00;
                        row.querySelector('input[name*="[qty_per_ctn]"]').value = product.qtityCtn || 0;
                        row.querySelector('input[name*="[price2]"]').value = product.price || 0.00;
                        row.querySelector('input[name*="[weight]"]').value = product.weight || 0.00;

                        // Trigger subtotal calculation
                        updateSubtotal(row);
                        updateTotalCbm(row);
                        updateTotalWeight(row);

                        // Show the product image if available
                        const imageInput = row.querySelector('input[name*="[image]"]');
                        const imagePreview = row.querySelector('.image-preview');

                        if (product.image) {
                            // Assuming the product image URL is provided in the API response
                            const imageUrl = `/products/${product.image}`;  // Replace this with the actual image URL in your API response

                            // Set the image source to the product image URL
                            imagePreview.src = imageUrl;
                            imagePreview.style.display = 'block';  // Make the image visible
                        } else {
                            // Hide the image preview if no image is available
                            imagePreview.style.display = 'none';
                        }

                        // Optionally, set the input file to the default value (if required)
                        imageInput.value = ''; // Clear the input file value if needed
                    }
                })
                .catch(error => console.error('Error fetching product details:', error));
            }
            updateTotal();
            let rowIndex = document.querySelectorAll('#orderTable tbody tr').length;

            const orderTableBody = document.querySelector('#orderTable tbody');
            const addButton = document.getElementById('addRowButton');

            function updateAddButtonVisibility() {
                const rowCount = orderTableBody.getElementsByTagName('tr').length;
                if (rowCount >= 8) {
                    addButton.style.display = 'none';
                } else {
                    addButton.style.display = 'block';
                }
            }
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
                            previewElement.style.display = 'block'; // Make sure it appears
                        };
                        reader.readAsDataURL(file);
                    }
                }
            });
            document.getElementById('addRowButton').addEventListener('click', function () {
                rowIndex++;
                const tableBody = document.querySelector('#orderTable tbody');

                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>${rowIndex}</td>
                    <td><input type="text" name="products[${rowIndex}][productName]" class="form-control productName"></td>
                    <td><input type="text" name="products[${rowIndex}][cartons]" class="form-control cartons"></td>
                    <td><input type="text" name="products[${rowIndex}][qty_per_ctn]" class="form-control qty_per_ctn"></td>
                    <td><input type="text" name="products[${rowIndex}][price2]" class="form-control price2" required></td>
                    <td><input type="text" name="products[${rowIndex}][price]" class="form-control price" required></td>
                    <td><input type="text" name="products[${rowIndex}][cbm]" class="form-control cbm" required></td>
                    <td><input type="text" name="products[${rowIndex}][cbm_total]" class="form-control cbm_total" required></td>
                    <td><input type="text" name="products[${rowIndex}][weight]" class="form-control weight" required></td>
                    <td><input type="text" name="products[${rowIndex}][total_weight]" class="form-control total_weight" required></td>
                    <td>
                        <input type="file" name="products[${rowIndex}][image]" class="form-control image-input" accept="image/*">
                        <img class="image-preview" style="max-width: 80px; max-height: 80px; display: none; margin-top: 5px;">
                    </td>
                    <td><input type="text" name="products[${rowIndex}][subtotal]" class="form-control subtotal" readonly></td>
                    <td><a href="javascript:void(0);" class="btn btn-danger removeRowButton"><i class="fas fa-trash-alt"></i></a></td>
                    `;
                tableBody.appendChild(newRow);

                updateTotal();

                updateAddButtonVisibility();
            });

            updateAddButtonVisibility();

            // Event Delegation for row removal & subtotal calculation
            document.querySelector('#orderTable tbody').addEventListener('click', function (event) {
                if (event.target.closest('.removeRowButton')) {
                    event.target.closest('tr').remove();
                    updateTotal();
                    updateAddButtonVisibility();
                }
            });

            document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
                if (
                    event.target.classList.contains('cartons') ||
                    event.target.classList.contains('qty_per_ctn') ||
                    event.target.classList.contains('price2') ||
                    event.target.classList.contains('price') ||
                    event.target.classList.contains('cbm') ||
                    event.target.classList.contains('weight')
                ) {
                    priceFunction(event.target.closest('tr'));
                    updateSubtotal(event.target.closest('tr'));
                    updateTotalCbm(event.target.closest('tr'));
                    updateTotalWeight(event.target.closest('tr'));
                }
            });

            function priceFunction(row){
                const bonus_percent = (parseFloat(document.getElementById('bonus_percent').value) / 100) || 0;
                const price_shop = parseFloat(row.querySelector('.price2').value) || 0;
                const price_client = price_shop * (1+bonus_percent);
                row.querySelector('.price').value = price_client.toFixed(2);
                updateTotal();
            }

            function updateTotalCbm(row){
                const cartons = parseFloat(row.querySelector('.cartons').value) || 0;
                const cbm = parseFloat(row.querySelector('.cbm').value) || 0;

                const total_cbm = cartons * cbm;
                row.querySelector('.cbm_total').value = total_cbm.toFixed(2);
                updateTotal();
            }

            function updateTotalWeight(row){
                const cartons = parseFloat(row.querySelector('.cartons').value) || 0;
                const weight = parseFloat(row.querySelector('.weight').value) || 0;

                const total_weight = cartons * weight;
                row.querySelector('.total_weight').value = total_weight.toFixed(2);
                updateTotal();
            }

            // Function to calculate subtotal for a row
            function updateSubtotal(row) {
                const cartons = parseFloat(row.querySelector('.cartons').value) || 0;
                const qtyPerCtn = parseFloat(row.querySelector('.qty_per_ctn').value) || 0;
                const price = parseFloat(row.querySelector('.price').value) || 0;

                const subtotal = cartons * qtyPerCtn * price;
                row.querySelector('.subtotal').value = subtotal.toFixed(2);

                updateTotal();
            }
            // Function to update total values in the footer
            function updateTotal() {
                let totalCartons = 0;
                let totalPcs = 0;
                let totalSubtotal = 0;
                let totalCbm = 0;
                let totalWeight = 0;

                document.querySelectorAll('#orderTable tbody tr').forEach(row => {
                    const cartons = parseFloat(row.querySelector('.cartons').value) || 0;
                    const qtyPerCtn = parseFloat(row.querySelector('.qty_per_ctn').value) || 0;
                    const subtotal = parseFloat(row.querySelector('.subtotal').value) || 0;
                    const cbm_total = parseFloat(row.querySelector('.cbm_total').value) || 0;
                    const total_weight = parseFloat(row.querySelector('.total_weight').value) || 0;

                    totalCartons += cartons;
                    totalPcs += cartons * qtyPerCtn;
                    totalSubtotal += subtotal;
                    totalCbm += cbm_total;
                    totalWeight += total_weight;
                });

                document.getElementById('totalCarton').textContent = totalCartons;
                document.getElementById('totalPcs').textContent = totalPcs;
                document.getElementById('totalSubtotal').textContent = totalSubtotal.toFixed(2);
                document.getElementById('total_subtotal').value = totalSubtotal.toFixed(2);
                document.getElementById('total_cbm').value = totalCbm.toFixed(2);
                document.getElementById('totalWeight').textContent = totalWeight.toFixed(2);
            }

            // Form validation before submit
            /* document.getElementById('commandForm').addEventListener('submit', function (e) {
                let customerSelect = document.getElementById('customer_id');
                let commissionInput = document.getElementById('commission_rate');
                let shippementInput = document.getElementById('shippement');
                let dateProforma = document.getElementById('date_proforma');
                let total_cbm = document.getElementById('total_cbm');

                if (customerSelect.value === "#" || commissionInput.value.trim() === "" || shippementInput.value.trim() === "" || dateProforma.value.trim() === "" || total_cbm.value === "") {
                    e.preventDefault();
                    const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
                    alertModal.show();
                }

                storeFormData(); // Save form data before submission

            }); */
            const proformaId = document.getElementById('proformaId').value;
            //console.log(proformaId);

            $(document).ready(function () {
                $('#commandForm').on('submit', function (e) {
                    e.preventDefault(); // Prevent page reload
                    /* let customerSelect = document.getElementById('customer_id');
                    let commissionInput = document.getElementById('commission_rate');
                    let shippementInput = document.getElementById('shippement');
                    let dateProforma = document.getElementById('date_proforma');
                    let total_cbm = document.getElementById('total_cbm');

                    if (customerSelect.value === "#" || commissionInput.value.trim() === "" || shippementInput.value.trim() === "" || dateProforma.value.trim() === "" || total_cbm.value === "") {
                        e.preventDefault();
                        const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
                        alertModal.show();
                    } */
                    let formData = new FormData(this);

                    if (!proformaId) {
                        $('#modalMessage').html("ID de la proforma manquant.");
                        $('#alertModalImage').modal('show');
                        return;
                    }

                    $.ajax({
                        url: `/proformas/rajout/${proformaId}`,
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function () {
                            // Optional: show a loader or disable the button
                        },
                        success: function (response) {
                            // Show success message in a Bootstrap modal
                            $('#modalMessage').html('Proforma rajouté avec succès.');
                            $('#alertModalImage').modal('show');

                            // Reset the form
                            $('#commandForm')[0].reset();

                            // Remove all rows from the table (adjust selector as needed)
                            $('#orderTable tbody').empty(); // Replace with your actual table ID
                        },
                        error: function (xhr) {
                            let message = 'Une erreur est survenue.';

                            if (xhr.status === 422) {
                                // Validation errors
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

                            // Set message and show modal
                            $('#modalMessage').html(message);
                            $('#alertModalImage').modal('show');
                        }
                    });
                });
            });
            /* // Reset form & preserve table structure
            document.querySelector('.btn-cancel').addEventListener('click', function () {
                document.getElementById('commandForm').reset();

                // Preserve table rows but clear inputs
                document.querySelectorAll('#orderTable tbody tr').forEach(row => {
                    row.querySelectorAll('input').forEach(input => input.value = '');
                });

                updateTotal();
            }); */
        });
    </script>
</body>
</html>
