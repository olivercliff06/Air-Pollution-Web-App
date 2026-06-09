<?php
require_once __DIR__ . '/config.php';

// Require login
require_login();

$user    = current_user();
$quizId  = isset($_GET['id']) ? (int)$_GET['id'] : null;
$quiz    = $quizId ? find_quiz($quizId) : null;
$quizzes = load_quizzes();

$error       = null;
$resultScore = null;
$resultTotal = null;
$stats       = null;

// -------------------------
// HANDLE QUIZ SUBMISSION
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $quizId = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
    $quiz   = $quizId ? find_quiz($quizId) : null;

    if (!$quiz) {
        $error = 'Quiz not found.';
    } else {
        $questions = $quiz['questions'] ?? [];
        $score     = 0;
        $total     = count($questions);

        foreach ($questions as $idx => $q) {
            $correctIndex = (int)($q['correctIndex'] ?? 0);
            $answer       = isset($_POST['answer'][$idx]) ? (int)$_POST['answer'][$idx] : -1;
            if ($answer === $correctIndex) {
                $score++;
            }
        }

        // Save attempt
        record_quiz_attempt($quizId, $user['username'], $score, $total);

        // Redirect with popup
        header("Location: quiz.php?submitted=1&score=$score&total=$total");
        exit;
    }

}

// -------------------------
// LOAD USER STATS (when viewing quiz)
// -------------------------
elseif ($quizId && $quiz) {
    $stats = get_quiz_user_stats($quizId, $user['username']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Game - Pollution Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header class="header">
    <div class="container">
        <h1 class="logo">🌍 Pollution Quiz</h1>
        <nav class="nav">
            <a href="index.php" class="nav-link">Home</a>
            <a href="new-post.php" class="nav-link">New Post</a>
            <a href="index.php#quiz" class="nav-link">Quiz Game</a>
            <a href="index.php#learning" class="nav-link">Learning Corner</a>
            <div class="nav-auth">
                <?php if (is_logged_in()): ?>
                    <?php $user = current_user(); ?>
                    <div class="user-info">
                        <a href="profile.php?user=<?php echo urlencode($user['username']); ?>" class="username-link">
                            <?php echo h($user['username']); ?><?php echo is_admin() ? ' (Admin)' : ''; ?>
                        </a>
                        <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'quiz.php'); ?>" class="btn btn-logout">Logout</a>
                    </div>
                <?php else: ?>
                    <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI'] ?? 'quiz.php'); ?>">Login</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main class="main-content">
    <div class="container">
        <section class="profile-section">
            <div class="card profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <span class="avatar-icon">❓</span>
                    </div>
                    <div class="profile-info">
                        <h1 class="profile-username">Quiz Game</h1>
                        <p class="profile-role">Test your knowledge about pollution and the environment.</p>
                    </div>
                </div>
            </div>

            <!-- ----------------- QUIZ LIST ----------------- -->
<div class="card posts-section-card" style="margin-top: 1.5rem;">
    <h2 class="section-title">Available Quizzes</h2>
    <div class="posts-list-container">
<?php if (empty($quizzes)): ?>
    <p>No quizzes available yet.</p>
<?php else: ?>
        <?php foreach ($quizzes as $q): ?>
<a href="take-quiz.php?id=<?php echo (int)$q['id']; ?>" class="post-list-item">
                <div class="post-list-content">
                    <h3 class="post-list-title"><?php echo h($q['title']); ?></h3>
                    <div class="post-list-meta">
                        Created:
                        <?php echo h((new DateTime($q['created_at']))->format('M j, Y H:i')); ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

        </section>
    </div>
</main>

<!-- ------------------ POPUP AFTER SUBMISSION ------------------ -->
<?php if (isset($_GET['submitted'])): ?>
<script>
    alert("Quiz submitted!\nYour score: <?php echo $_GET['score']; ?> / <?php echo $_GET['total']; ?>");
    window.location.href = "quiz.php"; // Back to quiz list
</script>
<?php endif; ?>

<footer class="footer">
    <div class="container">
        <p>&copy; 2025 Pollution Forum. Join the conversation to make a difference.</p>
    </div>
</footer>

</body>
</html>
