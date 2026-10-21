{{--
    Saisie au bureau, feuille de comptage en main.

    Les lignes sont dans le même ordre que la feuille imprimée : la saisie se
    fait de haut en bas, sans chercher.

    Ici le stock théorique EST affiché, contrairement à la feuille : c'est le
    moment de repérer une erreur de comptage ou de frappe. L'écart et le
    chiffre d'affaires se recalculent à chaque frappe, avant toute écriture.
--}}
@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Saisie du comptage — {{ $seance->reference }}</h4>
            <h6>
                {{ $seance->store->full_name }} · comptage du {{ $seance->counted_on->format('d/m/Y') }}
                @if ($seance->estPremiereSeance())
                    · <span class="text-warning">premier comptage : il pose le stock de départ, aucune vente n'en sera déduite</span>
                @else
                    · période du {{ $seance->debutPeriode()->format('d/m/Y') }} au {{ $seance->counted_on->format('d/m/Y') }}
                @endif
            </h6>
        </div>
        <div class="page-btn">
            <a href="{{ route('inventaires.feuille', $seance->id) }}" class="btn btn-added" target="_blank">
                <i class="fas fa-print me-1"></i> Réimprimer la feuille
            </a>
        </div>
    </div>

    @include('layouts.flash')

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('inventaires.valider', $seance->id) }}" id="formSaisie">
                @csrf

                <div class="alert alert-info">
                    Saisissez la quantité comptée pour chaque produit.
                    <strong>Une case laissée vide signifie « non compté »</strong> : le stock du produit ne sera pas
                    touché et aucune vente n'en sera déduite. Pour dire qu'il n'y en a plus, écrivez <strong>0</strong>.
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="tableSaisie">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produit</th>
                                <th class="text-end" title="Compté au comptage précédent">Début</th>
                                <th class="text-end">Achats</th>
                                <th class="text-end" title="Transferts reçus">Entrées</th>
                                <th class="text-end" title="Transferts envoyés">Sorties</th>
                                <th class="text-end" title="Ajustements et corrections tracés sur la période">Autres</th>
                                <th class="text-end" title="Ce que le stock enregistré devrait contenir">Théorique</th>
                                <th class="text-end" style="min-width:110px">Compté</th>
                                <th class="text-end" style="min-width:90px">Perte</th>
                                <th style="min-width:140px">Motif de la perte</th>
                                <th class="text-end" style="min-width:110px">Prix de vente</th>
                                <th class="text-end">Vendu</th>
                                <th class="text-end">Chiffre d'affaires</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lignes as $i => $ligne)
                            @php
                                $produit = $ligne->produit;
                                // Nom de champ HTML : « lignes[0][counted] ».
                                // Écrit « lignes.0[counted] », PHP transforme le point
                                // en souligné (lignes_0) et le formulaire n'envoie rien
                                // sous « lignes » : la validation refuse tout.
                                $cle = 'lignes['.$i.']';
                                $ancien = old('lignes.'.$i, []);
                            @endphp
                            <tr data-ligne data-premiere="{{ $seance->estPremiereSeance() ? '1' : '0' }}">
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <input type="hidden" name="{{ $cle }}[product_id]" value="{{ $produit->id }}">
                                    <input type="hidden" name="{{ $cle }}[fund_id]" value="{{ $ligne->fund_id }}">
                                    <strong>{{ $produit->libelle }}</strong>
                                    <small class="text-muted d-block">
                                        {{ $produit->sku }} · {{ \App\Models\Product::motUnite($produit->unite, 2) }}
                                        @if ($ligne->fund_id)
                                            · fonds {{ $funds->firstWhere('id', $ligne->fund_id)?->name ?? '#'.$ligne->fund_id }}
                                        @endif
                                    </small>
                                </td>
                                <td class="text-end">{{ number_format($ligne->opening, 0, ',', ' ') }}</td>
                                <td class="text-end">{{ $ligne->purchased ? '+'.number_format($ligne->purchased, 0, ',', ' ') : '—' }}</td>
                                <td class="text-end">{{ $ligne->transfer_in ? '+'.number_format($ligne->transfer_in, 0, ',', ' ') : '—' }}</td>
                                <td class="text-end">{{ $ligne->transfer_out ? '−'.number_format($ligne->transfer_out, 0, ',', ' ') : '—' }}</td>
                                <td class="text-end">{{ $ligne->other ? ($ligne->other > 0 ? '+' : '−').number_format(abs($ligne->other), 0, ',', ' ') : '—' }}</td>
                                <td class="text-end fw-medium" data-theorique="{{ $ligne->theorique }}">{{ number_format($ligne->theorique, 0, ',', ' ') }}</td>
                                <td>
                                    <input type="number" min="0" step="1" class="form-control form-control-sm text-end"
                                           name="{{ $cle }}[counted]" data-compte
                                           value="{{ $ancien['counted'] ?? '' }}" placeholder="—">
                                </td>
                                <td>
                                    <input type="number" min="0" step="1" class="form-control form-control-sm text-end"
                                           name="{{ $cle }}[loss]" data-perte
                                           value="{{ $ancien['loss'] ?? '' }}" placeholder="0">
                                </td>
                                <td>
                                    <input type="text" maxlength="255" class="form-control form-control-sm"
                                           name="{{ $cle }}[loss_reason]" data-motif
                                           value="{{ $ancien['loss_reason'] ?? '' }}" placeholder="Casse, vol…">
                                </td>
                                <td>
                                    <input type="number" min="0" step="1" class="form-control form-control-sm text-end"
                                           name="{{ $cle }}[unit_price]" data-prix
                                           value="{{ $ancien['unit_price'] ?? (int) ($produit->price ?? 0) }}">
                                </td>
                                <td class="text-end" data-vendu>—</td>
                                <td class="text-end" data-ca>—</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="14" class="text-center text-muted py-4">
                                    Aucun produit à compter dans cet emplacement.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if ($lignes->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="12" class="text-end">Total de la période</td>
                                <td class="text-end" id="totalVendu">0</td>
                                <td class="text-end" id="totalCa">0 GNF</td>
                            </tr>
                            <tr>
                                <td colspan="12" class="text-end text-muted">Dont pertes déclarées (hors chiffre d'affaires)</td>
                                <td class="text-end text-muted" id="totalPerteQte">0</td>
                                <td class="text-end text-muted" id="totalPerte">0 GNF</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-lg-8">
                        <div class="form-group">
                            <label>Notes sur ce comptage</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Remarques, difficultés de comptage…">{{ old('notes', $seance->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                @if ($lignes->isNotEmpty())
                <div class="alert alert-warning">
                    À la validation, le stock enregistré de cet emplacement est aligné sur les quantités comptées,
                    les ventes déduites et les pertes sont inscrites au journal, et le chiffre d'affaires est figé.
                    <strong>Un comptage validé ne se modifie plus.</strong>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-submit">Valider le comptage</button>
                    <a href="{{ route('inventaires.index') }}" class="btn btn-cancel">Revenir à la liste</a>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection

@section('script_perso')
<script>
    // Aperçu du calcul avant écriture, avec la même formule que le serveur :
    //   vendu = théorique − perte − compté
    // Le serveur recalcule tout à la validation : cet aperçu sert à repérer
    // une erreur de frappe (un 500 saisi au lieu de 50), pas à décider.
    (function () {
        const table = document.getElementById('tableSaisie');
        if (!table) return;

        const formatGnf = (v) => new Intl.NumberFormat('fr-FR').format(Math.round(v)) + ' GNF';
        const formatQte = (v) => new Intl.NumberFormat('fr-FR').format(Math.round(v));

        function recalculer() {
            let totalVendu = 0, totalCa = 0, totalPerte = 0, totalPerteQte = 0;

            table.querySelectorAll('[data-ligne]').forEach((tr) => {
                const premiere = tr.dataset.premiere === '1';
                const theorique = parseInt(tr.querySelector('[data-theorique]').dataset.theorique, 10) || 0;
                const champCompte = tr.querySelector('[data-compte]');
                const perte = parseInt(tr.querySelector('[data-perte]').value, 10) || 0;
                const prix = parseFloat(tr.querySelector('[data-prix]').value) || 0;
                const cellVendu = tr.querySelector('[data-vendu]');
                const cellCa = tr.querySelector('[data-ca]');

                // Case vide : produit non compté, rien n'en sera déduit.
                if (champCompte.value === '') {
                    cellVendu.textContent = '—';
                    cellCa.textContent = '—';
                    tr.classList.remove('table-warning');
                    return;
                }

                const compte = parseInt(champCompte.value, 10) || 0;
                // Un premier comptage ne déduit aucune vente : il pose le départ.
                const vendu = premiere ? 0 : Math.max(0, theorique - perte - compte);

                cellVendu.textContent = formatQte(vendu);
                cellCa.textContent = formatGnf(vendu * prix);

                // Un comptage supérieur au théorique est signalé : ce n'est pas
                // une vente négative, c'est un écart à comprendre.
                tr.classList.toggle('table-warning', compte + perte > theorique && !premiere);

                totalVendu += vendu;
                totalCa += vendu * prix;
                totalPerte += perte * prix;
                totalPerteQte += perte;
            });

            document.getElementById('totalVendu').textContent = formatQte(totalVendu);
            document.getElementById('totalCa').textContent = formatGnf(totalCa);
            document.getElementById('totalPerteQte').textContent = formatQte(totalPerteQte);
            document.getElementById('totalPerte').textContent = formatGnf(totalPerte);
        }

        table.addEventListener('input', recalculer);
        recalculer();
    })();
</script>
@endsection
