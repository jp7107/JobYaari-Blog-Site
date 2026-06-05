<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts — BlogHub Admin</title>
    <meta name="description" content="Admin dashboard to manage blog posts on BlogHub.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-page">

<!-- ─── Admin Header ──────────────────────────────────────── -->
<header class="admin-header" id="admin-header">
    <div class="admin-nav">
        <a href="/admin/blogs" class="nav-logo" aria-label="BlogHub Admin">
            <span class="logo-icon">✦</span>
            <span class="logo-text">BlogHub <em>Admin</em></span>
        </a>
        <div class="admin-nav-actions">
            <span class="admin-user-badge">
                👤 <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
            </span>
            <a href="/admin/logout" class="btn btn-ghost btn-sm" id="logout-btn">Sign Out</a>
        </div>
    </div>
</header>

<!-- ─── Main Content ──────────────────────────────────────── -->
<main class="admin-main" id="main-content">
    <div class="admin-container">

        <!-- Page heading -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title">Blog Posts</h1>
                <p class="admin-page-subtitle">
                    <?= number_format($totalPosts) ?> post<?= $totalPosts !== 1 ? 's' : '' ?> total
                    &middot; Page <?= (int)$page ?> of <?= max(1, (int)$totalPages) ?>
                </p>
            </div>
            <a href="/admin/blogs/create" class="btn btn-primary" id="create-post-btn">
                <span>+</span> New Post
            </a>
        </div>

        <!-- Flash messages -->
        <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="alert" id="success-alert">
            <span class="alert-icon">✓</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert" id="error-alert">
            <span class="alert-icon">⚠</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <!-- Blog posts table -->
        <?php if (empty($posts)): ?>
        <div class="admin-empty-state">
            <div class="empty-icon">📝</div>
            <h2 class="empty-title">No posts yet</h2>
            <p class="empty-subtitle">Create your first blog post to get started.</p>
            <a href="/admin/blogs/create" class="btn btn-primary" id="empty-create-btn">Create First Post</a>
        </div>
        <?php else: ?>
        <div class="admin-table-wrapper">
            <table class="admin-table" id="posts-table" aria-label="Blog posts">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Title</th>
                        <th scope="col">Category</th>
                        <th scope="col">Created</th>
                        <th scope="col" class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $index => $post): ?>
                    <tr class="table-row" id="post-row-<?= (int)$post->id ?>">
                        <td class="td-num"><?= ($offset ?? 0) + $index + 1 ?></td>
                        <td class="td-title">
                            <div class="post-title-cell">
                                <?php if ($post->image_path): ?>
                                <img src="/<?= htmlspecialchars($post->image_path) ?>"
                                     alt="<?= htmlspecialchars($post->title) ?>"
                                     class="post-thumb"
                                     loading="lazy">
                                <?php else: ?>
                                <div class="post-thumb post-thumb--placeholder">✦</div>
                                <?php endif; ?>
                                <div>
                                    <span class="post-title-text"><?= htmlspecialchars($post->title) ?></span>
                                    <span class="post-desc-preview"><?= htmlspecialchars(mb_substr($post->short_description, 0, 80)) ?><?= mb_strlen($post->short_description) > 80 ? '…' : '' ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="td-category">
                            <?php if ($post->category): ?>
                            <span class="category-badge"><?= htmlspecialchars($post->category) ?></span>
                            <?php else: ?>
                            <span class="category-badge category-badge--none">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="td-date">
                            <time datetime="<?= htmlspecialchars($post->created_date ?? '') ?>">
                                <?= $post->created_date ? date('d M Y', strtotime($post->created_date)) : '—' ?>
                            </time>
                        </td>
                        <td class="td-actions">
                            <div class="action-buttons">
                                <a href="/blogs/<?= (int)$post->id ?>"
                                   class="btn btn-xs btn-ghost"
                                   target="_blank"
                                   title="View post"
                                   id="view-post-<?= (int)$post->id ?>">View</a>
                                <a href="/admin/blogs/edit/<?= (int)$post->id ?>"
                                   class="btn btn-xs btn-outline"
                                   title="Edit post"
                                   id="edit-post-<?= (int)$post->id ?>">Edit</a>
                                <form method="POST"
                                      action="/admin/blogs/delete/<?= (int)$post->id ?>"
                                      class="delete-form"
                                      id="delete-form-<?= (int)$post->id ?>"
                                      onsubmit="return confirmDelete(this, '<?= htmlspecialchars(addslashes($post->title)) ?>')">
                                    <button type="submit"
                                            class="btn btn-xs btn-danger"
                                            title="Delete post"
                                            id="delete-post-<?= (int)$post->id ?>">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Blog posts pagination">
            <?php if ($page > 1): ?>
            <a href="/admin/blogs?page=<?= $page - 1 ?>" class="pagination-btn" id="prev-page-btn" aria-label="Previous page">← Prev</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            for ($p = $start; $p <= $end; $p++):
            ?>
            <a href="/admin/blogs?page=<?= $p ?>"
               class="pagination-btn<?= $p === $page ? ' pagination-btn--active' : '' ?>"
               id="page-btn-<?= $p ?>"
               aria-current="<?= $p === $page ? 'page' : 'false' ?>"
               aria-label="Page <?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
            <a href="/admin/blogs?page=<?= $page + 1 ?>" class="pagination-btn" id="next-page-btn" aria-label="Next page">Next →</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<script>
function confirmDelete(form, title) {
    return confirm('Delete "' + title + '"?\n\nThis action cannot be undone.');
}

// Auto-dismiss alerts after 5 seconds
setTimeout(function () {
    ['success-alert','error-alert'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 400); }
    });
}, 5000);
</script>
</body>
</html>
