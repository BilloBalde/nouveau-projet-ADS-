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
                        <h4>Gestion Dettes</h4>
                        <h5>Modification No. {{ $dette->id }}</h5>
                    </div>
                    <div class="page-btn">
                        <a href="{{ url()->previous() }}" class="btn btn-added">
                            <img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2">
                        </a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        @include('layouts.flash')
                        <form action="{{ route('journaliers.update', $dette->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="nomPrenant"><strong>Nom du Prenant</strong></label>
                                        <input type="text" name="nomPrenant" id="nomPrenant" value="{{ old('nomPrenant', $dette->nomPrenant) }}" class="form-control">
                                    </div>
                                    @error('nomPrenant')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="montant"><strong>Montant</strong></label>
                                        <input type="text" name="montant" id="montant" value="{{ old('montant', $dette->montant) }}" class="form-control">
                                    </div>
                                    @error('montant')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="datePrise"><strong>Date</strong></label>
                                        <input type="date" name="datePrise" id="datePrise" value="{{ old('datePrise', $dette->datePrise) }}" class="form-control">
                                    </div>
                                    @error('datePrise')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-lg-3 col-sm-3 col-12">
                                    <div class="form-group">
                                        <label for="contenu"><strong>Description</strong></label>
                                        <textarea name="contenu" id="contenu" cols="30" rows="4">{{ old('contenu', $dette->contenu) }}</textarea>
                                    </div>
                                    @error('contenu')
                                    <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <button type="submit" class="btn btn-submit me-2">Valider</button>
                                    <a href="{{ route('journaliers.create') }}" class="btn btn-secondary">Reset</a>
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


