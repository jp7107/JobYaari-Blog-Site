<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — BlogHub</title>
    <meta name="description" content="Secure admin login for BlogHub management panel.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-login-page">

<div class="login-backdrop">
    <div class="login-glow login-glow--1"></div>
    <div class="login-glow login-glow--2"></div>
</div>

<main class="login-wrapper" id="main-content">
    <div class="login-card" role="main">

        <!-- Brand -->
        <div class="login-brand">
            <span class="logo-icon">✦</span>
            <span class="logo-text">BlogHub</span>
        </div>
        <h1 class="login-title">Admin Panel</h1>
        <p class="login-subtitle">Sign in to manage your blog content</p>

        <!-- Alert messages -->
        <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert" id="login-alert">
            <span class="alert-icon">⚠</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Login form -->
        <form method="POST" action="/admin/login" class="login-form" id="login-form" novalidate>
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        value="<?= htmlspecialchars($usernameVal ?? '') ?>"
                        placeholder="Enter your username"
                        autocomplete="username"
                        required
                        aria-required="true"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                        aria-required="true"
                    >
                    <button type="button" class="toggle-password" id="toggle-password" aria-label="Toggle password visibility">
                        <span class="eye-icon">👁</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-login" id="login-submit-btn">
                <span class="btn-text">Sign In</span>
                <span class="btn-spinner" hidden aria-hidden="true">⟳</span>
            </button>
        </form>

        <p class="login-footer-text">
            <a href="/" class="login-back-link">← Back to Blog</a>
        </p>
    </div>
</main>

<script>
// Toggle password visibility
document.getElementById('toggle-password').addEventListener('click', function() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
});

// Show spinner on submit
document.getElementById('login-form').addEventListener('submit', function() {
    const btn = document.getElementById('login-submit-btn');
    btn.querySelector('.btn-text').hidden = true;
    btn.querySelector('.btn-spinner').hidden = false;
    btn.disabled = true;
});
</script>
</body>
</html>
