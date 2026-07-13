@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Dettes Management</h4>
            <h6>Update Ligne Commande {{ $commande->id }}</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form action="{{ route('ligneCommandes.update', $commande->id) }}" method="POST">
                @csrf
                @method('PUT') <!-- This is important for PUT method -->
                <div class="row">
                    <div class="col-sm-4 col-lg-4 col-12">
                        <img src="{{ asset('products/'.$commande->product->image) }}" style="width: 200px; height:220px" alt="">
                        <input type="file" name="preuve" class="form-control" placeholder="Cliquer ici pour modifier la preuve">
                    </div>
                    <div class="col-sm-8 col-lg-8 col-12">
                        <div class="row">
                            <div class="col-sm-6 col-lg-6 col-12">
                                <div class="form-group">
                                    <label for="item_no"><strong>Item No</strong></label>
                                    <input type="text" name="item_no" id="item_no" value="{{ $commande->product->item_no }}"  class="form-control" readonly>
                                </div>
                                @error('item_no')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-sm-6 col-lg-6 col-12">
                                <div class="form-group">
                                    <label for="cartons">Nombre de Cartons</label>
                                    <input type="number" id="cartons" name="cartons" class="form-control" value="{{ $commande->cartons }}" placeholder="Entrer le nombre de pcs/ctn : 20">
                                    @error('cartons')
                                    <span class="error-danger"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-6 col-12">
                                <div class="form-group">
                                    <label for="price">Prix de Office</label>
                                    <input type="text" id="price" name="price" class="form-control" value="{{ $commande->unit_price }}" placeholder="Entrer le nombre de pcs/ctn : 20">
                                    @error('price')
                                    <span class="error-danger"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-6 col-12">
                                <div class="form-group">
                                    <label for="price2">Prix du Shop</label>
                                    <input type="text" id="price2" name="price2" class="form-control" value="{{ $commande->price_shop }}" placeholder="Entrer le nombre de pcs/ctn : 20">
                                    @error('price2')
                                    <span class="error-danger"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <button type="submit" class="btn btn-submit me-2">Modifier</button>
                                <a href="{{ route('ligneCommandes.index') }}" class="btn btn-cancel">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

