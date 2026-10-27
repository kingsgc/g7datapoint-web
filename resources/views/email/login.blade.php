<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <title>Login Successful</title>
    <style type="text/css">
        @import url(https://fonts.googleapis.com/css?family=Nunito);

        img {
            max-width: 600px;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        html {
            margin: 0;
            padding: 0;
        }

        a {
            text-decoration: none;
            border: 0;
            outline: none;
            color: #bbbbbb;
        }

        a img {
            border: none;
        }

        td,
        h1,
        h2,
        h3 {
            font-family: Helvetica, Arial, sans-serif;
            font-weight: 400;
        }

        td {
            text-align: center;
        }

        body {
            -webkit-font-smoothing: antialiased;
            -webkit-text-size-adjust: none;
            width: 100%;
            height: 100%;
            color: #666;
            background: #fff;
            font-size: 16px;
            padding: 0px;
            margin: 0px;
        }

        table {
            border-collapse: collapse !important;
        }

        .headline {
            color: #444;
            font-size: 28px;
            font-weight: 700;
        }

        .force-full-width {
            width: 100% !important;
        }
    </style>
</head>

<body bgcolor="#fff" class="body"
    style="padding:20px; margin:0; display:block; background:#ffffff; -webkit-text-size-adjust:none">
    <table align="center" cellpadding="0" cellspacing="0" height="100%" width="100%">
        <tbody>
            <tr>
                <td align="center" bgcolor="#fff" valign="top" width="100%">
                    <center>
                        <table cellpadding="0" cellspacing="0" class="w320" style="margin: 0 auto;" width="600">
                            <tbody>
                                <tr>
                                    <td align="center" valign="top">
                                        <table bgcolor="#fff" cellpadding="0" cellspacing="0" width="100%"
                                            style="margin-top: 80px;">
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <img alt="login success" height="120"
                                                            src="{{ asset('welcome.png')}}" width="120">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="headline">Login Successful</td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <center>
                                                            <table cellpadding="0" cellspacing="0" width="80%"
                                                                style="margin: 0 auto;">
                                                                <tbody>
                                                                    <tr>
                                                                        <td style="color:#444; font-weight:400;">
                                                                            <br>
                                                                            Hello, {{ $username }} <br><br>
                                                                            We’re letting you know that you have
                                                                            successfully logged in to your
                                                                            <strong>{{ env('APP_NAME') }}</strong>
                                                                            account.
                                                                            <br><br>
                                                                            <p>If this was you, no action is required.
                                                                                If you didn’t initiate this login,
                                                                                please secure your account immediately
                                                                                by changing your password or contacting
                                                                                our support team.</p>
                                                                            <br>
                                                                            <span style="font-weight:bold;">Login
                                                                                Time:</span>
                                                                            {{ now() }} <br>
                                                                            <span style="font-weight:bold;">IP
                                                                                Address:</span>
                                                                            {{ $ip_address }} <br>
                                                                            <br><br>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </center>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <table bgcolor="#fff" cellpadding="0" cellspacing="0" class="force-full-width"
                                            style="margin: 0 auto; margin-bottom: 20px;">
                                            <tbody>
                                                <tr>
                                                    <td style="color:#888; font-size:14px;">
                                                        <p>If you need help, reach us at
                                                            <a href="mailto:{{ $system_email }}">
                                                                {{ $system_email }}
                                                            </a>
                                                        </p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </center>
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>