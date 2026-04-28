<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name', 'Mobile Manager') }}</title>
</head>
<body style="margin: 0; padding: 0; background: #f3f4f6; font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background: #f3f4f6; margin: 0; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; background: #ffffff; border-radius: 8px; margin: 0 auto;">
                    <tr>
                        <td style="padding: 28px 28px 8px;">
                            <img src="{{ asset('images/micronet-logo.svg') }}" width="150" alt="{{ config('app.name', 'Mobile Manager') }}" style="display: block; width: 150px; max-width: 150px; height: auto; border: 0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 28px 28px;">
                            {!! $body !!}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
