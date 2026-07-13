<!DOCTYPE html>
<html lang="fr">
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
                            <h4>Dettes</h4>
                            <h6>Suivi des dettes clients</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('dettes.create') }}" class="btn btn-added" style="background-color:#c1682f;border-color:#c1682f;">
                                <img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-1">Ajouter une dette
                            </a>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <div class="card">
                        <div class="card-body">
                            {{-- Filtres --}}
                            <form method="GET" action="{{ route('dettes.index') }}" class="row g-2 mb-3">
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <input type="text" name="reference" value="{{ request('reference') }}" class="form-control" placeholder="Référence…">
                                </div>
                                <div class="col-lg-3 col-sm-6 col-12">
                                    <select name="customer_id" class="form-control">
                                        <option value="">Tous les clients</option>
                                        @foreach($customers as $c)
                                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->customerName }} ({{ $c->mark }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <select name="status" class="form-control">
                                        <option value="">Tous statuts</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Payée</option>
                                        <option value="cancel" {{ request('status') == 'cancel' ? 'selected' : '' }}>Annulée</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <input type="date" name="endettement_date" value="{{ request('endettement_date') }}" class="form-control">
                                </div>
                                <div class="col-lg-2 col-sm-6 col-12">
                                    <button type="submit" class="btn btn-primary w-100" style="background-color:#c1682f;border-color:#c1682f;">Filtrer</button>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Référence</th>
                                            <th>Client</th>
                                            <th>Boutique</th>
                                            <th>Date</th>
                                            <th>Montant total</th>
                                            <th>Payé</th>
                                            <th>Reste</th>
                                            <th>Statut</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($dataTable as $data)
                                        <tr>
                                            <td>{{ $data->reference }}</td>
                                            <td>{{ $data->customer?->customerName ?? 'N/A' }}</td>
                                            <td>{{ $data->store?->store_name ?? 'N/A' }}</td>
                                            <td>{{ $data->endettement_date }}</td>
                                            <td>{{ number_format((float) $data->montant_total, 0, ',', ' ') }} GNF</td>
                                            <td>{{ number_format((float) $data->montant_paid, 0, ',', ' ') }} GNF</td>
                                            <td><strong style="color:#c1682f;">{{ number_format((float) $data->reste, 0, ',', ' ') }} GNF</strong></td>
                                            <td>
                                                <span class="badge {{ $data->status === 'paid' ? 'bg-success' : ($data->status === 'cancel' ? 'bg-secondary' : 'bg-warning text-dark') }}">
                                                    {{ ['pending' => 'En attente', 'paid' => 'Payée', 'cancel' => 'Annulée'][$data->status] ?? $data->status }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ route('dettes.edit', $data->id) }}" class="btn btn-warning btn-sm me-1" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($data->status === 'pending' && $data->reste > 0)
                                                <button type="button" class="btn btn-sm" style="background-color:#c1682f;color:#fff;"
                                                        data-bs-toggle="modal" data-bs-target="#payModal"
                                                        data-dette-id="{{ $data->id }}"
                                                        data-dette-ref="{{ $data->reference }}"
                                                        data-dette-reste="{{ $data->reste }}">
                                                    Encaisser
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="9" class="text-center text-muted py-5">Aucune dette enregistrée.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                <div class="mt-3 d-flex justify-content-center">
                                    {{ $dataTable->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal encaissement --}}
        <div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="payForm" action="">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Encaisser un versement — <span id="payRef"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted">Reste dû : <strong id="payReste"></strong></p>
                            <div class="form-group mb-3">
                                <label>Montant du versement (GNF)</label>
                                <input type="number" name="versement" min="1" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Moyen de paiement</label>
                                <select name="paid_by" class="form-control" required>
                                    <option value="cash">Espèces</option>
                                    <option value="orange money">Orange Money</option>
                                    <option value="check">Chèque</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Note (optionnel)</label>
                                <input type="text" name="notes" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn" style="background-color:#c1682f;color:#fff;">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @include('layouts.scripts')
        <script>
            document.getElementById('payModal').addEventListener('show.bs.modal', function (event) {
                const btn = event.relatedTarget;
                document.getElementById('payRef').textContent = btn.dataset.detteRef;
                document.getElementById('payReste').textContent = Number(btn.dataset.detteReste).toLocaleString('fr-FR') + ' GNF';
                const form = document.getElementById('payForm');
                form.action = '{{ url('dettes') }}/' + btn.dataset.detteId + '/pay';
                form.querySelector('input[name="versement"]').max = btn.dataset.detteReste;
            });
        </script>
    </body>
</html>
