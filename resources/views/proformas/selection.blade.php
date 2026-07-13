<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste des Proformas</h4>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <form action="{{ route('proformas.validate') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    @foreach ($proformas as $proforma)
                                        <div class="col-sm-3 mb-4">
                                            <div class="card p-3 h-100 shadow-sm" data-cbm="{{ $proforma->total_cbm }}" data-weight="{{ $proforma->total_weight }}">
                                                <h5 class="mb-2">{{ $proforma->identifier }}</h5>
                                
                                                <p><strong>Total Items:</strong> {{ $proforma->items }}</p>
                                                <p><strong>Total Cartons:</strong> {{ $proforma->total_ctns }}</p>
                                                <p><strong>Total CBM:</strong> {{ $proforma->total_cbm }}</p>
                                                <p><strong>Total Weight:</strong> {{ $proforma->total_weight }}</p>
                                
                                                <div class="form-check mb-2">
                                                    <input type="checkbox" class="form-check-input select-proforma" name="selected_proformas[]"
                                                    value="{{ $proforma->id }}"
                                                    id="select_{{ $proforma->id }}"
                                                    data-id="{{ $proforma->id }}">
                                                    <label class="form-check-label" for="select_{{ $proforma->id }}">Select this Proforma</label>
                                                </div>
                                
                                                <div>
                                                    <label for="cbm_input_{{ $proforma->id }}" class="form-label">Enter CBM:</label>
                                                    <input type="text" class="form-control cbm-input" name="cbm[{{ $proforma->id }}]" id="cbm_input_{{ $proforma->id }}" step="0.01">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center my-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="selectAllProformas">
                                        <label class="form-check-label" for="selectAllProformas">Select All Proformas</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="validateBtn">Valider</button>
                                </div>                            
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Validation Error Modal -->
        <div class="modal fade" id="validationErrorModal" tabindex="-1" aria-labelledby="validationErrorModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="validationErrorModalLabel">Validation Error</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="validationErrorMessage">
                        <!-- Message will be inserted here dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
  
        @include('layouts.scripts')
        <script>
            document.getElementById('selectAllProformas').addEventListener('change', function (event) {
                const isChecked = event.target.checked;

                document.querySelectorAll('.select-proforma').forEach(function (checkbox) {
                    checkbox.checked = isChecked;

                    // Get the card container for the current checkbox
                    const card = checkbox.closest('.card');
                    if (!card) return;

                    // Find the Total CBM text from the paragraph
                    const cbmText = Array.from(card.querySelectorAll('p')).find(p => p.textContent.includes('Total CBM:'));
                    if (!cbmText) return;

                    const totalCbm = parseFloat(cbmText.textContent.replace(/[^\d.]/g, '')) || 0;

                    // Get the CBM input inside the card
                    const cbmInput = card.querySelector('.cbm-input');

                    // Fill or clear the CBM input depending on whether it's checked
                    cbmInput.value = isChecked ? totalCbm : '';
                });
            });

            // Auto-fill CBM when a checkbox is selected
            document.querySelectorAll('.select-proforma').forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const proformaId = this.getAttribute('data-id');
                    const cbmInput = document.getElementById(`cbm_input_${proformaId}`);
                    const card = this.closest('.card');

                    if (this.checked && cbmInput && cbmInput.value.trim() === '') {
                        const cbm = card.getAttribute('data-cbm');
                        console.log(cbm);
                        
                        cbmInput.value = cbm || 0;
                    }else if (!this.checked && cbmInput) {
                        cbmInput.value = ''; // Clear the value when unchecked
                    }
                });
            });
            function checkValidationConditions(showModal = false) {
                let totalCbm = 0;
                let totalWeight = 0;

                document.querySelectorAll('.select-proforma:checked').forEach(function (checkbox) {
                    const card = checkbox.closest('.card');
                    if (!card) return;

                    const cbm = parseFloat(card.dataset.cbm) || 0;
                    const weight = parseFloat(card.dataset.weight) || 0;

                    totalCbm += cbm;
                    totalWeight += weight;
                });

                const isCbmValid = (totalCbm > 28 && totalCbm < 29) || (totalCbm > 68 && totalCbm < 69);
                const isWeightValid = totalWeight < 29;

                const validateBtn = document.getElementById('validateBtn');
                if (validateBtn) {
                    validateBtn.disabled = !(isCbmValid && isWeightValid);
                }

                if (showModal && !(isCbmValid && isWeightValid)) {
                    let errorMessage = `<p><strong>Validation failed:</strong></p><ul>`;
                    if (!isCbmValid) {
                        errorMessage += `<li>Total CBM must be between 28–29 or 68–69. Current: ${totalCbm.toFixed(2)}</li>`;
                    }
                    if (!isWeightValid) {
                        errorMessage += `<li>Total Weight must be under 29. Current: ${totalWeight.toFixed(2)}</li>`;
                    }
                    errorMessage += `</ul>`;

                    document.getElementById('validationErrorMessage').innerHTML = errorMessage;
                    const modal = new bootstrap.Modal(document.getElementById('validationErrorModal'));
                    modal.show();
                }
            }

            // Use this on "Validate" button click
            document.getElementById('validateBtn').addEventListener('click', function (e) {
                if (document.getElementById('validateBtn').disabled) {
                    e.preventDefault(); // prevent form submission
                    checkValidationConditions(true); // show modal
                }
            });

            // Also update validation on checkbox changes
            document.querySelectorAll('.select-proforma').forEach(el => {
                el.addEventListener('change', () => checkValidationConditions(false));
            });
            document.getElementById('selectAllProformas').addEventListener('change', function () {
                setTimeout(() => checkValidationConditions(false), 100);
            });

        </script>
    </body>
</html>
                            