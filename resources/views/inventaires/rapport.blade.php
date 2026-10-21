{{--
    Rapport de chiffre d'affaires, version imprimable.

    Même contenu que l'écran : tous les termes du calcul, pour que la
    soustraction se vérifie à la main sur le papier.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Chiffre d'affaires {{ $seance->reference }}</title>
    <style>
        @page { margin: 12mm 10mm; }
        body { font-family: sans-serif; font-size: 10px; color: #000; }
        .entete { border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 8px; }
        .entete .societe { font-size: 12px; font-weight: bold; }
        .entete h1 { font-size: 14px; margin: 2px 0 0; }
        .meta { width: 100%; margin-bottom: 8px; }
        .meta td { padding: 1px 0; }
        .resume { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .resume td { border: 1px solid #000; padding: 6px 8px; width: 33%; }
        .resume .valeur { font-size: 13px; font-weight: bold; }
        .formule { border: 1px solid #000; padding: 5px 7px; margin-bottom: 8px; font-size: 9px; }
        table.lignes { width: 100%; border-collapse: collapse; }
        table.lignes th, table.lignes td { border: 1px solid #555; padding: 3px 4px; }
        table.lignes th { background: #eee; font-size: 9px; }
        table.lignes td.n { text-align: right; }
        table.lignes tfoot td { font-weight: bold; background: #f5f5f5; }
        .pied { margin-top: 8px; font-size: 8px; color: #333; }
    </style>
</head>
<body>
    <div class="entete">
        <div class="societe">{{ $company->name ?? config('app.name') }}</div>
        <h1>Chiffre d'affaires de la période — {{ $seance->reference }}</h1>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Boutique :</strong> {{ $seance->store->full_name }}</td>
            <td>
                <strong>Période :</strong>
                @if ($seance->estPremiereSeance())
                    premier comptage ({{ $seance->counted_on->format('d/m/Y') }}) — stock de départ
                @else
                    du {{ $seance->debutPeriode()->format('d/m/Y') }} au {{ $seance->counted_on->format('d/m/Y') }}
                @endif
            </td>
        </tr>
        <tr>
            <td><strong>Validé le :</strong> {{ $seance->validated_at?->format('d/m/Y à H:i') }}</td>
            <td><strong>Par :</strong> {{ $seance->validator->name ?? '—' }}</td>
        </tr>
    </table>

    <table class="resume">
        <tr>
            <td>
                Chiffre d'affaires<br>
                <span class="valeur">{{ number_format((float) $seance->total_revenue_gnf, 0, ',', ' ') }} GNF</span>
            </td>
            <td>
                Quantité vendue<br>
                <span class="valeur">{{ \App\Models\Product::resumeQuantites($seance->lines, 'sold_quantity') }}</span>
            </td>
            <td>
                Pertes déclarées (hors CA)<br>
                <span class="valeur">{{ number_format((float) $seance->total_loss_gnf, 0, ',', ' ') }} GNF</span>
            </td>
        </tr>
    </table>

    <div class="formule">
        <strong>Calcul, pour chaque produit :</strong>
        compté au début + achats + transferts entrants − transferts sortants − pertes + autres mouvements
        − compté à la fin = quantité vendue, puis × prix de vente = chiffre d'affaires.
    </div>

    <table class="lignes">
        <thead>
            <tr>
                <th>Produit</th>
                <th>Unité</th>
                <th>Début</th>
                <th>Achats</th>
                <th>Entrées</th>
                <th>Sorties</th>
                <th>Pertes</th>
                <th>Autres</th>
                <th>Compté</th>
                <th>Vendu</th>
                <th>Prix</th>
                <th>Chiffre d'affaires</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($seance->lines->sortBy(fn ($l) => $l->product?->libelle) as $ligne)
            <tr>
                <td>{{ $ligne->product->libelle ?? 'Produit #'.$ligne->product_id }}</td>
                <td>{{ \App\Models\Product::motUnite($ligne->unite, 2) }}</td>
                <td class="n">{{ number_format((int) $ligne->opening_quantity, 0, ',', ' ') }}</td>
                <td class="n">{{ $ligne->purchased_quantity ? '+'.number_format((int) $ligne->purchased_quantity, 0, ',', ' ') : '' }}</td>
                <td class="n">{{ $ligne->transfer_in_quantity ? '+'.number_format((int) $ligne->transfer_in_quantity, 0, ',', ' ') : '' }}</td>
                <td class="n">{{ $ligne->transfer_out_quantity ? '-'.number_format((int) $ligne->transfer_out_quantity, 0, ',', ' ') : '' }}</td>
                <td class="n">{{ $ligne->loss_quantity ? '-'.number_format((int) $ligne->loss_quantity, 0, ',', ' ') : '' }}</td>
                <td class="n">{{ $ligne->other_quantity ? ($ligne->other_quantity > 0 ? '+' : '-').number_format(abs((int) $ligne->other_quantity), 0, ',', ' ') : '' }}</td>
                <td class="n">{{ number_format((int) $ligne->counted_quantity, 0, ',', ' ') }}</td>
                <td class="n">{{ number_format((int) $ligne->sold_quantity, 0, ',', ' ') }}</td>
                <td class="n">{{ number_format((float) $ligne->unit_price_gnf, 0, ',', ' ') }}</td>
                <td class="n">{{ number_format((float) $ligne->revenue_gnf, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9">Total</td>
                <td class="n">{{ number_format((int) $seance->total_sold_quantity, 0, ',', ' ') }}</td>
                <td></td>
                <td class="n">{{ number_format((float) $seance->total_revenue_gnf, 0, ',', ' ') }} GNF</td>
            </tr>
        </tfoot>
    </table>

    @if ($seance->notes)
    <div class="pied"><strong>Notes :</strong> {{ $seance->notes }}</div>
    @endif

    <div class="pied">
        Les prix sont ceux figés au moment du comptage : ce rapport ne change plus.
        Édité le {{ now()->format('d/m/Y à H:i') }}.
    </div>
</body>
</html>
