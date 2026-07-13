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
                            <h4>Liste Paiements Journaliers</h4>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('journaliers.index') }}"  class="btn btn-added"><img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img"></a>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">
                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="{{ asset('assets/img/icons/search-white.svg') }}" alt="img"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Reference</th>
                                            <th>Montant versé</th>
                                            <th>Total versé</th>
                                            <th>reste</th>
                                            <th>Paid By    </th>
                                            <th>Note    </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($payements as $item)
                                        <tr class="bor-b1">
                                        <td>{{ $item->created_at }}</td>
                                        <td>{{ $item->reference }}</td>
                                        <td>{{ $item->versement }}</td>
                                        <td>{{ $item->journalier->paid }}</td>
                                        <td>{{ $item->journalier->reste }}</td>
                                        <td>{{ $item->paid_by }}</td>
                                        <td>{{ $item->notes }}</td>
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
        @include('layouts.scripts')
    </body>
</html>



