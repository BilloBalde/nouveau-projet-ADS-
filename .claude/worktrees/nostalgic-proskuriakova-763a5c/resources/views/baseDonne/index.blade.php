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
                    @include('layouts.flash')
                    @foreach ($listTables as $item)
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Vider la table {{ $item[1] }}</h4>
                            <h6>Cette table a {{ $item[0] }} elements</h6>
                        </div>
                        <div class="page-btn">
                            <a href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#delete{{ $item[1] }}" class="btn btn-danger">Vider</a>
                        </div>
                    </div>
                    <div class="modal fade" id="delete{{ $item[1] }}" tabindex="-1" aria-labelledby="delete{{ $item[1] }}" data-bs-backdrop="static" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('deleteLines', $item[1]) }}" method="POST" id="exitForm">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="delete{{ $item[1] }}">Confirmation Suppression</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Voulez vous vider la table {{ $item[1] }} ?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @include('layouts.scripts')
    </body>
</html>

