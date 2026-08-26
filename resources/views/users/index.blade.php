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
                            <a href="{{ route('addUser') }}" class="btn btn-added"><img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Ajouter Utilisateur</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-input">
                                        <a class="btn btn-searchset">
                                            <img src="{{ asset('assets/img/icons/search-white.svg') }}" alt="img">
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
                                            <td>{{ $item->role }}</td>
                                            <td>{{ $item->updated_at }}</td>
                                            <td class="text-end">
                                                <a class="me-3" href="{{ route('editUser', $item->id) }}">
                                                    <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                                </a>
                                                @if($item->role_id != 1)
                                                    @php $isActive = strtolower((string) $item->status) === 'active'; @endphp
                                                    <button type="button" class="btn btn-link p-0 border-0 me-3 toggleStatusBtn"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#confirmStatusModal"
                                                        data-name="{{ $item->name }}"
                                                        data-active="{{ $isActive ? '1' : '0' }}"
                                                        data-action="{{ route('users.toggleStatus', $item->id) }}"
                                                        title="{{ $isActive ? 'Suspendre' : 'Activer (compte suspendu)' }}">
                                                        <i class="fa {{ $isActive ? 'fa-ban text-danger' : 'fa-lock text-warning' }}"></i>
                                                    </button>
                                                @endif
                                                  <a
                                                        type="button"
                                                        class="me-3 deleteButtionItem"
                                                        data-bs-toggle="modal"
                                                        data-slug="{{ $item->name }}"
                                                        data-bs-target="#confirmDeleteModal"
                                                        onclick="setDeleteFormAction('{{ route('deleteUser', $item->id) }}')">
                                                        <img src="{{ asset('assets/img/icons/delete.svg') }}" class="me-2" alt="img">
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

        <div class="modal fade" id="confirmStatusModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="statusModalTitle">Confirmation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <i id="statusModalIcon" class="fa" style="font-size:42px;"></i>
                        <p class="mt-3 mb-0" id="statusModalMessage"></p>
                    </div>
                    <div class="modal-footer">
                        <form id="statusForm" method="POST" action="">
                            @csrf
                            <button type="submit" class="btn" id="statusModalConfirmBtn">Confirmer</button>
                        </form>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.toggleStatusBtn').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const name = this.dataset.name;
                        const isActive = this.dataset.active === '1';
                        const action = this.dataset.action;

                        document.getElementById('statusForm').setAttribute('action', action);

                        const icon = document.getElementById('statusModalIcon');
                        const title = document.getElementById('statusModalTitle');
                        const message = document.getElementById('statusModalMessage');
                        const confirmBtn = document.getElementById('statusModalConfirmBtn');

                        if (isActive) {
                            title.textContent = 'Suspendre le compte';
                            icon.className = 'fa fa-ban text-danger';
                            message.innerHTML = 'Voulez-vous vraiment suspendre le compte de <strong>' + name + '</strong> ? Il ne pourra plus se connecter tant qu\'il ne sera pas réactivé.';
                            confirmBtn.textContent = 'Suspendre';
                            confirmBtn.className = 'btn btn-danger';
                        } else {
                            title.textContent = 'Activer le compte';
                            icon.className = 'fa fa-lock text-warning';
                            message.innerHTML = 'Voulez-vous activer le compte de <strong>' + name + '</strong> ? Il pourra de nouveau se connecter.';
                            confirmBtn.textContent = 'Activer';
                            confirmBtn.className = 'btn btn-success';
                        }
                    });
                });
            });
        </script>
    </body>
</html>

