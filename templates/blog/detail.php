<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($blogPost->title) ?> — BlogHub</title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr($blogPost->short_description, 0, 160)) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="public-page detail-page">

<!-- ─── Navigation ──────────────────────────────────────── -->
<header class="site-header" id="site-header">
    <nav class="nav-container" aria-label="Primary navigation">
        <a href="/" class="nav-logo" aria-label="BlogHub home">
            <span class="logo-icon">✦</span>
            <span class="logo-text">BlogHub</span>
        </a>
        <ul class="nav-links" role="list">
            <li><a href="/" class="nav-link">Home</a></li>
            <li><a href="/blogs" class="nav-link">Blog</a></li>
        </ul>
        <div class="nav-actions">
            <a href="/admin/login" class="btn btn-ghost btn-sm" id="admin-login-link">Admin</a>
        </div>
    </nav>
</header>

<!-- ─── Detail Article ──────────────────────────────────── -->
<main id="main-content" tabindex="-1">

    <!-- Hero image / gradient banner -->
    <div class="detail-hero" aria-hidden="true">
        <?php if ($blogPost->image_path): ?>
        <img src="/<?= htmlspecialchars($blogPost->image_path) ?>"
             alt="<?= htmlspecialchars($blogPost->title) ?>"
             class="detail-hero-img">
        <div class="detail-hero-overlay"></div>
        <?php else: ?>
        <div class="detail-hero-gradient"></div>
        <?php endif; ?>
    </div>

    <div class="detail-container">

        <!-- Breadcrumb -->
        <nav class="detail-breadcrumb" aria-label="Breadcrumb">
            <a href="/blogs" class="breadcrumb-link" id="back-to-blog-link">← Back to Blog</a>
        </nav>

        <!-- Article -->
        <article class="detail-article" aria-labelledby="detail-title">

            <!-- Meta row -->
            <div class="detail-meta">
                <?php if ($blogPost->category): ?>
                <span class="detail-category" id="detail-category-badge"><?= htmlspecialchars($blogPost->category) ?></span>
                <?php endif; ?>
                <time class="detail-date"
                      datetime="<?= htmlspecialchars($blogPost->created_date ?? '') ?>"
                      id="detail-date">
                    <?= $blogPost->created_date ? date('d F Y', strtotime($blogPost->created_date)) : '' ?>
                </time>
            </div>

            <!-- Title -->
            <h1 class="detail-title" id="detail-title"><?= htmlspecialchars($blogPost->title) ?></h1>

            <!-- Short description / lead -->
            <p class="detail-lead" id="detail-lead"><?= htmlspecialchars($blogPost->short_description) ?></p>

            <hr class="detail-divider">

            <!-- Full content -->
            <div class="detail-content" id="detail-content">
                <?= nl2br(htmlspecialchars($blogPost->content)) ?>
            </div>

        </article>

        <!-- Navigation footer -->
        <div class="detail-nav-footer">
            <a href="/blogs" class="btn btn-outline" id="all-posts-btn">← All Posts</a>
        </div>

    </div>
</main>

<!-- ─── Footer ──────────────────────────────────────────── -->
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
</body>
</html>
