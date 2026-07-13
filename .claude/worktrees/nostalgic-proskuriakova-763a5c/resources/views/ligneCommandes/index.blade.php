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
                            <h4>Lignes de Commande</h4>
                            <h6>Gerer vos Commandes</h6>
                        </div>

                    </div>

                    <!-- Success and Error Alerts -->
                    @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                    @endif

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
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="assets/img/icons/search-white.svg" alt="img"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="card" id="filter_inputs">
                                <div class="card-body pb-0">
                                    <form action="{{ route('ligneCommandes.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="achat_id" id="achat_id" class="form-control">
                                                        <option value="">Selectionner Proforma</option>
                                                        @foreach ($proformas as $item)
                                                        <option value="{{ $item->id }}" {{ request('achat_id') == $item->id ? 'selected' : '' }}>{{ $item->identifier }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="assets/img/icons/search-whites.svg" alt="img"></button>
                                                    <a href="{{ route('ligneCommandes.index') }}" class="btn btn-secondary">Reset</a>
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
                                            <th>Proforma No</th>
                                            <th>Picture</th>
                                            <th>Item No</th>
                                            <th>Cartons</th>
                                            <th>Prix Achat</th>
                                            <th>Prix Revient</th>
                                            <th>Montant</th>
                                            <th>Prix Vente</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $dataItem)
                                        <tr>
                                            <td>{{ $dataItem->achat->identifier }}</td>
                                            <td>
                                                <img src="{{ asset('products/' . $dataItem->product->image) }}" alt="product" style="height:100px">
                                            </td>
                                            <td>{{ $dataItem->product->libelle }}</td>
                                            <td>{{ $dataItem->cartons }} CTNS</td>
                                            <td>{{ $dataItem->unit_price_purchase }} FG</td>
                                            <td>{{ $dataItem->unit_price_sale }} FG</td>
                                            <td>{{ $dataItem->montant_sale }} FG</td>
                                            <td>{{ $dataItem->ctn_price_sale }} FG</td>
                                            <td>
                                            @if ($dataItem->achat->status == "commanded")
                                            <a class="me-3" data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $dataItem->id }}"
                                               data-id="{{ $dataItem->id }}">
                                                <img src="{{ asset('assets/img/icons/delete.svg') }}" alt="img">
                                            </a>
                                            @include('ligneCommandes.delete')
                                            @else
                                                Aucune Action
                                            @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')

    </body>
</html>
