<?php
require_once __DIR__ . '/config.php';

// -------------------------
// AUTH & INITIAL DATA
// -------------------------
require_login();

$user    = current_user();
$quizzes = load_quizzes();




// -------------------------
// LOAD USER STATS (VIEW MODE)
// -------------------------

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

<!-- ================= HEADER ================= -->
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
                    <div class="user-info">
                        <a href="profile.php?user=<?php echo urlencode($user['username']); ?>" class="username-link">
                            <?php echo h($user['username']); ?>
                            <?php echo is_admin() ? ' (Admin)' : ''; ?>
                        </a>
                        <a href="logout.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-logout">
                            Logout
                        </a>
                    </div>
                <?php else: ?>
                    <a class="btn btn-primary" href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<!-- ================= MAIN CONTENT ================= -->
<main class="main-content">
    <div class="container">
        <section class="profile-section">

            <!-- ===== QUIZ HEADER CARD ===== -->
            <div class="card profile-card">
                <div class="profile-header" style="display:flex; justify-content:space-between; align-items:center;">

                    <!-- Left: Quiz Info -->
                    <div style="display:flex; align-items:center;">
                        <div class="profile-avatar">
                            <span class="avatar-icon">❓</span>
                        </div>
                        <div class="profile-info" style="margin-left:0.75rem;">
                            <h1 class="profile-username">Quiz Game</h1>
                            <p class="profile-role">
                                Test your knowledge about pollution and the environment.
                            </p>
                        </div>
                    </div>

                    <!-- Right: Leaderboard -->
                    <a href="leaderboard.php" class="btn btn-primary leaderboard-btn">
                        <span class="leaderboard-icon">🏆</span>
                        Leaderboard
                    </a>

                </div>
            </div>

           <div class="card posts-section-card" style="margin-top: 1.5rem;">
    <h2 class="section-title">Available Quizzes</h2>

    <div class="posts-list-container">
        <?php if (empty($quizzes)): ?>
            <p>No quizzes available yet.</p>
        <?php else: ?>
            <?php foreach ($quizzes as $q): ?>
                <?php
                    $stats = get_quiz_user_stats((int)$q['id'], $user['username']);
                    $bestScore = $stats['best'];
                    $attempts  = $stats['attempts'];

                    $questionData = json_decode($q['questions_json'] ?? "[]", true);
                    $totalQuestions = is_array($questionData) ? count($questionData) : 0;
                ?>

                <!-- ✅ IMPORTANT: anchor must wrap EVERYTHING -->
                <a href="take-quiz.php?id=<?php echo (int)$q['id']; ?>" class="post-list-item">

                    <div class="post-list-content">
                        <h3 class="post-list-title">
                            <?php echo h($q['title']); ?>
                        </h3>

                        <div class="post-list-meta">
                            <span class="post-list-date">
                                Created: <?php echo h((new DateTime($q['created_at']))->format('M j, Y H:i')); ?>
                            </span>
                        </div>

                        <div class="post-list-meta" style="margin-top: 4px;">
                            <?php if ($attempts > 0): ?>
                                ✅ Your best score:
                                <strong><?php echo $bestScore; ?>/<?php echo $totalQuestions; ?></strong>
                                (<?php echo $attempts; ?> attempts)
                            <?php else: ?>
                                ❌ <em>Not attempted</em>
                            <?php endif; ?>
                        </div>
                    </div>

                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


        
<!-- ===== POPUP AFTER SUBMISSION ===== -->
<?php if (isset($_GET['submitted'])): ?>
<script>
    alert(
        "Quiz submitted!\nYour score: <?php echo (int)$_GET['score']; ?> / <?php echo (int)$_GET['total']; ?>"
    );
    window.location.href = "quiz.php";
</script>
<?php endif; ?>

<!-- ================= FOOTER ================= -->
<footer class="footer">
    <div class="container">
        <p>&copy; 2025 Pollution Forum. Join the conversation to make a difference.</p>
    </div>
</footer>

</body>
</html>
