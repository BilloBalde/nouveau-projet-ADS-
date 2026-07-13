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
                            <h4>Liste des Prises Journaliers</h4>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('journaliers.create') }}" class="btn btn-added"><img src="assets/img/icons/plus.svg" alt="img" class="me-2">Ajouter Dette</a>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="{{ asset('assets/img/icons/search-white.svg') }}" alt="img"></a>
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
                            <div class="card" id="filter_inputs">
                                <div class="card-body pb-0">
                                    <form action="{{ route('journaliers.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-6 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="datePrise" value="{{ request('datePrise') }}" placeholder="date de la dette" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="{{ asset('assets/img/icons/search-whites.svg') }}" alt="img"></button>
                                                    <button id="reset-filters" type="button" class="btn btn-secondary">Réinitialiser</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table dataitems">
                                    <thead>
                                        <tr>
                                            <th>Date Prise</th>
                                            <th>Nom du Prenant</th>
                                            <th>Montant Pris</th>
                                            <th>Total Paid</th>
                                            <th>Reste</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                            <th>Admin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $journalier)
                                        <tr>
                                            <td>{{ $journalier->datePrise }}</td>
                                            <td>{{ $journalier->nomPrenant }}</td>
                                            <td>{{ $journalier->montant }} FG</td>
                                            <td>{{ $journalier->paid }} FG</td>
                                            <td>{{ $journalier->reste }} FG</td>
                                            <td>{{ $journalier->contenu }}</td>
                                            <td>
                                                <a class="me-3" href="{{ route('journaliers.edit', $journalier->id) }}">
                                                    <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                                </a>
                                                <a
                                                    type="button"
                                                    class="me-3 deleteButtionItem"
                                                    data-bs-toggle="modal"
                                                    data-id="{{ $journalier->nomPrenant.'-'.$journalier->id }}"
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
        @include('layouts.delete')
        @include('journaliers.delete')
    </body>
</html>



