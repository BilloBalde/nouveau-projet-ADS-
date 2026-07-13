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
                            <h4>Commandes à valider</h4>
                            <h6>Gérer les commandes en attente</h6>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-path">
                                        <a class="btn btn-filter" id="filter_search">
                                            <img src="{{ asset('assets/img/icons/filter.svg') }}" alt="img">
                                            <span><img src="{{ asset('assets/img/icons/closes.svg') }}" alt="img"></span>
                                        </a>
                                    </div>
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="{{ asset('assets/img/icons/search-white.svg') }}" alt="img"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="card" id="filter_inputs">
                                <div class="card-body pb-0">
                                    <form action="{{ route('admin.orders.index') }}" method="GET">
                                        <div class="row">
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="order_id" value="{{ request('order_id') }}" placeholder="N° commande" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-4 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="customer_name" value="{{ request('customer_name') }}" placeholder="Nom client" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"></button>
                                                    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Reset</a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table datanew">
                                    <thead>
                                        <tr>
                                            <th>#ID</th>
                                            <th>Client</th>
                                            <th>Articles</th>
                                            <th>Total (GNF)</th>
                                            <th>Paiement</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $order)
                                        <tr>
                                            <td><strong>#{{ $order->id }}</strong></td>
                                            <td>
                                                {{ $order->user->name ?? 'N/A' }}
                                                <span class="d-block text-muted" style="font-size:11px;">
                                                    {{ $order->user->phone ?? '' }}
                                                </span>
                                            </td>
                                            <td style="font-size:12px;">
                                                @foreach($order->items as $item)
                                                    <span class="d-block">
                                                        {{ $item->product?->libelle ?? '#'.$item->product_id }}
                                                        <em class="text-muted">× {{ $item->quantity }}</em>
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td>{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</td>
                                            <td>{{ strtoupper($order->payment_method) }}</td>
                                            <td>
                                                @if($order->status == 'pending')
                                                    <span class="badges bg-warning">En attente</span>
                                                @elseif($order->status == 'approved')
                                                    <span class="badges bg-success">Approuvée</span>
                                                @elseif($order->status == 'rejected')
                                                    <span class="badges bg-danger">Rejetée</span>
                                                @else
                                                    <span class="badges bg-secondary">{{ $order->status }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    @if($order->status === 'pending')
                                                        {{-- Bouton Approuver → ouvre modal boutique --}}
                                                        <button type="button"
                                                            class="btn btn-success btn-sm btn-approve"
                                                            data-order-id="{{ $order->id }}"
                                                            data-order-label="#{{ $order->id }} — {{ $order->user?->name ?? 'Client' }}"
                                                            data-approve-url="{{ route('admin.orders.approve', $order) }}"
                                                            data-check-url="{{ route('admin.orders.stock.check', $order) }}">
                                                            <i class="fas fa-check me-1"></i> Approuver
                                                        </button>
                                                        <button 
                                                            type="button" 
                                                            class="btn btn-danger btn-sm btn-reject"
                                                            data-url="{{ route('admin.orders.reject', $order) }}">
                                                            <i class="fas fa-times me-1"></i> Rejeter
                                                        </button>
                                                    @endif
                                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye me-1"></i> Voir
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                Aucune commande en attente.
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{ $orders->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══ Modal approbation boutique ══ --}}
        <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
                <div class="modal-content" style="border-radius:16px;overflow:hidden;">

                    <div class="modal-header" style="background:linear-gradient(135deg,#1a0e00,#7c3a00);border:none;padding:20px 24px;">
                        <div>
                            <h5 class="modal-title text-white fw-bold mb-1">
                                <i class="fas fa-store me-2"></i>Approuver la commande
                            </h5>
                            <p class="text-white-50 mb-0" style="font-size:13px;" id="modalOrderLabel"></p>
                        </div>
                        <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Fermer">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-4">

                        {{-- Sélecteur boutique --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold" style="font-size:13px;">
                                <i class="fas fa-store-alt me-1 text-warning"></i>
                                Sélectionnez la boutique de vente
                            </label>
                            <select id="storeSelect" class="form-control" style="border-radius:10px;">
                                <option value="">— Choisir une boutique —</option>
                                @foreach(\App\Models\Store::orderBy('store_name')->get() as $store)
                                    <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Zone vérification stock --}}
                        <div id="stockCheckZone" style="display:none;">
                            <p class="fw-semibold mb-2" style="font-size:13px;">
                                <i class="fas fa-boxes me-1 text-info"></i> Disponibilité du stock
                            </p>
                            <div id="stockCheckList"></div>
                            <div id="forceApproveZone" style="display:none;" class="mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="forceApproveCheck">
                                    <label class="form-check-label text-danger fw-semibold" for="forceApproveCheck" style="font-size:13px;">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Forcer l'approbation malgré le stock insuffisant
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="stockCheckLoading" style="display:none;" class="text-center py-3 text-muted">
                            <div class="spinner-border spinner-border-sm me-2"></div>
                            Vérification du stock…
                        </div>

                    </div>

                    <div class="modal-footer" style="border-top:1px solid #f3f4f6;padding:16px 24px;">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" id="confirmApproveBtn" class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-check mr-1"></i> Confirmer l'approbation
                        </button>
                    </div>

                </div>
            </div>
        </div>

        @include('layouts.scripts')
        @include('layouts.delete')

        <script>

         // Gestion du rejet avec SweetAlert
$(document).on('click', '.btn-reject', function() {
    const url = $(this).data('url');

    Swal.fire({
        title: 'Rejeter la commande',
        text: 'Êtes-vous sûr ? Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, rejeter'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: url,
                method: 'POST',
                timeout: 20000, // évite un blocage indéfini si le serveur ne répond pas
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    window.location.reload();
                },
                error: function(xhr, status) {
                    console.log(xhr.responseText);
                    var msg = status === 'timeout'
                        ? 'Le serveur met trop de temps à répondre. Réessayez.'
                        : 'Impossible de rejeter la commande';
                    Swal.fire('Erreur', msg, 'error');
                }
            });
        }
    });
});
        $(function () {
            var csrfToken       = $('meta[name="csrf-token"]').attr('content');
            var currentCheckUrl = '';
            var currentApproveUrl = '';
            var stockOk = false;

            // Ouvrir le modal (Bootstrap 4)
            $(document).on('click', '.btn-approve', function () {
                currentCheckUrl   = $(this).data('check-url');
                currentApproveUrl = $(this).data('approve-url');

                $('#modalOrderLabel').text('Commande ' + $(this).data('order-label'));
                $('#storeSelect').val('');
                $('#stockCheckZone').hide();
                $('#stockCheckLoading').hide();
                $('#stockCheckList').html('');
                $('#confirmApproveBtn').prop('disabled', true);
                stockOk = false;

                $('#approveModal').modal('show');
            });
            // Fermeture manuelle de la modale
            $('#approveModal .close, #approveModal button[data-bs-dismiss="modal"]').on('click', function(e) {
                e.preventDefault();
                $('#approveModal').modal('hide');
            });

            // Également, après une erreur AJAX, on peut laisser le bouton réactiver sans fermer,
            // mais si vous voulez fermer en cas d'erreur :
            // error: function(xhr) { ...; $('#approveModal').modal('hide'); showToast(...); }

            // Vérification stock à chaque changement de boutique
            $('#storeSelect').on('change', function () {
                var storeId = $(this).val();
                $('#confirmApproveBtn').prop('disabled', true);
                $('#stockCheckZone').hide();
                $('#stockCheckList').html('');
                stockOk = false;

                if (!storeId) return;

                $('#stockCheckLoading').show();

                $.ajax({
                    url: currentCheckUrl + '?store_id=' + storeId,
                    timeout: 15000,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function (data) {
                        $('#stockCheckLoading').hide();
                        stockOk = data.all_ok;

                        var html = '';
                        $.each(data.items, function (i, item) {
                            var ok    = item.ok;
                            var icon  = ok ? '<i class="fas fa-check-circle text-success mr-2"></i>'
                                           : '<i class="fas fa-times-circle text-danger mr-2"></i>';
                            var badge = ok ? '<span class="badge badge-success">' + item.available + ' dispo</span>'
                                           : '<span class="badge badge-danger">' + item.available + ' dispo / ' + item.requested + ' demandé</span>';
                            html += '<div class="d-flex justify-content-between align-items-center py-2 px-3 mb-2" ' +
                                        'style="background:' + (ok ? '#f0fdf4' : '#fef2f2') + ';border-radius:10px;border-left:3px solid ' + (ok ? '#22c55e' : '#ef4444') + '">' +
                                        '<span style="font-size:13px;">' + icon + item.name + ' <em class="text-muted">× ' + item.requested + '</em></span>' +
                                        badge +
                                    '</div>';
                        });

                        $('#stockCheckList').html(html);
                        $('#stockCheckZone').show();

                        if (!data.all_ok) {
                            $('#forceApproveZone').show();
                            $('#forceApproveCheck').prop('checked', false);
                            $('#confirmApproveBtn').prop('disabled', true);
                        } else {
                            $('#forceApproveZone').hide();
                            $('#confirmApproveBtn').prop('disabled', false);
                        }
                    },
                    error: function () {
                        $('#stockCheckLoading').hide();
                        $('#stockCheckList').html('<div class="alert alert-warning py-2" style="font-size:13px;">Impossible de vérifier le stock.</div>');
                        $('#stockCheckZone').show();
                    }
                });
            });
            

            // Confirmer → POST via $.ajax puis redirect
            $('#confirmApproveBtn').on('click', function () {
                if (!stockOk || !$('#storeSelect').val()) return;

                $('#confirmApproveBtn').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm mr-1"></span> Traitement…');

                $.ajax({
                    url:    currentApproveUrl,
                    method: 'POST',
                    timeout: 30000, // évite un blocage indéfini de la modale si le serveur traîne
                    data:   { _token: csrfToken, store_id: $('#storeSelect').val() },
                    success: function () {
                        window.location.reload();
                    },
                    error: function (xhr, status) {
                        $('#confirmApproveBtn').prop('disabled', false)
                            .html('<i class="fas fa-check mr-1"></i> Confirmer l\'approbation');
                        var msg = status === 'timeout'
                            ? 'Le serveur met trop de temps à répondre. Vérifiez si la commande a été traitée avant de réessayer.'
                            : (xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'Une erreur est survenue. Veuillez réessayer.');
                        showToast(msg, 'error');
                    }
                });
            });

            // Échappatoire : si la modale reste ouverte plus de 45s (requête bloquée
            // côté serveur sans jamais répondre, même sans timeout jQuery atteint),
            // on force sa fermeture pour ne pas immobiliser toute la page.
            $('#approveModal').on('shown.bs.modal', function () {
                clearTimeout(window.__approveModalSafety);
                window.__approveModalSafety = setTimeout(function () {
                    $('#approveModal').modal('hide');
                    showToast('La fenêtre a été fermée automatiquement (délai dépassé). Vérifiez la commande avant de réessayer.', 'warning');
                }, 45000);
            });
            $('#approveModal').on('hidden.bs.modal', function () {
                clearTimeout(window.__approveModalSafety);
            });
        });
        $(window).on('load', function () {
            $('#global-loader').fadeOut('slow');
        });
        </script>

    </body>
</html>
