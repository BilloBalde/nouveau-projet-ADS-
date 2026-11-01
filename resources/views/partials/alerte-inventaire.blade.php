{{-- Inventaire à faire : tous les six mois par boutique (config/sales.php).
     Reçoit $alertes, issu d'InventaireRapportService::alertes(). --}}
@foreach ($alertes as $a)
@php
    $date = $a->echeance?->format('d/m/Y');
    [$classe, $icone, $texte] = match ($a->etat) {
        'en_retard' => ['alert-danger', 'fa-exclamation-triangle',
            "Inventaire en retard à {$a->store->store_name} : il était à faire avant le $date"
            .' ('.abs($a->jours).' jour'.(abs($a->jours) > 1 ? 's' : '').' de retard).'],
        'bientot' => ['alert-warning', 'fa-clipboard-list',
            "Inventaire à faire à {$a->store->store_name} avant le $date"
            .($a->jours === 0 ? ' — c\'est aujourd\'hui.' : ' (dans '.$a->jours.' jour'.($a->jours > 1 ? 's' : '').').')],
        default => ['alert-info', 'fa-clipboard-list',
            "Aucun inventaire validé à {$a->store->store_name} : faites un premier comptage pour poser le stock de départ."],
    };
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
