<!DOCTYPE html>
<html>
<head>
    <title>Report d'achat</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h2>Report d'achat</h2>
    <table>
        <thead>
            <tr>
                <th>Reference</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Depense</th>
                <th>Date Emis</th>
                <th>Date Fournis</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logistics as $data)
            <tr>
                <td>{{ $data->numeroPurchase }}</td>
                <td>{{ $data->type }}</td>
                <td>{{ $data->quantity }}</td>
                <td>{{ $data->depense }}</td>
                <td>{{ $data->dateEmis }}</td>
                <td>{{ $data->dateFournis }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
