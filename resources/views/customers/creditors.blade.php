<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.flash')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Créanciers</h4>
                            <h6>Suivi des soldes par boutique</h6>
                        </div>
                    </div>

                    @if($creditors->isEmpty())
                        <div class="card">
                            <div class="card-body">
                                <p class="mb-0">Aucun créancier trouvé pour le moment.</p>
                            </div>
                        </div>
                    @else
                        @foreach ($creditors as $storeId => $items)
                            <div class="card mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ $items->first()->store_name }}</h5>
                                    <span class="badge bg-primary">Total: {{ numberDelimiter($items->sum('balance')) }} FG</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table datanew">
                                            <thead>
                                                <tr>
                                                    <th>Mark</th>
                                                    <th>Nom</th>
                                                    <th>Téléphone</th>
                                                    <th>Email</th>
                                                    <th>Solde</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($items as $item)
                                                    <tr>
                                                        <td>
                                                            <a class="btn btn-success" style="color: white" href="{{ route('paiementsClient.add', $item->mark) }}" title="Voir Transactions">
                                                                {{ $item->mark }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $item->customerName }}</td>
                                                        <td>{{ $item->tel }}</td>
                                                        <td>{{ $item->email }}</td>
                                                        <td>{{ numberDelimiter($item->balance) }} FG</td>
                                                        <td>
                                                            <a href="{{ route('paiementsClient.add', $item->mark) }}" class="btn btn-primary btn-sm">Suivre</a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        @include('layouts.scripts')
    </body>
</html>
