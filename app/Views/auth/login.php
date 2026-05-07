<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — Sentinel</title>
    <link rel="stylesheet" href="/public/css/sentinel.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="brand-icon-large"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <h1>Sentinel</h1>
                <p>Security monitoring dashboard</p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e;padding:12px;border-radius:8px;margin-bottom:16px;font-size:0.85rem"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="/login" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="admin@example.com" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Sign In</button>
            </form>

            <div style="text-align:center;margin-top:16px">
                <a href="/forgot-password" style="color:var(--accent-cyan);font-size:0.8rem;text-decoration:none">Forgot password?</a>
            </div>
        </div>
    </div>
    <script src="/public/js/cursor.js"></script>
</body>
</html>
