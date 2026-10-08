<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des paiements - TELICO</title>
    <style>
        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #1F2414;
            background: #fff;
            margin: 1.5cm;
        }

        /* ===== EN-TÊTE ===== */
        /* Table plutôt que flexbox : le support flex de Dompdf est partiel/peu
           fiable selon les versions — une table garantit un rendu identique
           partout. */
        .header {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #3E4C21;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .header td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .invoice-head-logo {
            width: 64px;
            height: 64px;
            max-width: 64px;
            max-height: 64px;
            object-fit: contain;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #3E4C21;
            letter-spacing: -0.3px;
        }

        .company-details {
            font-size: 9px;
            color: #66695A;
            margin-top: 4px;
        }

        /* ===== TITRE / MÉTA DU RAPPORT ===== */
        .report-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .report-meta td {
            border: none;
            padding: 0;
            vertical-align: bottom;
        }

        .report-title {
            font-size: 17px;
            font-weight: 700;
            color: #1F2414;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .report-generated {
            font-size: 9px;
            color: #66695A;
        }

        .filters {
            background: #F9F9F3;
            padding: 8px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 9.5px;
            color: #66695A;
            border-left: 3px solid #3E4C21;
        }

        /* ===== TABLEAU ===== */
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 10px;
        }

        table.data th {
            background-color: #3E4C21;
            color: white;
            padding: 9px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            border: 1px solid #3E4C21;
        }

        table.data td {
            padding: 7px 8px;
            border: 1px solid #E6E9D5;
            vertical-align: top;
        }

        table.data tr:nth-child(even) td {
            background-color: #F9F9F3;
        }

        .amount {
            text-align: right;
            font-family: 'DejaVu Sans', monospace;
        }

        .note-cell {
            max-width: 180px;
            word-wrap: break-word;
            white-space: normal;
        }

        /* Évite de couper une ligne entre deux pages */
        table.data tr {
            page-break-inside: avoid;
        }

        /* ===== RÉSUMÉ ===== */
        .summary {
            background: #F9F9F3;
            border: 1px solid #CED5AE;
            border-radius: 8px;
            padding: 14px 20px;
            margin-top: 18px;
            width: 100%;
            max-width: 340px;
            margin-left: auto;
        }

        .summary-row {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-row td {
            border: none;
            padding: 3px 0;
            font-size: 11px;
        }

        .summary-row .total-label {
            color: #66695A;
        }

        .summary-row .total-value {
            text-align: right;
            font-weight: 700;
            color: #1F2414;
        }

        .summary-row.grand-total td {
            border-top: 2px solid #3E4C21;
            padding-top: 8px;
            font-size: 13px;
        }

        .summary-row.grand-total .total-label {
            font-weight: 700;
            color: #1F2414;
        }

        .summary-row.grand-total .total-value {
            color: #3E4C21;
        }

        /* ===== PIED DE PAGE ===== */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #66695A;
            text-align: center;
            border-top: 1px solid #E6E9D5;
            padding-top: 8px;
            margin-top: 20px;
            background: white;
        }

        @page {
            margin: 1.5cm;
        }
    </style>
</head>
<body>
        @php
            // Dompdf résout mal les chemins Windows passés tels quels à public_path()
            // (mélange de \ et /, caractères non-ASCII dans le chemin utilisateur) —
            // le logo n'apparaissait pas dans le PDF. On l'encode en base64 pour
            // s'affranchir totalement de la résolution de chemin par Dompdf.
            $logoPath = ($company && $company->logo && file_exists(public_path('companies/'.$company->logo)))
                ? public_path('companies/'.$company->logo)
                : null;
            $logoDataUri = $logoPath
                ? 'data:'.mime_content_type($logoPath).';base64,'.base64_encode(file_get_contents($logoPath))
                : null;
        @endphp
    <table class="header">
        <tr>
            <td style="width:74px;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="{{ $company->name ?? 'Logo' }}" class="invoice-head-logo">
                @endif
            </td>
            <td>
                <div class="company-name">{{ $company->name ?? 'TELICO' }}</div>
                <div class="company-details">
                    {{ $company->address ?? 'Madina, Ecole Gare Routière Dabola Camion, Conakry' }}<br>
                    Tél: {{ $company->phone ?? '' }} | Email: {{ $company->email ?? '' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="report-meta">
        <tr>
            <td class="report-title">Liste des paiements</td>
            <td class="report-generated" style="text-align:right;">Généré le {{ \Carbon\Carbon::now()->format('d/m/Y à H:i') }}<br>par {{ auth()->user()->name ?? 'Système' }}</td>
        </tr>
    </table>

    @if(request('facture_id') || request('paid_by') || request('period'))
        <div class="filters">
            <strong>Filtres appliqués :</strong>
            @if(request('facture_id')) Facture ID : {{ request('facture_id') }} &nbsp;|&nbsp; @endif
            @if(request('paid_by')) Moyen de paiement : {{ request('paid_by') }} &nbsp;|&nbsp; @endif
            @if(request('period')) Période : {{ ucfirst(request('period')) }} @endif
        </div>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th>Facture N°</th>
                <th>Versement (GNF)</th>
                <th>Total payé (GNF)</th>
                <th>Reste à payer (GNF)</th>
                <th>Payé par</th>
                <th>Note</th>
                <th>Date paiement</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalVersement = 0;
                $totalPaye = 0;
                $totalReste = 0;
            @endphp
            @forelse($payments as $item)
                @php
                    $totalVersement += $item->versement;
                    $totalPaye += $item->total_paye;
                    $totalReste += $item->reste;
                @endphp
                <tr>
                    <td>{{ $item->numeroFacture }}</td>
                    <td class="amount">{{ number_format($item->versement, 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($item->total_paye, 0, ',', ' ') }}</td>
                    <td class="amount">{{ number_format($item->reste, 0, ',', ' ') }}</td>
                    <td>{{ ucfirst($item->paid_by) }}</td>
                    <td class="note-cell">{{ $item->note ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:#66695A;padding:20px;">Aucun paiement pour cette sélection.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <table class="summary-row">
            <tr>
                <td class="total-label">Total des versements</td>
                <td class="total-value">{{ number_format($totalVersement, 0, ',', ' ') }} GNF</td>
            </tr>
            <tr>
                <td class="total-label">Total restant dû</td>
                <td class="total-value">{{ number_format($totalReste, 0, ',', ' ') }} GNF</td>
            </tr>
        </table>
        <table class="summary-row">
            <tr class="grand-total">
                <td class="total-label">Total payé</td>
                <td class="total-value">{{ number_format($totalPaye, 0, ',', ' ') }} GNF</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ $company->name ?? 'TELICO' }} — Rapport de paiements — Document confidentiel
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $size = 8;
            $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = $pdf->get_width() - $width - 42;
            $y = $pdf->get_height() - 34;
            $pdf->page_text($x, $y, $text, $font, $size, array(0.6, 0.54, 0.47));
        }
    </script>
</body>
</html>
