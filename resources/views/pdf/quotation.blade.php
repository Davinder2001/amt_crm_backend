<!DOCTYPE html>
<html>
<head>
    <title>Quotation #{{ $quotation->id }}</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h2>Quotation</h2>
    <p><strong>Customer:</strong> {{ $quotation->customer_name }}</p>

    <table>
        <thead>
            <tr>
                <th>Item</th><th>Qty</th><th>Price</th><th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ $item['price'] }}</td>
                    <td>{{ $item['quantity'] * $item['price'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
