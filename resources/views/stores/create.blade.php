@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Gestion des Stocks</h4>
            <h6>Ajouter/Modifier Magasin</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form action="{{ route('boutiques.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label><strong>Nom du Stock</strong></label>
                            <input type="text" name="store_name" class="form-control">
                        </div>
                        @error('store_name')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom de la Localité</label>
                            <select name="place_id" id="place_id" class="form-control">
                                <option value="">Selectionner la Localité</option>
                                @foreach ($places as $item)
                                <option value="{{ $item->id }}">{{ $item->placeName }}</option>
                                @endforeach
                                <option value="add_place">Ajouter la localité</option>
                            </select>
                        </div>
                        @error('place_id')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Nom Agent</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="">Selectionner le Vendeur</option>
                                @foreach ($users as $item)
                                <option value="{{ $item->id }}">{{ $item->username }}</option>
                                @endforeach
                                <option value="add_agent">Ajouter l'agent</option>
                            </select>
                        </div>
                        @error('user_id')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-lg-6 col-sm-6 col-12">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="" class="form-control">
                                <option value="">Selectionner le Status</option>
                                <option value=1>Oui</option>
                                <option value=0>No</option>
                            </select>
                        </div>
                        @error('status')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label><strong>Description</strong></label>
                            <textarea name="description" id="description" cols="30" rows="10" class="form-control"></textarea>
                        </div>
                        @error('description')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label>Image</label>
                            <div class="image-upload">
                                <input type="file" name="store_picture" class="form-control">
                                <div class="image-uploads">
                                    <img src="{{ asset('assets/img/icons/upload.svg') }}" alt="img">
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

@section('script_perso')
<script>
    document.getElementById('place_id').addEventListener('change', function() {
        if (this.value === "add_place") {
            console.log('Place already');

            window.location.href = "{{ route('places.create') }}";
        }
    });
    document.getElementById('user_id').addEventListener('change', function() {
        if (this.value === "add_agent") {
            console.log('Place already');

            window.location.href = "{{ route('addUser') }}";
        }
    });
</script>
@endsection

