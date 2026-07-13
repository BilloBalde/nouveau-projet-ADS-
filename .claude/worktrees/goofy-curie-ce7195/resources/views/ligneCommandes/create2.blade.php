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
                            <h4>Products Add</h4>
                            <h6>Add Product</h6>
                        </div>
                    </div>
                    @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('fall'))
                    <div class="alert alert-danger">{{ session('fall') }}</div>
                    @endif
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('commandes.store') }}" id="proforma-form" action="{{ route('proformas.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="customer_id"><strong>Client</strong></label>
                                            <select name="customer_id" id="customer_id" class="form-control">
                                                <option value="#">Selectionner le client</option>
                                                @foreach ($customers as $item)
                                                    <option value="{{ $item->id }}">{{ $item->customerName.' '.$item->mark }}</option>
                                                @endforeach
                                                <option value="addCustomer">Ajouter le Client</option>
                                            </select>
                                        </div>
                                        @error('customer_id')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="commission">Commission Pourcentage</label>
                                            <input type="text" id="commission" name="commission" class="form-control" placeholder="Entrer le pourcentage ex : 2">
                                            @error('commission')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="item_no"><strong>Item No</strong></label>
                                            <input type="text" name="item_no" id="item_no"  list="listProducts" class="form-control">
                                            <datalist id="listProducts">
                                                @foreach ($products as $item)
                                                    <option
                                                        data-id="{{ $item->id }}"
                                                        data-item_no="{{ $item->item_no }}"
                                                        data-name="{{ $item->name }}"
                                                        data-price="{{ $item->price }}"
                                                        data-description="{{ $item->description }}"
                                                        data-store_id="{{ $item->store_id }}"
                                                        data-qtityCtn="{{ $item->qtityCtn }}"
                                                        data-cbm="{{ $item->cbm }}"
                                                        data-weight="{{ $item->weight }}">
                                                        {{ $item->item_no }}|{{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </datalist>
                                        </div>
                                        @error('item_no')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="name">Name</label>
                                            <input type="text" id="name" name="name" class="form-control" placeholder="Entrer un nom comme: Bic">
                                            @error('name')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <input type="text" id="description" name="description" class="form-control" placeholder="Description: Bic avec plusieurs couleurs">
                                            @error('description')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="store_id">Vendor</label>
                                            <select name="store_id" id="store_id" class="form-control">
                                                <option>Select Vendor</option>
                                                @foreach ($shops as $item)
                                                <option value="{{ $item->id }}">{{ $item->store_number }}</option>
                                                @endforeach
                                            </select>
                                            @error('store_id')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="price">Price Office</label>
                                            <input type="text" id="price" name="price" class="form-control" placeholder="Entrer le prix unitaire en RMB : 20">
                                            @error('price')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="price2">Price Shop</label>
                                            <input type="text" id="price2" name="price2" class="form-control" placeholder="Entrer le prix unitaire en RMB : 20">
                                            @error('price2')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="qtityCtn">Quantity/Ctn</label>
                                            <input type="number" id="qtityCtn" name="qtityCtn" class="form-control" placeholder="Entrer le nombre de pcs/ctn : 20">
                                            @error('qtityCtn')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="cartons">Nombre de Cartons</label>
                                            <input type="number" id="cartons" name="cartons" class="form-control" placeholder="Entrer le nombre de pcs/ctn : 20">
                                            @error('cartons')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="quantity">Quantite Totale</label>
                                            <input type="number" id="quantity" name="quantity" class="form-control" readonly>
                                            @error('quantity')
                                            <span class="error-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="total_price">Prix Total</label>
                                            <input type="text" id="total_price" name="total_price" class="form-control" placeholder="Entrer le volume: 0.024">
                                            @error('total_price')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="cbm">Volume CBM</label>
                                            <input type="text" id="cbm" name="cbm" class="form-control" placeholder="Entrer le volume: 0.024">
                                            @error('cbm')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="total_cbm">Volume total CBM</label>
                                            <input type="text" id="total_cbm" name="total_cbm" class="form-control" placeholder="Entrer le volume: 0.024">
                                            @error('total_cbm')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="weight">Weight KG</label>
                                            <input type="text" id="weight" name="weight" class="form-control" placeholder="Entrer le poids: 52">
                                            @error('weight')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-4 col-12">
                                        <div class="form-group">
                                            <label for="total_weight">Total Weight KG</label>
                                            <input type="text" id="total_weight" name="total_weight" class="form-control" placeholder="Entrer le poids: 52">
                                            @error('total_weight')
                                            <span class="text-danger"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <!-- Other fields remain unchanged -->
                                    <div class="col-lg-12 col-sm-12 col-12 prodImg">
                                        <div class="form-group">
                                            <label for="image"> Product Image</label>
                                            <div id="image-container">
                                                <!-- Initially, show the file input field -->
                                                <div class="image-upload">
                                                    <input type="file" id="image" name="image">
                                                    <div class="image-uploads">
                                                        <img src="{{ asset('assets/img/icons/upload.svg') }}" alt="Upload Icon">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @error('image')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" id="buttonsubmit" class="btn btn-submit me-2">Submit</button>
                                        <a href="" class="btn btn-cancel">Terminer</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="open-modal" class="modal" style="display: none;">
            <div class="modal-dialog">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Entrez la valeur transport</h5>
                  <button type="button" class="close">&times;</button>
                </div>
                <div class="modal-body">
                  <form id="modal-form">
                    <label for="additional-value">Entrez une valeur :</label>
                    <input type="text" id="additional-value" name="additional_value" class="form-control">
                    <span class="text-danger" id="error-novalue"></span>
                  </form>
                </div>
                <div class="modal-footer">
                  <button type="button" id="submit-form" class="btn btn-primary">Soumettre</button>
                </div>
              </div>
            </div>
        </div>

        @include('layouts.scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const itemNoInput = document.getElementById('item_no');
                const nameInput = document.getElementById('name');
                const descriptionInput = document.getElementById('description');
                const priceInput = document.getElementById('price');
                const storeIdInput = document.getElementById('store_id');
                const qtityCtnInput = document.getElementById('qtityCtn');
                const cbmInput = document.getElementById('cbm');
                const weightInput = document.getElementById('weight');
                const cartonsInput = document.getElementById('cartons');
                const quantityInput = document.getElementById('quantity');
                const totalPriceInput = document.getElementById('total_price');
                const totalCbmInput = document.getElementById('total_cbm');
                const totalWeightInput = document.getElementById('total_weight');

                // Function to populate fields based on the selected product
                function populateFieldsFromProduct(product) {
                    nameInput.value = product.name || '';
                    descriptionInput.value = product.description || '';
                    priceInput.value = product.price || '';
                    qtityCtnInput.value = product.qtityCtn || '';
                    cbmInput.value = product.cbm || '';
                    weightInput.value = product.weight || '';
                    storeIdInput.value = product.store_id || '';
                }

                // Handle item_no input for searching product
                itemNoInput.addEventListener('input', function() {
                    const itemNo = itemNoInput.value.trim();
                    const options = document.querySelectorAll('#listProducts option');
                    let matchedProduct = null;

                    options.forEach(function(option) {
                        if (option.value.includes(itemNo)) {
                            matchedProduct = {
                                name: option.getAttribute('data-name'),
                                description: option.getAttribute('data-description'),
                                price: parseFloat(option.getAttribute('data-price')),
                                qtityCtn: parseInt(option.getAttribute('data-qtityCtn')),
                                cbm: parseFloat(option.getAttribute('data-cbm')),
                                weight: parseFloat(option.getAttribute('data-weight')),
                                store_id: option.getAttribute('data-store_id'),
                            };
                            return;
                        }
                    });

                    if (matchedProduct) {
                        populateFieldsFromProduct(matchedProduct);
                    } else {
                        // Clear the fields if no match is found
                        nameInput.value = '';
                        descriptionInput.value = '';
                        priceInput.value = '';
                        storeIdInput.value = '';
                        qtityCtnInput.value = '';
                        cbmInput.value = '';
                        weightInput.value = '';
                    }
                });

                // Function to update calculated fields based on input
                function updateCalculatedFields() {
                    const cartons = parseInt(cartonsInput.value) || 0;
                    const qtityCtn = parseInt(qtityCtnInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;
                    const cbm = parseFloat(cbmInput.value) || 0;
                    const weight = parseFloat(weightInput.value) || 0;

                    // Calculate quantity, total_price, total_cbm, and total_weight
                    const quantity = cartons * qtityCtn;
                    const totalPrice = quantity * price;
                    const totalCbm = cartons * cbm;
                    const totalWeight = cartons * weight;

                    // Update the calculated fields
                    quantityInput.value = quantity;
                    totalPriceInput.value = totalPrice.toFixed(2); // format to two decimal places
                    totalCbmInput.value = totalCbm.toFixed(3); // format to three decimal places
                    totalWeightInput.value = totalWeight.toFixed(2); // format to two decimal places
                }

                // Add event listener to update calculations when 'cartons' field is changed
                cartonsInput.addEventListener('input', updateCalculatedFields);

                // Add event listener to update calculations if the other fields are manually changed
                qtityCtnInput.addEventListener('input', updateCalculatedFields);
                priceInput.addEventListener('input', updateCalculatedFields);
                cbmInput.addEventListener('input', updateCalculatedFields);
                weightInput.addEventListener('input', updateCalculatedFields);

                const form = document.getElementById('exitForm');
                const confirmExitButton = document.getElementById('confirmExit');
                const invoiceId2 = document.getElementById('invoice_id2');

                // When the user attempts to exit
                document.querySelectorAll('a, button').forEach(function(element) {
                    if (element.id !== 'buttonsubmit') { // Ignore the submit button
                        element.addEventListener('click', function(event) {
                            event.preventDefault(); // Prevent default navigation
                            $('#exitModal').modal('show'); // Show exit confirmation modal
                        });
                    }
                });

                // When the user confirms the exit (clicks "Quitter")
                confirmExitButton.addEventListener('click', function() {
                    const formAction = form.action;
                    if (formAction !== "{{ route('ligneCommandes.confirmer') }}") {
                        console.error('Form action is incorrect. Expected:', "{{ route('ligneCommandes.confirmer') }}");
                        event.preventDefault();  // Stop form submission
                        return;
                    }else{
                        console.log(formAction);
                        form.submit(); // Submit the form if "Quitter" is confirmed
                    }
                });

                // Optional: handle the modal closing without submitting the form
                document.querySelector('.btn-secondary[data-bs-dismiss="modal"]').addEventListener('click', function() {
                    $('#exitModal').modal('hide'); // Hide the modal if "Annuler" is clicked
                });
            });
            // Open Modal
            document.getElementById('open-modal').addEventListener('click', function () {
                const modal = document.getElementById('value-modal');
                modal.style.display = 'block';
                modal.classList.add('show'); // Add Bootstrap-like "show" class for visibility
                });

                // Close Modal
            document.querySelector('.close').addEventListener('click', function () {
                const modal = document.getElementById('value-modal');
                modal.style.display = 'none';
                modal.classList.remove('show');
            });

            // Submit the form with the additional value
            document.getElementById('buttonsubmit').addEventListener('click', function (event) {
                // Prevent default form submission
               // Prevent default behavior of the button
                event.preventDefault();

                // Get the value of the input field
                const additionalValue = document.getElementById('additional-value').value.trim();
                const errorMessageValue = document.getElementById('error-novalue');
                // Check if the input is empty
                if (!additionalValue) {
                    errorMessageValue.textContent = 'Veuillez entrer une valeur avant de soumettre.'; // Show error message alert()
                    return; // Stop further execution
                }

                // Append the value as a hidden input to the proforma form
                const proformaForm = document.getElementById('proforma-form');

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'additional_value';
                hiddenInput.value = additionalValue;

                proformaForm.appendChild(hiddenInput);

                // Submit the form
                proformaForm.submit();
            });
        </script>
    </body>
</html>
