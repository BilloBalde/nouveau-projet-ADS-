@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Fin d'année {{ $annee }} : partage du bénéfice</h4>
            <h6>Saisissez la part de chaque associé ; ce qui n'est attribué à personne reste à la boutique</h6>
        </div>
        <div class="page-btn">
            <a href="{{ route('associates.index', ['annee' => $annee]) }}" class="btn btn-cancel">Comptes des associés</a>
        </div>
    </div>

    @include('layouts.flash')

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a href="{{ route('associates.annee', $annee - 1) }}" class="btn btn-sm btn-light" title="Année précédente"><i class="fas fa-chevron-left"></i></a>
        <strong class="px-1">{{ $annee }}</strong>
        @if ($annee < now()->year)
        <a href="{{ route('associates.annee', $annee + 1) }}" class="btn btn-sm btn-light" title="Année suivante"><i class="fas fa-chevron-right"></i></a>
        @endif
        @if ($cloturee)
            <span class="badge bg-success ms-2"><i class="fas fa-lock me-1"></i>Année clôturée</span>
        @endif
    </div>

    {{-- Le bénéfice de l'année, en repère : c'est lui qu'on partage. --}}
    <div class="row">
        @foreach ([
            ['Bénéfice de l\'année', $benefice, \App\Support\ModeDeVente::parInventaire() ? 'marge des comptages validés' : 'marge des ventes', ''],
            ['Parts attribuées aux associés', $parts, $lignes->count().' associé'.($lignes->count() > 1 ? 's' : ''), ''],
            ['Reste pour la boutique', $resteBoutique, $resteBoutique < 0 ? 'les parts dépassent le bénéfice' : 'bénéfice − parts', $resteBoutique < 0 ? 'text-danger' : ''],
        ] as [$titre, $montant, $detail, $classe])
        <div class="col-lg-4 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small">{{ $titre }}</div>
                    <div class="fs-4 fw-bold {{ $classe }}">{{ francs($montant) }}</div>
                    <div class="text-muted small">{{ $detail }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if ($anneeEnAttente !== null && ! $cloturee)
    <div class="alert alert-warning">
        L'année {{ $anneeEnAttente }} n'est pas clôturée : son report est le point de départ de {{ $annee }}.
        <a href="{{ route('associates.annee', $anneeEnAttente) }}" class="alert-link">Clôturer {{ $anneeEnAttente }} d'abord</a>.
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if ($lignes->isEmpty())
                <p class="text-muted mb-0">
                    Aucun associé pour {{ $annee }}.
                    <a href="{{ route('associates.create') }}">Enregistrer un associé</a>.
                </p>
            @else
            <form method="POST" action="{{ route('associates.parts', $annee) }}" id="formParts">
                @csrf
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Associé</th>
                                <th class="text-end">Report {{ $annee - 1 }}</th>
                                <th class="text-end" style="min-width:180px;">Part du bénéfice</th>
                                <th class="text-end">Sorties {{ $annee }}</th>
                                <th class="text-end">Solde</th>
                                <th class="text-end">À verser</th>
                                <th class="text-end">Reporté sur {{ $annee + 1 }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lignes as $ligne)
                            @php($c = $ligne->compte)
                            <tr data-ligne data-report="{{ $c['report'] }}" data-sorties="{{ $c['sorties'] }}">
                                <td class="fw-medium">
                                    <a href="{{ route('associates.show', ['associate' => $ligne->associe, 'annee' => $annee]) }}">{{ $ligne->associe->name }}</a>
                                </td>
                                <td class="text-end {{ $c['report'] < 0 ? 'text-danger' : '' }}">{{ francs($c['report']) }}</td>
                                <td class="text-end">
                                    @if ($cloturee)
                                        {{ francs($c['part']) }}
                                    @else
                                        <input type="number" min="0" step="1" name="parts[{{ $ligne->associe->id }}]" data-part
                                               class="form-control form-control-sm text-end"
                                               value="{{ old('parts.'.$ligne->associe->id, $c['part'] ? (float) $c['part'] : '') }}" placeholder="0">
                                    @endif
                                </td>
                                <td class="text-end">− {{ francs($c['sorties']) }}</td>
                                <td class="text-end fw-bold" data-solde>{{ francs($c['solde']) }}</td>
                                <td class="text-end text-success fw-medium" data-verser>{{ francs($c['a_verser']) }}</td>
                                <td class="text-end text-danger" data-reporter>{{ $c['a_reporter'] < 0 ? francs($c['a_reporter']) : '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-end">{{ francs($totaux['report']) }}</td>
                                <td class="text-end" data-total-parts>{{ francs($parts) }}</td>
                                <td class="text-end">− {{ francs($totaux['sorties']) }}</td>
                                <td></td>
                                <td class="text-end text-success" data-total-verser>{{ francs($totaux['a_verser']) }}</td>
                                <td class="text-end text-danger">{{ $totaux['a_reporter'] < 0 ? francs($totaux['a_reporter']) : '—' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @unless ($cloturee)
                <button type="submit" class="btn btn-submit me-2"><i class="fas fa-save me-1"></i> Enregistrer les parts</button>
                @endunless
            </form>

            @unless ($cloturee)
            <hr>
            <form method="POST" action="{{ route('associates.cloturer', $annee) }}" class="d-flex flex-wrap align-items-center gap-3"
                  data-confirmer="Clôturer l'année {{ $annee }} ?"
                  data-confirmer-texte="Les parts et les sorties de l'année seront figées et le solde de chaque associé arrêté. Cette action est définitive."
                  data-confirmer-bouton="Clôturer l'année"
                  data-confirmer-ton="danger"
                  data-confirmer-icone="fa-lock">
                @csrf
                <button type="submit" class="btn btn-danger" @disabled($anneeEnAttente !== null)>
                    <i class="fas fa-lock me-1"></i> Clôturer l'année {{ $annee }}
                </button>
                <span class="text-muted small">
                    Enregistrez d'abord les parts. À la clôture, chaque solde positif est à verser à l'associé ;
                    un solde négatif est reporté sur {{ $annee + 1 }}.
                </span>
            </form>
            @endunless
            @endif
        </div>
    </div>
</div>
@endsection
@section('script_perso')
<script>
    // Aperçu du solde pendant la saisie des parts : report + part − sorties.
    // Le serveur refait le calcul à l'enregistrement ; ceci n'est qu'un repère.
    document.addEventListener('DOMContentLoaded', function () {
        const fmt = n => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(Math.round(n)) + ' FG';
        const lignes = document.querySelectorAll('[data-ligne]');
        function recalculer() {
            let parts = 0, aVerser = 0;
            lignes.forEach(function (tr) {
                const champ = tr.querySelector('[data-part]');
                if (!champ) return;
                const part = parseFloat(champ.value) || 0;
                const solde = parseFloat(tr.dataset.report) + part - parseFloat(tr.dataset.sorties);
                parts += part;
                aVerser += Math.max(0, solde);
                tr.querySelector('[data-solde]').textContent = fmt(solde);
                tr.querySelector('[data-verser]').textContent = fmt(Math.max(0, solde));
                tr.querySelector('[data-reporter]').textContent = solde < 0 ? fmt(solde) : '—';
            });
            const totalParts = document.querySelector('[data-total-parts]');
            if (totalParts && document.querySelector('[data-part]')) {
                totalParts.textContent = fmt(parts);
                document.querySelector('[data-total-verser]').textContent = fmt(aVerser);
            }
        }
        document.querySelectorAll('[data-part]').forEach(el => el.addEventListener('input', recalculer));
    });
</script>
@endsection
