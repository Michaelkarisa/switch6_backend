<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Advertisement payment confirmed</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; padding:24px; color:#1b1b18;">
    <div style="max-width:480px;margin:0 auto;background:#fff;border-radius:8px;padding:24px;">
        <h2 style="margin-top:0;">Your campaign is live 📣</h2>
        <p>Hi {{ $userName }},</p>
        <p>
            Payment for your advertisement <strong>"{{ $title }}"</strong> was successful and the
            campaign is now active.
        </p>
        <table style="width:100%;border-collapse:collapse;margin:16px 0;">
            <tr>
                <td style="padding:6px 0;color:#706f6c;">Campaign type</td>
                <td style="padding:6px 0;text-align:right;">{{ ucfirst($campaignType) }}</td>
            </tr>
            @if($period)
            <tr>
                <td style="padding:6px 0;color:#706f6c;">Period</td>
                <td style="padding:6px 0;text-align:right;">{{ ucfirst(str_replace('_', ' ', $period)) }}</td>
            </tr>
            @endif
            @if($endDate)
            <tr>
                <td style="padding:6px 0;color:#706f6c;">Runs until</td>
                <td style="padding:6px 0;text-align:right;">{{ $endDate }}</td>
            </tr>
            @endif
        </table>
        <p style="color:#706f6c;font-size:13px;">Thanks for advertising with us.</p>
    </div>
</body>
</html>
