{{--
    Feuille de comptage à imprimer, emportée dans la boutique.

    Elle n'affiche VOLONTAIREMENT pas le stock théorique. Montrer la quantité
    attendue pousse à la recopier au lieu de compter : l'inventaire ne
    révélerait alors plus ni les ventes ni les pertes, et le chiffre d'affaires
    serait celui qu'on croyait, pas celui qui a eu lieu.

    Le stock théorique apparaît à la saisie, au bureau, pour repérer une
    erreur de comptage ou de frappe avant de valider.

    L'ordre des lignes est celui de l'écran de saisie : le gérant saisit en
    suivant sa feuille, ligne après ligne.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Feuille de comptage {{ $seance->reference }}</title>
    <style>
        @page { margin: 14mm 10mm; }
        body { font-family: sans-serif; font-size: 11px; color: #000; }
        .entete { border-bottom: 2px solid #000; padding-bottom: 6px; margin-bottom: 10px; }
        .entete h1 { font-size: 15px; margin: 0 0 2px; }
        .entete .societe { font-size: 12px; font-weight: bold; }
        .meta { width: 100%; font-size: 11px; margin-bottom: 10px; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .consigne { border: 1px solid #000; padding: 6px 8px; margin-bottom: 10px; font-size: 10px; }
        table.produits { width: 100%; border-collapse: collapse; }
        table.produits th, table.produits td { border: 1px solid #000; padding: 5px 4px; }
        table.produits th { background: #eee; font-size: 10px; text-align: left; }
        table.produits td.saisie { width: 70px; height: 22px; }
        table.produits td.perte { width: 60px; }
        table.produits td.motif { width: 110px; }
        .num { text-align: center; width: 26px; }
        .unite { font-size: 10px; color: #333; }
        .signatures { margin-top: 18px; font-size: 11px; width: 100%; }
        .signatures td { padding-top: 26px; border-top: 1px solid #000; width: 33%; }
        .pied { margin-top: 10px; font-size: 9px; color: #333; }
    </style>
</head>
<body>
    <div class="entete">
        <div class="societe">{{ $company->name ?? config('app.name') }}</div>
        <h1>Feuille de comptage — {{ $seance->reference }}</h1>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Emplacement :</strong> {{ $seance->store->full_name }}</td>
            <td><strong>Date du comptage :</strong> {{ $seance->counted_on->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>
                <strong>Période couverte :</strong>
                @if ($seance->estPremiereSeance())
                    premier comptage — il pose le stock de départ
                @else
                    du {{ $seance->debutPeriode()->format('d/m/Y') }} au {{ $seance->counted_on->format('d/m/Y') }}
                @endif
            </td>
            <td><strong>Compté par :</strong> ______________________</td>
        </tr>
    </table>

    <div class="consigne">
        Comptez le stock réellement présent, produit par produit, et écrivez la quantité dans la case.
        Si un produit manque ou est abîmé (casse, vol, péremption), portez la quantité perdue dans
        « Perte » et indiquez le motif : une perte ne sera pas comptée comme une vente.
        Laissez la case vide pour un produit que vous n'avez pas compté — écrivez <strong>0</strong>
        si vous avez compté et qu'il n'y en a plus.
    </div>

    <table class="produits">
        <thead>
            <tr>
                <th class="num">#</th>
                <th>Produit</th>
                <th>Référence</th>
                <th>Unité</th>
                <th>Quantité comptée</th>
                <th>Perte</th>
                <th>Motif de la perte</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($produits as $i => $produit)
            <tr>
                <td class="num">{{ $i + 1 }}</td>
                <td>{{ $produit->libelle }}</td>
                <td>{{ $produit->sku }}</td>
                <td class="unite">{{ \App\Models\Product::motUnite($produit->unite, 2) }}</td>
                <td class="saisie"></td>
                <td class="perte"></td>
                <td class="motif"></td>
            </tr>
            @empty
            <tr>
                <td colspan="7">Aucun produit à compter dans cet emplacement.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>Compté par</td>
            <td>Vérifié par</td>
            <td>Validé par</td>
        </tr>
    </table>

    <div class="pied">
        {{ $produits->count() }} produit(s) à compter — feuille éditée le {{ now()->format('d/m/Y à H:i') }}.
        Le stock théorique n'est pas indiqué : comptez ce que vous voyez.
    </div>
</body>
</html>
