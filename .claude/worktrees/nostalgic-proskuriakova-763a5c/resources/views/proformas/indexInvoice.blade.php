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
                            <h4>Liste des Invoices</h4>
                            <h6>Gestion des Invoices</h6>
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
                                    <form action="{{ route('invoiceClients.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" list="identifyList" name="invoice_no" value="{{ request('invoice_no') }}" placeholder="invoice_no" class="form-control">
                                                    <datalist id="identifyList">
                                                        @foreach ($dataTable as $item)
                                                        <option value="{{ $item->invoice_no }}">{{ $item->invoice_no }}</option>
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="proforma_id" id="proforma_id" class="form-control">
                                                        <option value="">Selectionner Proforma</option>
                                                        @foreach ($proformas as $item)
                                                        <option value="{{ $item->id }}" {{ request('proforma_id') == $item->id ? 'selected' : '' }}>{{ $item->identifier }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="customer_id" id="customer_id" class="form-control">
                                                        <option value="">Selectionner Client</option>
                                                        @foreach ($customers as $item)
                                                        <option value="{{ $item->id }}" {{ request('customer_id') == $item->id ? 'selected' : '' }}>{{ $item->customerName }} - {{ $item->mark }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="status" id="status" class="form-control">
                                                        <option value="">Selectionner status</option>
                                                        @foreach ($enumValues as $item)
                                                        <option value="{{ $item }}">{{ $item }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="invoice_date" id="invoice_date" placeholder="selectionner date" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="assets/img/icons/search-whites.svg" alt="img"></button>
                                                    <a href="{{ route('invoiceClients.index') }}" class="btn btn-secondary">Reset</a>
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
                                            <th>No Invoice</th>
                                            <th>No Proforma</th>
                                            <th>Information Client</th>
                                            <th>Date Invoice</th>
                                            <th>Total Montant</th>
                                            <th>Interêt</th>
                                            <th>Statut</th>
                                            <th>Voir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $item)
                                        <tr>
                                            <td>{{ $item->invoice_no }}</td>
                                            <td>{{ $item->proforma->identifier }}</td>
                                            <td>{{ $item->customer->customerName }}-{{ $item->customer->mark }}</td>
                                            <td>{{ $item->invoice_date }}</td>
                                            <td>{{ $item->amount }} 元</td>
                                            <td>{{ $item->interest }} 元</td>
                                            <td>{{ $item->status }}</td>
                                            <td>
                                                <a href="{{ route('proformas.show', $item->proforma->identifier) }}"><img src="assets/img/icons/eye1.svg" class="me-2" alt="img"></a>
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


