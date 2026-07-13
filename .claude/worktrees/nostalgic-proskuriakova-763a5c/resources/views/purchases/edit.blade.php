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
                            <h4>Gestion d'Achat</h4>
                            <h6>Modifier l'Achat</h6>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('purchases.update', $purchase->id) }}" method="post" id="Register" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="text" name="id" value="{{ $purchase->id }}" hidden>
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="libelle">Libelle</label>
                                            <input type="text" id="libelle" name="libelle" class="form-control" value="{{ $purchase->product->libelle }}" readonly>
                                            <span class="error-danger"><strong id="libelle-error"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="price">Prix</label>
                                            <input type="text" id="price" value="{{ $purchase->price }}" name="price" class="form-control">
                                            <span class="error-danger"><strong id="price-error"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="quantity">Quantité</label>
                                            <input type="text" id="quantity" value="{{ $purchase->quantity }}" name="quantity" class="form-control">
                                            <span class="error-danger"><strong id="quantity-error"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-submit me-2">Modifier</button>
                                        <a href="{{ route('purchases.index') }}" class="btn btn-cancel">Annuler</a>
                                    </div>
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
