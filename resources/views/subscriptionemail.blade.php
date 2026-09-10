<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription confirmed</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:24px; color:#1b1b18;">
    <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:8px;padding:24px;">
        <h2 style="margin-top:0;">You're subscribed 🎉</h2>
        <p>Hi {{ $userName }},</p>
        <p>
            Your payment was successful and your <strong>{{ $planName }}</strong> plan is now active
            at <strong>{{ $quality }}p</strong> quality.
        </p>
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <tr>
                <td style="padding:6px 0;color:#706f6c;">Plan</td>
                <td style="padding:6px 0;text-align:right;">{{ $planName }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#706f6c;">Quality</td>
                <td style="padding:6px 0;text-align:right;">{{ $quality }}p</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#706f6c;">Started</td>
                <td style="padding:6px 0;text-align:right;">{{ $startsAt }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#706f6c;">Renews / expires</td>
                <td style="padding:6px 0;text-align:right;">{{ $expiresAt }}</td>
            </tr>
        </table>
        <p style="color:#706f6c;font-size:13px;">Thanks for subscribing.</p>
    </div>
</body>
</html>
