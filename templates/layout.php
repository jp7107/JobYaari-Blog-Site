<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'BlogHub') ?> — BlogHub</title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'Discover insightful articles, news, and stories on BlogHub.') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= htmlspecialchars($bodyClass ?? '') ?>">

<!-- ─── Navigation ─────────────────────────────────────────── -->
<header class="site-header" id="site-header">
    <nav class="nav-container" aria-label="Primary navigation">
        <a href="/" class="nav-logo" aria-label="BlogHub home">
            <span class="logo-icon">✦</span>
            <span class="logo-text">BlogHub</span>
        </a>
        <ul class="nav-links" role="list">
            <li><a href="/" class="nav-link<?= (($activeNav ?? '') === 'home') ? ' active' : '' ?>">Home</a></li>
            <li><a href="/blogs" class="nav-link<?= (($activeNav ?? '') === 'blogs') ? ' active' : '' ?>">Blog</a></li>
        </ul>
        <div class="nav-actions">
            <a href="/admin/login" class="btn btn-ghost btn-sm" id="admin-login-link">Admin</a>
        </div>
        <button class="nav-hamburger" id="nav-hamburger" aria-label="Toggle menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </nav>
</header>

<!-- ─── Page Content ───────────────────────────────────────── -->
<main id="main-content" tabindex="-1">
    <?php if (isset($content)) echo $content; ?>
</main>

<!-- ─── Footer ─────────────────────────────────────────────── -->
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-brand">
            <span class="logo-icon">✦</span>
            <span class="logo-text">BlogHub</span>
            <p class="footer-tagline">Crafting stories that matter.</p>
        </div>
        <div class="footer-links">
            <a href="/" class="footer-link">Home</a>
            <a href="/blogs" class="footer-link">Blog</a>
        </div>
        <p class="footer-copy">&copy; <?= date('Y') ?> BlogHub. All rights reserved.</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>
<script src="/assets/js/app.js"></script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
