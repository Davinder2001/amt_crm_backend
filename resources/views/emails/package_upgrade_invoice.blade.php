<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Package Upgrade Invoice</title>
</head>
<body>
    <h2>Hello {{ $user->name }},</h2>

    <p>Your company <strong>{{ $company->company_name }}</strong> has successfully upgraded its package.</p>

    <h3>Payment Summary:</h3>
    <ul>
        <li><strong>Order ID:</strong> {{ $orderId }}</li>
        <li><strong>Transaction ID:</strong> {{ $transactionId }}</li>
        <li><strong>Payment Mode:</strong> {{ $paymentMode }}</li>
        <li><strong>Amount:</strong> ₹{{ $amount }}</li>
        <li><strong>Status:</strong> {{ $paymentStatus }}</li>
    </ul>

    <h3>Package Details:</h3>
    <ul>
        <li><strong>Package ID:</strong> {{ $packageId }}</li>
        <li><strong>Type:</strong> {{ ucfirst($packageType) }}</li>
    </ul>

    <p>Thank you for choosing AMT CRM. You can now enjoy your upgraded plan!</p>

    <p>Regards,<br>Team AMT CRM</p>
</body>
</html>
