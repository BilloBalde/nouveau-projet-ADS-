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
                            <h4>Group Permissions</h4>
                            <h6>Créez des rôles et cochez les actions qu'ils autorisent</h6>
                        </div>
                        <div class="page-btn">
                            <a class="btn btn-added" href="{{ route('roles.create') }}" style="background-color:#c1682f;border-color:#c1682f;">
                                <img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-1">Ajouter un rôle
                            </a>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-input">
                                    <a class="btn btn-searchset"><img src="assets/img/icons/search-white.svg" alt="img"></a>
                                    </div>
                                </div>
                                <div class="wordset">
                                    <ul>
                                        <li>
                                            <a data-bs-toggle="tooltip" data-bs-placement="top" title="pdf"><img src="assets/img/icons/pdf.svg" alt="img"></a>
                                        </li>
                                        <li>
                                            <a data-bs-toggle="tooltip" data-bs-placement="top" title="excel"><img src="assets/img/icons/excel.svg" alt="img"></a>
                                        </li>
                                        <li>
                                            <a data-bs-toggle="tooltip" data-bs-placement="top" title="print"><img src="assets/img/icons/printer.svg" alt="img"></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Rôle</th>
                                            <th>Identifiant</th>
                                            <th>Permissions</th>
                                            <th>Utilisateurs</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $d)
                                        <tr>
                                            <td><strong>{{ $d->nameRole }}</strong></td>
                                            <td>{{ $d->slug }}</td>
                                            <td>
                                                @if($d->slug === 'admin')
                                                    <span class="badge bg-success">Toutes (accès total)</span>
                                                @elseif($d->slug === 'customer')
                                                    <span class="badge bg-secondary">Client e-commerce</span>
                                                @else
                                                    <span class="badge" style="background:#c1682f;">{{ $d->permissions_count }} action(s)</span>
                                                @endif
                                            </td>
                                            <td>{{ $d->users_count }}</td>
                                            <td class="text-end">
                                                @if($d->slug !== 'admin' && $d->slug !== 'customer')
                                                <a class="me-3" href="{{ route('roles.edit', $d->id) }}" title="Modifier les permissions">
                                                    <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                                </a>
                                                <form method="POST" action="{{ route('roles.destroy', $d->id) }}" class="d-inline"
                                                      onsubmit="return confirm('Supprimer le rôle {{ $d->nameRole }} ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="border-0 bg-transparent p-0" title="Supprimer">
                                                        <img src="{{ asset('assets/img/icons/delete.svg') }}" alt="img">
                                                    </button>
                                                </form>
                                                @else
                                                <span class="text-muted" title="Rôle système">—</span>
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
        <script src="assets/js/jquery-3.6.0.min.js"></script>

        <script src="assets/js/feather.min.js"></script>

        <script src="assets/js/jquery.slimscroll.min.js"></script>

        <script src="assets/js/jquery.dataTables.min.js"></script>
        <script src="assets/js/dataTables.bootstrap4.min.js"></script>

        <script src="assets/js/bootstrap.bundle.min.js"></script>

        <script src="assets/plugins/select2/js/select2.min.js"></script>

        <script src="assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
        <script src="assets/plugins/sweetalert/sweetalerts.min.js"></script>

        <script src="assets/js/script.js"></script>
    </body>
</html>
