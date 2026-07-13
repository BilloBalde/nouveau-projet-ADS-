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
                            <h4>Currency Settings</h4>
                            <h6>Manage Currency Settings</h6>
                        </div>
                        <div class="page-btn">
                            <a class="btn btn-added" data-bs-toggle="modal" data-bs-target="#addpayment"><img src="assets/img/icons/plus.svg" alt="img" class="me-1">Add New Currency</a>
                        </div>
                    </div>
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
                                    </ul>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Currency name</th>
                                            <th>Currency code</th>
                                            <th>Currency symbol</th>
                                            <th>Taux → GNF</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $d)
                                        <tr>
                                            <td>{{ $d->currencyName }}</td>
                                            <td>{{ $d->currencyCode}}</td>
                                            <td>{{ $d->currencySymbol}}</td>
                                            <td>{{ $d->rate_to_gnf ? number_format($d->rate_to_gnf, 4) : '—' }}</td>
                                            <td>
                                                <div class="status-toggle d-flex justify-content-between align-items-center">
                                                    <input type="checkbox" id="user1" class="check" {{ ($d->status==1) ? 'checked' : '' }} disabled>
                                                    <label for="user1" class="checktoggle">checkbox</label>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a class="me-3 edit-currency-btn" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#editpayment"
                                                   data-id="{{ $d->id }}"
                                                   data-name="{{ $d->currencyName }}"
                                                   data-code="{{ $d->currencyCode }}"
                                                   data-symbol="{{ $d->currencySymbol }}"
                                                   data-rate="{{ $d->rate_to_gnf }}"
                                                   data-status="{{ $d->status }}">
                                                    <img src="assets/img/icons/edit.svg" alt="img">
                                                </a>
                                                <form action="{{ route('currencySetting.destroy', $d->id) }}" method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette devise ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link p-0 me-3">
                                                        <img src="assets/img/icons/delete.svg" alt="img">
                                                    </button>
                                                </form>
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

        <div class="modal fade" id="addpayment" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Currency </h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('currencySetting.store') }}" method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="currencyName">Currency Name<span class="manitory">*</span></label>
                                        <input type="text" name="currencyName" id="currencyName">
                                        <span class="text-danger">
                                            <strong id="currencyName-error"></strong>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="currencyCode">Currency Code</label>
                                        <input type="text" name="currencyCode" id="currencyCode">
                                        <span class="text-danger">
                                            <strong id="currencyCode-error"></strong>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="currencySymbol">Currency Symbol<span class="manitory">*</span></label>
                                        <input type="text" name="currencySymbol" id="currencySymbol">
                                        <span class="text-danger">
                                            <strong id="currencySymbol-error"></strong>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="rate_to_gnf">Taux → GNF (1 unité = X GNF)</label>
                                        <input type="text" inputmode="decimal" class="form-control decimal-input" name="rate_to_gnf" id="rate_to_gnf">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label for="status">Status</label>
                                        <select class="select" name="status" id="status">
                                            <option>Choose Status</option>
                                            <option value=1> Active</option>
                                            <option value=0> InActive</option>
                                        </select>
                                        <span class="text-danger">
                                            <strong id="typeName-error"></strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-submit">Confirm</button>
                            <button type="reset" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editpayment" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Currency</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="editCurrencyForm" action="" method="post">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Currency Name<span class="manitory">*</span></label>
                                        <input type="text" name="currencyName" id="edit_currencyName">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Currency Code</label>
                                        <input type="text" name="currencyCode" id="edit_currencyCode">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Currency Symbol<span class="manitory">*</span></label>
                                        <input type="text" name="currencySymbol" id="edit_currencySymbol">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Taux → GNF (1 unité = X GNF)</label>
                                        <input type="text" inputmode="decimal" class="form-control decimal-input" name="rate_to_gnf" id="edit_rate_to_gnf">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group mb-0">
                                        <label>Status</label>
                                        <select class="select" name="status" id="edit_status">
                                            <option value="1">Active</option>
                                            <option value="0">InActive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-submit">Update</button>
                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
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
    <script>
        document.querySelectorAll('.edit-currency-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                document.getElementById('editCurrencyForm').action = '{{ url("currencySetting") }}/' + id;
                document.getElementById('edit_currencyName').value = this.getAttribute('data-name') || '';
                document.getElementById('edit_currencyCode').value = this.getAttribute('data-code') || '';
                document.getElementById('edit_currencySymbol').value = this.getAttribute('data-symbol') || '';
                document.getElementById('edit_rate_to_gnf').value = this.getAttribute('data-rate') || '';
                document.getElementById('edit_status').value = this.getAttribute('data-status') || '1';
            });
        });

        // Champs de taux : on tape librement (virgule ou point), on ne garde que les
        // chiffres et un seul séparateur décimal (converti en point tout de suite)
        // (évite les soucis des inputs type=number avec le clavier/locale française).
        document.querySelectorAll('.decimal-input').forEach(function (input) {
            input.addEventListener('input', function () {
                let value = input.value.replace(/[^0-9.,]/g, '').replace(',', '.');
                const firstDot = value.indexOf('.');
                if (firstDot !== -1) {
                    value = value.slice(0, firstDot + 1) + value.slice(firstDot + 1).replace(/\./g, '');
                }
                input.value = value;
            });
        });
    </script>
    </body>
</html>

