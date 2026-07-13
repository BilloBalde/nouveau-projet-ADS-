{{-- resources/views/exports/sales.blade.php --}}
<table>
    <thead>
        <tr>
            <th style="background-color: #4F81BD; color: white; font-weight: bold; text-align: center;">N° Facture</th>
            <th style="background-color: #4F81BD; color: white; font-weight: bold; text-align: center;">Produit</th>
            <th style="background-color: #4F81BD; color: white; font-weight: bold; text-align: center;">Quantité</th>
            <th style="background-color: #4F81BD; color: white; font-weight: bold; text-align: center;">Prix Unitaire</th>
            <th style="background-color: #4F81BD; color: white; font-weight: bold; text-align: center;">Prix Total</th>
            <th style="background-color: #4F81BD; color: white; font-weight: bold; text-align: center;">Intérêt</th>
            <th style="background-color: #4F81BD; color: white; font-weight: bold; text-align: center;">Date</th>
        </tr>
    </thead>
    <tbody>
        @foreach($sales as $sale)
        <tr>
            <td>{{ $sale->numeroFacture }}</td>
            <td>{{ $sale->produit }}</td>
            <td>{{ $sale->quantity }}</td>
            <td>{{ $sale->prix }}</td>
            <td>{{ $sale->prixTotal }}</td>
            <td>{{ $sale->interet }}</td>
            <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" style="text-align: right; font-weight: bold;">TOTAUX</td>
            <td style="font-weight: bold;">{{ $totalQuantity }}</td>
            <td></td>
            <td style="font-weight: bold;">{{ $totalAmount }}</td>
            <td style="font-weight: bold;">{{ $totalInterest }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>