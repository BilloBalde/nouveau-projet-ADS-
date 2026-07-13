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
                            <h4>Commandes Clients</h4>
                            <h6>Consulter et confirmer les demandes</h6>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table datanew">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Magasin</th>
                                            <th>Client</th>
                                            <th>Téléphone</th>
                                            <th>Total</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $order)
                                            <tr>
                                                <td>{{ $order->id }}</td>
                                                <td>{{ $order->store?->store_name ?? 'N/A' }}</td>
                                                <td>{{ $order->customer_name }}</td>
                                                <td>{{ $order->phone }}</td>
                                                <td>{{ numberDelimiter($order->total_amount) }} FG</td>
                                                <td>{{ ucfirst($order->status) }}</td>
                                                <td>{{ $order->created_at }}</td>
                                                <td class="text-end">
                                                    @if($order->status === 'pending')
                                                        <form method="POST" action="{{ route('customer-orders.status', $order) }}" style="display:inline-block;">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status" value="confirmed">
                                                            <button type="submit" class="btn btn-sm btn-success me-2">
                                                                Confirmer
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <a href="{{ route('customer-orders.show', $order) }}">
                                                        <img src="{{ asset('assets/img/icons/eye1.svg') }}" alt="img">
                                                    </a>
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
        @include('layouts.scripts')
    </body>
</html>
