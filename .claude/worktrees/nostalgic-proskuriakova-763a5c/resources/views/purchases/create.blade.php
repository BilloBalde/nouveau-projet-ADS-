Ω<!DOCTYPE html>
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
                            <h4>Gestion des Achats</h4>
                            <h6>Ajouter Achat</h6>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            @include('layouts.flash')
                            <form action="{{ route('purchases.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body">
                                    <div id="purchases-container">
                                        <div class="purchase-entry">
                                            <div class="row">
                                                <!-- Hidden field for numeroPurchase -->
                                                @foreach(old('purchases', [['product_id' => '', 'price' => '', 'quantity' => '', 'description' => '']]) as $index => $oldPurchase)
                                                <div class="col-lg-6 col-sm-6 col-12">
                                                    <div class="form-group">
                                                        <label for="numeroPurchase">numero identification</label>
                                                        <input type="text" name="purchases[0][numeroPurchase]" id="numeroPurchase" value="{{ $numeroPurchase }}" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6 col-sm-6 col-12">
                                                    <div class="form-group">
                                                        <label for="store_id">Store_id</label>
                                                        <input type="text" name="purchases[0][store_id]" id="store_id" value="{{ $store_id }}" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 col-sm-4 col-12">
                                                    <div class="form-group">
                                                        <label for="product_id">Produit</label>
                                                        <select name="purchases[0][product_id]" id="product_id" class="form-control">
                                                            <option value="">Selectionner le Produit</option>
                                                            @foreach ($products as $item)
                                                            @php
                                                                $product = App\Models\Product::with('categories')->find($item->id);
                                                            @endphp
                                                            {{ $item->libelle }}-
                                                            <option value="{{ $item->id }}">
                                                                {{ $item->libelle }}-
                                                                @foreach ($product->categories as $category)
                                                                {{ $category->slug . ' (' . $category->category_type . ')' }}
                                                                @endforeach
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    @error('purchases.'.$index.'.product_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-lg-4 col-sm-4 col-12">
                                                    <div class="form-group">
                                                        <label for="quantity">Quantité</label>
                                                        <input type="text" name="purchases[0][quantity]" id="quantity" class="form-control quantity">
                                                    </div>
                                                    @error('purchases.'.$index.'.quantity')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-lg-4 col-sm-4 col-12">
                                                    <div class="form-group">
                                                        <label for="price">Price</label>
                                                        <input type="text" name="purchases[0][price]" id="price" class="form-control">
                                                    </div>
                                                    @error('purchases.'.$index.'.price')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <div class="col-lg-12 col-sm-12 col-12">
                                                    <div class="form-group">
                                                        <label for="description">Description</label>
                                                        <textarea name="purchases[0][description]" id="description" cols="30" rows="10" class="form-control">Enter the text ici</textarea>
                                                    </div>
                                                    @error('purchases.'.$index.'.description')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                                <hr>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group">
                                                <button type="button" id="add-purchase" class="btn btn-secondary">Ajouter nouvelle ligne</button>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-sm-6 col-12">
                                            <div class="form-group mt-3">
                                                <label for="totalQuantity">Total Quantité</label>
                                                <input type="text" id="totalQuantity" class="form-control" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <div class="col-lg-12">
                                        <button type="submit" id="soumission" class="btn btn-submit me-2">Soumettre</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
        <script>
            var maxQuantity = {{ $quantity }};

            function updateTotalQuantity() {
                let total = 0;
                document.querySelectorAll('.quantity').forEach(input => {
                    total += parseFloat(input.value) || 0;
                });
                document.getElementById('totalQuantity').value = total;

                const errorElement = document.getElementById('quantityError');
                const submitButton = document.getElementById('soumission');

                // Check if total quantity exceeds the allowed limit
                if (total > maxQuantity) {
                    if (!errorElement) {
                        const errorDiv = document.createElement('div');
                        errorDiv.id = 'quantityError';
                        errorDiv.className = 'text-danger';
                        errorDiv.textContent = 'Quantité totale depassant la limite de ' + maxQuantity;
                        document.getElementById('totalQuantity').parentNode.appendChild(errorDiv);
                    }

                    // Remove the last added purchase entry if total exceeds maxQuantity
                    const purchaseEntries = document.getElementsByClassName('purchase-entry');
                    if (purchaseEntries.length > 1) {
                        const lastEntry = purchaseEntries[purchaseEntries.length - 1];
                        lastEntry.remove();
                    }

                    // Update the total quantity again after removing the last row
                    total = 0;
                    document.querySelectorAll('.quantity').forEach(input => {
                        total += parseFloat(input.value) || 0;
                    });
                    document.getElementById('totalQuantity').value = total;

                } else {
                    if (errorElement) {
                        errorElement.remove();
                    }
                }

                // Enable/disable submit button based on total quantity
                submitButton.disabled = total !== maxQuantity;
            }

            function addQuantityEventListeners() {
                document.querySelectorAll('.quantity').forEach(input => {
                    input.addEventListener('input', updateTotalQuantity);
                });
            }

            document.getElementById('add-purchase').addEventListener('click', function() {
                const container = document.getElementById('purchases-container');
                const index = container.getElementsByClassName('purchase-entry').length;
                const newEntry = document.querySelector('.purchase-entry').cloneNode(true);

                // Reset fields and set names for new entry
                newEntry.querySelectorAll('input, select, textarea').forEach(input => {
                    input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                    if (input.name.includes('numeroPurchase')) {
                        input.value = document.getElementById('numeroPurchase').value;
                    } else if(input.name.includes('store_id')) {
                        input.value = document.getElementById('store_id').value;
                    } else {
                        input.value = ''; // Clear values for new entry
                    }
                });

                // Create and append the remove button
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-danger'; // Apply styling
                removeButton.textContent = 'Supprimer cette ligne';

                // Remove button event listener
                removeButton.addEventListener('click', function() {
                    const purchaseEntries = document.getElementsByClassName('purchase-entry');
                    if (purchaseEntries.length > 1) {
                        newEntry.remove();
                        updateTotalQuantity();
                    } else {
                        alert("Vous ne pouvez pas supprimer la dernière ligne.");
                    }
                });

                // Append the remove button to the new entry
                const descriptionField = newEntry.querySelector('textarea'); // Assuming description is the last element
                descriptionField.parentNode.appendChild(removeButton);

                // Append the new entry to the container
                container.appendChild(newEntry);
                addQuantityEventListeners(); // Add event listeners for quantity inputs
                updateTotalQuantity();
            });

            // Initial call to set up quantity event listeners for existing fields
            addQuantityEventListeners();

            // Ensure the total quantity is updated on page load
            window.addEventListener('load', updateTotalQuantity);
        </script>

        <script>
            let shouldConfirmExit = true;

            // Function to open the exit confirmation modal
            function showExitModal(event) {
                if (shouldConfirmExit) {
                    event.preventDefault();
                    event.stopPropagation();
                    $('#exitModal').modal('show');
                }
            }

            document.querySelectorAll('a, button').forEach(function (element) {
                element.addEventListener('click', function (event) {
                    if (element.id !== 'soumission' && element.id !== 'add-purchase') {
                        showExitModal(event);
                    }
                });
            });

            document.getElementById('exitForm').addEventListener('submit', function () {
                shouldConfirmExit = false;
            });

            window.addEventListener('popstate', function (event) {
                showExitModal();
            });
        </script>
        @include('purchases.exitmodal')
    </body>
</html>
