<?php
require_once __DIR__ . '/config.php';
require_login();

$user   = current_user();
$quizId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$quiz   = $quizId ? find_quiz($quizId) : null;

// If quiz not found → go back to quiz list
if (!$quiz) {
    header("Location: quiz.php");
    exit;
}

$stats = get_quiz_user_stats($quizId, $user['username']);

// -------------------------
// HANDLE QUIZ SUBMISSION
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $questions = $quiz['questions'] ?? [];
    $score     = 0;
    $total     = count($questions);

    foreach ($questions as $idx => $q) {
        $correctIndex = (int)($q['correctIndex'] ?? -1);
        $answer       = isset($_POST['answer'][$idx]) ? (int)$_POST['answer'][$idx] : -1;

        if ($answer === $correctIndex) {
            $score++;
        }
    }

    // Save attempt
    record_quiz_attempt($quizId, $user['username'], $score, $total);

    // Redirect back to quiz list with popup
    header("Location: quiz.php?submitted=1&score=$score&total=$total");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo h($quiz['title']); ?> - Quiz</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>

<header class="header">
    <div class="container">
        <h1 class="logo">🌍 Pollution Quiz</h1>
        <nav class="nav">
            <a href="quiz.php" class="nav-link">← Back to Quizzes</a>
        </nav>
    </div>
</header>

<main class="main-content">
    <div class="container">
        <div class="card">

            <h2 class="section-title"><?php echo h($quiz['title']); ?></h2>

            <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                Your best score:
                <strong><?php echo (int)$stats['best']; ?></strong> /
                <?php echo count($quiz['questions']); ?> —
                Attempts: <strong><?php echo (int)$stats['attempts']; ?></strong>
            </p>

            <form method="post">
                <?php foreach ($quiz['questions'] as $idx => $q): ?>
                    <div class="form-group" style="margin-bottom:1rem;">
                        <h3 style="font-weight:600;">
                            <?php echo ($idx + 1) . '. ' . h($q['text']); ?>
                </h3>

                        <?php foreach ($q['options'] as $optIdx => $optText): ?>
                            <label style="display:block;">
                                <input
                                    type="radio"
                                    name="answer[<?php echo $idx; ?>]"
                                    value="<?php echo $optIdx; ?>"
                                    required
                                >
                                <?php echo h($optText); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">
                    Submit Answers
                </button>
            </form>

        </div>
    </div>
</main>

</body>
</html>