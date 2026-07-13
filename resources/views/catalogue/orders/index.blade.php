@extends('layouts.visitor.visitor')

@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/storefront.css') }}">

<div class="section-container">
    <section class="order-section">
        <div class="order-form-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="section-title" style="margin:0;">Mes Commandes</h2>
                <form method="POST" action="{{ route('catalogue.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Déconnexion</button>
                </form>
            </div>

            @include('layouts.flash')

            @if($orders->isEmpty())
                <div class="cart-empty">
                    <div class="cart-empty-icon">🧾</div>
                    <h4>Aucune commande</h4>
                    <p>Retournez au catalogue pour commander.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table cart-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Boutique</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td>{{ $order->id }}</td>
                                    <td>{{ $order->store?->store_name ?? 'N/A' }}</td>
                                    <td><strong>{{ numberDelimiter($order->total_amount) }} FG</strong></td>
                                    <td>{{ ucfirst($order->status) }}</td>
                                    <td>{{ $order->created_at }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5">
                                        <strong>Détails</strong>
                                        <ul class="mb-0">
                                            @foreach($order->items as $item)
                                                <li>{{ $item->product?->libelle }} — {{ $item->quantity }} PCS</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

