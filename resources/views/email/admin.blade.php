<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notification: Account Transaction</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f7fafc;
            margin: 0;
            padding: 0;
            color: #1a202c;
            line-height: 1.6;
        }

        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: linear-gradient(135deg, #1a365d, #2c5282);
            padding: 25px;
            text-align: center;
            color: white;
        }

        .logo {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .admin-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }

        .content {
            padding: 30px;
        }

        .transaction-type {
            display: inline-block;
            background: #ebf8ff;
            color: #2b6cb0;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            text-transform: uppercase;
            border-left: 4px solid #2b6cb0;
        }

        .alert-message {
            background: #fffaf0;
            border-left: 4px solid #dd6b20;
            padding: 12px 15px;
            margin: 20px 0;
            font-size: 14px;
            border-radius: 0 4px 4px 0;
        }

        .details-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 25px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            font-size: 14px;
        }

        .details-table th {
            background-color: #2c5282;
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }

        .details-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        .details-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .highlight {
            color: #2b6cb0;
            font-weight: 600;
        }

        .footer {
            padding: 20px 30px;
            background-color: #edf2f7;
            font-size: 13px;
            color: #4a5568;
        }

        .signature {
            margin-top: 25px;
            font-weight: 600;
        }

        .timestamp {
            font-size: 12px;
            color: #718096;
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <div class="logo">{{ config('app.name') }} ADMIN NOTIFICATION</div>
            <div class="admin-badge">FOR ADMINISTRATORS ONLY</div>
        </div>

        <div class="content">
            <div class="transaction-type">{{ $deposit_type }} TRANSACTION PROCESSED</div>

            <div class="alert-message">
                <strong>Admin Action Required:</strong> Verify this transaction was properly authorized and documented.
            </div>

            <p>Dear Admin Team,</p>

            <p>The following account transaction has been processed by <span class="highlight">{{ $credited_by
                    }}</span>:</p>

            <table class="details-table">
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Account Holder</td>
                    <td>{{ $username }}</td>
                </tr>
                <tr>
                    <td>Transaction Type</td>
                    <td>{{ $deposit_type }}</td>
                </tr>
                <tr>
                    <td>Amount</td>
                    <td><strong>₦{{ $amount }}</strong></td>
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
                    <td><code>{{ $transid }}</code></td>
                </tr>
                <tr>
                    <td>Wallet Type</td>
                    <td>{{ $wallet }}</td>
                </tr>
                <tr>
                    <td>Processed By</td>
                    <td><strong>{{ $credited_by }}</strong></td>
                </tr>
                <tr>
                    <td>Timestamp</td>
                    <td>{{ now()->format('Y-m-d H:i:s') }}</td>
                </tr>
            </table>

            <p style="margin-top: 25px;">This is an automated notification. Please review this transaction for accuracy
                and compliance.</p>

            <div class="timestamp">
                System generated on {{ now()->format('F j, Y \a\t H:i') }}
            </div>
        </div>

        <div class="footer">
            <div class="signature">{{ config('app.name') }} Administration Team</div>
            <p>{{ config('app.email') }}</p>
            <p style="margin-top: 15px; font-size: 12px; color: #718096;">
                Confidential - This message is intended only for the use of authorized administrators.
                If you are not the intended recipient, please notify the system security team immediately.
            </p>
        </div>
    </div>
</body>

</html>
