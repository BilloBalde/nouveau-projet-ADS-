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
                        <h4>Gestion Dettes</h4>
                    </div>
                    <div class="page-btn">
                        <a href="{{ url()->previous() }}" class="btn btn-added">
                            <img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2">
                        </a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flash')
                        <form action="{{ route('journaliers.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="nomPrenant"><strong>Nom du Prenant</strong></label>
                                        <input type="text" name="nomPrenant" id="nomPrenant" value="{{ old('nomPrenant') }}" class="form-control">
                                    </div>
                                    @error('nomPrenant')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="montant"><strong>Montant</strong></label>
                                        <input type="text" name="montant" id="montant" value="{{ old('montant') }}" class="form-control">
                                    </div>
                                    @error('montant')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="datePrise"><strong>Date</strong></label>
                                        <input type="date" name="datePrise" id="datePrise" value="{{ old('datePrise') }}" class="form-control">
                                    </div>
                                    @error('datePrise')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="contenu"><strong>Description</strong></label>
                                        <textarea name="contenu" id="contenu" cols="30" rows="4">
                                            {{ old('contenu') }}
                                        </textarea>
                                    </div>
                                    @error('contenu')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit" class="btn btn-submit me-2">Valider</button>
                                    <a href="{{ route('journaliers.create') }}" class="btn btn-secondary">Reset</a>
                                </div>
                            </div>
                        </form>
                    </div>
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
                                    <input type="text" id="search-input" class="form-control" placeholder="Search...">
                                </div>
                            </div>
                            <div class="wordset">
                                <ul>
                                    <li>
                                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="pdf"><img src="{{ asset('assets/img/icons/pdf.svg') }}" alt="img"></a>
                                    </li>
                                    <li>
                                        <a data-bs-toggle="tooltip" data-bs-placement="top" title="excel"><img src="{{ asset('assets/img/icons/excel.svg') }}" alt="img"></a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table dataitems">
                                <thead>
                                    <tr>
                                        <th>Nom du Prenant</th>
                                        <th>Montant Pris</th>
                                        <th>Total Paid</th>
                                        <th>Reste</th>
                                        <th>Date Prise</th>
                                        <th>Description</th>
                                        <th>Action</th>
                                        <th>Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($dataTable as $journalier)
                                    <tr>
                                        <td>{{ $journalier->nomPrenant }}</td>
                                        <td>{{ $journalier->montant }} FG</td>
                                        <td>{{ $journalier->paid }} FG</td>
                                        <td>{{ $journalier->reste }} FG</td>
                                        <td>{{ $journalier->datePrise }}</td>
                                        <td>{{ $journalier->contenu }}</td>
                                        <td>
                                            <a class="me-3" href="{{ route('journaliers.edit', $journalier->id) }}">
                                                <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                            </a>
                                            <a
                                                type="button"
                                                class="me-3 deleteButtionItem"
                                                data-bs-toggle="modal"
                                                data-id="{{ $journalier->datePrise }}"
                                                data-bs-target="#confirmDeleteModal"
                                                onclick="setDeleteFormAction('{{ route('journaliers.delete', $journalier->id) }}) }}')">
                                                <img src="{{ asset('assets/img/icons/delete.svg') }}" alt="img">
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            <a class="action-set" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="true">
                                                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                            </a>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a href="{{ route('journaliers.paymentVoir', $journalier->id) }}" class="dropdown-item"><img src="{{ asset('assets/img/icons/dollar-square.svg') }}" class="me-2" alt="img">Voir Paiements</a>
                                                </li>
                                                <li>
                                                    <a href="{{ route('journaliers.paymentForm', $journalier->id) }}" class="dropdown-item"><img src="{{ asset('assets/img/icons/plus-circle.svg') }}" class="me-2" alt="img">Faire Paiement</a>
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!-- Pagination Links -->
                            <div class="d-flex justify-content-between align-items-center">
                                <br>
                                <br>
                                <br>
                                <div>
                                    <nav aria-label="Page navigation example">
                                        <ul class="pagination">
                                            {{ $dataTable->links('pagination::bootstrap-5') }}
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layouts.scripts')
    @include('journaliers.delete')
</body>
</html>

