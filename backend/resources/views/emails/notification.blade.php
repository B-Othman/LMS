<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background-color: #f5f7fa;
            color: #1a1a2e;
            font-size: 14px;
            line-height: 1.6;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(59, 122, 184, 0.08);
        }
        .header {
            background-color: #3b7ab8;
            padding: 28px 36px;
            display: flex;
            align-items: center;
        }
        .header-logo {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-decoration: none;
        }
        .header-logo span {
            opacity: 0.7;
            font-weight: 400;
        }
        .body {
            padding: 36px;
        }
        .footer {
            padding: 24px 36px;
            background-color: #f5f7fa;
            border-top: 1px solid #e8edf4;
            text-align: center;
            color: #6b7a99;
            font-size: 12px;
        }
        .footer a {
            color: #3b7ab8;
            text-decoration: none;
        }
        a.button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 28px;
            background-color: #3b7ab8;
            color: #ffffff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <span class="header-logo">SECURE<span>CY</span></span>
        </div>
        <div class="body">
            {!! $bodyHtml !!}
        </div>
        <div class="footer">
            <p>You are receiving this email because you have an account on the Securecy LMS.</p>
            <p style="margin-top: 8px;">&copy; {{ date('Y') }} Securecy. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
