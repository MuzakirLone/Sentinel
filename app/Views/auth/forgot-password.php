<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Sentinel</title>
    <link rel="stylesheet" href="/public/css/sentinel.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="brand-icon-large"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
                <h1>Reset Password</h1>
                <p>Enter your email, the master key, and a new password</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ((array) $errors as $err): ?>
                        <div><?= htmlspecialchars($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/forgot-password" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="form-group">
                    <label class="form-label" for="email">Account Email</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="admin@example.com" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="master_key">Master Key (APP_SECRET)</label>
                    <input type="password" id="master_key" name="master_key" class="form-input" placeholder="Your APP_SECRET from docker-compose.yml" required>
                    <div style="font-size:0.7rem;color:var(--text-muted);margin-top:4px">
                        Find this in your <code>docker-compose.yml</code> → <code>APP_SECRET</code> environment variable.
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" placeholder="••••••••" minlength="8" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="••••••••" minlength="8" required>
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Reset Password</button>
            </form>

            <div style="text-align:center;margin-top:16px">
                <a href="/login" style="color:var(--accent-cyan);font-size:0.8rem;text-decoration:none">← Back to Sign In</a>
            </div>
        </div>
    </div>
    <script src="/public/js/cursor.js"></script>
</body>
</html>
