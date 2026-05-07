<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — Sentinel</title>
    <link rel="stylesheet" href="/public/css/sentinel.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="brand-icon-large"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <h1>Sentinel</h1>
                <p>Create your administrator account</p>
            </div>

            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $err): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="POST" action="/signup" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?? '' ?>">
                <div class="form-group">
                    <label class="form-label" for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" class="form-input" placeholder="John Doe" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="admin@example.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Min. 8 characters" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary auth-submit">Create Account</button>
            </form>
        </div>
    </div>
    <script src="/public/js/cursor.js"></script>
</body>
</html>
