<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Company Registration</title>
</head>
<body>
    <h2>Hello {{ $user->name }},</h2>

    <p>Thank you for registering your company <strong>{{ $company->company_name }}</strong> with AMT CRM.</p>

    <p>Our team will verify your details within <strong>24 hours</strong>. You'll be notified once verification is complete.</p>

    <p>Regards,<br>Team AMT CRM</p>
</body>
</html>
