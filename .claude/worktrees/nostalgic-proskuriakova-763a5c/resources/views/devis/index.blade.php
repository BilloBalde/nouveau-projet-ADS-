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
                        <h4>Liste des Devis</h4>
                        <h6>Gérer vos devis</h6>
                    </div>
                    <div class="page-btn">
                        <a href="{{ route('devis.create') }}" class="btn btn-added">
                            <i class="fa fa-plus me-2"></i>Nouveau Devis
                        </a>
                    </div>
                </div>

                @include('layouts.flash')

                <div class="card">
                    <div class="card-body">
                        <!-- Filtres -->
                        <div class="table-top">
                            <div class="search-set">
                                <div class="search-path">
                                    <a class="btn btn-filter" id="filter_search">
                                        <img src="{{ asset('assets/img/icons/filter.svg') }}" alt="img">
                                        <span><img src="{{ asset('assets/img/icons/closes.svg') }}" alt="img"></span>
                                    </a>
                                </div>
                                <div class="search-input">
                                    <a class="btn btn-searchset">
                                        <img src="{{ asset('assets/img/icons/search-white.svg') }}" alt="img">
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Formulaire de filtre -->
                        <div class="card" id="filter_inputs" style="display: none;">
                            <div class="card-body pb-0">
                                <form action="{{ route('devis.index') }}" method="GET">
                                    <div class="row">
                                        <div class="col-lg-3 col-sm-6 col-12">
                                            <div class="form-group">
                                                <input type="text" name="numero_devis" value="{{ request('numero_devis') }}" 
                                                    placeholder="N° Devis" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-sm-6 col-12">
                                            <div class="form-group">
                                                <select name="customer_id" class="form-control">
                                                    <option value="">Tous les clients</option>
                                                    @foreach($customers as $customer)
                                                    <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                                        {{ $customer->customerName }} - {{ $customer->mark }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-sm-6 col-12">
                                            <div class="form-group">
                                                <select name="status" class="form-control">
                                                    <option value="">Tous les statuts</option>
                                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Brouillon</option>
                                                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Envoyé</option>
                                                    <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepté</option>
                                                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejeté</option>
                                                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expiré</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-sm-6 col-12">
                                            <div class="form-group">
                                                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                                                    placeholder="Date début" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-sm-6 col-12">
                                            <div class="form-group">
                                                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                                                    placeholder="Date fin" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-lg-12 col-sm-6 col-12 d-flex align-items-center">
                                            <div class="form-group d-flex">
                                                <button type="submit" class="btn btn-filters me-2">
                                                    <img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"> Filtrer
                                                </button>
                                                <a href="{{ route('devis.index') }}" class="btn btn-cancel">Réinitialiser</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Tableau des devis -->
                        <div class="table-responsive">
                            <table class="table table-hover" id="devisTable">
                                <thead>
                                    <tr>
                                        <th>N° Devis</th>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Boutique</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Validé le</th>
                                        <th>Créé par</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($devis as $d)
                                    <tr>
                                        <td>
                                            <a href="{{ route('devis.show', $d->id) }}" class="text-primary">
                                                {{ $d->numero_devis }}
                                            </a>
                                        </td>
                                        <td>{{ $d->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($d->customer)
                                                <strong>{{ $d->customer->customerName }}</strong><br>
                                                <small>{{ $d->customer->mark }}</small>
                                            @else
                                                <span class="text-muted">Client supprimé</span>
                                            @endif
                                        </td>
                                        <td>{{ $d->store?->store_name ?? 'N/A' }}</td>
                                        <td class="font-weight-bold">{{ number_format($d->total_amount, 0, ',', ' ') }} FG</td>
                                        <td>
                                            @php
                                                $statusClasses = [
                                                    'draft' => 'bg-secondary',
                                                    'sent' => 'bg-info',
                                                    'accepted' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    'expired' => 'bg-warning',
                                                ];
                                                $statusLabels = [
                                                    'draft' => 'Brouillon',
                                                    'sent' => 'Envoyé',
                                                    'accepted' => 'Accepté',
                                                    'rejected' => 'Rejeté',
                                                    'expired' => 'Expiré',
                                                ];
                                            @endphp
                                            <span class="badges {{ $statusClasses[$d->status] ?? 'bg-secondary' }}">
                                                {{ $statusLabels[$d->status] ?? $d->status }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($d->validated_at)
                                                {{ $d->validated_at->format('d/m/Y H:i') }}<br>
                                                <small>par {{ $d->validatedBy?->name ?? 'N/A' }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $d->createdBy?->name ?? 'N/A' }}</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <a class="action-set" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="true">
                                                    <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                                </a>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a href="{{ route('devis.show', $d->id) }}" class="dropdown-item">
                                                            <i class="fa fa-eye me-2"></i>Voir détails
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('devis.pdf', $d->id) }}" class="dropdown-item" target="_blank">
                                                            <i class="fa fa-file-pdf me-2"></i>Télécharger PDF
                                                        </a>
                                                    </li>
                                                    
                                                    @if($d->status == 'draft')
                                                    <li>
                                                        <a href="{{ route('devis.edit', $d->id) }}" class="dropdown-item">
                                                            <i class="fa fa-edit me-2"></i>Modifier
                                                        </a>
                                                    </li>
                                                    @endif

                                                    <li class="dropdown-divider"></li>
                                                    
                                                    @if($d->status == 'draft')
                                                    <li>
                                                        <a href="#" class="dropdown-item" onclick="changeStatus({{ $d->id }}, 'sent')">
                                                            <i class="fa fa-paper-plane me-2"></i>Marquer comme envoyé
                                                        </a>
                                                    </li>
                                                    @endif
                                                    
                                                    @if($d->status == 'sent')
                                                    <li>
                                                        <a href="#" class="dropdown-item" onclick="changeStatus({{ $d->id }}, 'accepted')">
                                                            <i class="fa fa-check-circle me-2 text-success"></i>Accepter
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" class="dropdown-item" onclick="changeStatus({{ $d->id }}, 'rejected')">
                                                            <i class="fa fa-times-circle me-2 text-danger"></i>Rejeter
                                                        </a>
                                                    </li>
                                                    @endif
                                                    
                                                    @if($d->status == 'accepted' && !$d->validated_at)
                                                    <li>
                                                        <a href="#" class="dropdown-item" onclick="validateDevis({{ $d->id }})">
                                                            <i class="fa fa-check-double me-2 text-success"></i>Valider et créer facture
                                                        </a>
                                                    </li>
                                                    @endif
                                                    
                                                    @if($d->status == 'draft')
                                                    <li class="dropdown-divider"></li>
                                                    <li>
                                                        <a href="#" class="dropdown-item text-danger" onclick="confirmDelete({{ $d->id }})">
                                                            <i class="fa fa-trash me-2"></i>Supprimer
                                                        </a>
                                                    </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <img src="{{ asset('assets/img/icons/empty-box.svg') }}" alt="Aucun devis" style="width: 80px; opacity: 0.5;">
                                            <p class="mt-3">Aucun devis trouvé</p>
                                            <a href="{{ route('devis.create') }}" class="btn btn-primary btn-sm mt-2">
                                                <i class="fa fa-plus me-2"></i>Créer un devis
                                            </a>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $devis->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire caché pour changement de statut -->
    <form id="statusForm" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Formulaire caché pour validation -->
    <form id="validateForm" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Formulaire caché pour suppression -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
    
    @include('layouts.scripts')
    
    <script>
    $(document).ready(function() {
        console.log('Document ready');
        
        // Initialize filter toggle
        $('#filter_search').on('click', function() {
            $('#filter_inputs').slideToggle();
            $(this).toggleClass('setclose');
            console.log('Filter clicked');
        });

        // Initialize simple table instead of DataTable if it's causing issues
        if ($.fn.DataTable) {
            try {
                $('#devisTable').DataTable({
                    "paging": false,
                    "ordering": true,
                    "info": false,
                    "searching": false,
                    "language": {
                        "emptyTable": "Aucune donnée disponible"
                    }
                });
                console.log('DataTable initialized');
            } catch(e) {
                console.error('DataTable error:', e);
            }
        }
    });

    function changeStatus(devisId, status) {
        if (confirm('Êtes-vous sûr de vouloir changer le statut de ce devis ?')) {
            const form = document.getElementById('statusForm');
            form.action = '{{ url("devis") }}/' + devisId + '/status';
            
            // Ajouter le champ status
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'status';
            input.value = status;
            form.appendChild(input);
            
            form.submit();
        }
    }

    function validateDevis(devisId) {
        if (confirm('La validation créera une facture et affectera le stock. Continuer ?')) {
            const form = document.getElementById('validateForm');
            form.action = '{{ url("devis") }}/' + devisId + '/validate';
            form.submit();
        }
    }

    function confirmDelete(devisId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce devis ? Cette action est irréversible.')) {
            const form = document.getElementById('deleteForm');
            form.action = '{{ url("devis") }}/' + devisId;
            form.submit();
        }
    }
    </script>
    
    <style>
    /* Fix for DataTable if needed */
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        margin-bottom: 10px;
    }
    table.dataTable thead th {
        border-bottom: 2px solid #dee2e6;
    }
    </style>
</body>
</html>