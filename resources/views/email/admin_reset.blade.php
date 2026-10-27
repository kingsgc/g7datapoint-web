<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - {{ config('app.name') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #ffffff;
            text-align: center;
            padding: 30px;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
        }

        .content {
            padding: 40px;
        }

        .transaction-badge {
            display: inline-block;
            background: #e0e7ff;
            color: #2563eb;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .greeting {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .info-text {
            margin-bottom: 25px;
        }

        .details-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .details-table th,
        .details-table td {
            padding: 12px 15px;
            font-size: 14px;
        }

        .details-table th {
            background-color: #f1f5f9;
            text-align: left;
            font-weight: 600;
            color: #334155;
        }

        .details-table td {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }

        .details-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .footer {
            padding: 25px;
            background-color: #f1f5f9;
            text-align: center;
            font-size: 14px;
            color: #64748b;
        }

        .signature {
            margin-top: 30px;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <div>Password Reset Notification</div>
        </div>

        <div class="content">
            <div class="transaction-badge">ADMIN ACTION</div>

            <div class="greeting">Hello, {{ $username }}</div>

            <p class="info-text">
                Your account password has been <strong>reset by an Admin</strong>. Please find your updated login
                details below:
            </p>

            <table class="details-table">
                <tr>
                    <th>Username</th>
                    <td>{{ $username }}</td>
                </tr>
                <tr>
                    <th>Password</th>
                    <td>{{ $password }}</td>
                </tr>
            </table>

            <p style="margin-top: 25px;">
                Kindly log in using these credentials and change your password immediately for security reasons.
            </p>

            <div class="signature">
                Thank you,<br>
                The {{ config('app.name') }} Team
            </div>
        </div>

        <div class="footer">
            <p>{{ config('app.name') }} Inc</p>
            <p>{{ config('app.email') }}</p>
            <p style="margin-top: 15px; font-size: 12px;">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>
