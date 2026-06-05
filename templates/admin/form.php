<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Blog Post') ?> — BlogHub Admin</title>
    <meta name="description" content="Admin form to create or edit a blog post on BlogHub.">
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
    <div class="admin-container admin-container--form">

        <!-- Page heading -->
        <div class="admin-page-header">
            <div>
                <h1 class="admin-page-title"><?= htmlspecialchars($title ?? 'Blog Post') ?></h1>
                <p class="admin-page-subtitle">
                    <a href="/admin/blogs" class="breadcrumb-link" id="back-to-list-link">← Back to all posts</a>
                </p>
            </div>
        </div>

        <!-- Validation errors summary -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert" id="form-errors-alert">
            <span class="alert-icon">⚠</span>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul class="error-list">
                    <?php foreach ($errors as $field => $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Blog post form -->
        <form method="POST"
              action="<?= htmlspecialchars($action ?? '') ?>"
              enctype="multipart/form-data"
              class="admin-form"
              id="blog-post-form"
              novalidate>

            <div class="form-grid">
                <!-- LEFT COLUMN -->
                <div class="form-col">

                    <!-- Title -->
                    <div class="form-group <?= isset($errors['title']) ? 'form-group--error' : '' ?>">
                        <label for="title" class="form-label">
                            Title <span class="required-star" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="form-input"
                            value="<?= htmlspecialchars($postData['title'] ?? '') ?>"
                            placeholder="Enter a compelling blog title…"
                            maxlength="200"
                            required
                            aria-required="true"
                            aria-describedby="title-hint<?= isset($errors['title']) ? ' title-error' : '' ?>"
                        >
                        <span class="form-hint" id="title-hint">Max 200 characters</span>
                        <?php if (isset($errors['title'])): ?>
                        <span class="form-error-msg" id="title-error" role="alert"><?= htmlspecialchars($errors['title']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Short Description -->
                    <div class="form-group <?= isset($errors['short_description']) ? 'form-group--error' : '' ?>">
                        <label for="short_description" class="form-label">
                            Short Description <span class="required-star" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="short_description"
                            name="short_description"
                            class="form-input form-textarea form-textarea--sm"
                            placeholder="A brief summary shown on the listing page…"
                            maxlength="500"
                            required
                            aria-required="true"
                            aria-describedby="desc-hint<?= isset($errors['short_description']) ? ' desc-error' : '' ?>"
                            rows="3"
                        ><?= htmlspecialchars($postData['short_description'] ?? '') ?></textarea>
                        <span class="form-hint" id="desc-hint">Max 500 characters</span>
                        <?php if (isset($errors['short_description'])): ?>
                        <span class="form-error-msg" id="desc-error" role="alert"><?= htmlspecialchars($errors['short_description']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Category -->
                    <div class="form-group <?= isset($errors['category']) ? 'form-group--error' : '' ?>">
                        <label for="category" class="form-label">Category</label>
                        <input
                            type="text"
                            id="category"
                            name="category"
                            class="form-input"
                            value="<?= htmlspecialchars($postData['category'] ?? '') ?>"
                            placeholder="e.g. Technology, Travel, Results…"
                            maxlength="100"
                            aria-describedby="cat-hint<?= isset($errors['category']) ? ' cat-error' : '' ?>"
                        >
                        <span class="form-hint" id="cat-hint">Optional · Max 100 characters</span>
                        <?php if (isset($errors['category'])): ?>
                        <span class="form-error-msg" id="cat-error" role="alert"><?= htmlspecialchars($errors['category']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Featured Image -->
                    <div class="form-group <?= isset($errors['image']) ? 'form-group--error' : '' ?>">
                        <label for="image" class="form-label">Featured Image</label>

                        <?php if (!empty($postData['image_path'])): ?>
                        <div class="current-image-preview" id="current-image-wrapper">
                            <img src="/<?= htmlspecialchars($postData['image_path']) ?>"
                                 alt="Current featured image"
                                 class="image-preview-thumb"
                                 id="current-image-preview">
                            <span class="image-preview-label">Current image</span>
                        </div>
                        <?php endif; ?>

                        <label class="file-drop-zone" for="image" id="file-drop-zone">
                            <span class="file-drop-icon">🖼️</span>
                            <span class="file-drop-text">Click to upload or drag & drop</span>
                            <span class="file-drop-sub">JPG, JPEG, PNG or GIF · Max 5 MB</span>
                            <input
                                type="file"
                                id="image"
                                name="image"
                                class="file-input-hidden"
                                accept=".jpg,.jpeg,.png,.gif"
                                aria-describedby="img-hint<?= isset($errors['image']) ? ' img-error' : '' ?>"
                            >
                        </label>
                        <div class="new-image-preview" id="new-image-preview" hidden>
                            <img src="" alt="New image preview" id="new-image-preview-img" class="image-preview-thumb">
                            <button type="button" class="btn btn-xs btn-danger" id="remove-image-btn" aria-label="Remove selected image">✕ Remove</button>
                        </div>
                        <span class="form-hint" id="img-hint">Leave empty to keep existing image</span>
                        <?php if (isset($errors['image'])): ?>
                        <span class="form-error-msg" id="img-error" role="alert"><?= htmlspecialchars($errors['image']) ?></span>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- RIGHT COLUMN — Content -->
                <div class="form-col form-col--wide">
                    <div class="form-group form-group--fill <?= isset($errors['content']) ? 'form-group--error' : '' ?>">
                        <label for="content" class="form-label">
                            Content <span class="required-star" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="content"
                            name="content"
                            class="form-input form-textarea form-textarea--lg"
                            placeholder="Write your full blog post content here…"
                            maxlength="50000"
                            required
                            aria-required="true"
                            aria-describedby="content-hint<?= isset($errors['content']) ? ' content-error' : '' ?>"
                            rows="20"
                        ><?= htmlspecialchars($postData['content'] ?? '') ?></textarea>
                        <span class="form-hint" id="content-hint">Max 50,000 characters · <span id="content-char-count">0</span> used</span>
                        <?php if (isset($errors['content'])): ?>
                        <span class="form-error-msg" id="content-error" role="alert"><?= htmlspecialchars($errors['content']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Form action buttons -->
            <div class="form-actions">
                <a href="/admin/blogs" class="btn btn-outline" id="cancel-btn">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submit-post-btn">
                    <span class="btn-text"><?= strpos($title ?? '', 'Edit') !== false ? 'Update Post' : 'Publish Post' ?></span>
                    <span class="btn-spinner" hidden aria-hidden="true">⟳</span>
                </button>
            </div>

        </form>

    </div>
</main>

<script>
// ── Character counter for content ────────────────────────────
(function () {
    var content = document.getElementById('content');
    var counter = document.getElementById('content-char-count');
    if (content && counter) {
        function update() { counter.textContent = content.value.length.toLocaleString(); }
        content.addEventListener('input', update);
        update();
    }
})();

// ── Image preview ────────────────────────────────────────────
(function () {
    var input     = document.getElementById('image');
    var preview   = document.getElementById('new-image-preview');
    var previewImg = document.getElementById('new-image-preview-img');
    var removeBtn = document.getElementById('remove-image-btn');

    if (!input) return;

    input.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                preview.hidden = false;
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            input.value = '';
            preview.hidden = true;
            previewImg.src = '';
        });
    }
})();

// ── Loading spinner on submit ────────────────────────────────
document.getElementById('blog-post-form').addEventListener('submit', function () {
    var btn = document.getElementById('submit-post-btn');
    btn.querySelector('.btn-text').hidden = true;
    btn.querySelector('.btn-spinner').hidden = false;
    btn.disabled = true;
});

// ── Drag and drop on file zone ───────────────────────────────
(function () {
    var zone = document.getElementById('file-drop-zone');
    if (!zone) return;
    zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('file-drop-zone--over'); });
    zone.addEventListener('dragleave', function () { zone.classList.remove('file-drop-zone--over'); });
    zone.addEventListener('drop', function (e) {
        e.preventDefault();
        zone.classList.remove('file-drop-zone--over');
        var files = e.dataTransfer.files;
        if (files.length) {
            document.getElementById('image').files = files;
            document.getElementById('image').dispatchEvent(new Event('change'));
        }
    });
})();
</script>
</body>
</html>
