<div class="modal fade" id="addpurchase" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter les Infos de la Logistic</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('logistics.store') }}" method="post">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="typeLogistic">Type d'achat</label>
                                <select class="select" name="typeLogistic" id="typeLogistic" class="form-control">
                                    <option value="">Choose type d'achat</option>
                                    <option value="conteneur"> Conteneur</option>
                                    <option value="particulier"> Particulier</option>
                                </select>
                                <span class="text-danger">
                                    <strong id="typeLogistic-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="numeroPurchase">Numero Identification</label>
                                <input type="text" name="numeroPurchase" id="numeroPurchase" class="form-control">
                                <span class="text-danger">
                                    <strong id="numeroPurchase-error"></strong>
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
                                <label for="quantity">Quantité</label>
                                <input type="text" name="quantity" id="quantity" class="form-control">
                                <span class="text-danger">
                                    <strong id="quantity-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="depense">Dépense Total</label>
                                <input type="text" name="depense" id="depense" class="form-control">
                                <span class="text-danger">
                                    <strong id="depense-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="dateEmis">Date Emis</label>
                                <input type="date" name="dateEmis" id="dateEmis" class="form-control">
                                <span class="text-danger">
                                    <strong id="dateEmis-error"></strong>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="dateFournis">Date Fournis</label>
                                <input type="date" name="dateFournis" id="dateFournis" class="form-control">
                                <span class="text-danger">
                                    <strong id="dateFournis-error"></strong>
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
{{-- <script>
document.querySelector('form').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent default form submission

    // Log event to check if target is defined
    console.log(event);

    let formData = new FormData(this);
    fetch("{{ route('logistics.store') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
            'Accept': 'application/json',
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.errors) {
            for (let key in data.errors) {
                document.getElementById(`${key}-error`).textContent = data.errors[key][0];
            }
        } else if (data.success) {
            window.location.href = data.redirect_url;
        }
    })
    .catch(error => console.error('Error:', error));
});
</script> --}}
