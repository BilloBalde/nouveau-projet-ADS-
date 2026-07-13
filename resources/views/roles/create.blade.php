<!DOCTYPE html>
<html lang="fr">
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
                            <h6>Créer un nouveau rôle</h6>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('roles.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-6 col-12">
                                        <div class="form-group">
                                            <label>Nom du rôle <span class="text-danger">*</span></label>
                                            <input type="text" name="nameRole" value="{{ old('nameRole') }}" placeholder="Ex : Vendeur, Caissier, Livreur…" required>
                                        </div>
                                        @error('nameRole')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-12 mt-2">
                                        @include('roles.partials.permission-grid')
                                    </div>
                                    <div class="col-lg-12 mt-4">
                                        <button type="submit" class="btn btn-submit me-2" style="background-color:#c1682f;border-color:#c1682f;">Créer le rôle</button>
                                        <a href="{{ route('roles.index') }}" class="btn btn-cancel">Annuler</a>
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
