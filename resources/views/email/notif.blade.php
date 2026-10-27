<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #1e293b;
            line-height: 1.6;
        }

        .email-container {
            max-width: 640px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
        }

        .header {
            padding: 28px 40px;
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }

        .logo {
            font-size: 22px;
            font-weight: 700;
            color: #1e40af;
            text-decoration: none;
        }

        .content {
            padding: 40px;
        }

        .title {
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 30px 0;
            text-align: left;
        }

        .message-body {
            font-size: 15px;
            color: #334155;
            line-height: 1.7;
        }

        .message-body p {
            margin: 0 0 20px 0;
        }

        .email-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 25px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            font-size: 14px;
        }

        .email-table th {
            background-color: #f1f5f9;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
        }

        .email-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .email-table tr:last-child td {
            border-bottom: none;
        }

        .email-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .signature {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .footer {
            padding: 24px 40px;
            background-color: #f1f5f9;
            font-size: 13px;
            color: #64748b;
            text-align: center;
        }

        .footer p {
            margin: 6px 0;
        }

        .highlight-box {
            background-color: #f8fafc;
            border-left: 4px solid #1e40af;
            padding: 16px;
            margin: 20px 0;
            border-radius: 0 4px 4px 0;
        }

        .action-button {
            display: inline-block;
            background-color: #1e40af;
            color: white !important;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            <a href="#" class="logo">{{ $app_name }}</a>
        </div>

        <div class="content">
            <h1 class="title">{{ $title }}</h1>

            <div class="message-body">
                {!! $messages !!}
            </div>
            <div class="signature">
                <p style="margin-bottom: 8px;">Thank you for using <strong>{{ $app_name }}</strong>.</p>
                <p style="color: #64748b;">Best regards,<br>The {{ $app_name }} Team</p>
            </div>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $app_name }}. All rights reserved.</p>
            <p>{{ $sender_mail }}</p>
            <p style="margin-top: 16px; font-size: 12px;">
                This is an automated message. Please do not reply directly to this email.
            </p>
        </div>
    </div>
</body>

</html>
