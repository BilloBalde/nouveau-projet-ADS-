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
                            <h4>Products Update</h4>
                            <h6>Update Product</h6>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('logistics.update', $logistic->id) }}" method="post">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="numeroPurchase">Numero Reference</label>
                                            <input type="text" name="numeroPurchase" value="{{ $logistic->numeroPurchase }}" class="form-control" readonly>
                                            <span class="text-danger">
                                                <strong class="numeroPurchase-error"></strong>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="depense">Dépense Total</label>
                                            <input type="text" name="depense" value="{{ $logistic->depense }}" class="form-control">
                                            <span class="text-danger">
                                                <strong class="depense-error"></strong>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="dateEmis">Date Emis</label>
                                            <input type="date" name="dateEmis" value="{{ $logistic->dateEmis }}" class="form-control">
                                            <span class="text-danger">
                                                <strong class="dateEmis-error"></strong>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="dateFournis">Date Fournis</label>
                                            <input type="date" name="dateFournis" value="{{ $logistic->dateFournis }}" class="form-control">
                                            <span class="text-danger">
                                                <strong class="dateFournis-error"></strong>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-submit">Confirm</button>
                                    <a href="{{ route('logistics.index') }}" class="btn btn-cancel">Retour</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
    </body>
</html>

