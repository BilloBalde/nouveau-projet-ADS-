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
                            <h4>Payment Settings</h4>
                            <h6>Manage Payment Settings</h6>
                        </div>
                        <div class="page-btn">
                            <a class="btn btn-added" data-bs-toggle="modal" data-bs-target="#addpayment"><img src="{{ asset('assets/img/icons/plus.svg') }}" alt="img" class="me-2">Add Payment Settings</a>
                        </div>
                    </div>

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
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>
                                                <label class="checkboxs">
                                                    <input type="checkbox">
                                                    <span class="checkmarks"></span>
                                                </label>
                                            </th>
                                            <th>Payment Type Name</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $data)
                                        <tr>
                                            <td>
                                                <label class="checkboxs">
                                                    <input type="checkbox">
                                                    <span class="checkmarks"></span>
                                                </label>
                                            </td>
                                            <td>{{ $data->typeName }}</td>
                                            <td>
                                                <div class="status-toggle d-flex justify-content-between align-items-center">
                                                    <input type="checkbox" id="user1" class="check" {{ ($data->status == 1) ? 'checked' : ''}}>
                                                    <label for="user1" class="checktoggle">checkbox</label>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a class="me-3 editbtn" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#editpayment" data-id="{{ $data->id }}" data-typeNom="{{ $data->typeName }}" data-status="{{ $data->status }}">
                                                    <img src="{{ asset('assets/img/icons/edit.svg') }}" alt="img">
                                                </a>
                                                <a class="me-3 confirm-text deletebtn" href="javascript:void(0);">
                                                    <img src="{{ asset('assets/img/icons/delete.svg') }}" alt="img">
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

        <div class="modal fade" id="addpayment" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add payment type</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('paymentSetting.store') }}" method="post">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="typeName">Payment Name</label>
                                        <input type="text" name="typeName" id="typeName">
                                        <span class="text-danger">
                                            <strong id="typeName-error"></strong>
                                        </span>
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
                                            <strong id="status-error"></strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer ">
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
                        <h5 class="modal-title">Edit payment type</h5>
                        <button type="button" id="fermerModal" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('paymentSetting.store') }}" method="post" id="editpaymentForm">
                        @csrf
                        <input type="hidden" name="id" id="id">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="typeName">Payment Name</label>
                                        <input type="text" name="typeName" id="typeName">
                                        <span class="text-danger">
                                            <strong id="typeName-error"></strong>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-control" id="status" name="status">
                                            <option>Select Option</option>
                                            <option value=1> Active</option>
                                            <option value=0> InActive</option>
                                        </select>
                                        <span class="text-danger">
                                            <strong id="status-error"></strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-submit" id="submitForm">Update</button>
                            <button type="reset" class="btn btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    @include('layouts.scripts')

    <script>
        $('.editbtn').click(function(){
            console.log("Je tombe ici!");
            var id = $(this).data('id');
            var typeNom = $(this).data('typeNom');
            var status = $(this).data('status');
            console.log(typeNom);

            $('#id').val(id);
            $('#typeName').val(typeName);
            $('#status').val(status);
        });
        $('#fermerModal').click(function(){
            $('#id').val("");
            $('#typeName').val("");
            $('#status').val("");
            $('#typeName-error').html( "" );
            $('#status-error' ).html( "" );
        });
        $('.deletebtn').click(function(){
            var id = $(this).data('id');
            $('#id').val(id);
        });
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $("#submitForm").click(function(e){
            console.log("Je tombe ici non");

            e.preventDefault();

            var formData = new FormData(document.getElementById('Register'));

            $( '#typeName-error' ).html( "" );
            $( '#status-error' ).html( "" );

            $.ajax({
                type:'POST',
                url:"{{ route('paymentSetting.store') }}",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                /* data:{id:id, categorie:categorie, slug:slug, typeCategory:typeCategory}, */
                success:function(data){
                    if($.isEmptyObject(data.error)){
                        //alert(data.success);
                        location.reload();
                    } else if(!$.isEmptyObject(data.noEntry)){
                        printErrorMsg(data.noEntry);
                    } else{
                        //
                        $( '#typeName-error' ).html( data.error.typeName );
                        $( '#status-error' ).html( data.error.status );
                    }
                }
            });

        });

        function printErrorMsg (noEntry) {
            $(".print-error-msg").find("ul").html('');
            $(".print-error-msg").css('display','block');
            $(".print-error-msg").find("ul").append('<li>'+noEntry+'</li>');
        }
    </script>
    </body>
</html>
