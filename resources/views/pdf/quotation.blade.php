<!DOCTYPE html>
<html>

<head>
    <title>Quotation #{{ $quotation->id }}</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 20px;
        }

        h2 {
            margin-bottom: 0;
        }

        .info,
        .totals {
            margin-top: 10px;
            font-size: 14px;
        }

        .info p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            font-size: 14px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: left;
        }

        .totals {
            width: 100%;
            margin-top: 20px;
            font-size: 15px;
        }

        .totals td {
            border: none;
            padding: 6px 10px;
        }

        .company-name {
            text-align: center;
        }

        .right {
            text-align: right;
        }
    </style>
</head>

<body>
    <h2 class="company-name">{{ $quotation->company->company_name }}</h2>

    <div class="info">
        <p><strong>Date:</strong> {{ $quotation->created_at->format('d M Y') }}</p>
        <p><strong>Customer Name:</strong> {{ $quotation->customer_name }}</p>
        <p><strong>Customer Number:</strong> {{ $quotation->customer_number }}</p>
        @if ($quotation->customer_email)
            <p><strong>Email:</strong> {{ $quotation->customer_email }}</p>
        @endif
    </div>

  <table>
    <thead>
        <tr>
            <th>Sr No</th> <!-- Added Sr No header -->
            <th>Item</th>
            <th>Qty</th>
            <th>Price (₹)</th>
            <th>Total (₹)</th>
        </tr>
    </thead>
    <tbody>
        @php $subtotal = 0; @endphp
        @foreach ($quotation->items as $index => $item)
            @php
                $lineTotal = $item['quantity'] * $item['price'];
                $subtotal += $lineTotal;
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td> <!-- Sr No using $index + 1 -->
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['price'], 2) }}</td>
                <td>{{ number_format($lineTotal, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>


    <table class="totals">
        <tr>
            <td class="right" colspan="3"><strong>Subtotal:</strong></td>
            <td class="right">₹{{ number_format($quotation->sub_total, 2) }}</td>
        </tr>
        <tr>
            <td class="right" colspan="3"><strong>Tax ({{ $quotation->tax_percent }}%):</strong></td>
            <td class="right">₹{{ number_format($quotation->tax_amount, 2) }}</td>
        </tr>
        <tr>
            <td class="right" colspan="3"><strong>Service Charges:</strong></td>
            <td class="right">₹{{ number_format($quotation->service_charges, 2) }}</td>
        </tr>
        <tr>
            <td class="right" colspan="3"><strong>Total:</strong></td>
            <td class="right"><strong>₹{{ number_format($quotation->total, 2) }}</strong></td>
        </tr>
    </table>
</body>

</html>
