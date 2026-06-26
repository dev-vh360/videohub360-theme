<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="<?php echo esc_attr( (string) ( $opts['theme_color'] ?? '#2563eb' ) ); ?>">
  <title><?php echo $app_name; ?> — Offline</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:0;padding:40px;background:#f9fafb;color:#111827;}
    .card{max-width:760px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:24px;box-shadow:0 10px 20px rgba(0,0,0,.05);}
    h1{margin:0 0 8px;font-size:22px;}
    p{margin:0 0 14px;line-height:1.55;color:#374151;}
    a{color:<?php echo esc_attr( (string) ( $opts['theme_color'] ?? '#2563eb' ) ); ?>;text-decoration:none;}
    a:hover{text-decoration:underline;}
    .hint{font-size:13px;color:#6b7280;margin-top:16px;}
  </style>
</head>
<body>
  <div class="card">
    <h1>You’re offline</h1>
    <p><?php echo esc_html( $app_name ); ?> can’t reach the internet right now.</p>
    <p>Try again when you’re back online, or go to the <a href="<?php echo $home; ?>">home page</a>.</p>
    <p class="hint">Tip: Keep caching on “Safe” if your site has logged-in community features.</p>
  </div>
</body>
</html>
