<?php
// Global configuration and core helper functions for the PHP version

// Always start the session for auth
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paths
define('DATA_DIR', __DIR__ . '/data');
define('UPLOADS_DIR', __DIR__ . '/uploads');

// Ensure data and uploads directories exist
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0777, true);
}
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0777, true);
}

// Admin credentials (demo)
const ADMIN_USERNAME = 'admin';
const ADMIN_PASSWORD = 'admin123';

// ---------- Database configuration (MySQL) ----------
// Adjust these constants to match your local database setup.
// Default values are suitable for a typical XAMPP installation.

const DB_HOST = '127.0.0.1';
const DB_NAME = 'wapproject';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}

// ---------- Generic JSON storage helpers ----------

function load_json(string $filename, $default = []) {
    $path = DATA_DIR . '/' . $filename;
    if (!file_exists($path)) {
        return $default;
    }
    $contents = file_get_contents($path);
    if ($contents === false || $contents === '') {
        return $default;
    }
    $data = json_decode($contents, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        return $default;
    }
    return $data;
}

function save_json(string $filename, $data): void {
    $path = DATA_DIR . '/' . $filename;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ---------- User storage helpers ----------

function load_users(): array {
    $stmt = db()->query('SELECT id, username, password_hash, bio, is_admin, created_at FROM users ORDER BY id ASC');
    return $stmt->fetchAll();
}

function save_users(array $users): void {
    // Not used with SQL-backed storage; kept for compatibility.
}

function find_user_by_username(string $username): ?array {
    $username = trim($username);
    if ($username === '') {
        return null;
    }

    $stmt = db()->prepare('SELECT id, username, password_hash, bio, is_admin, created_at FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function get_user_bio(string $username): string {
    $user = find_user_by_username($username);

    if (!$user) {
        return '';
    }

    return isset($user['bio']) ? (string)$user['bio'] : '';
}

function update_user_bio(string $username, string $bio): void {
    $username = trim($username);
    $bio      = trim($bio);

    if ($username === '') {
        return;
    }

    // Admin account is virtual; do not try to store bio for admin here.
    if (strcasecmp($username, ADMIN_USERNAME) === 0) {
        return;
    }

    $stmt = db()->prepare('UPDATE users SET bio = ? WHERE username = ?');
    $stmt->execute([$bio, $username]);
}

/**
 * Register a new user.
 * Returns ['success' => bool, 'message' => string|null]
 */
function register_user(string $username, string $password, string $password_confirm): array {
    $username         = trim($username);
    $password         = trim($password);
    $password_confirm = trim($password_confirm);

    if ($username === '' || $password === '' || $password_confirm === '') {
        return ['success' => false, 'message' => 'Please fill in all fields.'];
    }

    if (strlen($username) < 3) {
        return ['success' => false, 'message' => 'Username must be at least 3 characters long.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    if ($password !== $password_confirm) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }

    // Disallow using the admin username here (admin is special)
    if (strcasecmp($username, ADMIN_USERNAME) === 0) {
        return ['success' => false, 'message' => 'This username is reserved. Please choose another.'];
    }

    // Check if username already exists
    if (find_user_by_username($username) !== null) {
        return ['success' => false, 'message' => 'Username is already taken.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = db()->prepare('INSERT INTO users (username, password_hash, created_at, is_admin) VALUES (?, ?, NOW(), 0)');
    $stmt->execute([$username, $hash]);

    return ['success' => true, 'message' => null];
}

/**
 * Verify standard user credentials (non-admin).
 * Returns ['success' => bool, 'user' => array|null, 'message' => string|null]
 */
function verify_user_credentials(string $username, string $password): array {
    $username = trim($username);
    $password = trim($password);

    if ($username === '' || $password === '') {
        return ['success' => false, 'user' => null, 'message' => 'Please enter username and password'];
    }

    $user = find_user_by_username($username);
    if (!$user) {
        return ['success' => false, 'user' => null, 'message' => 'User not found or password incorrect'];
    }

    if (!isset($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'user' => null, 'message' => 'User not found or password incorrect'];
    }

    return ['success' => true, 'user' => $user, 'message' => null];
}

// ---------- Auth helpers ----------

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function is_admin(): bool {
    $user = current_user();
    return $user && !empty($user['is_admin']);
}

function login_user(string $username, bool $asAdmin = false): void {
    $_SESSION['user'] = [
        'username'  => $username,
        'is_admin'  => $asAdmin,
        'loginTime' => date('c'),
    ];
}

function logout_user(): void {
    unset($_SESSION['user']);
}

/**
 * Handle login attempt. Returns array: ['success' => bool, 'is_admin' => bool, 'message' => string|null]
 */
function handle_login_attempt(string $username, string $password): array {
    $username = trim($username);
    $password = trim($password);

    if ($username === '' || $password === '') {
        return ['success' => false, 'is_admin' => false, 'message' => 'Please enter username and password'];
    }

    // Admin user (fixed credentials)
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        login_user($username, true);
        return ['success' => true, 'is_admin' => true, 'message' => null];
    }

    // Regular registered users
    $result = verify_user_credentials($username, $password);
    if ($result['success'] && $result['user']) {
        // Log in as regular (non-admin) user
        login_user($result['user']['username'], false);
        return ['success' => true, 'is_admin' => false, 'message' => null];
    }

    return [
        'success'  => false,
        'is_admin' => false,
        'message'  => $result['message'] ?? 'Login failed',
    ];
}

/**
 * Require a logged-in user; if not logged in, redirect to login.php with ?redirect=...
 */
function require_login(): void {
    if (!is_logged_in()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
        header("Location: login.php?redirect={$redirect}");
        exit;
    }
}

// ---------- Articles helpers (Learning Corner) ----------

function default_articles(): array {
    // Default articles copied from the previous JS version
    return [
        [
            'id' => 'understanding-pollution',
            'title' => '📚 Understanding Pollution',
            'description' => 'Learn about different types of pollution and their impact on our environment. Knowledge is the first step toward making a positive change.',
            'content' => '<h2>Understanding Pollution</h2>
<p>Pollution is the introduction of harmful materials into the environment. These harmful materials are called pollutants. Pollutants can be natural, such as volcanic ash, or created by human activity, such as trash or runoff produced by factories.</p>

<h3>Types of Pollution</h3>
<p>There are several types of pollution that affect our planet:</p>
<ul>
    <li><strong>Air Pollution:</strong> Caused by harmful gases and particles released into the atmosphere from industrial activities, vehicles, and burning of fossil fuels.</li>
    <li><strong>Water Pollution:</strong> Occurs when harmful substances contaminate water bodies, making them unsafe for aquatic life and human consumption.</li>
    <li><strong>Land Pollution:</strong> Results from improper waste disposal, deforestation, and industrial activities that degrade soil quality.</li>
    <li><strong>Noise Pollution:</strong> Excessive noise from traffic, construction, and industrial activities that disrupts natural habitats and human health.</li>
</ul>

<h3>Impact on Environment</h3>
<p>Pollution has severe consequences for our environment, including climate change, loss of biodiversity, and degradation of natural resources. Understanding these impacts is crucial for developing effective solutions.</p>',
        ],
        [
            'id' => 'environmental-solutions',
            'title' => '🌿 Environmental Solutions',
            'description' => 'Discover practical solutions and sustainable practices that can help reduce pollution in your community and beyond.',
            'content' => '<h2>Environmental Solutions</h2>
<p>Addressing pollution requires collective action and sustainable practices. Here are some effective solutions:</p>

<h3>Renewable Energy</h3>
<p>Transitioning to renewable energy sources like solar, wind, and hydroelectric power can significantly reduce air pollution and greenhouse gas emissions.</p>

<h3>Waste Reduction</h3>
<p>Implementing the 3Rs (Reduce, Reuse, Recycle) helps minimize waste production and prevents pollutants from entering our environment.</p>

<h3>Sustainable Transportation</h3>
<p>Using public transportation, cycling, walking, or electric vehicles reduces emissions and helps improve air quality in urban areas.</p>

<h3>Conservation Efforts</h3>
<p>Protecting natural habitats, planting trees, and supporting conservation programs help maintain ecological balance and reduce environmental degradation.</p>',
        ],
        [
            'id' => 'pollution-statistics',
            'title' => '📊 Pollution Statistics',
            'description' => 'Stay informed with the latest data and statistics about pollution levels worldwide and their trends over time.',
            'content' => '<h2>Pollution Statistics</h2>
<p>Understanding pollution statistics helps us grasp the scale of environmental challenges we face:</p>

<h3>Global Air Quality</h3>
<p>According to recent studies, over 90% of the world\'s population lives in areas where air quality exceeds WHO guidelines. Air pollution causes millions of premature deaths annually.</p>

<h3>Water Contamination</h3>
<p>Approximately 2.2 billion people lack access to safely managed drinking water services, and water pollution affects marine ecosystems worldwide.</p>

<h3>Plastic Waste</h3>
<p>Over 8 million tons of plastic enter our oceans each year, causing severe harm to marine life and ecosystems. Only 9% of all plastic waste has been recycled.</p>

<h3>Trends and Progress</h3>
<p>While pollution levels remain high, many countries are making progress through environmental regulations, technological innovations, and public awareness campaigns.</p>',
        ],
        [
            'id' => 'community-action',
            'title' => '🤝 Community Action',
            'description' => 'Find out how you can get involved in local and global initiatives to combat pollution and protect our planet.',
            'content' => '<h2>Community Action</h2>
<p>Individual and community actions play a crucial role in combating pollution. Here\'s how you can make a difference:</p>

<h3>Local Initiatives</h3>
<p>Join or organize local clean-up events, tree planting activities, and environmental awareness campaigns in your community.</p>

<h3>Advocacy and Education</h3>
<p>Raise awareness about pollution issues through social media, community workshops, and educational programs. Knowledge sharing is powerful.</p>

<h3>Sustainable Lifestyle Choices</h3>
<p>Make conscious choices in daily life: reduce single-use plastics, conserve energy, use eco-friendly products, and support sustainable businesses.</p>

<h3>Support Environmental Organizations</h3>
<p>Volunteer with or donate to organizations working on environmental protection, conservation, and pollution reduction initiatives.</p>

<h3>Policy Engagement</h3>
<p>Engage with local and national policymakers to support environmental regulations and sustainable development policies.</p>',
        ],
    ];
}

function load_articles(): array {
    $articles = load_json('articles.json', null);
    if ($articles === null) {
        $articles = default_articles();
        save_json('articles.json', $articles);
    }
    return $articles;
}

function find_article(string $id): ?array {
    foreach (load_articles() as $article) {
        if ($article['id'] === $id) {
            return $article;
        }
    }
    return null;
}

function save_articles(array $articles): void {
    save_json('articles.json', $articles);
}

// ---------- Forum posts helpers ----------

function load_posts(): array {
    $pdo = db();

    $stmt = $pdo->query(
        'SELECT p.id, p.title, p.category, p.content, p.images, p.created_at, u.username AS author
         FROM posts p
         JOIN users u ON p.user_id = u.id
         ORDER BY p.created_at DESC'
    );

    $posts = [];

    while ($row = $stmt->fetch()) {
        $images = [];
        if (!empty($row['images'])) {
            $decoded = json_decode($row['images'], true);
            if (is_array($decoded)) {
                $images = $decoded;
            }
        }

        $postId = (int)$row['id'];

        $posts[] = [
            'id'       => $postId,
            'title'    => $row['title'],
            'author'   => $row['author'],
            'category' => $row['category'],
            'content'  => $row['content'],
            'images'   => $images,
            'date'     => $row['created_at'],
            'comments' => load_comments_for_post($postId),
        ];
    }

    return $posts;
}

function save_posts(array $posts): void {
    // No-op with SQL-backed storage. Left for compatibility.
}

function find_post(int $id): ?array {
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT p.id, p.title, p.category, p.content, p.images, p.created_at, u.username AS author
         FROM posts p
         JOIN users u ON p.user_id = u.id
         WHERE p.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $images = [];
    if (!empty($row['images'])) {
        $decoded = json_decode($row['images'], true);
        if (is_array($decoded)) {
            $images = $decoded;
        }
    }

    $postId = (int)$row['id'];

    return [
        'id'       => $postId,
        'title'    => $row['title'],
        'author'   => $row['author'],
        'category' => $row['category'],
        'content'  => $row['content'],
        'images'   => $images,
        'date'     => $row['created_at'],
        'comments' => load_comments_for_post($postId),
    ];
}

function load_comments_for_post(int $postId): array {
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT c.id, c.content, c.created_at, u.username AS author
         FROM comments c
         JOIN users u ON c.user_id = u.id
         WHERE c.post_id = ?
         ORDER BY c.created_at ASC'
    );
    $stmt->execute([$postId]);

    $comments = [];
    while ($row = $stmt->fetch()) {
        $comments[] = [
            'id'      => (int)$row['id'],
            'author'  => $row['author'],
            'content' => $row['content'],
            'date'    => $row['created_at'],
        ];
    }

    return $comments;
}

function create_post(string $title, string $category, string $content, array $images, string $authorUsername): int {
    $pdo = db();

    $user = find_user_by_username($authorUsername);
    if (!$user) {
        throw new RuntimeException('Author user not found.');
    }

    $imagesJson = json_encode(array_values($images), JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare(
        'INSERT INTO posts (user_id, title, category, content, images, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([(int)$user['id'], $title, $category, $content, $imagesJson]);

    return (int)$pdo->lastInsertId();
}

function add_comment_to_post(int $postId, string $authorUsername, string $content): void {
    $pdo = db();

    $user = find_user_by_username($authorUsername);
    if (!$user) {
        throw new RuntimeException('Comment user not found.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO comments (post_id, user_id, content, created_at)
         VALUES (?, ?, ?, NOW())'
    );
    $stmt->execute([$postId, (int)$user['id'], $content]);
}

// ---------- Reporting helpers ----------

function report_post(int $postId, string $username, string $reportType, ?string $reason = null): bool {
    $pdo  = db();
    $user = find_user_by_username($username);
    if (!$user) {
        return false;
    }

    // Ensure post exists
    $post = find_post($postId);
    if (!$post) {
        return false;
    }

    // Normalise report type
    $reportType = strtolower(trim($reportType));
    $validTypes = ['misinformation', 'spam', 'scam', 'other'];
    if (!in_array($reportType, $validTypes, true)) {
        $reportType = 'other';
    }

    // Avoid duplicate report from same user on same post
    $check = $pdo->prepare('SELECT id FROM reports WHERE post_id = ? AND user_id = ? LIMIT 1');
    $check->execute([$postId, (int)$user['id']]);
    if ($check->fetch()) {
        return false;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO reports (post_id, user_id, report_type, reason, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$postId, (int)$user['id'], $reportType, $reason]);

    return true;
}

function get_reported_posts(): array {
    $pdo = db();

    $stmt = $pdo->query(
        'SELECT p.id AS post_id,
                p.title,
                p.category,
                u.username AS author,
                COUNT(r.id) AS report_count,
                MAX(r.created_at) AS last_reported_at,
                GROUP_CONCAT(DISTINCT r.report_type ORDER BY r.report_type SEPARATOR \', \') AS report_types,
                MAX(CASE WHEN r.reason IS NOT NULL AND r.reason <> \'\' THEN r.reason END) AS sample_reason
         FROM reports r
         JOIN posts p ON r.post_id = p.id
         JOIN users u ON p.user_id = u.id
         GROUP BY p.id, p.title, p.category, u.username
         ORDER BY report_count DESC, last_reported_at DESC'
    );

    return $stmt->fetchAll();
}

/**
 * Delete a post if it belongs to the given user, or if the user is an admin.
 * Returns true if a post was deleted, false otherwise.
 */
function delete_post_as_user(int $postId, string $username): bool {
    $pdo  = db();
    $user = find_user_by_username($username);
    if (!$user) {
        return false;
    }

    // Admins can delete any post
    if (!empty($user['is_admin'])) {
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        return $stmt->rowCount() > 0;
    }

    // Regular users can delete only their own posts
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
    $stmt->execute([$postId, (int)$user['id']]);

    return $stmt->rowCount() > 0;
}

/**
 * Delete a comment if it belongs to the given user, or if the user is an admin.
 * Returns the post_id the comment belonged to on success, or null on failure/not allowed.
 */
function delete_comment_as_user(int $commentId, string $username): ?int {
    $pdo  = db();
    $user = find_user_by_username($username);
    if (!$user) {
        return null;
    }

    // First, find the comment and ensure ownership (or admin)
    if (!empty($user['is_admin'])) {
        $stmt = $pdo->prepare('SELECT post_id FROM comments WHERE id = ?');
        $stmt->execute([$commentId]);
    } else {
        $stmt = $pdo->prepare('SELECT post_id FROM comments WHERE id = ? AND user_id = ?');
        $stmt->execute([$commentId, (int)$user['id']]);
    }

    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $postId = (int)$row['post_id'];

    // Now delete the comment
    $del = $pdo->prepare('DELETE FROM comments WHERE id = ?');
    $del->execute([$commentId]);

    if ($del->rowCount() === 0) {
        return null;
    }

    return $postId;
}

// ---------- Quiz helpers ----------

function load_quizzes(): array {
    $stmt = db()->query('SELECT id, title, questions_json, created_by_user_id, created_at FROM quizzes ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function find_quiz(int $id): ?array {
    $stmt = db()->prepare('SELECT id, title, questions_json, created_by_user_id, created_at FROM quizzes WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    $questions = json_decode($row['questions_json'] ?? '[]', true);
    if (!is_array($questions)) {
        $questions = [];
    }

    return [
        'id'        => (int)$row['id'],
        'title'     => $row['title'],
        'questions' => $questions,
        'created_at'=> $row['created_at'],
    ];
}

function save_quiz(?int $id, string $title, array $questions, ?string $creatorUsername = null): int {
    $pdo = db();

    $questionsJson = json_encode($questions, JSON_UNESCAPED_UNICODE);

    $creatorId = null;
    if ($creatorUsername !== null) {
        $user = find_user_by_username($creatorUsername);
        if ($user) {
            $creatorId = (int)$user['id'];
        }
    }

    if ($id === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO quizzes (title, questions_json, created_by_user_id, created_at)
             VALUES (?, ?, ?, NOW())'
        );
        $stmt->execute([$title, $questionsJson, $creatorId]);
        return (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare(
        'UPDATE quizzes SET title = ?, questions_json = ? WHERE id = ?'
    );
    $stmt->execute([$title, $questionsJson, $id]);
    return $id;
}

function record_quiz_attempt(int $quizId, string $username, int $score, int $totalQuestions): void {
    $pdo  = db();
    $user = find_user_by_username($username);
    if (!$user) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO quiz_attempts (quiz_id, user_id, score, total_questions, created_at)
         VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$quizId, (int)$user['id'], $score, $totalQuestions]);
}

function get_quiz_user_stats(int $quizId, string $username): array {
    $pdo  = db();
    $user = find_user_by_username($username);
    if (!$user) {
        return ['best' => null, 'attempts' => 0];
    }

    $stmt = $pdo->prepare(
        'SELECT MAX(score) AS best_score, COUNT(*) AS attempts
         FROM quiz_attempts
         WHERE quiz_id = ? AND user_id = ?'
    );
    $stmt->execute([$quizId, (int)$user['id']]);
    $row = $stmt->fetch();

    return [
        'best'     => isset($row['best_score']) ? (int)$row['best_score'] : null,
        'attempts' => (int)($row['attempts'] ?? 0),
    ];
}

// ---------- Utility helpers ----------

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function get_total_users(): int {
    $stmt = db()->query('SELECT COUNT(*) AS c FROM users');
    $row  = $stmt->fetch();
    return (int)($row['c'] ?? 0);
}

function get_total_posts(): int {
    $stmt = db()->query('SELECT COUNT(*) AS c FROM posts');
    $row  = $stmt->fetch();
    return (int)($row['c'] ?? 0);
}

function time_ago(DateTime $date): string {
    $now  = new DateTime();
    $diff = $now->getTimestamp() - $date->getTimestamp();

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . ($hours === 1 ? 'hour' : 'hours') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ' . ($days === 1 ? 'day' : 'days') . ' ago';
    }

    return $date->format('M j, Y');
}

// Category labels used across several pages
function category_labels(): array {
    return [
        'air'     => 'Air Pollution',
        'water'   => 'Water Pollution',
        'land'    => 'Land Pollution',
        'noise'   => 'Noise Pollution',
        'plastic' => 'Plastic Waste',
        'general' => 'General Discussion',
    ];
}

/* ---------- Badge System ---------- */

function get_user_badges(string $username): array {
    $pdo = db();
    $user = find_user_by_username($username);
    if (!$user) return [];

    $userId = (int)$user['id'];
    $badges = [];

    // -------- POST-BASED BADGES --------
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM posts WHERE user_id = ?');
    $stmt->execute([$userId]);
    $postCount = (int)$stmt->fetchColumn();

    if ($postCount >= 1) $badges[] = '🌱 New Contributor';
    if ($postCount >= 5) $badges[] = '📝 Active Poster';
    if ($postCount >= 10) $badges[] = '🔥 Super Contributor';

    // -------- QUIZ-BASED BADGES --------
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM quiz_attempts WHERE user_id = ?');
    $stmt->execute([$userId]);
    $quizAttempts = (int)$stmt->fetchColumn();

    if ($quizAttempts >= 1) $badges[] = '🧠 Quiz Beginner';
    if ($quizAttempts >= 5) $badges[] = '🎓 Quiz Master';

    // -------- PERFECT SCORE BADGE --------
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM quiz_attempts 
         WHERE user_id = ? AND score = total_questions'
    );
    $stmt->execute([$userId]);
    $perfectScores = (int)$stmt->fetchColumn();

    if ($perfectScores > 0) $badges[] = '🏆 Knowledge Champion';

    return $badges;
}
//---------- Quiz Leaderboard ----------
function get_quiz_leaderboard(int $limit = 10): array {
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT 
            u.username,
            COUNT(qa.id) AS attempts,
            MAX(qa.score) AS best_score,
            SUM(qa.score) AS total_score
         FROM quiz_attempts qa
         JOIN users u ON qa.user_id = u.id
         GROUP BY u.id, u.username
         ORDER BY best_score DESC, total_score DESC
         LIMIT ?'
    );

    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}
function get_top_quiz_performers(int $limit = 5): array {
    $stmt = db()->prepare("
        SELECT 
            u.username,
            q.title AS quiz_title,
            MAX(a.score) AS best_score
        FROM quiz_attempts a
        JOIN users u ON a.user_id = u.id
        JOIN quizzes q ON a.quiz_id = q.id
        GROUP BY u.username, q.id
        ORDER BY best_score DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}





