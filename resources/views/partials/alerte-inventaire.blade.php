{{-- Inventaire de fin d'année : rappel du 10 décembre au 10 janvier
     (config/sales.php), tant que la boutique n'a pas validé de comptage dans
     cette période. Reçoit $alertes, issu d'InventaireRapportService::alertes(). --}}
@foreach ($alertes as $a)
@php
    $annee = $a->echeance->year;
    $jours = abs($a->jours);
    [$classe, $icone, $texte] = $a->etat === 'en_retard'
        ? ['alert-danger', 'fa-exclamation-triangle',
            "Inventaire de fin d'année $annee pas encore fait à {$a->store->store_name} : à faire au plus vite, avant le {$a->fin->format('d/m/Y')}."]
        : ['alert-warning', 'fa-clipboard-list',
            "Inventaire de fin d'année $annee à faire à {$a->store->store_name} avant le 31/12/$annee"
            .($jours === 0 ? ' — c\'est aujourd\'hui.' : ' (dans '.$jours.' jour'.($jours > 1 ? 's' : '').').')];
@endphp
<div class="alert {{ $classe }} d-flex flex-wrap align-items-center gap-2">
    <i class="fas {{ $icone }}"></i>
    <span>{{ $texte }}</span>
    @if ($a->derniere)
    <small class="text-muted">Dernier comptage : {{ $a->derniere->format('d/m/Y') }}.</small>
    @endif
    @permission('stock.adjust')
    <a href="{{ route('inventaires.create', ['store_id' => $a->store->id]) }}" class="alert-link ms-auto">Faire le comptage</a>
    @endpermission
</div>
@endforeach
