<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des mouvements - {{ $company->name ?? config('app.name') }}</title>
    <style>
        /* ===== RESET & TYPOGRAPHIE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            line-height: 1.5;
            color: #1c1917;
            background: white;
            padding: 0.8cm;
        }

        .wrapper {
            max-width: 100%;
            margin: 0 auto;
        }

        /* ===== EN-TÊTE ===== */
        .logo img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .company-info h1 {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 17pt;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 4px;
        }

        .company-info p {
            font-size: 9pt;
            color: #78716c;
            margin: 2px 0;
        }

        .report-info {
            font-size: 9pt;
            background: #fdf6ec;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 4px solid #c1682f;
        }

        .report-info p {
            margin: 4px 0;
            color: #1c1917;
        }

        .report-info strong {
            color: #b45309;
        }

        .admin-badge {
            background: #c1682f;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 8pt;
            font-weight: 600;
            display: inline-block;
            margin-top: 4px;
        }

        /* ===== TITRE DU RAPPORT ===== */
        .title-section {
            text-align: center;
            margin: 25px 0 15px;
        }

        .title-section h2 {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 15pt;
            font-weight: 700;
            color: #92400e;
            text-transform: uppercase;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }

        .filters {
            background: #fdf6ec;
            display: inline-block;
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 9pt;
            color: #1c1917;
            border: 1px solid #f0e0c8;
        }

        .filters strong {
            color: #b45309;
        }

        /* ===== CARTES RÉCAPITULATIVES ===== */
        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .summary-card {
            display: table-cell;
            padding: 14px 8px;
            text-align: center;
            background-color: #fdf6ec;
            border: 1px solid #f0e0c8;
        }

        .summary-card .label {
            font-size: 9pt;
            text-transform: uppercase;
            font-weight: 600;
            color: #78716c;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .summary-card .value {
            font-size: 15pt;
            font-weight: 800;
            line-height: 1.2;
        }

        .summary-card.ventes { border-top: 4px solid #059669; }
        .summary-card.achats { border-top: 4px solid #b45309; }
        .summary-card.depenses { border-top: 4px solid #b91c1c; }
        .summary-card.solde { border-top: 4px solid #92400e; }

        .text-success { color: #059669; }
        .text-danger { color: #b91c1c; }
        .text-warning { color: #b45309; }
        .text-primary { color: #92400e; }

        /* ===== TABLEAU DES MOUVEMENTS ===== */
        .movements-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            font-size: 9pt;
        }

        .movements-table th {
            background: #92400e;
            color: white;
            padding: 10px 6px;
            font-weight: 600;
            text-align: center;
            border: none;
            font-size: 9pt;
            letter-spacing: 0.3px;
        }

        .movements-table td {
            padding: 8px 6px;
            border: 1px solid #f1e9dd;
            vertical-align: middle;
        }

        .movements-table tr:nth-child(even) {
            background-color: #fdfaf5;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            color: white;
            text-align: center;
            min-width: 70px;
        }

        .badge.vente { background: #059669; }
        .badge.achat { background: #b45309; }
        .badge.depense { background: #b91c1c; }
        .badge.autre { background: #78716c; }

        .total-row {
            background: #fdf6ec !important;
            font-weight: 700;
            border-top: 2px solid #c1682f;
        }

        .total-row td {
            padding: 10px 6px;
            background: #fdf6ec;
            color: #92400e;
        }

        .nowrap {
            white-space: nowrap;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            padding-top: 14px;
            border-top: 2px solid #c1682f;
            text-align: center;
            font-size: 10px;
            color: #78716c;
            background: #fdf6ec;
            padding: 14px;
            margin: 0;
            box-sizing: border-box;
        }

        .footer-contact {
            margin-bottom: 6px;
            font-weight: 600;
            color: #1c1917;
        }

        .footer-thanks {
            font-family: Georgia, 'Times New Roman', serif;
            font-weight: 700;
            color: #92400e;
            margin-top: 8px;
            font-size: 11px;
            font-style: italic;
        }

        .footer-date {
            margin-top: 8px;
            font-size: 10px;
            color: #a8a29e;
        }

        /* ===== UTILITAIRES ===== */
        .empty-message {
            padding: 30px;
            text-align: center;
            background: #fdfaf5;
            color: #78716c;
            font-style: italic;
            border: 1px dashed #d6ccc0;
        }
    </style>
</head>
<body>
{{-- ======================================== --}}
{{-- EN-TÊTE : LOGO + SOCIÉTÉ + INFOS RAPPORT --}}
{{-- ======================================== --}}
<div class="header" style="display: table; width: 100%; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 3px solid #c1682f;">

    {{-- Logo --}}
    <div style="display: table-cell; width: 90px; vertical-align: middle; text-align: left;">
        @if($company->logo ?? false)
            <img src="{{ public_path('companies/'.$company->logo) }}" alt="{{ $company->name ?? 'Logo' }}" style="max-width: 70px; height: auto; border-radius: 8px;">
        @elseif(file_exists(public_path('images/customers/logo.jpg')))
            <img src="{{ public_path('images/customers/logo.jpg') }}" alt="{{ $company->name ?? 'Logo' }}" style="max-width: 70px; height: auto; border-radius: 8px;">
        @else
            <div style="width: 70px; height: 60px; background: #fdf6ec; border-radius: 6px; text-align: center; color: #b45309; font-size: 10px; padding-top: 22px;">Logo</div>
        @endif
    </div>

    {{-- Informations société --}}
    <div class="company-info" style="display: table-cell; vertical-align: middle; text-align: center; padding: 0 15px;">
        <h1>{{ $company->name ?? config('app.name') }}</h1>
        <p>{{ $company->address ?? 'Adresse non renseignée' }}</p>
        <p>
            Tél : {{ $company->phone ?? '+224 626 311 915' }} | Email : {{ $company->email ?? 'saikououmar47@gmail.com' }}
            @if($company->rc ?? false)
                | RC : {{ $company->rc }} | NIF : {{ $company->nif ?? 'GN.TCC.2022.A.03557' }}
            @endif
        </p>
    </div>

    {{-- Métadonnées du rapport --}}
    <div class="report-info" style="display: table-cell; width: 190px; vertical-align: middle; text-align: right;">
        <p><strong>RAPPORT N°</strong> {{ 'RPT-' . now()->format('Ymd-His') }}</p>
        <p><strong>Émis le</strong> {{ now()->format('d/m/Y à H:i') }}</p>
        <p><strong>Par</strong> {{ auth()->user()->name ?? auth()->user()->email ?? 'Système' }}</p>
        @if($isAdmin)
            <span class="admin-badge">Administrateur</span>
        @endif
    </div>
</div>

        {{-- ======================================== --}}
        {{-- TITRE ET FILTRES APPLIQUÉS --}}
        {{-- ======================================== --}}
        <div class="title-section">
            <h2>Rapport des mouvements financiers</h2>
            <div class="filters">
                <strong>Période :</strong>
                @php
                    $dateDebut = request('date_debut');
                    $dateFin = request('date_fin');
                @endphp
                @if($dateDebut && $dateFin)
                    @if($dateDebut === $dateFin)
                        le {{ \Carbon\Carbon::parse($dateDebut)->isoFormat('DD/MM/YYYY') }}
                    @else
                        du {{ \Carbon\Carbon::parse($dateDebut)->isoFormat('DD/MM/YYYY') }}
                        au {{ \Carbon\Carbon::parse($dateFin)->isoFormat('DD/MM/YYYY') }}
                    @endif
                @elseif($dateDebut)
                    à partir du {{ \Carbon\Carbon::parse($dateDebut)->isoFormat('DD/MM/YYYY') }}
                @elseif($dateFin)
                    jusqu'au {{ \Carbon\Carbon::parse($dateFin)->isoFormat('DD/MM/YYYY') }}
                @else
                    Toutes les dates
                @endif
                &nbsp;|&nbsp;
                <strong>Type :</strong>
                @switch(request('type'))
                    @case('sale') Ventes @break
                    @case('purchase') Achats @break
                    @case('expense') Dépenses @break
                    @default Tous
                @endswitch
            </div>
        </div>

        {{-- ======================================== --}}
        {{-- RÉCAPITULATIF : 4 CARTES DE SYNTHÈSE --}}
        {{-- ======================================== --}}
        <div class="summary-grid">
            <div class="summary-card ventes">
                <div class="label">Ventes</div>
                <div class="value text-success">{{ number_format($totalVentes ?? 0, 0, ',', ' ') }} F</div>
            </div>
            <div class="summary-card achats">
                <div class="label">Achats</div>
                <div class="value text-warning">{{ number_format($totalAchats ?? 0, 0, ',', ' ') }} F</div>
            </div>
            <div class="summary-card depenses">
                <div class="label">Dépenses</div>
                <div class="value text-danger">{{ number_format($totalDepenses ?? 0, 0, ',', ' ') }} F</div>
            </div>
            <div class="summary-card solde">
                <div class="label">Solde</div>
                <div class="value {{ ($solde ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($solde ?? 0, 0, ',', ' ') }} F
                </div>
            </div>
        </div>

        {{-- ======================================== --}}
        {{-- DÉTAIL DES MOUVEMENTS --}}
        {{-- ======================================== --}}
        <table class="movements-table" cellspacing="0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Référence</th>
                    <th>Type</th>
                    <th>Produit / Libellé</th>
                    <th class="text-center">Qté</th>
                    <th class="text-right">Montant</th>
                    <th>Détails</th>
                    @if($isAdmin)
                        <th>Magasin</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($movements ?? [] as $mvt)
                <tr>
                    <td class="nowrap">
                        @if(isset($mvt['date']))
                            @if($mvt['date'] instanceof \Carbon\Carbon)
                                {{ $mvt['date']->isoFormat('DD/MM/YYYY HH:mm') }}
                            @else
                                {{ \Carbon\Carbon::parse($mvt['date'])->isoFormat('DD/MM/YYYY HH:mm') }}
                            @endif
                        @else
                            N/A
                        @endif
                    </td>
                    <td><strong>{{ $mvt['reference'] ?? '—' }}</strong></td>
                    <td class="text-center">
                        @php
                            $type = $mvt['type_code'] ?? $mvt['type'] ?? '';
                            $badgeClass = match($type) {
                                'sale' => 'vente',
                                'purchase' => 'achat',
                                'expense' => 'depense',
                                default => 'autre'
                            };
                            $typeLabel = match($type) {
                                'sale' => 'Vente',
                                'purchase' => 'Achat',
                                'expense' => 'Dépense',
                                default => $mvt['type'] ?? '—'
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $typeLabel }}</span>
                    </td>
                    <td>{{ $mvt['produit_nom'] ?? '—' }}</td>
                    <td class="text-center">{{ $mvt['produit_qte'] ?? '—' }}</td>
                    <td class="text-right {{ ($mvt['type_code'] ?? '') == 'sale' ? 'text-success' : 'text-danger' }}">
                        <strong>{{ number_format($mvt['montant'] ?? 0, 0, ',', ' ') }} F</strong>
                    </td>
                    <td>{{ $mvt['details'] ?? '—' }}</td>
                    @if($isAdmin)
                        <td>{{ $mvt['store_name'] ?? 'N/A' }}</td>
                    @endif
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isAdmin ? 8 : 7 }}" class="empty-message">
                        Aucun mouvement trouvé pour les critères sélectionnés.
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if(isset($movements) && count($movements) > 0)
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="text-right"><strong>TOTAL GÉNÉRAL</strong></td>
                    <td class="text-right">
                        <strong>{{ number_format(collect($movements)->sum('montant'), 0, ',', ' ') }} F</strong>
                    </td>
                    <td colspan="{{ $isAdmin ? 2 : 1 }}"></td>
                </tr>
            </tfoot>
            @endif
        </table>

        {{-- ======================================== --}}
        {{-- PIED DE PAGE : SIGNATURE NUMÉRIQUE --}}
        {{-- ======================================== --}}

        <div class="footer">
            @if(!empty($company?->address))
                <div class="footer-contact">{{ $company->address }}</div>
            @endif
            <div class="footer-thanks">Merci pour votre confiance et à bientôt!</div>
            <div class="footer-date">Rapport généré le {{ now()->format('d/m/Y H:i') }}</div>
        </div>

</body>
</html>
