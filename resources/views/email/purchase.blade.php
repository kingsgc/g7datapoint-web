<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
        
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333333;
            line-height: 1.6;
        }
        
        .email-container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .email-header {
            background: linear-gradient(135deg, #0066cc 0%, #004080 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .email-logo {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .email-body {
            padding: 40px;
        }
        
        .email-title {
            font-size: 22px;
            font-weight: 600;
            color: #0066cc;
            margin-top: 0;
            margin-bottom: 25px;
        }
        
        .email-content {
            font-size: 15px;
            color: #555555;
        }
        
        .email-footer {
            text-align: center;
            padding: 25px;
            background: #f5f7fa;
            font-size: 13px;
            color: #777777;
        }
        
        .email-button {
            display: inline-block;
            background: #0066cc;
            color: white !important;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 6px;
            font-weight: 500;
            margin: 20px 0;
        }
        
        .email-divider {
            border-top: 1px solid #eaeaea;
            margin: 25px 0;
        }
        
        .email-highlight {
            background-color: #f0f7ff;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #0066cc;
        }
        
        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="email-logo">{{ $app_name }}</div>
            <div style="font-size: 16px; opacity: 0.9;">{{ $title }}</div>
        </div>
        
        <div class="email-body">
            <h1 class="email-title">{{ $title }}</h1>
            
            <div class="email-content">
                <p>{{ $mes }}</p>
                
               @if(isset($action_url) && isset($action_text))
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{ $action_url }}" 
                        style="display: inline-block; 
                                background: #0066cc; 
                                color: white !important; 
                                text-decoration: none; 
                                padding: 12px 25px; 
                                border-radius: 6px; 
                                font-weight: 500;">
                            {{ $action_text }}
                        </a>
                    </div>
                @endif
                
                <div class="email-divider"></div>
                
                <p>If you have any questions, please don't hesitate to contact our support team.</p>
                
                <div class="email-highlight">
                    <p style="margin: 0;">Thank you for choosing <strong>{{ $app_name }}</strong>.</p>
                </div>
            </div>
        </div>
        
        <div class="email-footer">
            <p>&copy; {{ now()->year }} {{ $app_name }}. All rights reserved.</p>
            <p>
                <a href="mailto:{{ $sender_mail }}" style="color: #0066cc; text-decoration: none;">{{ $sender_mail }}</a> | 
                <a href="{{ url('') }}" style="color: #0066cc; text-decoration: none;">Visit Website</a>
            </p>
        </div>
    </div>
</body>
</html>