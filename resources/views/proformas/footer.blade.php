<div class="modal fade" id="alertModalImage" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="alertModalLabel">ALERTE!!!</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">X</button>
            </div>
            <div class="modal-body">
                <p id="modalMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" id="closeModalBtn" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<!-- Preloader Overlay -->
<div id="preloaderOverlay"
     style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:9999; text-align:center;">
    <div style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%);">
        <div class="spinner-border text-primary" role="status" style="width:3rem; height:3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3">Veuillez patienter...</p>
    </div>
</div>
@include('layouts.scripts')
<!-- JavaScript for Modal and Table Management -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // =============== PAGE LEAVE CONFIRMATION ===============
    let allowLeave = false;
    const leaveBtn = document.getElementById('leave-page-btn');
    const confirmBtn = document.getElementById('confirm-leave');
    let targetUrl = '';

    window.addEventListener('beforeunload', function (e) {
        if (!allowLeave) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    if (leaveBtn && confirmBtn) {
        leaveBtn.addEventListener('click', function (e) {
            e.preventDefault();
            targetUrl = leaveBtn.getAttribute('href');
            const modal = new bootstrap.Modal(document.getElementById('leaveConfirmModal'));
            modal.show();
        });

        confirmBtn.addEventListener('click', function () {
            allowLeave = true;
            window.location.href = targetUrl;
        });
    }

    // =============== MAIN FUNCTIONALITY ===============
    const orderTableBody = document.querySelector('#orderTable tbody');
    const addButton = document.getElementById('addRowButton');
    const categories = @json($categories);
    let rowIndex = document.querySelectorAll('#orderTable tbody tr').length;
    let formData = {};
    let formSuccess = false;
    let typingTimer;
    const redirectUrl = "{{ route('ligneCommandes.index') }}";
    
    // Store calculation timeout
    let calculationTimeout;

    // =============== UTILITY FUNCTIONS ===============
    function validateNumericInput(input) {
        const value = parseFloat(input.value);
        if (isNaN(value) || value <= 0) {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }

    function validateRequiredField(input) {
        if (input.value.trim() === '') {
            input.classList.add('is-invalid');
            input.classList.remove('is-valid');
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }

    function showModal(message) {
        const modalMessage = document.getElementById('modalMessage');
        if (modalMessage) {
            modalMessage.textContent = message;
            const alertModal = new bootstrap.Modal(document.getElementById('alertModalImage'));
            alertModal.show();
        }
    }

    function storeFormData() {
        formData = {};
        document.querySelectorAll('#orderTable tbody tr').forEach((row, index) => {
            formData[index] = {
                productName: row.querySelector('.productName')?.value || '',
                cartons: row.querySelector('.cartons')?.value || '',
                unit_price_purchase: row.querySelector('.unit_price_purchase')?.value || '',
                unit_price_sale: row.querySelector('.unit_price_sale')?.value || '',
                price_sale: row.querySelector('.price_sale')?.value || '',
                subtotal: row.querySelector('.subtotal')?.value || '',
            };
        });
    }

    function restoreFormData() {
        document.querySelectorAll('#orderTable tbody tr').forEach((row, index) => {
            if (formData[index]) {
                const data = formData[index];
                if (row.querySelector('.productName')) row.querySelector('.productName').value = data.productName;
                if (row.querySelector('.cartons')) row.querySelector('.cartons').value = data.cartons;
                if (row.querySelector('.qty_per_ctn')) row.querySelector('.qty_per_ctn').value = data.qty_per_ctn;
                if (row.querySelector('.unit_price_purchase')) row.querySelector('.unit_price_purchase').value = data.unit_price_purchase;
                if (row.querySelector('.unit_price_sale')) row.querySelector('.unit_price_sale').value = data.unit_price_sale;
                if (row.querySelector('.price_sale')) row.querySelector('.price_sale').value = data.price_sale;
                if (row.querySelector('.subtotal')) row.querySelector('.subtotal').value = data.subtotal;
            }
        });
    }

    function updateAddButtonVisibility() {
        if (addButton) {
            const rowCount = orderTableBody.getElementsByTagName('tr').length;
            addButton.style.display = rowCount >= 20 ? 'none' : 'block';
        }
    }

    function generateCategorySelect(rowIndex) {
        let select = `<select name="products[${rowIndex}][category_id]" class="form-control category-select">
                        <option value="">Select Category</option>`;
        
        categories.forEach(cat => {
            select += `<option value="${cat.id}">${cat.slug}</option>`;
        });

        select += `</select>`;
        return `<td>${select}</td>`;
    }

    // =============== FIXED ROW CALCULATIONS ===============
    function calculateUnitSalePrice(unitPurchasePrice, totalCartons, shippingCost) {
        if (totalCartons === 0 || isNaN(totalCartons) || totalCartons <= 0) return unitPurchasePrice;
        return unitPurchasePrice + (shippingCost / totalCartons);
    }

    function updateAllRows() {
        // Get current values
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
        
        // Second pass: update unit sale prices and subtotals for ALL rows using the SAME totalCartons
        document.querySelectorAll('#orderTable tbody tr').forEach(row => {
            const unitPurchasePrice = parseFloat(row.querySelector('.unit_price_purchase')?.value) || 0;
            const cartons = parseFloat(row.querySelector('.cartons')?.value) || 0;
            
            // Calculate unit sale price based on this row's purchase price and the GLOBAL total cartons
            const unitSalePrice = calculateUnitSalePrice(unitPurchasePrice, totalCartons, shippingCost);
            
            // Update unit sale price field
            const unitSaleField = row.querySelector('.unit_price_sale');
            if (unitSaleField) {
                unitSaleField.value = unitSalePrice.toFixed(2);
            }
            
            // Update subtotal for this row
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

    // Single function to trigger recalculation of ALL rows
    function recalculateAllRows() {
        clearTimeout(calculationTimeout);
        calculationTimeout = setTimeout(() => {
            updateAllRows();
        }, 50);
    }

    // =============== PRODUCT SUGGESTIONS ===============
    function fetchProductSuggestions(query, input) {
        fetch(`/fetch-product-details-suggestion?productName=${encodeURIComponent(query)}&suggestions=true`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.suggestions) {
                showSuggestions(input, data.suggestions);
            } else {
                closeSuggestions(input);
            }
        })
        .catch(error => console.error('Error fetching product suggestions:', error));
    }

    function showSuggestions(input, suggestions) {
        closeSuggestions(input);

        const list = document.createElement('ul');
        list.className = 'suggestions-list';
        Object.assign(list.style, {
            position: 'absolute',
            background: '#fff',
            border: '1px solid #ccc',
            zIndex: 1000,
            width: `${input.offsetWidth}px`,
            maxHeight: '200px',
            overflowY: 'auto',
            listStyle: 'none',
            padding: '0',
            margin: '0',
            boxShadow: '0 2px 15px rgba(0,0,0,0.2)'
        });

        suggestions.forEach(product => {
            const li = document.createElement('li');
            li.textContent = product.libelle;
            Object.assign(li.style, {
                padding: '8px 12px',
                cursor: 'pointer',
                borderBottom: '1px solid #eee'
            });
            
            li.addEventListener('mouseover', () => li.style.backgroundColor = '#f5f5f5');
            li.addEventListener('mouseout', () => li.style.backgroundColor = '');
            li.addEventListener('click', () => {
                input.value = product.libelle;
                closeSuggestions(input);
                fetchProductDetails(product.libelle, input.closest('tr'));
            });
            list.appendChild(li);
        });

        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(list);
    }

    function closeSuggestions(input) {
        const existing = input.parentNode.querySelector('.suggestions-list');
        if (existing) existing.remove();
    }

    function fetchProductDetails(productName, row) {
        fetch(`/fetch-product-details?productName=${encodeURIComponent(productName)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.product) {
                const product = data.product;
                
                // Set product name
                const productInput = row.querySelector('input[name*="[productName]"]');
                if (productInput) productInput.value = product.libelle || 'null';

                // Set category
                const categorySelect = row.querySelector('select[name*="[category_id]"]');
                if (categorySelect && product.category_id) {
                    categorySelect.value = product.category_id;
                }

                // Set image preview
                const imagePreview = row.querySelector('.image-preview');
                if (imagePreview && product.image) {
                    const imageUrl = `/products/${product.image}`;
                    imagePreview.src = imageUrl;
                    imagePreview.style.display = 'block';
                } else if (imagePreview) {
                    imagePreview.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error fetching product details:', error));
    }

    // =============== IMAGE PREVIEW ===============
    function handleImagePreview(event) {
        const fileInput = event.target;
        const file = fileInput.files[0];

        if (file) {
            let previewElement = fileInput.closest('tr').querySelector('.image-preview');
            if (!previewElement) {
                previewElement = document.createElement('img');
                previewElement.classList.add('image-preview');
                Object.assign(previewElement.style, {
                    maxWidth: '80px',
                    maxHeight: '80px',
                    display: 'block',
                    marginTop: '5px'
                });
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

    // =============== ROW MANAGEMENT ===============
    function addNewRow() {
        storeFormData();
        rowIndex++;
        
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td><input type="text" name="products[${rowIndex}][productName]" class="form-control productName" autocomplete="off"></td>
            ${generateCategorySelect(rowIndex)}
            <td><input type="number" name="products[${rowIndex}][cartons]" class="form-control cartons" min="0" step="0.01"></td>
            <td><input type="number" name="products[${rowIndex}][unit_price_purchase]" class="form-control unit_price_purchase" min="0" step="0.01"></td>
            <td><input type="number" name="products[${rowIndex}][unit_price_sale]" class="form-control unit_price_sale" min="0" step="0.01"></td>
            <td><input type="number" name="products[${rowIndex}][price_sale]" class="form-control price_sale" min="0" step="0.01"></td>
            <td>
                <input type="file" name="products[${rowIndex}][image]" class="form-control image-input" accept="image/*">
                <img class="image-preview" style="max-width: 80px; max-height: 80px; display: none; margin-top: 5px;">
            </td>
            <td><input type="number" name="products[${rowIndex}][subtotal]" class="form-control subtotal" readonly step="0.01"></td>
            <td><a href="javascript:void(0);" class="btn btn-danger removeRowButton"><i class="fas fa-trash-alt"></i></a></td>
        `;
        
        orderTableBody.appendChild(newRow);
        recalculateAllRows(); // Recalculate ALL rows when adding a new row
        updateAddButtonVisibility();
        restoreFormData();
    }

    function removeRow(button) {
        button.closest('tr').remove();
        recalculateAllRows(); // Recalculate ALL rows when removing a row
        updateAddButtonVisibility();
    }

    // =============== EVENT LISTENERS ===============
    // Single input event listener for table
    orderTableBody.addEventListener('input', function (event) {
        const input = event.target;
        const row = input.closest('tr');
        
        // Validation for numeric fields
        if (input.classList.contains('cartons') ||  
            input.classList.contains('unit_price_purchase') || 
            input.classList.contains('unit_price_sale') || 
            input.classList.contains('price_sale')) {
            validateNumericInput(input);
        }

        // Product name suggestions
        if (input.classList.contains('productName')) {
            clearTimeout(typingTimer);
            if (input.value.length >= 3) {
                typingTimer = setTimeout(() => {
                    fetchProductSuggestions(input.value, input);
                }, 300);
            } else {
                closeSuggestions(input);
            }
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

    // Image preview
    orderTableBody.addEventListener('change', function (event) {
        if (event.target.classList.contains('image-input')) {
            handleImagePreview(event);
        }
    });

    // Row removal
    orderTableBody.addEventListener('click', function (event) {
        const removeBtn = event.target.closest('.removeRowButton');
        if (removeBtn) {
            removeRow(removeBtn);
        }
    });

    // Add row button
    if (addButton) {
        addButton.addEventListener('click', addNewRow);
    }

    // Modal close handler
    const closeModalBtn = document.getElementById('closeModalBtn');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            if (formSuccess) {
                window.location.href = redirectUrl;
            }
        });
    }

    // =============== FORM SUBMISSION ===============
    $(document).ready(function () {
        $('#commandForm').on('submit', function (e) {
            e.preventDefault();
            
            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('commandes.store') }}",
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
                    
                    formSuccess = true;
                    showModal('Achat créée avec succès.');
                    
                    // Reset form
                    $('#commandForm')[0].reset();
                    $('#orderTable tbody').empty();
                    
                    // Add one empty row
                    addNewRow();
                },
                error: function (xhr) {
                    $('#preloaderOverlay').hide();
                    $('#submitFormbutton').prop('disabled', false);
                    
                    let message = 'Une erreur est survenue.';
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        message = Object.values(errors).flat().join('<br>');
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        message = xhr.responseJSON.error;
                    }
                    
                    showModal(message);
                }
            });
        });
    });

    // Initialize
    updateAddButtonVisibility();
    recalculateAllRows(); // Initial calculation of all rows
});
</script>
