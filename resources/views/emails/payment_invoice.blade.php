<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Invoice</title>
</head>
<body>
    <h2>Hello {{ $user->name }},</h2>

    <p>We have received your payment for the company <strong>{{ $company->company_name }}</strong>.</p>

    <p><strong>Transaction ID:</strong> {{ $transaction_id }}</p>
    <p><strong>Amount:</strong> ₹{{ $amount }}</p>

    <p>Your setup is now complete. You can log in to your dashboard and start using our services.</p>

    <p>Thank you for choosing AMT CRM!</p>

    <p>Regards,<br>Team AMT CRM</p>
</body>
</html>
