<?php
require_once __DIR__ . '/config.php';
require_login();
$pdo = db();
$leaderboard = get_quiz_leaderboard(10);
/* Fetch quizzes for dropdown */
$quizStmt = $pdo->query("SELECT id, title FROM quizzes ORDER BY created_at DESC");
$quizzes  = $quizStmt->fetchAll();
/* Selected quiz */
$selectedQuizId = isset($_GET['quiz_id']) && $_GET['quiz_id'] !== ''
    ? (int)$_GET['quiz_id']
    : null;

if ($selectedQuizId) {
    // Specific quiz leaderboard
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            COUNT(qa.id) AS attempts,
            MAX(qa.score) AS best_score,
            SUM(qa.score) AS total_score
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        WHERE qa.quiz_id = ?
        GROUP BY qa.user_id
        ORDER BY best_score DESC, total_score DESC
        LIMIT 10
    ");
    $stmt->execute([$selectedQuizId]);
} else {
    // Combined leaderboard (ALL quizzes)
    $stmt = $pdo->query("
        SELECT 
            u.username,
            COUNT(qa.id) AS attempts,
            MAX(qa.score) AS best_score,
            SUM(qa.score) AS total_score
        FROM quiz_attempts qa
        JOIN users u ON qa.user_id = u.id
        GROUP BY qa.user_id
        ORDER BY total_score DESC, best_score DESC
        LIMIT 10
    ");
}

$leaderboard = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Leaderboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="container">
        <h1 class="logo">🏆 Quiz Leaderboard</h1>
        <a href="quiz.php" class="nav-link">← Back to Quizzes</a>
    </div>
</header>
<main class="main-content">
    <div class="card leaderboard-card">
        <h2 class="section-title">Top Quiz Performers</h2>

        <!-- Dropdown -->
        <form method="get" class="leaderboard-filter select">
            <label for="quiz_id">Filter by Quiz:</label>
            <select name="quiz_id" id="quiz_id" onchange="this.form.submit()">
                <option value="">All Quizzes (Combined)</option>
                <?php foreach ($quizzes as $quiz): ?>
                    <option value="<?= (int)$quiz['id'] ?>"
                        <?= $selectedQuizId === (int)$quiz['id'] ? 'selected' : '' ?>>
                        Quiz #<?= (int)$quiz['id'] ?> — <?= h($quiz['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
</div>

        <?php if (empty($leaderboard)): ?>
            <p class="empty-text">No quiz attempts yet.</p>
        <?php else: ?>
            <table class="leaderboard-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Attempts</th>
                        <th>Best Score</th>
                        <th>Total Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard as $index => $row): ?>
                        <tr>
                            <td class="rank"><?php echo $index + 1; ?></td>
                            <td class="username">
                                <a href="profile.php?user=<?php echo urlencode($row['username']); ?>">
                                    <?php echo h($row['username']); ?>
                                </a>
                            </td>
                            <td><?php echo (int)$row['attempts']; ?></td>
                            <td><?php echo (int)$row['best_score']; ?></td>
                            <td><?php echo (int)$row['total_score']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
        <?php endif; ?>
    </div>
</main>
</body>
</html>
