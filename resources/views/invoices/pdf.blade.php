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
        .summary-table {
            width: 50%;
            margin-top: 20px;
            float: right;
            border-collapse: collapse;
        }
        .summary-table td {
            border: none;
            padding: 4px 8px;
        }
        .footer {
            clear: both;
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
    <h2 style="text-align: center">{{ $company_name }}</h2>

    <p><strong>Client Name:</strong> {{ $invoice->client_name }}</p>
    <p><strong>Invoice Date:</strong> {{ $invoice->invoice_date }}</p>
    <p><strong>Issued By:</strong> {{ $issued_by }}</p>
    <p><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>

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

    <table class="summary-table">
        <tr>
            <td><strong>Subtotal:</strong></td>
            <td>₹{{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td>
                <strong>
                    Discount
                    @if($invoice->discount_percentage > 0)
                        ({{ $invoice->discount_percentage }}%)
                    @endif
                    :
                </strong>
            </td>
            <td>- ₹{{ number_format($invoice->discount_amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td><strong>₹{{ number_format($invoice->final_amount, 2) }}</strong></td>
        </tr>
        <tr>
            <td><strong>Payment Method:</strong></td>
            <td>{{ $invoice->payment_method }}</td>
        </tr>
    </table>

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
