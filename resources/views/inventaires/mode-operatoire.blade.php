{{--
    Mode opératoire à imprimer et à laisser au gérant, à côté de sa caisse.

    Écrit pour lui, pas pour un informaticien : des phrases courtes, aucun
    terme technique, et les trois règles qui décident si son chiffre d'affaires
    sera juste ou faux. Tient sur une page A4.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Mode opératoire — inventaire</title>
    <style>
        @page { margin: 11mm 11mm; }
        body { font-family: sans-serif; font-size: 10.5px; color: #000; line-height: 1.35; }
        .entete { border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 8px; }
        .entete .societe { font-size: 11px; font-weight: bold; }
        .entete h1 { font-size: 16px; margin: 2px 0 0; }
        .entete .sous { font-size: 10px; color: #333; }

        h2 { font-size: 11.5px; margin: 9px 0 4px; padding-bottom: 2px; border-bottom: 1px solid #999; }

        table.etapes { width: 100%; border-collapse: collapse; }
        table.etapes td { padding: 4px 5px; vertical-align: top; border: 1px solid #bbb; }
        table.etapes td.num { width: 22px; text-align: center; font-weight: bold; font-size: 14px; background: #eee; }
        table.etapes td.quoi { font-weight: bold; width: 130px; }

        .regles { width: 100%; border-collapse: collapse; margin-top: 3px; }
        .regles td { padding: 4px 5px; border: 1px solid #bbb; vertical-align: top; }
        .regles td.titre { font-weight: bold; width: 150px; }

        .attention { border: 2px solid #000; padding: 5px 7px; margin-top: 7px; }
        .attention .t { font-weight: bold; }
        .attention ul { margin: 3px 0 0; padding-left: 16px; }
        .attention li { margin-bottom: 2px; }

        .exemple { background: #f3f3f3; border: 1px solid #999; padding: 5px 7px; margin-top: 7px; }
        /* Pas de signe moins typographique ici : la police à chasse fixe de
           dompdf ne le contient pas et l'imprime en « ? ». */
        .exemple .calcul { font-family: monospace; font-size: 11px; margin: 3px 0; }

        .pied { margin-top: 8px; font-size: 8.5px; color: #333; border-top: 1px solid #999; padding-top: 4px; }
    </style>
</head>
<body>

<div class="entete">
    <div class="societe">{{ $company->name ?? config('app.name') }}</div>
    <h1>Comment faire l'inventaire de votre boutique</h1>
    <div class="sous">À faire une fois par mois. C'est ce comptage qui calcule votre chiffre d'affaires.</div>
</div>

<p style="margin:0 0 6px">
    Vous ne saisissez pas vos ventes une par une. Vous vendez normalement, puis vous comptez ce
    qu'il reste en boutique. Ce qui a disparu depuis le dernier comptage — en retirant ce que vous
    avez reçu — a été vendu. L'application le multiplie par vos prix et vous donne votre chiffre
    d'affaires.
</p>

<h2>Les 4 étapes, à chaque fin de mois</h2>

<table class="etapes">
    <tr>
        <td class="num">1</td>
        <td class="quoi">Ouvrir le comptage</td>
        <td>
            Menu <strong>Stock &rsaquo; Inventaires et chiffre d'affaires</strong>, puis
            <strong>Nouveau comptage</strong>. Choisissez votre boutique et la date du jour.
        </td>
    </tr>
    <tr>
        <td class="num">2</td>
        <td class="quoi">Imprimer la feuille</td>
        <td>
            La feuille de comptage s'affiche : imprimez-la. Elle liste vos produits avec des cases
            vides. <strong>Les quantités attendues n'y figurent pas, c'est volontaire</strong> :
            vous devez compter ce que vous voyez, pas recopier un chiffre.
        </td>
    </tr>
    <tr>
        <td class="num">3</td>
        <td class="quoi">Compter en boutique</td>
        <td>
            Produit par produit, écrivez la quantité réelle au stylo. Si un article est cassé, volé
            ou périmé, notez-le dans la colonne <strong>Perte</strong> et dites pourquoi.
        </td>
    </tr>
    <tr>
        <td class="num">4</td>
        <td class="quoi">Saisir au bureau</td>
        <td>
            Revenez sur l'écran, cliquez <strong>Saisir</strong> et recopiez votre feuille de haut en
            bas — les lignes sont dans le même ordre. Vérifiez, puis
            <strong>Validez le comptage</strong>. Votre chiffre d'affaires s'affiche.
        </td>
    </tr>
</table>

<h2>Trois règles pour que le chiffre soit juste</h2>

<table class="regles">
    <tr>
        <td class="titre">Saisissez tous vos achats</td>
        <td>
            Chaque livraison reçue doit être enregistrée dans le menu <strong>Achat</strong>, à sa
            date. <strong>C'est la règle la plus importante.</strong> Un achat oublié, et
            l'application croira que cette marchandise n'est jamais entrée : votre chiffre d'affaires
            sera plus bas que la réalité.
        </td>
    </tr>
    <tr>
        <td class="titre">Passez par les transferts</td>
        <td>
            Marchandise envoyée à une autre boutique, ou reçue d'elle : utilisez le menu
            <strong>Transferts</strong>. Sinon elle sera comptée comme vendue là où elle a disparu.
        </td>
    </tr>
    <tr>
        <td class="titre">Déclarez les pertes</td>
        <td>
            Casse, vol, péremption : notez la quantité dans la colonne <strong>Perte</strong>. Une
            perte déclarée sort du stock mais <strong>n'est pas comptée comme une vente</strong>.
            Sans ça, vous paierez des impôts sur de la marchandise que vous n'avez jamais vendue.
        </td>
    </tr>
</table>

<div class="attention">
    <div class="t">À retenir absolument</div>
    <ul>
        <li>
            <strong>Le tout premier comptage affiche 0 F de chiffre d'affaires.</strong> C'est
            normal : il sert à enregistrer votre stock de départ. Votre chiffre apparaîtra au
            comptage suivant.
        </li>
        <li>
            <strong>Une case laissée vide veut dire « je n'ai pas compté ce produit ».</strong>
            Son stock ne sera pas touché. Si vous avez compté et qu'il n'en reste aucun, écrivez
            <strong>0</strong>.
        </li>
        <li>
            <strong>Un comptage validé ne se modifie plus.</strong> Relisez vos chiffres avant de
            cliquer sur Valider.
        </li>
        <li>
            Ne comptez pas pendant que vous vendez : fermez, ou comptez avant l'ouverture.
        </li>
    </ul>
</div>

<div class="exemple">
    <strong>Exemple</strong> — Au 1<sup>er</sup> du mois vous aviez 100 sacs de ciment. Vous avez été
    livré de 50 sacs le 15. À la fin du mois, vous en comptez 30.
    <div class="calcul">100 (début) + 50 (reçus) - 30 (restants) = <strong>120 sacs vendus</strong></div>
    <div class="calcul">120 × 50 000 F = <strong>6 000 000 F de chiffre d'affaires</strong></div>
    Si vous aviez oublié d'enregistrer la livraison des 50 sacs, l'application aurait affiché
    70 sacs vendus et 3 500 000 F : <strong>2 500 000 F de moins que la réalité</strong>.
</div>

<div class="pied">
    Document édité le {{ now()->format('d/m/Y') }}
    @if (! empty($company?->phone)) · Assistance : {{ $company->phone }} @endif
    @if (! empty($company?->email)) · {{ $company->email }} @endif
    <br>
    À conserver près de la caisse. Réimprimable depuis l'écran « Inventaires et chiffre d'affaires ».
</div>

</body>
</html>
