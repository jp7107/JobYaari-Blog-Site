<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog — BlogHub</title>
    <meta name="description" content="Discover the latest articles, stories, and insights on BlogHub. Filter by category, date, or search for specific topics.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="public-page">

<!-- ─── Navigation ───────────────────────────────────────── -->
<header class="site-header" id="site-header">
    <nav class="nav-container" aria-label="Primary navigation">
        <a href="/" class="nav-logo" aria-label="BlogHub home">
            <span class="logo-icon">✦</span>
            <span class="logo-text">BlogHub</span>
        </a>
        <ul class="nav-links" role="list">
            <li><a href="/" class="nav-link">Home</a></li>
            <li><a href="/blogs" class="nav-link active" aria-current="page">Blog</a></li>
        </ul>
        <div class="nav-actions">
            <a href="/admin/login" class="btn btn-ghost btn-sm" id="admin-login-link">Admin</a>
        </div>
    </nav>
</header>

<!-- ─── Hero ─────────────────────────────────────────────── -->
<section class="listing-hero" aria-labelledby="listing-hero-title">
    <div class="listing-hero-content">
        <h1 class="listing-hero-title" id="listing-hero-title">Explore Stories</h1>
        <p class="listing-hero-sub">Insights, updates, and ideas — curated just for you.</p>
    </div>
    <div class="listing-hero-glow" aria-hidden="true"></div>
</section>

<!-- ─── Filters ──────────────────────────────────────────── -->
<section class="filters-section" aria-label="Blog filters" id="filters-section">
    <div class="filters-container">

        <!-- Search -->
        <div class="filter-search-wrapper">
            <label for="search-input" class="sr-only">Search posts</label>
            <span class="search-icon" aria-hidden="true">🔍</span>
            <input
                type="search"
                id="search-input"
                class="filter-search-input"
                placeholder="Search articles…"
                aria-label="Search blog posts"
                autocomplete="off"
            >
            <span class="search-clear" id="search-clear" role="button" tabindex="0" aria-label="Clear search" hidden>✕</span>
        </div>

        <!-- Category filter -->
        <div class="filter-group" role="group" aria-label="Filter by category">
            <button class="filter-chip filter-chip--active"
                    data-filter="category"
                    data-value=""
                    id="cat-all-btn"
                    aria-pressed="true">All</button>
            <?php foreach ($categories as $cat): ?>
            <button class="filter-chip"
                    data-filter="category"
                    data-value="<?= htmlspecialchars($cat) ?>"
                    id="cat-<?= htmlspecialchars(strtolower(preg_replace('/[^a-z0-9]/i', '-', $cat))) ?>-btn"
                    aria-pressed="false"><?= htmlspecialchars($cat) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Date filter -->
        <div class="filter-group filter-group--date" role="group" aria-label="Filter by date">
            <select id="date-filter-select" class="filter-select" aria-label="Date range filter">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>

        <!-- Custom date range (shown only when "Custom" selected) -->
        <div class="custom-date-range" id="custom-date-range" hidden aria-label="Custom date range">
            <label for="date-start" class="sr-only">Start date</label>
            <input type="date" id="date-start" class="filter-date-input" aria-label="Start date">
            <span class="date-separator" aria-hidden="true">→</span>
            <label for="date-end" class="sr-only">End date</label>
            <input type="date" id="date-end" class="filter-date-input" aria-label="End date">
            <button class="btn btn-sm btn-primary" id="apply-date-btn">Apply</button>
        </div>

        <!-- Date validation error -->
        <div class="filter-date-error" id="date-error" role="alert" aria-live="polite" hidden></div>

    </div>
</section>

<!-- ─── Blog Grid ─────────────────────────────────────────── -->
<section class="listing-section" aria-label="Blog posts" id="listing-section">
    <div class="listing-container">

        <!-- Results summary -->
        <div class="results-bar" id="results-bar" aria-live="polite" aria-atomic="true">
            <span id="results-count"><?= count($posts) ?></span> article<?= count($posts) !== 1 ? 's' : '' ?> found
        </div>

        <!-- Loading overlay -->
        <div class="listing-loading" id="listing-loading" aria-label="Loading posts" hidden>
            <div class="spinner" aria-hidden="true"></div>
            <span>Finding posts…</span>
        </div>

        <!-- Error display -->
        <div class="listing-error" id="listing-error" role="alert" aria-live="assertive" hidden>
            <span class="alert-icon">⚠</span>
            <span id="listing-error-msg">Could not load posts. Please try again.</span>
            <button class="btn btn-sm btn-outline" id="listing-retry-btn">Retry</button>
        </div>

        <!-- Posts grid -->
        <div class="posts-grid" id="posts-grid" aria-label="Blog post cards">
            <?php if (empty($posts)): ?>
            <div class="posts-empty" id="posts-empty">
                <div class="empty-icon">📭</div>
                <h2 class="empty-title">No posts yet</h2>
                <p class="empty-subtitle">Check back soon for new articles!</p>
            </div>
            <?php else: ?>
            <?php foreach ($posts as $post): ?>
            <article class="post-card" id="post-card-<?= (int)$post->id ?>" aria-labelledby="post-title-<?= (int)$post->id ?>">
                <a href="/blogs/<?= (int)$post->id ?>" class="post-card-link" tabindex="-1" aria-hidden="true">
                    <div class="post-card-image">
                        <?php if ($post->image_path): ?>
                        <img src="/<?= htmlspecialchars($post->image_path) ?>"
                             alt="<?= htmlspecialchars($post->title) ?>"
                             class="post-card-img"
                             loading="lazy">
                        <?php else: ?>
                        <div class="post-card-img-placeholder" aria-hidden="true">✦</div>
                        <?php endif; ?>
                        <?php if ($post->category): ?>
                        <span class="post-card-category"><?= htmlspecialchars($post->category) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="post-card-body">
                    <time class="post-card-date" datetime="<?= htmlspecialchars($post->created_date ?? '') ?>">
                        <?= $post->created_date ? date('d M Y', strtotime($post->created_date)) : '' ?>
                    </time>
                    <h2 class="post-card-title" id="post-title-<?= (int)$post->id ?>">
                        <a href="/blogs/<?= (int)$post->id ?>" class="post-card-title-link">
                            <?= htmlspecialchars($post->title) ?>
                        </a>
                    </h2>
                    <p class="post-card-excerpt">
                        <?= htmlspecialchars(mb_substr($post->short_description, 0, 150)) ?><?= mb_strlen($post->short_description) > 150 ? '…' : '' ?>
                    </p>
                    <a href="/blogs/<?= (int)$post->id ?>" class="post-card-read-more" aria-label="Read more about <?= htmlspecialchars($post->title) ?>">
                        Read more <span aria-hidden="true">→</span>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- ─── Footer ───────────────────────────────────────────── -->
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

<!-- Pass PHP categories to JS -->
<script>
window.BLOG_CATEGORIES = <?= json_encode($categories) ?>;
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
        crossorigin="anonymous"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
