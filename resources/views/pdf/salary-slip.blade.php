<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Slip - {{ $employee->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            padding: 20px;
            color: #333;
        }
        h2 {
            text-align: center;
            margin-bottom: 40px;
        }
        .details, .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .details td {
            padding: 6px 10px;
        }
        .salary-table th, .salary-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: left;
        }
        .salary-table th {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>

    <h2>Salary Slip</h2>

    <table class="details">
        <tr>
            <td><strong>Employee Name:</strong> {{ $employee->name }}</td>
            <td><strong>Employee Email:</strong> {{ $employee->email }}</td>
        </tr>
        <tr>
            <td><strong>Phone:</strong> {{ $employee->number }}</td>
            <td><strong>Company:</strong> {{ $employee->companies->first()->company_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Salary Date:</strong> {{ now()->format('d M, Y') }}</td>
            <td><strong>Employee ID:</strong> {{ $employee->id }}</td>
        </tr>
    </table>

    <table class="salary-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Earnings</th>
                <th>Amount</th>
                <th>Deductions</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Basic Salary</td>
                <td>₹{{ number_format($salaryDetails['current_salary'] ?? 0, 2) }}</td>
                <td>-</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>

    <p><strong>Total Salary:</strong> ₹{{ number_format($salaryDetails['current_salary'] ?? 0, 2) }}</p>

</body>
</html>
