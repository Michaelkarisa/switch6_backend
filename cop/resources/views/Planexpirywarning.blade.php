<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Plan Expiry Warning</title>
  <style>
    body { margin:0; padding:0; background:#0f172a; font-family:'DM Sans',Arial,sans-serif; color:#e2e8f0; }
    .wrap { max-width:560px; margin:40px auto; background:#1e293b; border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,.07); }
    .header { background:linear-gradient(135deg,#14532d,#1e3a5f); padding:36px 40px; }
    .logo { display:flex; align-items:center; gap:10px; margin-bottom:24px; }
    .logo-icon { width:40px; height:40px; border-radius:10px; background:linear-gradient(135deg,#3b82f6,#22c55e); display:flex; align-items:center; justify-content:center; font-size:20px; }
    .logo-text { font-size:18px; font-weight:700; color:#fff; }
    .logo-sub { font-size:11px; color:rgba(255,255,255,.5); }
    .badge { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:99px; border:1px solid rgba(239,68,68,.35); background:rgba(239,68,68,.1); font-size:11px; font-weight:700; color:#fca5a5; letter-spacing:.06em; text-transform:uppercase; }
    .dot { width:6px; height:6px; border-radius:50%; background:#ef4444; }
    .body { padding:36px 40px; }
    h1 { font-size:24px; font-weight:700; color:#fff; margin:0 0 12px; line-height:1.2; }
    p { font-size:15px; color:#94a3b8; line-height:1.7; margin:0 0 16px; }
    .plan-box { background:#0f172a; border:1px solid rgba(255,255,255,.08); border-radius:12px; padding:20px 24px; margin:24px 0; }
    .plan-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid rgba(255,255,255,.05); }
    .plan-row:last-child { border-bottom:none; }
    .plan-label { font-size:12px; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
    .plan-value { font-size:14px; font-weight:600; color:#e2e8f0; }
    .days-badge { font-size:28px; font-weight:800; color:#f97316; }
    .cta { display:block; text-align:center; padding:14px 32px; background:#22c55e; color:#fff; font-size:15px; font-weight:700; text-decoration:none; border-radius:12px; margin:28px 0 0; }
    .footer { padding:24px 40px; border-top:1px solid rgba(255,255,255,.06); text-align:center; font-size:12px; color:#475569; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <div class="logo">
        <div class="logo-icon">📡</div>
        <div>
          <div class="logo-text">Switch6</div>
          <div class="logo-sub">Sports Broadcast OS</div>
        </div>
      </div>
      <div class="badge"><span class="dot"></span> Plan expiring soon</div>
    </div>

    <div class="body">
      <h1>Hi {{ $userName }},</h1>
      <p>
        Your <strong style="color:#e2e8f0;">{{ $planName }}</strong> plan is expiring in
        <span style="color:#f97316; font-weight:700;">{{ $daysLeft }} {{ $daysLeft === 1 ? 'day' : 'days' }}</span>.
        Renew now to keep your matches, streams, and broadcasts running without interruption.
      </p>

      <div class="plan-box">
        <div class="plan-row">
          <span class="plan-label">Plan</span>
          <span class="plan-value">{{ $planName }}</span>
        </div>
        <div class="plan-row">
          <span class="plan-label">Expires on</span>
          <span class="plan-value">{{ $expiresAt }}</span>
        </div>
        <div class="plan-row">
          <span class="plan-label">Days remaining</span>
          <span class="days-badge">{{ $daysLeft }}</span>
        </div>
      </div>

      <p>
        After expiry, your workspace will enter a <strong style="color:#e2e8f0;">7-day grace period</strong>
        — your data is safe, but live streams and ad injection will be paused.
      </p>

      <a href="{{ $renewUrl }}" class="cta">Renew My Plan →</a>
    </div>

    <div class="footer">
      You're receiving this because you have a Switch6 account.<br>
      © {{ date('Y') }} Switch6 · Sports Broadcast OS
    </div>
  </div>
</body>
</html>