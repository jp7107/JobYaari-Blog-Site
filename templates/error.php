<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?= (int)($errorCode ?? 500) ?> — BlogHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="error-page">
<div class="error-wrapper">
    <div class="error-card">
        <div class="error-code"><?= (int)($errorCode ?? 500) ?></div>
        <div class="error-divider"></div>
        <h1 class="error-title">
            <?php
            $codes = [404 => 'Page Not Found', 403 => 'Access Denied', 500 => 'Server Error'];
            echo htmlspecialchars($codes[$errorCode ?? 500] ?? 'Something Went Wrong');
            ?>
        </h1>
        <p class="error-message"><?= htmlspecialchars($errorMessage ?? 'An unexpected error occurred. Please try again later.') ?></p>
        <div class="error-actions">
            <a href="/" class="btn btn-primary" id="error-home-btn">Go Home</a>
            <button onclick="history.back()" class="btn btn-outline" id="error-back-btn">Go Back</button>
        </div>
    </div>
    <div class="error-glow"></div>
</div>
</body>
</html>
