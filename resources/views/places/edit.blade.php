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
                            <h4>Gestion des Localités</h4>
                            <h6>Modifier Localité {{ $place->id }}</h6>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('places.update', $place->id) }}" method="post" id="Register">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="placeName">Nom de la localité</label>
                                            <input type="text" id="placeName" name="placeName" value="{{ $place->placeName }}">
                                            <span class="error-danger"><strong id="placeName-error"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="countryName">Pays</label>
                                            <select class="select" id="countryName" name="countryName">
                                                <option>Choisir Pays</option>
                                                <option value="Guinea" {{ ($place->countryName == "Guinea") ? 'selected' : '' }}>Guinea</option>
                                                <option value="Liberia" {{ ($place->countryName == "Liberia") ? 'selected' : '' }}>Liberia</option>
                                                <option value="Sierra Leone" {{ ($place->countryName == "Sierra Leone") ? 'selected' : '' }}>Sierra Leone</option>
                                            </select>
                                            <span class="error-danger"><strong id="countryName-error"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea class="form-control" id="description" name="description">{{ $place->description }}</textarea>
                                            <span class="error-danger"><strong id="description-error"></strong></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-submit me-2">Soumettre</button>
                                        <a href="{{ route('places.index') }}" class="btn btn-cancel">Annuler</a>
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


