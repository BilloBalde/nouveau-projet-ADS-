<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des paiements - SMH</title>
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
            color: #2c3e50;
            background: #fff;
            margin: 1.5cm;
        }

        /* ===== TYPOGRAPHY ===== */
        h1 {
            font-size: 24px;
            font-weight: 600;
            color: #1a3e60;
            margin-bottom: 4px;
        }

        h2 {
            font-size: 18px;
            font-weight: 500;
            color: #2c3e50;
            border-left: 4px solid #2c7da0;
            padding-left: 12px;
            margin: 20px 0 15px 0;
        }

        /* ===== HEADER ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #d0dee5;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1a3e60;
            letter-spacing: -0.5px;
        }

        .company-details {
            font-size: 9px;
            color: #6c86a3;
            margin-top: 5px;
        }

        .logo-placeholder {
            width: 80px;
            height: 80px;
            background: #f0f4f8;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #7f8c8d;
            text-align: center;
            border: 1px solid #e0e7ed;
        }

        /* ===== REPORT META ===== */
        .report-meta {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 20px;
            font-size: 9px;
            color: #5b7a9a;
            border-bottom: 1px dashed #e0e7ed;
            padding-bottom: 8px;
        }

        .report-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a3e60;
            margin: 0;
        }

        .filters {
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 9px;
            color: #2c3e50;
            border-left: 3px solid #2c7da0;
        }

        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }

        th {
            background-color: #1a3e60;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #1a3e60;
        }

        td {
            padding: 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }

        /* Zebra striping for readability */
        tr:nth-child(even) {
            background-color: #f9fcff;
        }

        /* Hover effect (optional, works in some PDF viewers) */
        tr:hover {
            background-color: #f1f5f9;
        }

        /* Numeric columns */
        .amount {
            text-align: right;
            font-family: 'DejaVu Sans', monospace;
        }

        /* Note column – wrap long text */
        .note-cell {
            max-width: 180px;
            word-wrap: break-word;
            white-space: normal;
        }

        /* ===== SUMMARY ===== */
        .summary {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px 20px;
            margin-top: 25px;
            width: 100%;
            max-width: 350px;
            margin-left: auto;
            text-align: right;
        }

        .summary p {
            margin: 4px 0;
            font-size: 11px;
            font-weight: 500;
        }

        .summary .total-label {
            font-weight: 600;
            color: #1a3e60;
        }

        .summary .total-value {
            font-weight: 700;
            color: #2c7da0;
        }

        /* ===== FOOTER ===== */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #8aa4c2;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            margin-top: 20px;
            background: white;
        }

        /* Page break handling */
        .page-break {
            page-break-before: always;
        }

        /* Avoid breaking rows across pages */
        tr {
            page-break-inside: avoid;
        }

        /* ===== PRINT / PDF OPTIMIZATION ===== */
        @page {
            margin: 1.5cm;
            @bottom-center {
                content: "SMH - Document confidentiel";
                font-size: 8px;
                color: #8aa4c2;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ $company->name ?? 'SMH' }}</div>
            <div class="company-details">
                {{ $company->address ?? 'Centre Faloulay, 1er étage B30, Madina route Niger — T6, en face de la station Star, Sonfonia' }}<br>
                Tél: +224 626 311 915 | Email: saikououmar47@gmail.com
            </div>
        </div>

        @if($company && $company->logo && file_exists(public_path('companies/'.$company->logo)))
            <img src="{{ public_path('companies/'.$company->logo) }}" alt="{{ $company->name ?? 'Logo' }}" class="invoice-head-logo">
        @elseif(file_exists(public_path('images/customers/logo.jpg')))
            <img src="{{ public_path('images/customers/logo.jpg') }}" alt="Logo" class="invoice-head-logo">
        @endif
    </div>

    <div class="report-meta">
        <div class="report-title">Liste des paiements</div>
        <div>Généré le : {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    @if(request('facture_id') || request('paid_by') || request('date_from') || request('date_to'))
        <div class="filters">
            <strong>Filtres appliqués :</strong>
            @if(request('facture_id')) Facture ID: {{ request('facture_id') }} | @endif
            @if(request('paid_by')) Moyen de paiement: {{ request('paid_by') }} | @endif
            @if(request('date_from')) Du: {{ request('date_from') }} | @endif
            @if(request('date_to')) Au: {{ request('date_to') }} | @endif
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Facture N°</th>
                <th>Versement (FG)</th>
                <th>Total payé (FG)</th>
                <th>Reste à payer (FG)</th>
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
            @foreach($payments as $item)
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
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p><span class="total-label">Total des versements :</span> <span class="total-value">{{ number_format($totalVersement, 0, ',', ' ') }} FG</span></p>
        <p><span class="total-label">Total payé :</span> <span class="total-value">{{ number_format($totalPaye, 0, ',', ' ') }} FG</span></p>
        <p><span class="total-label">Total restant dû :</span> <span class="total-value">{{ number_format($totalReste, 0, ',', ' ') }} FG</span></p>
    </div>

    <div class="footer">
        Document généré par {{ auth()->user()->name ?? 'Système' }} | SMH – Rapport de paiements | Page <span class="pageNumber"></span> / <span class="totalPages"></span>
    </div>
</body>
</html>