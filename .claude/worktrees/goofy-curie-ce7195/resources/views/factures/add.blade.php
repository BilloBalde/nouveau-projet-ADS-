<div class="modal fade" id="addsale" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter les Infos de la Facture pour cette vente</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="factureForm" action="{{ route('factures.store') }}" method="post">
                @csrf
                @php
                    $countFactures = App\Models\Facture::all()->count() + 1;
                    $numeroFacture = date('Y-m').''.sprintf("%04d", $countFactures);
                @endphp
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="numero_facture">Numero Facture</label>
                                <input type="text" name="numero_facture" id="numero_facture" class="form-control" value="{{ $numeroFacture }}" readonly>
                                <span class="text-danger">
                                    <strong id="numero_facture-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="store_id">Stock</label>
                                <select class="select" name="store_id" class="form-control">
                                    <option value="">Choisir Stock</option>
                                    @foreach($boutiques as $boutique)
                                    <option value="{{ $boutique->id }}">{{ $boutique->store_name }}</option>
                                    @endforeach
                                </select>
                                <span class="text-danger">
                                    <strong class="store_id-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="search">Chercher Client</label>
                                <input type="text" name="search" id="search" class="form-control" placeholder="Taper le nom ou la mark du client">
                                <span class="text-danger">
                                    <strong id="search-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="customerSelect">Selectionner le Client</label>
                                <select id="customerSelect" name="customer_id" class="form-control">
                                    <option value="">Select a client</option>
                                    <!-- Options will be dynamically populated here -->
                                </select>
                                <span class="text-danger">
                                    <strong id="customer_id-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div id="newCustomerFields" style="display:none;">
                            <hr>
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label for="customerName">Nom du Client</label>
                                    <input type="text" name="customerName" id="customerName" class="form-control">
                                    <span class="text-danger">
                                        <strong id="customerName-error"></strong>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-0">
                                    <label for="mark">Mark du Client</label>
                                    <input type="text" name="mark" id="mark" class="form-control">
                                    <span class="text-danger">
                                        <strong id="mark-error"></strong>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="tel">Téléphone</label>
                                    <input type="text" name="tel" id="tel" class="form-control">
                                    <span class="text-danger">
                                        <strong id="tel-error"></strong>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email" class="form-control">
                                    <span class="text-danger">
                                        <strong id="email-error"></strong>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address">Adresse</label>
                                    <input type="text" name="address" id="address" class="form-control">
                                    <span class="text-danger">
                                        <strong id="address-error"></strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <hr>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="avance">Montant Avancé</label>
                                <input type="text" name="avance" id="avance" class="form-control">
                                <span class="text-danger">
                                    <strong id="avance-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea type="text" name="notes" id="notes" class="form-control"></textarea>
                                <span class="text-danger">
                                    <strong id="notes-error"></strong>
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
<script>
    document.getElementById('search').addEventListener('keyup', function() {
        let searchValue = this.value;

        fetch("{{ route('customers.search') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('input[name=_token]').value,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({ search: searchValue })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'no_results') {
                // Show the new customer fields
                document.getElementById('newCustomerFields').style.display = 'block';
            } else if (data.status === 'found') {
                // Hide the new customer fields if they were previously shown
                document.getElementById('newCustomerFields').style.display = 'none';

                let customerSelect = document.getElementById('customerSelect');
                customerSelect.innerHTML = '<option value="">Select a client</option>'; // Clear existing options

                data.customers.forEach(customer => {
                    let option = document.createElement('option');
                    option.value = customer.id;
                    option.textContent = customer.customerName + ' - ' + customer.mark;
                    customerSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
</script>
{{-- <script>
    document.getElementById('factureForm').addEventListener('submit', function(e) {
        e.preventDefault();

        let formData = new FormData(this);

        fetch("{{ route('factures.store') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('input[name=_token]').value,
                "Accept": "application/json",
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Clear previous errors
            document.querySelectorAll('.text-danger strong').forEach(element => {
                element.textContent = '';
            });

            if (data.errors) {
                // Display errors
                for (let key in data.errors) {
                    document.getElementById(`${key}-error`).textContent = data.errors[key][0];
                }
            } else {
                // Submit the modal form directly
                let modalForm = new FormData();
                modalForm.append('numero_facture', data.numero_facture);
                modalForm.append('customer_id', data.customer_id);
                modalForm.append('avance', data.avance);
                modalForm.append('notes', data.notes);

                fetch("{{ route('factures.store') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('input[name=_token]').value,
                        "Accept": "application/json",
                    },
                    body: modalForm
                })
                .then(modalResponse => modalResponse.json())
                .then(modalData => {
                    if (modalData.success) {
                        alert('Facture ajoutée avec succès');
                        // You can close the modal here if needed
                        $('#addsale').modal('hide');
                    } else {
                        console.error('Erreur:', modalData.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                });
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la soumission du formulaire.');
        });
    });
</script> --}}
<style>
    .custom-select {
        position: relative;
    }

    .select-items {
        position: absolute;
        background-color: #f9f9f9;
        border: 1px solid #dcdcdc;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        z-index: 99;
    }

    .select-hide {
        display: none;
    }

    .select-option {
        padding: 10px;
        cursor: pointer;
    }

    .select-option:hover {
        background-color: #e1e1e1;
    }
</style>
