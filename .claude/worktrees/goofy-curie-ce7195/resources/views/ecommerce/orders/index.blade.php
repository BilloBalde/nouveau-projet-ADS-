@extends('layouts.shop-app')

@section('title', 'Mes commandes')

@section('content')
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Mes commandes</h1>
    <p class="text-gray-600 text-sm mb-8">Historique de vos achats</p>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N°</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paiement</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">#{{ $order->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</td>
                            <td class="px-4 py-3 text-sm">
                                @if($order->payment_status == 'paid')
                                    <span class="text-emerald-600 font-medium">Payé</span>
                                @else
                                    <span class="text-amber-600 font-medium">En attente</span>
                                @endif
                                <span class="block text-xs text-gray-500">{{ strtoupper($order->payment_method) }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                @php
                                    $statusMap = [
                                        'pending'    => ['label' => 'En attente',  'class' => 'bg-yellow-100 text-yellow-800'],
                                        'approved'   => ['label' => 'Confirmée',   'class' => 'bg-blue-100 text-blue-800'],
                                        'processing' => ['label' => 'En cours',    'class' => 'bg-indigo-100 text-indigo-800'],
                                        'delivering' => ['label' => 'En livraison','class' => 'bg-indigo-100 text-indigo-800'],
                                        'completed'  => ['label' => 'Livrée',      'class' => 'bg-green-100 text-green-800'],
                                        'delivered'  => ['label' => 'Livrée',      'class' => 'bg-green-100 text-green-800'],
                                        'rejected'   => ['label' => 'Refusée',     'class' => 'bg-red-100 text-red-800'],
                                        'cancelled'  => ['label' => 'Annulée',     'class' => 'bg-gray-100 text-gray-800'],
                                    ];
                                    $s = $statusMap[$order->status] ?? ['label' => $order->status, 'class' => 'bg-gray-100 text-gray-700'];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $s['class'] }}">{{ $s['label'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('orders.show', $order->id) }}" class="text-amber-600 hover:text-amber-700 text-sm font-medium">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">Vous n'avez pas encore passé de commande.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
