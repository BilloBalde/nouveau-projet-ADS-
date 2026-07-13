<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table  datanew">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Produit</th>
                        <th>Prix</th>
                        <th>Quantity</th>
                        <th>Description</th>
                        <th>Created at</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dataTable as $data)
                    <tr>
                        <td>{{ $data->numeroPurchase }}</td>
                        <td>{{ $data->product }}</td>
                        <td>{{ $data->price }}</td>
                        <td>{{ $data->quantity }}</td>
                        <td>{{ $data->description }}</td>
                        <td>{{ $data->created_at }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
