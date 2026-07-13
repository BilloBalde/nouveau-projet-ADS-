<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        {{-- <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div> --}}

        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste des Dépenses</h4>
                            <h6>Gérer vos Dépenses</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('expenses.create') }}" class="btn btn-added"><img src="assets/img/icons/plus.svg" alt="img" class="me-2">Ajouter Dépense</a>
                        </div>
                    </div>
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
                                    <form action="{{ route('expenses.index') }}" method="GET"> <!-- Update to GET method -->
                                        @csrf
                                        <div class="row">
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" name="reference" placeholder="Reference" class="form-control" value="{{ request('reference') }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <select name="expense_categories_id" id="expense_categories_id" class="form-control">
                                                        <option value="">Selectionner category</option>
                                                        @foreach ($categories_expenses as $item)
                                                        <option value="{{ $item->id }}" {{ request('expense_categories_id') == $item->id ? 'selected' : '' }}>{{ $item->categoryName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="date" name="created_at" id="created_at" value="{{ request('created_at') }}" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="text" placeholder="Status" name="status" class="form-control" value="{{ request('status') }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <input type="number" step="0.01" placeholder="Amount" name="amount" class="form-control" value="{{ request('amount') }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-2 col-sm-6 col-12 d-flex align-items-center">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2"><img src="assets/img/icons/search-whites.svg" alt="img"></button>
                                                    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Reset</a>
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
                                            <th>Categorie</th>
                                            <th>Montant</th>
                                            <th>Status</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            @if(in_array(auth()->user()->role, ['admin', 'superuser']))
                                                <th>Magasin</th>
                                            @endif
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($expenses as $expense)
                                        <tr>
                                            <td>{{ $expense->reference }}</td>
                                            <td>{{ $expense->category->categoryName }}</td>
                                            <td>{{ $expense->amount }}</td>
                                            <td>{{ $expense->status }}</td>
                                            <td>{{ $expense->description }}</td>
                                            <td>{{ $expense->created_at }}</td>
                                            @if(in_array(auth()->user()->role, ['admin', 'superuser']))
                                                <td>{{ $expense->store->store_name ?? 'N/A' }}</td>
                                            @endif
                                            <td>
                                                <a class="me-3" href="{{ route('expenses.edit', $expense->id) }}">
                                                    <img src="assets/img/icons/edit.svg" alt="img">
                                                </a>
                                                <a class="btn me-3" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="{{ $expense->id }}" data-reference="{{ $expense->reference }}"><img src="assets/img/icons/delete.svg" alt="img"></a>
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
        <!-- Delete Confirmation Modal -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var deleteModal = document.getElementById('deleteModal');
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    var button = event.relatedTarget;
                    var deleteId = button.getAttribute('data-id');
                    var deleteRef = button.getAttribute('data-reference');
                    var form = deleteModal.querySelector('#deleteForm');
                    form.action = '/expenses/' + deleteId;

                    var expenseRefSpan = deleteModal.querySelector('#expense-reference');
                    expenseRefSpan.textContent = deleteRef;
                });
            });
        </script>
        @include('expenses.delete')
    </body>
</html>
