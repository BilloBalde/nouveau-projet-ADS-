<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Liste des Paiements</h4>
                            <h6>Gérer vos Paiements</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('factures.index') }}">
                                <img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img">
                            </a>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-path">
                                        <a class="btn btn-filter" id="filter_search">
                                            <img src="assets/img/icons/filter.svg" alt="img">
                                            <span><img src="assets/img/icons/closes.svg" alt="img"></span>
                                        </a>
                                    </div>
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="assets/img/icons/search-white.svg" alt="img"></a>
                                    </div>
                                </div>

                                <div class="wordset">
                                    <ul>
                                        <li>
                                            <form action="{{ route('payments.export') }}" method="GET" class="d-inline" id="pdf-export-form">
                                                @foreach(request()->all() as $key => $value)
                                                    @if(is_array($value))
                                                        @foreach($value as $arrayValue)
                                                            <input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}">
                                                        @endforeach
                                                    @else
                                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                    @endif
                                                @endforeach
                                                <button type="submit" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="PDF (avec filtres)"
                                                        style="padding: 5px 10px;">
                                                    <img src="{{ asset('assets/img/icons/pdf.svg') }}" alt="pdf" style="width: 16px; height: 16px;">
                                                    <span style="margin-left: 5px;">PDF</span>
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('payments.exportExcel') }}" method="GET" class="d-inline" id="excel-export-form">
                                                @foreach(request()->all() as $key => $value)
                                                    @if(is_array($value))
                                                        @foreach($value as $arrayValue)
                                                            <input type="hidden" name="{{ $key }}[]" value="{{ $arrayValue }}">
                                                        @endforeach
                                                    @else
                                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                                    @endif
                                                @endforeach
                                                <button type="submit" 
                                                        class="btn btn-sm btn-success" 
                                                        data-bs-toggle="tooltip" 
                                                        data-bs-placement="top" 
                                                        title="Excel (avec filtres)"
                                                        style="padding: 5px 10px;">
                                                    <img src="{{ asset('assets/img/icons/excel.svg') }}" alt="excel" style="width: 16px; height: 16px;">
                                                    <span style="margin-left: 5px;">Excel</span>
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Formulaire de filtres -->
                            <div class="card" id="filter_inputs">
                                <div class="card-body pb-0">
                                    <form action="{{ route('payments.index') }}" method="GET">
                                        @csrf
                                        <div class="row">
                                            <!-- Filtre par facture -->
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <label for="period">Facture</label>
                                                    <select name="facture_id" class="form-control">
                                                        <option value="">Sélectionner une facture</option>
                                                        @foreach ($factures as $item)
                                                            <option value="{{ $item->id }}" {{ request('facture_id') == $item->id ? 'selected' : '' }}>
                                                                {{ $item->numero_facture }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Filtre période -->
                                            <div class="col-lg-3 col-sm-6 col-12">
                                                <div class="form-group">
                                                    <label for="period">Période</label>
                                                    <select id="period" name="period" class="form-control">
                                                        <option value="daily" {{ request('period', 'daily') == 'daily' ? 'selected' : '' }}>Jour</option>
                                                        <option value="weekly" {{ request('period') == 'weekly' ? 'selected' : '' }}>Semaine</option>
                                                        <option value="monthly" {{ request('period') == 'monthly' ? 'selected' : '' }}>Mois</option>
                                                        <option value="quarterly" {{ request('period') == 'quarterly' ? 'selected' : '' }}>Trimestre</option>
                                                        <option value="semestral" {{ request('period') == 'semestral' ? 'selected' : '' }}>Semestre</option>
                                                        <option value="yearly" {{ request('period') == 'yearly' ? 'selected' : '' }}>Année</option>
                                                        <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>Personnalisé</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Champs dynamiques pour chaque période -->
                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="daily">
                                                <div class="form-group">
                                                    <label for="date">Jour</label>
                                                    <input type="date" id="date" name="date" value="{{ request('date', $startDate ?? date('Y-m-d')) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="weekly">
                                                <div class="form-group">
                                                    <label for="week_start">Semaine de début</label>
                                                    <input type="date" id="week_start" name="week_start" value="{{ request('week_start', \Carbon\Carbon::now()->startOfWeek()->toDateString()) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="monthly">
                                                <div class="form-group">
                                                    <label for="month">Mois</label>
                                                    <input type="month" id="month" name="month" value="{{ request('month', date('Y-m')) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="quarterly">
                                                <div class="form-group">
                                                    <label for="quarter">Trimestre</label>
                                                    <select id="quarter" name="quarter" class="form-control">
                                                        @for ($q = 1; $q <= 4; $q++)
                                                            <option value="{{ $q }}" {{ (int) request('quarter', ceil(now()->month / 3)) === $q ? 'selected' : '' }}>Q{{ $q }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="semestral">
                                                <div class="form-group">
                                                    <label for="semester">Semestre</label>
                                                    <select id="semester" name="semester" class="form-control">
                                                        <option value="1" {{ (int) request('semester', now()->month <= 6 ? 1 : 2) === 1 ? 'selected' : '' }}>S1</option>
                                                        <option value="2" {{ (int) request('semester', now()->month <= 6 ? 1 : 2) === 2 ? 'selected' : '' }}>S2</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="quarterly,semestral,yearly">
                                                <div class="form-group">
                                                    <label for="year">Année</label>
                                                    <input type="number" id="year" name="year" class="form-control" value="{{ request('year', now()->year) }}" min="2020" max="2100">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="custom">
                                                <div class="form-group">
                                                    <label for="start_date">Date de début</label>
                                                    <input type="date" id="start_date" name="start_date" value="{{ request('start_date', $startDate ?? date('Y-m-d')) }}" class="form-control">
                                                </div>
                                            </div>

                                            <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="custom">
                                                <div class="form-group">
                                                    <label for="end_date">Date de fin</label>
                                                    <input type="date" id="end_date" name="end_date" value="{{ request('end_date', $endDate ?? date('Y-m-d')) }}" class="form-control">
                                                </div>
                                            </div>

                                            <!-- Boutons d'action -->
                                            <div class="col-lg-3 col-sm-6 col-12 d-flex align-items-end">
                                                <div class="form-group d-flex">
                                                    <button type="submit" class="btn btn-filters me-2">
                                                        <img src="assets/img/icons/search-whites.svg" alt="img">
                                                    </button>
                                                    <a href="{{ route('payments.index') }}" class="btn btn-secondary">Réinitialiser</a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Tableau des paiements -->
                            <div class="table-responsive">
                                <table class="table datanew">
                                    <thead>
                                        <tr>
                                            <th>Facture</th>
                                            <th>Versement</th>
                                            <th>Total Payé</th>
                                            <th>Reste à Payer</th>
                                            <th>Payé par</th>
                                            <th style="min-width: 200px;">Note</th>
                                            <th>Date Paiement</th>
                                            <th>Reçu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dataTable as $item)
                                        <tr>
                                            <td>{{ $item->numeroFacture }}</td>
                                            <td>{{ number_format($item->versement, 0, ',', ' ') }}</td>
                                            <td>{{ number_format($item->total_paye, 0, ',', ' ') }}</td>
                                            <td>{{ number_format($item->reste, 0, ',', ' ') }}</td>
                                            <td>{{ ucfirst($item->paid_by) }}</td>
                                            <td style="max-width: 300px; word-wrap: break-word; white-space: normal; word-break: break-word;">{{ $item->note }}</td>
                                            <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @if ($item->receipt_number)
                                                    <a href="{{ route('receipts.payments.show', $item->id) }}" class="btn btn-sm">
                                                        <img src="{{ asset('assets/img/icons/printer.svg') }}" alt="img">
                                                    </a>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Word wrap for notes column */
            td:nth-child(6) {
                max-width: 300px;
                word-wrap: break-word;
                white-space: normal;
                word-break: break-word;
            }
            .period-field {
                display: none;
            }
        </style>

        @include('layouts.scripts')
        <script>
            // Le plugin DataTable (initialisé globalement dans script.js) trie par
            // défaut sur la 1ère colonne, écrasant le tri "plus récent d'abord"
            // déjà fait côté serveur. On force le tri sur "Date Paiement" (colonne 6), desc.
            $(document).ready(function () {
                if (window.jQuery && $.fn.DataTable && $.fn.DataTable.isDataTable('.datanew')) {
                    $('.datanew').DataTable().order([6, 'desc']).draw();
                }
            });
        </script>
        <script>
            function togglePeriodFields() {
                const period = document.getElementById('period').value;
                document.querySelectorAll('.period-field').forEach(field => {
                    const periods = field.dataset.period.split(',');
                    field.style.display = periods.includes(period) ? 'block' : 'none';
                });
            }

            document.getElementById('period').addEventListener('change', togglePeriodFields);
            window.addEventListener('load', togglePeriodFields);
        </script>
    </body>
</html>