<?php
/**
 * SQLite migration + seed script.
 * Run: php schema/migrate_sqlite.php
 * Creates the blog_local.sqlite database with all tables and the default admin user.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$dbName   = $_ENV['DB_NAME'] ?? 'blog_local';
$dbPath   = dirname(__DIR__) . '/' . $dbName . '.sqlite';

echo "Creating SQLite database at: $dbPath\n";

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("PRAGMA foreign_keys = ON;");
$pdo->exec("PRAGMA journal_mode = WAL;");

// ── Drop existing tables ──────────────────────────────────────
$pdo->exec("DROP TABLE IF EXISTS login_attempts;");
$pdo->exec("DROP TABLE IF EXISTS admin_users;");
$pdo->exec("DROP TABLE IF EXISTS blog_posts;");
echo "Dropped existing tables.\n";

// ── blog_posts ────────────────────────────────────────────────
$pdo->exec("
CREATE TABLE blog_posts (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    title            VARCHAR(200) NOT NULL,
    content          TEXT NOT NULL,
    short_description VARCHAR(500) NOT NULL,
    category         VARCHAR(100) DEFAULT NULL,
    image_path       VARCHAR(500) DEFAULT NULL,
    created_date     DATETIME NOT NULL
);
");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_created_date ON blog_posts (created_date);");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_category    ON blog_posts (category);");
echo "Created blog_posts table.\n";

// ── admin_users ───────────────────────────────────────────────
$pdo->exec("
CREATE TABLE admin_users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME NOT NULL
);
");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_username ON admin_users (username);");
echo "Created admin_users table.\n";

// ── login_attempts ────────────────────────────────────────────
$pdo->exec("
CREATE TABLE login_attempts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    username     VARCHAR(100) NOT NULL,
    attempt_time DATETIME NOT NULL,
    ip_address   VARCHAR(45) NOT NULL
);
");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_username_time ON login_attempts (username, attempt_time);");
echo "Created login_attempts table.\n";

// ── Seed admin user: admin / Password@123 ────────────────────
$hash = password_hash('Password@123', PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $pdo->prepare(
    "INSERT INTO admin_users (username, password_hash, created_at) VALUES (:u, :h, datetime('now'))"
);
$stmt->execute([':u' => 'admin', ':h' => $hash]);
echo "Seeded admin user: admin / Password@123\n";

// ── Seed sample blog posts ────────────────────────────────────
$samples = [
    [
        'title'             => 'Welcome to BlogHub',
        'content'           => "Welcome to BlogHub — your new home for insightful articles and stories.\n\nWe're thrilled to have you here. This platform brings together writers, thinkers, and curious minds to share their perspectives on the topics that matter most.\n\nExplore our growing library of posts, filter by category or date, and dive deep into the content that interests you. Whether you are here for technology, travel, career advice, or just a good read, we've got you covered.\n\nHappy reading!",
        'short_description' => 'Discover what BlogHub is all about — a platform for insightful articles, stories, and ideas from across the globe.',
        'category'          => 'General',
        'created_date'      => date('Y-m-d H:i:s', strtotime('-2 days')),
    ],
    [
        'title'             => 'Top 5 PHP Best Practices in 2024',
        'content'           => "PHP remains one of the most widely used server-side languages in the world, powering millions of websites and web applications.\n\nHere are the top 5 best practices every PHP developer should follow in 2024:\n\n1. **Use Typed Properties** — PHP 8.x strict types reduce bugs significantly.\n2. **Adopt PSR Standards** — PSR-4 autoloading and PSR-12 coding style keep projects consistent.\n3. **Embrace Composer** — Manage dependencies with Composer instead of manual includes.\n4. **Write Tests** — PHPUnit for unit tests and property-based testing with Eris for robust coverage.\n5. **Use Prepared Statements** — Always use PDO prepared statements to prevent SQL injection.\n\nFollowing these practices will make your codebase more maintainable, secure, and scalable.",
        'short_description' => 'A deep dive into the top PHP best practices every developer should be following in modern web development.',
        'category'          => 'Technology',
        'created_date'      => date('Y-m-d H:i:s', strtotime('-5 days')),
    ],
    [
        'title'             => 'How to Build a Career in Web Development',
        'content'           => "The web development industry is growing rapidly and there has never been a better time to start your career in this field.\n\nHere is a roadmap to get you started:\n\n**1. Learn the Fundamentals**\nStart with HTML, CSS, and JavaScript. These are the building blocks of every website.\n\n**2. Pick a Back-End Language**\nPHP, Python, Node.js, or Ruby are all excellent choices. PHP is particularly popular for CMS-based projects.\n\n**3. Understand Databases**\nLearn SQL fundamentals and how to work with MySQL or PostgreSQL.\n\n**4. Build Real Projects**\nNothing beats hands-on experience. Build a portfolio of 3-5 real projects that demonstrate your skills.\n\n**5. Apply and Network**\nJoin developer communities, attend meetups, and start applying to junior positions.\n\nRemember: every expert was once a beginner. Start today!",
        'short_description' => 'A comprehensive career roadmap for aspiring web developers — from fundamentals to landing your first job.',
        'category'          => 'Jobs',
        'created_date'      => date('Y-m-d H:i:s', strtotime('-10 days')),
    ],
    [
        'title'             => 'Understanding SQL Injection and How to Prevent It',
        'content'           => "SQL injection remains one of the most common and dangerous web security vulnerabilities. Understanding how it works is the first step to preventing it.\n\n**What is SQL Injection?**\nSQL injection occurs when an attacker inserts malicious SQL code into an input field, manipulating the database query.\n\n**Example of Vulnerable Code:**\n\$query = \"SELECT * FROM users WHERE username = '\" . \$_POST['username'] . \"'\";\n\nIf a user submits: admin' OR '1'='1\nThe query becomes: SELECT * FROM users WHERE username = 'admin' OR '1'='1'\n\nThis returns all users, bypassing authentication entirely.\n\n**Prevention with Prepared Statements:**\n\$stmt = \$pdo->prepare('SELECT * FROM users WHERE username = :username');\n\$stmt->execute([':username' => \$_POST['username']]);\n\nAlways use prepared statements with parameter binding. Never concatenate user input directly into SQL queries.",
        'short_description' => 'Learn what SQL injection attacks are, how they work, and how prepared statements keep your database completely safe.',
        'category'          => 'Technology',
        'created_date'      => date('Y-m-d H:i:s', strtotime('-15 days')),
    ],
    [
        'title'             => '2024 Job Market: Results and Outlook',
        'content'           => "The 2024 global job market has shown remarkable resilience despite economic headwinds. Here is a summary of key results and what to expect in the coming year.\n\n**Key Results:**\n- Technology sector hiring grew by 12% YoY, with AI/ML roles seeing 40% growth.\n- Remote work positions now account for 28% of all job postings globally.\n- Web development roles remain in high demand, with PHP, JavaScript, and Python topping the charts.\n- Average salary for mid-level developers increased by 8% compared to 2023.\n\n**Outlook for 2025:**\n- Continued growth in cloud infrastructure roles\n- Increased demand for full-stack developers\n- AI tooling skills becoming a differentiator in hiring decisions\n\nThe market remains favorable for skilled developers who invest in continuous learning.",
        'short_description' => 'A comprehensive review of the 2024 job market results with outlook data for technology and web development roles.',
        'category'          => 'Result',
        'created_date'      => date('Y-m-d H:i:s', strtotime('-3 days')),
    ],
];

$stmt = $pdo->prepare("
    INSERT INTO blog_posts (title, content, short_description, category, created_date)
    VALUES (:title, :content, :short_description, :category, :created_date)
");

foreach ($samples as $post) {
    $stmt->execute([
        ':title'             => $post['title'],
        ':content'           => $post['content'],
        ':short_description' => $post['short_description'],
        ':category'          => $post['category'],
        ':created_date'      => $post['created_date'],
    ]);
    echo "Inserted: {$post['title']}\n";
}

echo "\n✅ SQLite migration complete!\n";
echo "   Database: $dbPath\n";
echo "   Admin login: admin / Password@123\n";
echo "   Posts seeded: " . count($samples) . "\n\n";
echo "Start the server with:\n";
echo "   php -S localhost:8080 -t public/\n";
echo "Then open: http://localhost:8080/blogs\n";
