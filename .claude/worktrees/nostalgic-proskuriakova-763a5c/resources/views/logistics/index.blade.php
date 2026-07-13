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
                            <h4>Liste des Logistics</h4>
                            <h6>Gérer vos Logistics</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('purchases.index') }}" class="btn btn-added"><img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2"></a>
                        </div>
                    </div>

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
                                    <form action="{{ route('logistics.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="numeroPurchase" value="{{ request('numeroPurchase') }}" placeholder="Reference" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="typeLogistic" id="typeLogistic" class="form-control">
                                                        <option value="">Select type d'Achat</option>
                                                        <option value="conteneur" {{ request('typeLogistic') == 'conteneur' ? 'selected' : '' }}>Conteneur</option>
                                                        <option value="particulier" {{ request('typeLogistic') == 'particulier' ? 'selected' : '' }}>Particulier</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="dateEmis" value="{{ request('dateEmis') }}" placeholder="date creation" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="dateFournis" value="{{ request('dateFournis') }}" placeholder="date creation" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="assets/img/icons/search-whites.svg" alt="img"></button>
                                                    <a href="{{ route('logistics.index') }}" class="btn btn-secondary">Annuler</a>
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
                                            <th>Reference</th>
                                            <th>Type</th>
                                            <th>Stock</th>
                                            <th>Quantity</th>
                                            <th>Depense</th>
                                            <th>Date Emis</th>
                                            <th>Date Fournis</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $data)
                                        <tr>
                                            <td>{{ $data->numeroPurchase }}</td>
                                            <td>{{ $data->typeLogistic }}</td>
                                            <td>{{ $data->store ? $data->store->store_name : 'N/A' }}</td>
                                            <td>{{ $data->quantity }}</td>
                                            <td>{{ $data->depense }}</td>
                                            <td>{{ $data->dateEmis }}</td>
                                            <td>{{ $data->dateFournis }}</td>
                                            <td class="text-end">
                                                <a class="me-3" href="{{ route('logistics.edit', $data->id) }}">
                                                    <img src="assets/img/icons/edit.svg" alt="img">
                                                </a>
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
