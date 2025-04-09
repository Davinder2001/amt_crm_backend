<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 20px;
            font-size: 14px;
        }

        h2, h3 {
            margin-bottom: 5px;
        }

        p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-style: italic;
        }

        .signature {
            margin-top: 40px;
            text-align: right;
        }

        .signature img {
            height: 50px;
        }
    </style>
</head>
<body>
    <h2>Invoice #{{ $company_name }}</h2>

    <p><strong>Client Name:</strong> {{ $invoice->client_name }}</p>
    <p><strong>Invoice Date:</strong> {{ $invoice->invoice_date }}</p>
    <p><strong>Issued By:</strong> {{ $invoice->invoice_number  }}</p>

    <h3>Items</h3>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price (₹)</th>
                <th>Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Total Amount: ₹{{ number_format($invoice->total_amount, 2) }}</h3>

    @isset($footer_note)
        <div class="footer">
            <p>{{ $footer_note }}</p>
        </div>
    @endisset

    @if(!empty($show_signature))
        <div class="signature">
            <p>Authorized Signature</p>
            <img src="{{ public_path('signature.png') }}" alt="Signature">
        </div>
    @endif
</body>
</html>
