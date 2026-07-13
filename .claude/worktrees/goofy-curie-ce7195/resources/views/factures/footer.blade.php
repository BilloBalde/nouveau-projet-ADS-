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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

@include('layouts.scripts')
<!-- JavaScript for Modal and Table Management -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    let allowLeave = false;
    const leaveBtn = document.getElementById('leave-page-btn');
    const confirmBtn = document.getElementById('confirm-leave');
    let targetUrl = '';

    // Block all navigation attempts unless allowLeave is true
    window.addEventListener('beforeunload', function (e) {
        if (!allowLeave) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Back button click -> show confirmation modal
    leaveBtn.addEventListener('click', function (e) {
        e.preventDefault();
        targetUrl = leaveBtn.getAttribute('href');
        const modal = new bootstrap.Modal(document.getElementById('leaveConfirmModal'));
        modal.show();
    });

    // Confirm leave -> allow and navigate
    confirmBtn.addEventListener('click', function () {
        allowLeave = true;
        window.location.href = targetUrl;
    });
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const addCustomerButton = document.getElementById('addCustomer');
        //console.log('Button click');
        const addCustomerModal = new bootstrap.Modal(document.getElementById('addCustomerModal'));

        addCustomerButton.addEventListener('click', function () {
            addCustomerModal.show();
        });

        // Gestion de la soumission du formulaire d'ajout de client
        document.getElementById('addCustomerForm').addEventListener('submit', function (e) {
            e.preventDefault();
            let formData = new FormData(this);
            
            const storeCustomerUrl = "{{ route('facturations.storeCustomer') }}";
            fetch(storeCustomerUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const customerSelect = document.getElementById('customer_id');

                    let newOption = document.createElement('option');
                    newOption.value = data.customer.id;
                    newOption.textContent = `${data.customer.customerName} ${data.customer.mark}`;
                    customerSelect.appendChild(newOption);

                    customerSelect.value = data.customer.id;

                    // Ferme le modal et reset le formulaire
                    addCustomerModal.hide();
                    document.getElementById('addCustomerForm').reset();
                } else {
                    console.log('Erreur lors de l’ajout du client.');
                }
            })
            .catch(error => console.error('Erreur:', error));
        });
        // Validation function for numeric fields
        function validateNumericInput(input) {
            const value = parseFloat(input.value);
            if (isNaN(value) || value <= 0) {
                input.classList.add('is-invalid');
                input.classList.remove('is-valid');
                return false;
            }else {
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
            }else {
                input.classList.remove('is-invalid');
                return true;
            }
        }
        function showModal(message) {
            document.getElementById('modalMessage').textContent = message;
            const alertModal = new bootstrap.Modal(document.getElementById('alertModalImage'));
            alertModal.show();
        }

        // Event listener for real-time validation of table fields
        document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
            const input = event.target;

            // Validate numeric fields (cartons, qty_per_ctn, price, etc.)
            if (input.classList.contains('cartons') || input.classList.contains('qty_per_ctn') || input.classList.contains('price')) {
                validateNumericInput(input);
                updateSubtotal(input.closest('tr'));  // Recalculate subtotal after input validation
            }

            // Trigger update of totals
            updateTotal();
        });
        
        updateTotal();
        let rowIndex = document.querySelectorAll('#orderTable tbody tr').length;

        let formData = {}; // Object to store form data

        const orderTableBody = document.querySelector('#orderTable tbody');
        const addButton = document.getElementById('addRowButton');

        // Optional: Preview image before submission (display thumbnail)
        document.querySelector('#orderTable tbody').addEventListener('change', async function (event) {
          if (event.target.type === 'file') {
              const fileInput = event.target;
              const file = fileInput.files[0];

              if (!file) return;

              // Resize image before previewing/sending
              const resizedBlob = await resizeImage(file, 500); // resize to 500px width
              const previewElement = fileInput.closest('tr').querySelector('.image-preview') || createPreviewImage(fileInput);

              // Preview resized image
              const reader = new FileReader();
              reader.onload = function (e) {
                  previewElement.src = e.target.result;
                  previewElement.style.display = 'block';
              };
              reader.readAsDataURL(resizedBlob);

              // Replace file in input with resized blob
              const resizedFile = new File([resizedBlob], file.name, { type: resizedBlob.type });
              const dataTransfer = new DataTransfer();
              dataTransfer.items.add(resizedFile);
              fileInput.files = dataTransfer.files;
          }
      });

      // Helper to create preview <img> tag
      function createPreviewImage(fileInput) {
          const img = document.createElement('img');
          img.classList.add('image-preview');
          img.style.maxWidth = '80px';
          img.style.maxHeight = '80px';
          img.style.display = 'block';
          img.style.marginTop = '5px';
          fileInput.parentNode.appendChild(img);
          return img;
      }

      // Resize function using canvas
      function resizeImage(file, maxWidth) {
          return new Promise((resolve, reject) => {
              const reader = new FileReader();
              reader.onload = function (event) {
                  const img = new Image();
                  img.onload = function () {
                      const scale = maxWidth / img.width;
                      const canvas = document.createElement('canvas');
                      canvas.width = maxWidth;
                      canvas.height = img.height * scale;
                      const ctx = canvas.getContext('2d');
                      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                      const mimeType = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                      canvas.toBlob(blob => resolve(blob), mimeType, 0.75); // 75% quality
                  };
                  img.onerror = reject;
                  img.src = event.target.result;
              };
              reader.onerror = reject;
              reader.readAsDataURL(file);
          });
      }


        document.getElementById('addRowButton').addEventListener('click', function () {
            rowIndex++;
            const tableBody = document.querySelector('#orderTable tbody');

            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td>${rowIndex}</td>
                <td><input type="text" name="products[${rowIndex}][productName]" class="form-control productName"></td>
                 <td>
                     <input type="file" name="products[${rowIndex}][image]" class="form-control image-input" accept="image/*">
                     <img class="image-preview" style="max-width: 80px; max-height: 80px; display: none; margin-top: 5px;">
                 </td>
                <td><input type="text" name="products[${rowIndex}][cartons]" class="form-control cartons"></td>
                <td><input type="text" name="products[${rowIndex}][qty_per_ctn]" class="form-control qty_per_ctn" value="1" readonly></td>
                <td><input type="text" name="products[${rowIndex}][price]" class="form-control price" required></td>
                <td><input type="text" name="products[${rowIndex}][subtotal]" class="form-control subtotal" readonly></td>
                <td><a href="javascript:void(0);" class="btn btn-danger removeRowButton"><i class="fas fa-trash-alt"></i></a></td>
            `;
            tableBody.appendChild(newRow);

            updateTotal();
        });


        document.querySelector('#orderTable tbody').addEventListener('input', function (event) {
            if (
                event.target.classList.contains('cartons') ||
                event.target.classList.contains('qty_per_ctn') ||
                event.target.classList.contains('price'))
            ) {
                updateSubtotal(event.target.closest('tr'));
            }
        });

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
            let totalPcs = 0;
            let totalSubtotal = 0;

            document.querySelectorAll('#orderTable tbody tr').forEach(row => {
                const cartons = parseFloat(row.querySelector('.cartons').value) || 0;
                const qtyPerCtn = parseFloat(row.querySelector('.qty_per_ctn').value) || 0;
                const subtotal = parseFloat(row.querySelector('.subtotal').value) || 0;

                totalPcs += cartons * qtyPerCtn;
                totalSubtotal += subtotal;
            });

            document.getElementById('totalPcs').textContent = totalPcs;
            document.getElementById('totalSubtotal').textContent = totalSubtotal.toFixed(2);
            document.getElementById('total_subtotal').value = totalSubtotal.toFixed(2);
        }

        
        $(document).ready(function () {
            $('#commandForm').on('submit', function (e) {
                e.preventDefault(); // Prevent page reload

                let formData = new FormData(this);
                
                const storeCommandeUrl = "{{ route('facturations.store') }}";
                $.ajax({
                    url: storeCommandeUrl,
                    method: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function () {
                        // Optional: show a loader or disable the button
                    },
                    success: function (response) {
                        // Show success message in a Bootstrap modal
                        $('#modalMessage').html('Facturation effectuée avec succès.');
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
    });
</script>

