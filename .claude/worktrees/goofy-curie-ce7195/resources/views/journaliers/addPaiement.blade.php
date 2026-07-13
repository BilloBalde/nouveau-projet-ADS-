<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <style>
            .hidden {
                display: none;
            }
        </style>
        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>
                                {{ $journalier->nomPrenant.'-'.$journalier->datePrise }}
                            </h4>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('journaliers.index') }}" class="btn btn-added">
                                <img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2">
                            </a>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('journalier.pay') }}" id="paiementForm" method="post">
                                @csrf
                                <div class="modal-body">
                                    <div class="row">
                                        <input class="form-control" type="text" name="journalier_id" id="journalier_id" value="{{ $journalier->id }}" hidden>
                                        <div class="col-lg-4 col-sm-4 col-12">
                                            <div class="form-group">
                                                <label for="reste" style="text-align: left; font-weight:800px;">Montant A Payer</label>
                                                <input class="form-control" type="text" name="reste" id="reste" value="{{ numberDelimiter($journalier->reste) }}" readonly>
                                                @error('reste')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-4 col-12">
                                            <div class="form-group">
                                                <label for="versement" style="text-align: left; font-weight:800px;">Montant Reçu</label>
                                                <input class="form-control" type="text" name="versement" id="versement">
                                                @error('versement')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-sm-4 col-12">
                                            <div class="form-group">
                                                <label for="paid_by" style="text-align: left; font-weight:800px;">Type Paiement</label>
                                                <select class="form-control" name="paid_by" id="paid_by">
                                                    <option value="">Selectionner le paiement</option>
                                                    <option Value="cash">Cash</option>
                                                    <option value="check">Check</option>
                                                    <option value="orange money">Orange Money</option>
                                                </select>
                                                @error('paid_by')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-12">
                                            <div class="form-group mb-0">
                                                <label for="notes" style="text-align: left; font-weight:800px;">Note</label>
                                                <textarea class="form-control" name="notes" id="notes"></textarea>
                                            </div>
                                            @error('notes')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-submit">Valider</button>
                                    <a href="{{ route('journaliers.index') }}" type="button" class="btn btn-cancel" >Fermer</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
    </body>
</html>>


