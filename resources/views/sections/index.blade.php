@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Sections</h4>
            <h6>
                @if ($current)
                    Vous êtes dans la section « {{ $current->store_name }} »
                @else
                    Vue globale : toutes les sections
                @endif
            </h6>
        </div>
        @unless (auth()->user()->isRestrictedToStore())
            @if ($current)
            <div class="page-btn">
                <form method="POST" action="{{ route('sections.switch') }}">@csrf
                    <button class="btn btn-added">Revenir à la vue globale</button>
                </form>
            </div>
            @endif
        @endunless
    </div>
    @include('layouts.flash')

    <div class="row">
        @forelse ($sections as $section)
        @php $actif = $current && $current->id === $section->store->id; @endphp
        <div class="col-xl-4 col-md-6 col-12 d-flex">
            <div class="card flex-fill" style="{{ $actif ? 'border:2px solid var(--adm-brand-600,#3E4C21);' : '' }}">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="mb-1">
                                <i class="fas {{ $section->store->type === 'entrepot' ? 'fa-warehouse' : 'fa-store' }} me-1 text-primary"></i>
                                {{ $section->store->store_name }}
                            </h5>
                            <span class="badge bg-light text-dark">{{ $section->store->type_label }}</span>
                            @if ($section->store->is_main)<span class="badge bg-success">Principal</span>@endif
                            @if ($actif)<span class="badge bg-primary">Section actuelle</span>@endif
                        </div>
                    </div>
                    <div class="row text-center my-2">
                        <div class="col-4"><div class="text-muted" style="font-size:11px;">Stock</div><strong>{{ number_format($section->stock, 0, ',', ' ') }}</strong></div>
                        <div class="col-4"><div class="text-muted" style="font-size:11px;">Ventes du jour</div><strong>{{ numberDelimiter($section->ca_today) }}</strong></div>
                        <div class="col-4"><div class="text-muted" style="font-size:11px;">Employés</div><strong>{{ $section->staff }}</strong></div>
                    </div>
                    <p class="text-muted mb-3" style="font-size:12px;">
                        @if ($section->locations->isNotEmpty())
                            Comprend : {{ $section->locations->map(fn ($l) => $l->store_name.' ('.$l->type_label.')')->join(', ') }}
                        @else
                            Aucun entrepôt ni stock interne rattaché.
                        @endif
                    </p>
                    <div class="mt-auto">
                        @if (auth()->user()->isRestrictedToStore())
                            <a href="{{ route('home') }}" class="btn btn-primary w-100">Ouvrir</a>
                        @elseif ($actif)
                            <a href="{{ route('home') }}" class="btn btn-primary w-100">Continuer dans cette section</a>
                        @else
                            <form method="POST" action="{{ route('sections.switch') }}">@csrf
                                <input type="hidden" name="section_id" value="{{ $section->store->id }}">
                                <button class="btn btn-primary w-100">Entrer dans cette section</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12"><div class="alert alert-warning">Aucune section accessible. Demandez à l'administrateur de vous affecter à un magasin.</div></div>
        @endforelse
    </div>
</div>
@endsection
