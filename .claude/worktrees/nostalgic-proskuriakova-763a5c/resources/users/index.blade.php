<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.header')
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
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste des Utilisateurs</h4>
                            <h6>Gérer vos Utilisateurs</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('addUser') }}" class="btn btn-added"><img src="assets/img/icons/plus.svg" alt="img" class="me-2">Ajouter Utilisateur</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-input">
                                        <a class="btn btn-searchset">
                                            <img src="assets/img/icons/search-white.svg" alt="img">
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Phone</th>
                                            <th>email</th>
                                            <th>Nom Utilisateur</th>
                                            <th>Mot de Passe</th>
                                            <th>Status</th>
                                            <th>Role</th>
                                            <th>Date Creation</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $item)
                                        <tr>
                                            <td>{{ $item->name }}</td>
                                            <td>{{ $item->phone }}</td>
                                            <td>{{ $item->email }}</td>
                                            <td>{{ $item->username }}</td>
                                            <td>{{ $item->motdepasse }}</td>
                                            <td>
                                                <div class="status-toggle d-flex justify-content-between align-items-center">
                                                    <input type="checkbox" id="user1" class="check" {{ ($item->status == 'Active') ?  'checked' : ''}}>
                                                    <label for="user1" class="checktoggle">checkbox</label>
                                                </div>
                                            </td>
                                            <td>{{ $item->role }}</td>
                                            <td>{{ $item->updated_at }}</td>
                                            <td class="text-end">
                                                <a class="me-3" href="{{ route('editUser', $item->id) }}">
                                                    <img src="assets/img/icons/edit.svg" alt="img">
                                                </a>
                                                  <a
                                                        type="button"
                                                        class="me-3 deleteButtionItem"
                                                        data-bs-toggle="modal"
                                                        data-slug="{{ $item->name }}"
                                                        data-bs-target="#confirmDeleteModal"
                                                        onclick="setDeleteFormAction('{{ route('deleteUser', $item->id) }}')">
                                                        <img src="assets/img/icons/delete.svg" class="me-2" alt="img">
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
        @include('layouts.delete')
        @include('users.delete')
    </body>
</html>

