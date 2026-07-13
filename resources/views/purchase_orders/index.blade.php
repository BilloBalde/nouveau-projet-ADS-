<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            .dataTables_paginate, .dataTables_info {
                display: none !important;
            }
            .po-status {
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }
            .po-status-pending { background: #fff4de; color: #ffab00; }
            .po-status-received { background: #e2f5e9; color: #2ea86f; }
            .po-status-cancelled { background: #fdecea; color: #e04f4f; }
            .po-origin-chine { color: #c1682f; font-weight: 600; }
            .po-origin-guinee { color: #2e7dc1; font-weight: 600; }
        </style>
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
                            <h4>Achats / Imports</h4>
                            <h6>Gérer vos arrivages et calculer le coût de revient réel</h6>
                        </div>
                        <div class="page-btn">
                            <a class="btn btn-added" href="{{ route('purchase-orders.create') }}">
                                <img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Nouvel Achat
                            </a>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-path">
                                        <a class="btn btn-filter" id="filter_search">
                                            <img src="assets/img/icons/filter.svg" alt="img">
                                            <span><img src="assets/img/icons/closes.svg" alt="img"></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card" id="filter_inputs">
                                <div class="card-body pb-0">
                                    <form action="{{ route('purchase-orders.index') }}" method="GET">
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="reference" value="{{ request('reference') }}" placeholder="Référence" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="store_id" class="form-control">
                                                        <option value="">Tous les magasins</option>
                                                        @foreach($stores as $store)
                                                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>{{ $store->store_name ?? $store->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="origin" class="form-control">
                                                        <option value="">Toutes origines</option>
                                                        <option value="chine" {{ request('origin') == 'chine' ? 'selected' : '' }}>Chine</option>
                                                        <option value="guinee" {{ request('origin') == 'guinee' ? 'selected' : '' }}>Guinée</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="status" class="form-control">
                                                        <option value="">Tous statuts</option>
                                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>En attente</option>
                                                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Reçu</option>
                                                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Annulé</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="assets/img/icons/search-whites.svg" alt="img"></button>
                                                    <a href="{{ route('purchase-orders.index') }}" class="btn btn-secondary">Annuler</a>
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
                                            <th>Référence</th>
                                            <th>Magasin</th>
                                            <th>Origine</th>
                                            <th>Lignes</th>
                                            <th>CBM total</th>
                                            <th>Frais (transport+douane+divers)</th>
                                            <th>Statut</th>
                                            <th>Date émission</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($dataTable as $order)
                                        <tr>
                                            <td><a href="{{ route('purchase-orders.show', $order->id) }}">{{ $order->reference }}</a></td>
                                            <td>{{ $order->store->store_name ?? $order->store->name ?? '—' }}</td>
                                            <td>
                                                <span class="{{ $order->origin === 'chine' ? 'po-origin-chine' : 'po-origin-guinee' }}">
                                                    {{ $order->origin === 'chine' ? '🇨🇳 Chine' : '🇬🇳 Guinée' }}
                                                </span>
                                            </td>
                                            <td>{{ $order->items->count() }}</td>
                                            <td>{{ $order->total_cbm ? number_format($order->total_cbm, 3) : '—' }}</td>
                                            <td>{{ number_format($order->transport_cost_gnf + $order->customs_cost_gnf + $order->other_fees_gnf, 0, ',', ' ') }} GNF</td>
                                            <td>
                                                @php
                                                    $statusLabels = ['pending' => 'En attente', 'received' => 'Reçu', 'cancelled' => 'Annulé'];
                                                @endphp
                                                <span class="po-status po-status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                                            </td>
                                            <td>{{ \Illuminate\Support\Carbon::parse($order->date_emis)->format('d/m/Y') }}</td>
                                            <td class="text-end">
                                                <a class="me-3" href="{{ route('purchase-orders.show', $order->id) }}" title="Voir">
                                                    <img src="assets/img/icons/eye.svg" alt="img">
                                                </a>
                                                @if($order->status !== 'cancelled')
                                                <a class="me-3" href="{{ route('purchase-orders.edit', $order->id) }}" title="Modifier">
                                                    <img src="assets/img/icons/edit.svg" alt="img">
                                                </a>
                                                <form action="{{ route('purchase-orders.destroy', $order->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Annuler cet achat ? Le stock ajouté sera retiré.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link p-0" title="Annuler">
                                                        <img src="assets/img/icons/delete.svg" alt="img">
                                                    </button>
                                                </form>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="9" class="text-center">Aucun achat enregistré pour le moment.</td>
                                        </tr>
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
        @include('layouts.scripts')
        <script>
            $(document).ready(function () {
                if ($.fn.DataTable.isDataTable('.datanew')) {
                    $('.datanew').DataTable().order([7, 'desc']).draw();
                }
            });
        </script>
    </body>
</html>
