@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Gestion des Stocks</h4>
            <h6>Modifier Boutique</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form action="{{ route('boutiques.update', $store->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="text" name="id" id="id" Value="{{ $store->id }}" hidden>
                <div class="row">
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label><strong>Nom du Stock</strong></label>
                            <input type="text" name="store_name" class="form-control" value="{{ $store->store_name }}">
                        </div>
                        @error('store_name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label><strong>Nom de la Localité</strong></label>
                            <select name="place_id" id="" class="form-control">
                                <option value="">Selectionner la Localité</option>
                                @foreach ($places as $item)
                                <option value="{{ $item->id }}" {{ ($store->place_id==$item->id) ? 'selected' : '' }}>{{ $item->placeName }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('place_id')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label><strong>Nom utilisateur</strong></label>
                            <select name="user_id" id="" class="form-control">
                                <option value="">Selectionner le Vendeur</option>
                                @foreach ($users as $item)
                                <option value="{{ $item->id }}" {{ ($store->user_id==$item->id) ? 'selected' : '' }}>{{ $item->username }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('user_id')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label><strong>Status</strong></label>
                            <select name="status" id="" class="form-control">
                                <option value="">Selectionner le Status</option>
                                <option value=1 {{ ($store->status==1) ? 'selected' : '' }}>Oui</option>
                                <option value=0 {{ ($store->status==0) ? 'selected' : '' }}>No</option>
                            </select>
                        </div>
                        @error('status')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label><strong>Description</strong></label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control">
                                {{ old('description', $store->description) }}
                            </textarea>
                        </div>
                        @error('description')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label><strong>Image</strong></label>
                            <img src="{{ asset('stores/' . $store->store_picture) }}" style="height: 200px"  alt="product">
                            <div class="image-upload">
                                <input type="file" name="store_picture" class="form-control">
                                <div class="image-uploads">
                                    <img style="max-height: 10%"  src="{{ asset('assets/img/icons/upload.svg') }}" alt="img">
                                    <h4>Drag and drop a file to upload</h4>
                                </div>
                            </div>
                        </div>
                        @error('store_picture')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-submit me-2">Soumettre</button>
                        <a href="{{ route('boutiques.index') }}" class="btn btn-cancel">Annuler</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

