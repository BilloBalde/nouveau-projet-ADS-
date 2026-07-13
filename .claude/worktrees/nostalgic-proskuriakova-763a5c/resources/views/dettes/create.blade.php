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
                            <h4>Dettes</h4>
                            <h6>Enregistrer une nouvelle dette</h6>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <div class="card">
                        <div class="card-body">
                            <form action="{{ route('dettes.store') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label>Référence</label>
                                            <input type="text" name="reference" value="{{ old('reference', $reference) }}" readonly>
                                        </div>
                                        @error('reference')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label>Client <span class="text-danger">*</span></label>
                                            <select name="customer_id" class="form-control" required>
                                                <option value="">— Sélectionner —</option>
                                                @foreach($customers as $c)
                                                    <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                                        {{ $c->customerName }} ({{ $c->mark }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('customer_id')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label>Boutique <span class="text-danger">*</span></label>
                                            <select name="store_id" class="form-control" required>
                                                <option value="">— Sélectionner —</option>
                                                @foreach($stores as $s)
                                                    <option value="{{ $s->id }}" {{ old('store_id') == $s->id ? 'selected' : '' }}>{{ $s->store_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @error('store_id')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label>Montant total (GNF) <span class="text-danger">*</span></label>
                                            <input type="number" name="montant_total" min="0" value="{{ old('montant_total') }}" required>
                                        </div>
                                        @error('montant_total')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label>Montant déjà payé (GNF) <span class="text-danger">*</span></label>
                                            <input type="number" name="montant_paid" min="0" value="{{ old('montant_paid', 0) }}" required>
                                        </div>
                                        @error('montant_paid')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label>Date d'endettement <span class="text-danger">*</span></label>
                                            <input type="date" name="endettement_date" value="{{ old('endettement_date', now()->format('Y-m-d')) }}" required>
                                        </div>
                                        @error('endettement_date')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>Notes</label>
                                            <textarea name="notes" rows="4">{{ old('notes') }}</textarea>
                                        </div>
                                        @error('notes')<span class="text-danger">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-submit me-2" style="background-color:#c1682f;border-color:#c1682f;">Enregistrer</button>
                                        <a href="{{ route('dettes.index') }}" class="btn btn-cancel">Annuler</a>
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
