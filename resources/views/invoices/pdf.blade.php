<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 13px;
            color: #000;
        }

        .container {
            padding: 20px 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .logo img {
            height: 50px;
        }

        .store-info {
            text-align: right;
        }

        .invoice-box {
            margin-top: 20px;
            border: 1px solid #ccc;
            padding: 20px;
        }

        .invoice-box h2 {
            text-align: center;
            margin: 0;
        }

        .details p {
            margin: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #aaa;
            padding: 6px;
            text-align: left;
        }

        .summary {
            margin-top: 15px;
            width: 100%;
            max-width: 300px;
            float: right;
        }

        .summary td {
            border: none;
            padding: 4px 8px;
        }

        .signature {
            clear: both;
            text-align: right;
            margin-top: 60px;
        }

        .amount-words {
            margin-top: 10px;
            font-weight: bold;
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="{{ public_path('logo.png') }}" alt="Logo">
            </div>
            <div class="store-info">
                <strong>{{ $company_name }}</strong><br>
                {{ $company_address }}<br>
                Phone: {{ $company_phone }}<br>
                GSTIN: {{ $company_gstin }}
            </div>
        </div>

        <div class="invoice-box">
            <h2>GST INVOICE</h2>
            <div class="details">
                <p><strong>Name:</strong> {{ $invoice->client_name }}</p>
                <p><strong>Contact:</strong> {{ $invoice->client_phone }}</p>
                @if (!empty($invoice->client_address))
                    <p><strong>Address:</strong> {{ $invoice->client_address }}</p>
                @endif
                <p><strong>Invoice No:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Date:</strong> {{ $invoice->invoice_date }}</p>
                <p><strong>Payment Mode:</strong> {{ $invoice->payment_method }}</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Sn.</th>
                        <th>Product/Item</th>
                        <th>Unit Price</th>
                        <th>Tax Rate</th>
                        <th>Qty</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ $item->tax_percentage }}%</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="summary">
                <tr>
                    <td><strong>Sub Total:</strong></td>
                    <td>₹{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Discount:</strong></td>
                    <td>- ₹{{ number_format($invoice->discount_amount, 2) }} / {{ $invoice->discount_percentage }}%</td>
                </tr>
                @if (!empty($invoice->tax_amount) && $invoice->tax_amount > 0)
                    <tr>
                        <td><strong>Tax:</strong></td>
                        <td>₹{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>Total:</strong></td>
                    <td><strong>₹{{ number_format($invoice->final_amount, 2) }}</strong></td>
                </tr>
            </table>

            <p class="amount-words">Amount in Words: {{ ucwords($invoice->amount_in_words) }}</p>

            @if(!empty($show_signature))
            <div class="signature">
                <p>Authorized Signatory</p>
                <img src="{{ public_path('signature.png') }}" alt="Signature">
            </div>
            @endif
        </div>
    </div>
</body>
</html>
