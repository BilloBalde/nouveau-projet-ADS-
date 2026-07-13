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
                            <h4>Commande #{{ $order->id }}</h4>
                            <h6>{{ $order->customer_name }}</h6>
                        </div>
                    </div>
                    @include('layouts.flash')

                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div><strong>Magasin:</strong> {{ $order->store?->store_name ?? 'N/A' }}</div>
                                    <div><strong>Téléphone:</strong> {{ $order->phone }}</div>
                                    <div><strong>Adresse:</strong> {{ $order->address }}</div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div><strong>Statut:</strong> {{ ucfirst($order->status) }}</div>
                                    <div><strong>Total:</strong> {{ numberDelimiter($order->total_amount) }} FG</div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th>Quantité</th>
                                            <th>Prix</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->items as $item)
                                            <tr>
                                                <td>{{ $item->product?->libelle }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ numberDelimiter($item->unit_price) }} FG</td>
                                                <td>{{ numberDelimiter($item->total_price) }} FG</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <form method="POST" action="{{ route('customer-orders.status', $order) }}" class="row g-2 mt-3">
                                @csrf
                                @method('PUT')
                                <div class="col-auto">
                                    <select name="status" class="form-control">
                                        <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>En attente</option>
                                        <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmée</option>
                                        <option value="paid" {{ $order->status === 'paid' ? 'selected' : '' }}>Payée</option>
                                        <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Annulée</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">Mettre à jour</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
    </body>
</html>
