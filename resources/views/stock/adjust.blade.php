@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Inventaire / ajustement de stock</h4>
            <h6>Saisissez la quantité réellement comptée : l'écart est enregistré dans le journal avec votre nom et le motif</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form method="POST" action="{{ route('stock.adjust') }}" class="row">
                @csrf
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Emplacement</label>
                        <select name="store_id" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach ($stores as $s)
                            <option value="{{ $s->id }}" {{ (string) old('store_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->full_name }} ({{ $s->type_label }})</option>
                            @endforeach
                        </select>
                        @error('store_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Produit</label>
                        <select name="product_id" class="form-control" required>
                            <option value="">Choisir</option>
                            @foreach ($products as $p)
                            <option value="{{ $p->id }}" {{ (string) old('product_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->libelle }} ({{ $p->sku }})</option>
                            @endforeach
                        </select>
                        @error('product_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-2 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Quantité comptée</label>
                        <input type="number" min="0" name="counted" value="{{ old('counted') }}" class="form-control" required>
                        @error('counted') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-lg-2 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Nature</label>
                        <select name="type" class="form-control">
                            <option value="INVENTORY">Inventaire</option>
                            <option value="ADJUSTMENT" {{ old('type') === 'ADJUSTMENT' ? 'selected' : '' }}>Ajustement (casse, perte…)</option>
                        </select>
                    </div>
                </div>
                @if ($funds->isNotEmpty())
                <div class="col-lg-4 col-sm-6 col-12">
                    <div class="form-group">
                        <label>Propriétaire du stock compté</label>
                        <select name="fund_id" class="form-control">
                            <option value="">{{ \App\Models\Fund::OWNER_LABEL }}</option>
                            @foreach ($funds as $f)
                            <option value="{{ $f->id }}" {{ (string) old('fund_id') === (string) $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
                <div class="col-12">
                    <div class="form-group">
                        <label>Motif (obligatoire)</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="form-control" required placeholder="Ex. : inventaire mensuel, 3 sacs déchirés…">
                        @error('reason') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-submit">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
