{{-- Fournisseur de l'achat : existant (liste) ou nouveau (créé sans quitter
     le formulaire, puis sélectionné automatiquement). Les fournisseurs sont
     les partenaires, déjà utilisés pour les ventes tierces. --}}
<div class="col-lg-3 col-md-4 col-sm-6 col-12">
    <div class="form-group">
        <label>Fournisseur</label>
        <div class="row g-1">
            <div class="col-9">
                <select name="partner_id" id="supplierSelect" class="form-control">
                    <option value="">— Non renseigné —</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) old('partner_id', $selectedSupplierId ?? '') === (string) $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}{{ $supplier->phone ? ' ('.$supplier->phone.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-3">
                <button type="button" class="btn btn-secondary w-100" data-bs-toggle="modal" data-bs-target="#newSupplierModal" title="Nouveau fournisseur">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
        @error('partner_id') <span class="text-danger">{{ $message }}</span> @enderror
    </div>
</div>

@push('purchase_modals')
<div class="modal fade" id="newSupplierModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau fournisseur</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="newSupplierError" class="alert alert-danger" style="display:none;"></div>
                <div class="form-group">
                    <label>Nom du fournisseur</label>
                    <input type="text" id="newSupplierName" class="form-control">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="text" id="newSupplierPhone" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-submit" id="newSupplierSubmit">Créer le fournisseur</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const errorBox = document.getElementById('newSupplierError');
        document.getElementById('newSupplierSubmit').addEventListener('click', function () {
            errorBox.style.display = 'none';
            fetch('{{ route('pos.storePartner') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    name: document.getElementById('newSupplierName').value,
                    phone: document.getElementById('newSupplierPhone').value,
                }),
            }).then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            }).then(function (result) {
                if (!result.ok || !result.data.success) {
                    errorBox.textContent = result.data.errors
                        ? Object.values(result.data.errors).flat().join(' ')
                        : (result.data.message || 'Erreur lors de la création.');
                    errorBox.style.display = 'block';
                    return;
                }
                const partner = result.data.partner;
                const select = document.getElementById('supplierSelect');
                select.add(new Option(partner.name + (partner.phone ? ' (' + partner.phone + ')' : ''), partner.id, true, true));
                select.dispatchEvent(new Event('change', { bubbles: true }));
                document.getElementById('newSupplierName').value = '';
                document.getElementById('newSupplierPhone').value = '';
                bootstrap.Modal.getOrCreateInstance(document.getElementById('newSupplierModal')).hide();
            }).catch(function () {
                errorBox.textContent = 'Erreur réseau, réessayez.';
                errorBox.style.display = 'block';
            });
        });
    });
</script>
@endpush
