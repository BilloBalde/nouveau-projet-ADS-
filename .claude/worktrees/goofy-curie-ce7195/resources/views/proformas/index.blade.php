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
                            <h6>Gestion des Proformas</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('proformas.create') }}" class="btn btn-added"><img src="assets/img/icons/plus.svg" alt="img" class="me-2">Faire Achat</a>
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
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="assets/img/icons/search-white.svg" alt="img"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="card" id="filter_inputs">
                                <div class="card-body pb-0">
                                    <form action="{{ route('proformas.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" list="identifyList" name="identifier" value="{{ request('identifier') }}" placeholder="identifier" class="form-control">
                                                    <datalist id="identifyList">
                                                        @foreach ($dataTable as $item)
                                                        <option value="{{ $item->identifier }}">{{ $item->identifier }}</option>
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="status" id="status" class="form-control">
                                                        <option value="">Selectionner status</option>
                                                        @foreach ($enumValues as $item)
                                                        <option value="{{ $item }}">{{ $item }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="date_achat" id="date_achat" placeholder="Sélectionner une date" class="form-control" onfocus="(this.type='date')" onblur="if(!this.value)this.type='text'">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="assets/img/icons/search-whites.svg" alt="img"></button>
                                                    <a href="{{ route('proformas.index') }}" class="btn btn-secondary">Reset</a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Date Commande</th>
                                            <th>No Achat</th>
                                            <th>Total Cartons</th>
                                            <th>Transport</th>
                                            <th>Total Montant</th>
                                            <th>Statut</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $item)
                                        <tr>
                                            <td>{{ $item->date_achat }}</td>
                                            <td>{{ $item->identifier }}</td>
                                            <td>{{ $item->total_ctns }}</td>
                                            <td>{{ $item->shippment }} FG</td>
                                            <td>{{ $item->total_amount }} FG</td>
                                            <td>{{ $item->status }}</td>
                                            <td>
                                                
                                                @if ($item->status !== 'delivered')
                                                <a href="{{ route('proformas.editInvoice', $item->id) }}" class="me-3"><img src="assets/img/icons/edit.svg" class="me-2" alt="img"></a>
                                                <a
                                                    type="button"
                                                    class="me-3 deleteButtionItem"
                                                    data-bs-toggle="modal"
                                                    data-id="{{ $item->identifier }}"
                                                    data-bs-target="#confirmDeleteModal"
                                                    onclick="setDeleteFormAction('{{ route('proformas.destroy', $item->id) }}')">
                                                    <img src="assets/img/icons/delete.svg" alt="img">
                                                </a>
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
        @include('proformas.delete')
    </body>
</html>