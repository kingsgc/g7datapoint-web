<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Notification - {{ $app_name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .header {
            padding: 30px;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            text-align: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
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
            margin-bottom: 25px;
        }

        .amount-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
            border-left: 4px solid #2563eb;
        }

        .amount-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 5px;
        }

        .amount-value {
            font-size: 28px;
            font-weight: 700;
            color: #1e40af;
        }

        .details-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 30px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .details-table th {
            background-color: #f1f5f9;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .details-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        .details-table tr:nth-child(even) {
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

        .reference-id {
            font-family: monospace;
            background: #f1f5f9;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ $app_name }}</div>
            <div>Account Notification</div>
        </div>

        <div class="content">
            <div class="transaction-badge">{{ $deposit_type }} COMPLETED</div>

            <div class="greeting">Hello Valued User,</div>

            <p>Your account has been successfully <strong>{{ strtolower($deposit_type) }}ed</strong> with the following
                details:</p>

            <div class="amount-card">
                <div class="amount-label">Transaction Amount</div>
                <div class="amount-value">₦{{ $amount }}</div>
            </div>

            <table class="details-table">
                <tr>
                    <th>Description</th>
                    <th>Details</th>
                </tr>
                <tr>
                    <td>Previous Balance</td>
                    <td>₦{{ $oldbal }}</td>
                </tr>
                <tr>
                    <td>New Balance</td>
                    <td><strong>₦{{ $newbal }}</strong></td>
                </tr>
                <tr>
                    <td>Reference ID</td>
                    <td><span class="reference-id">{{ $transid }}</span></td>
                </tr>
                <tr>
                    <td>Wallet Type</td>
                    <td>{{ $wallet }}</td>
                </tr>
                <tr>
                    <td>Processed By</td>
                    <td>Admin Team</td>
                </tr>
            </table>

            <p style="margin-top: 25px;">Thank you for trusting us with your transaction. If you have any questions,
                please contact our support team.</p>

            <div class="signature">
                Best regards,<br>
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
