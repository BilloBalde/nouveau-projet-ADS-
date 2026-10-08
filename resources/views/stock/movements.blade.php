@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Journal des mouvements de stock</h4>
            <h6>Chaque entrée et sortie, par qui, quand, pourquoi — lecture seule</h6>
        </div>
        <div class="page-btn">
            <a href="{{ route('stock.trace') }}" class="btn btn-added">Traçabilité d'un produit</a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-lg-3 col-sm-6">
                    <select name="product_id" class="form-control">
                        <option value="">Tous les produits</option>
                        @foreach ($products as $p)
                        <option value="{{ $p->id }}" {{ (string) request('product_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <select name="store_id" class="form-control">
                        <option value="">Tous les emplacements</option>
                        @foreach ($stores as $s)
                        <option value="{{ $s->id }}" {{ (string) request('store_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <select name="type" class="form-control">
                        <option value="">Tous les types</option>
                        @foreach (\App\Models\StockMovement::LABELS as $value => $label)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-sm-6">
                    <select name="user_id" class="form-control">
                        <option value="">Tous les utilisateurs</option>
                        @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <input type="text" name="document_ref" value="{{ request('document_ref') }}" class="form-control" placeholder="N° facture, achat, transfert…">
                </div>
                <div class="col-lg-2 col-sm-6"><input type="date" name="date_debut" value="{{ request('date_debut') }}" class="form-control"></div>
                <div class="col-lg-2 col-sm-6"><input type="date" name="date_fin" value="{{ request('date_fin') }}" class="form-control"></div>
                <div class="col-lg-2 col-sm-6"><button class="btn btn-primary w-100">Filtrer</button></div>
                <div class="col-lg-2 col-sm-6"><a href="{{ route('stock.movements') }}" class="btn btn-secondary w-100">Réinitialiser</a></div>
            </form>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr><th>Date</th><th>Type</th><th>Produit</th><th>Emplacement</th><th class="text-end">Qté</th><th class="text-end">Stock avant → après</th><th>Détail</th><th>Document</th><th>Utilisateur</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $m)
                            @include('stock.partials.movement-row', ['m' => $m])
                        @empty
                        <tr><td colspan="9" class="text-center text-muted">Aucun mouvement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movements->links() }}
            <p class="text-muted mb-0" style="font-size:12px;">
                Le journal ne se modifie pas et ne se supprime pas : une erreur se corrige par un nouveau mouvement (inventaire ou correction).
                Il démarre à la mise en service de ce suivi ; les opérations antérieures restent consultables dans les ventes, achats et transferts.
            </p>
        </div>
    </div>
</div>
@endsection
