@extends('layouts.template')

@section('content')
<style>
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
        margin-bottom: 18px;
    }
    @media (max-width: 1200px) {
        .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 576px) {
        .kpi-grid { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    }
    .kpi {
        color: #fff;
        border-radius: 10px;
        padding: 18px;
        min-height: 92px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .kpi .label { font-size: 13px; opacity: .9; margin-bottom: 4px; }
    .kpi .value { font-size: 22px; font-weight: 700; margin: 0; }
    .kpi .sub { font-size: 12px; opacity: .85; margin-top: 2px; }
    .kpi.green { background: #178a53; }
    .kpi.red { background: #d63b3b; }
    .kpi.yellow { background: #f2b400; color: #1f2937; }
    .kpi.gray { background: #6b7280; }
    .kpi.blue { background: #00a7c7; }
    .report-table th, .report-table td { vertical-align: middle; }
</style>

<div class="content">
    <div class="page-header d-flex align-items-center justify-content-between">
        <div class="page-title">
            <h4>Daily Report</h4>
            <h6>
                {{ $stores->firstWhere('id', $storeId)?->store_name ?? 'Toutes les boutiques' }}
                ({{ $label }})
            </h6>
        </div>
        <div class="page-btn">
            <a href="{{ route('reports.daily.export.pdf', request()->all()) }}" class="btn btn-added" title="Exporter en PDF" target="_blank">
                <img src="{{ asset('assets/img/icons/printer.svg') }}" alt="img">
                PDF
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('reports.daily') }}" method="GET" class="no-print">
                <div class="row">
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="form-group">
                            <label for="store_id">Boutique</label>
                            <select id="store_id" name="store_id" class="form-control" {{ auth()->user()->role_id == 3 ? 'disabled' : '' }}>
                                @if(auth()->user()->role_id != 3)
                                    <option value="">Toutes les boutiques</option>
                                @endif
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" {{ (int)($storeId ?? 0) === (int)$store->id ? 'selected' : '' }}>
                                        {{ $store->store_name }}
                                    </option>
                                @endforeach
                            </select>
                            @if(auth()->user()->role_id == 3)
                                <input type="hidden" name="store_id" value="{{ $storeId }}">
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12">
                        <div class="form-group">
                            <label for="period">Période</label>
                            <select id="period" name="period" class="form-control">
                                <option value="daily" {{ $period === 'daily' ? 'selected' : '' }}>Jour</option>
                                <option value="weekly" {{ $period === 'weekly' ? 'selected' : '' }}>Semaine</option>
                                <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Mois</option>
                                <option value="quarterly" {{ $period === 'quarterly' ? 'selected' : '' }}>Trimestre</option>
                                <option value="semestral" {{ $period === 'semestral' ? 'selected' : '' }}>Semestre</option>
                                <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Annee</option>
                                <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Personnalisé</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="daily">
                        <div class="form-group">
                            <label for="date">Jour</label>
                            <input type="date" id="date" name="date" value="{{ request('date', $start->toDateString()) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="weekly">
                        <div class="form-group">
                            <label for="week_start">Semaine de début</label>
                            <input type="date" id="week_start" name="week_start" value="{{ request('week_start', $start->copy()->startOfWeek()->toDateString()) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="monthly">
                        <div class="form-group">
                            <label for="month">Mois</label>
                            <input type="month" id="month" name="month" value="{{ request('month', $start->format('Y-m')) }}" class="form-control">
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
                            <input type="date" id="start_date" name="start_date" value="{{ request('start_date', $start->toDateString()) }}" class="form-control">
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-12 period-field" data-period="custom">
                        <div class="form-group">
                            <label for="end_date">Date de fin</label>
                            <input type="date" id="end_date" name="end_date" value="{{ request('end_date', $end->toDateString()) }}" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 col-12 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" style="background-color: #ff6b35; border-color: #ff6b35;">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi green">
            <div>
                <div class="label">Ventes</div>
                <p class="value">{{ numberDelimiter($totalVentes) }} FG</p>
                <div class="sub">Payé (factures): {{ numberDelimiter($totalPayesVentes) }} FG</div>
            </div>
            <i data-feather="trending-up"></i>
        </div>
        <div class="kpi blue">
            <div>
                <div class="label">Paiements encaissés</div>
                <p class="value">{{ numberDelimiter($totalEncaisse) }} FG</p>
                <div class="sub">Période: paiements enregistrés</div>
            </div>
            <i data-feather="credit-card"></i>
        </div>
        <div class="kpi red">
            <div>
                <div class="label">Non payé (reste)</div>
                <p class="value">{{ numberDelimiter($totalReste) }} FG</p>
                <div class="sub">Dettes sur factures</div>
            </div>
            <i data-feather="alert-triangle"></i>
        </div>
        <div class="kpi gray">
            <div>
                <div class="label">Achats</div>
                <p class="value">{{ numberDelimiter($totalAchats) }} FG</p>
                <div class="sub">Total achats (grand_total)</div>
            </div>
            <i data-feather="shopping-cart"></i>
        </div>
        <div class="kpi yellow">
            <div>
                <div class="label">Dépenses</div>
                <p class="value">{{ numberDelimiter($totalDepenses) }} FG</p>
                <div class="sub">Total dépenses</div>
            </div>
            <i data-feather="minus-circle"></i>
        </div>
        <div class="kpi green">
            <div>
                <div class="label">Balance totale</div>
                <p class="value">{{ numberDelimiter($profit) }} FG</p>
                <div class="sub">Ventes - Achats - Dépenses</div>
            </div>
            <i data-feather="bar-chart-2"></i>
        </div>
    </div>

    <div class="card report-box">
        <div class="card-body">
            <h5 class="mb-3">Détails par boutique</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped report-table">
                    <thead>
                        <tr>
                            <th>Boutique</th>
                            <th>Achats</th>
                            <th>Ventes</th>
                            <th>Paiements encaissés</th>
                            <th>Non payé (reste)</th>
                            <th>Dépenses</th>
                            <th>Balance (approx.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($breakdown as $row)
                            <tr>
                                <td>{{ $row['store']->store_name }}</td>
                                <td>{{ numberDelimiter($row['achats']) }} FG</td>
                                <td>{{ numberDelimiter($row['ventes']) }} FG</td>
                                <td>{{ numberDelimiter($row['encaisse']) }} FG</td>
                                <td>{{ numberDelimiter($row['reste']) }} FG</td>
                                <td>{{ numberDelimiter($row['depenses']) }} FG</td>
                                <td><strong>{{ numberDelimiter($row['profit']) }} FG</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Aucune donnée pour cette période.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card report-box">
        <div class="card-body">
            <h5 class="mb-3">Dépenses journalières</h5>
            @if($dailyExpenses->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped report-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Nombre de dépenses</th>
                                <th>Total du jour</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyExpenses as $dailyExpense)
                                @php
                                    $dateObj = \Carbon\Carbon::parse($dailyExpense->expense_date);
                                    $dayTotal = (float) $dailyExpense->total_amount;
                                    $expenseCount = (int) $dailyExpense->expense_count;
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $dateObj->translatedFormat('l d F Y') }}</strong>
                                    </td>
                                    <td>{{ $expenseCount }}</td>
                                    <td class="text-danger"><strong>{{ numberDelimiter($dayTotal) }} FG</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-info">
                                <th>Total</th>
                                <th>{{ $dailyExpenses->sum('expense_count') }}</th>
                                <th class="text-danger"><strong>{{ numberDelimiter($dailyExpenses->sum('total_amount')) }} FG</strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i data-feather="info"></i>
                    Aucune dépense enregistrée pour cette période.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script_perso')
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
@endsection
