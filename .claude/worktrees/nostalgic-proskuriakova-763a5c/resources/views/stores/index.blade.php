<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        {{-- <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div> --}}
    <style>
        .word-wrap {
            white-space: normal;
            overflow-wrap: break-word; /* Break long words if necessary */
            word-wrap: break-word;
            max-width: 200px;
        }
    </style>
        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste des Stocks</h4>
                            <h6>Gerer vos Stocks</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('boutiques.create') }}" class="btn btn-added"><img src="assets/img/icons/plus.svg" alt="img" class="me-2">Add Store</a>
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
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="assets/img/icons/search-white.svg" alt="img"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Nom Stock</th>
                                            <th>Localité</th>
                                            <th>Vendeur</th>
                                            <th>Image</th>
                                            <th>Status</th>
                                            <th>Description</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $item)
                                        <tr>
                                            <td>{{ $item->store_name }}</td>
                                            <td>{{ $item->place }}</td>
                                            <td>{{ $item->user }}</td>
                                            <td>
                                                <img src="{{ asset('stores/' . $item->store_picture) }}" alt="product" style="width: 180px; height: 150px;">
                                            </td>
                                            <td>
                                                <div class="status-toggle d-flex justify-content-between align-items-center">
                                                    <input type="checkbox" id="user1" class="check" {{ ($item->status == 1) ?  'checked' : ''}}>
                                                    <label for="user1" class="checktoggle">checkbox</label>
                                                </div>
                                            </td>
                                            <td style="word-wrap: break-word; white-space: normal;">{{ $item->description }}</td>
                                            <td>{{ $item->updated_at }}</td>
                                            <td>
                                                @if (Auth::user() && Auth::user()->role_id != 3)
                                                <a class="me-3" href="{{ route('boutiques.edit', $item->id) }}">
                                                    <img src="assets/img/icons/edit.svg" alt="img">
                                                </a>
                                                <a
                                                    type="button"
                                                    class="me-3 deleteButtionItem"
                                                    data-bs-toggle="modal"
                                                    data-slug="{{ $item->store_name }}"
                                                    data-bs-target="#confirmDeleteModal"
                                                    onclick="setDeleteFormAction('{{ route('boutiques.destroy', $item->id) }}')">
                                                    <img src="assets/img/icons/delete.svg" class="me-2" alt="img">
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
        @include('layouts.delete')
        @include('stores.delete')
    </body>
</html>


